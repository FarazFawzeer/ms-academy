<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Player;
use App\Models\PracticeSession;
use App\Support\AgeGroup;
use App\Support\ImageUpload;
use App\Support\PlayerSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'active');
        $group  = $request->query('group');
        $days   = $request->query('days');

        $players = Player::query()
            ->select('id', 'player_no', 'name', 'dob', 'guardian_name', 'guardian_phone', 'photo_path',
                'training_days', 'sessions_balance', 'reminder_pending', 'is_active')
            ->search($request->query('q'))
            ->inAgeGroup($group)
            ->trainingDays($days)
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('players.index', [
            'players' => $players,
            'status'  => $status,
            'group'   => $group,
            'days'    => $days,
            'groups'  => AgeGroup::all(),
            'slots'   => PracticeSession::slots(),
        ]);
    }

    public function create()
    {
        return view('players.form', ['player' => null, 'slots' => PracticeSession::slots()]);
    }

    public function store(Request $request)
    {
        $plan = Player::planFor(Player::normaliseDays($request->input('days')));

        $v = Validator::make($request->all(), $this->rules() + $this->packageRules($request, $plan, false),
            $this->messages() + $this->packageMessages($plan));
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $mode = $request->input('payment_mode') ?: ($request->boolean('collect_fee') ? 'now' : 'none');

        try {
            $player = DB::transaction(function () use ($request, $mode, $plan) {
                $data = $request->only(['name', 'dob', 'phone', 'guardian_name', 'guardian_phone']);
                $data['training_days'] = Player::normaliseDays($request->input('days'));
                $data['joined_at']     = $request->input('joined_at') ?: today()->toDateString();
                $data['qr_token']      = $this->newToken();

                if ($request->hasFile('photo')) {
                    $data['photo_path'] = ImageUpload::square($request->file('photo'), 'uploads/players');
                }

                $player = Player::create($data);
                $player->update(['player_no' => sprintf('%s-%04d', config('academy.player_prefix', 'MS'), $player->id)]);

                $this->applyPackage($player, $mode, $plan, $request, 'First payment on joining');

                return $player;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'errors' => ['photo' => [$e->getMessage()]]], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Player added to ' . $player->age_group . '. You can print the ID card now.',
            'redirect' => route('admin.players.show', $player),
        ]);
    }

    public function show(Player $player)
    {
        // last 30 practice days: present and absent together, newest first
        $present = $player->attendances()->latest('attendance_date')->limit(30)
            ->get(['id', 'attendance_date', 'age_group', 'method', 'is_extra', 'created_at'])
            ->map(fn ($a) => (object) ['date' => $a->attendance_date, 'present' => true, 'extra' => $a->is_extra,
                'counted' => true, 'time' => $a->created_at, 'method' => $a->method, 'group' => $a->age_group]);
        $missed = $player->absences()->latest('attendance_date')->limit(30)
            ->get(['attendance_date', 'age_group', 'counted'])
            ->map(fn ($a) => (object) ['date' => $a->attendance_date, 'present' => false, 'extra' => false,
                'counted' => $a->counted, 'time' => null, 'method' => null, 'group' => $a->age_group]);
        $attendance = $present->concat($missed)->sortByDesc(fn ($r) => $r->date->toDateString())->take(30)->values();

        $payments      = $player->payments()->latest('paid_at')->limit(10)->get();
        $totalAttended = $player->attendances()->count();
        $totalMissed   = $player->absences()->count();
        $nextMove      = AgeGroup::nextMove($player->dob);

        return view('players.show', compact('player', 'attendance', 'payments', 'totalAttended', 'totalMissed', 'nextMove'));
    }

    public function edit(Player $player)
    {
        return view('players.form', ['player' => $player, 'slots' => PracticeSession::slots()]);
    }

    public function update(Request $request, Player $player)
    {
        $plan = Player::planFor(Player::normaliseDays($request->input('days')));

        $v = Validator::make($request->all(), $this->rules() + $this->packageRules($request, $plan, true),
            $this->messages() + $this->packageMessages($plan));
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $request->only(['name', 'dob', 'phone', 'guardian_name', 'guardian_phone']);
        $data['training_days'] = Player::normaliseDays($request->input('days'));
        if ($request->filled('joined_at')) {
            $data['joined_at'] = $request->input('joined_at');
        }

        try {
            if ($request->hasFile('photo')) {
                $new = ImageUpload::square($request->file('photo'), 'uploads/players');
                ImageUpload::delete($player->photo_path);
                $data['photo_path'] = $new;
            }
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'errors' => ['photo' => [$e->getMessage()]]], 422);
        }

        $mode = $request->input('payment_mode') ?: 'keep';

        DB::transaction(function () use ($player, $data, $mode, $plan, $request) {
            $player->fill($data);
            $daysChanged = $player->isDirty('training_days') || $player->isDirty('joined_at');
            $player->save();
            $player->refresh();

            // new training days / joining date: absences that no longer apply go, their sessions come back
            // (only then - a day the admin marked absent by hand stays as it is on a normal edit)
            if ($daysChanged) {
                PlayerSessions::removeWrongDayAbsences($player);
            }

            $this->applyPackage($player, $mode, $plan, $request, 'Payment added from Edit Player');
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Player updated.',
            'redirect' => route('admin.players.show', $player),
        ]);
    }

    /**
     * Admin only: remove a player for good (e.g. added twice by mistake).
     * Their attendance, absences and payments go too (database cascade), so payments drop out of the
     * revenue reports - the screen asks to deactivate instead when the history should stay.
     */
    public function destroy(Player $player)
    {
        $name = $player->name;

        DB::transaction(fn () => $player->delete());
        ImageUpload::delete($player->photo_path);

        return response()->json([
            'success'  => true,
            'message'  => $name . ' was deleted.',
            'redirect' => route('admin.players.index'),
        ]);
    }

    /**
     * Admin only: what is recorded for one date (for the "Mark attendance" popup).
     * Any date can be marked; this only tells the admin what the day was.
     */
    public function dayInfo(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), ['date' => 'required|date']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Choose a date.'], 422);
        }

        $date    = Carbon::parse($request->input('date'))->toDateString();
        $session = PracticeSession::forDate($date);
        $ownDay  = $session && $player->trainsOn($session->slot);
        $status  = $player->attendances()->whereDate('attendance_date', $date)->exists() ? 'present'
            : ($player->absences()->whereDate('attendance_date', $date)->exists() ? 'absent' : '');

        if ($session) {
            $practice = $session->label . ($ownDay ? '' : ' (not ' . $player->name . "'s day)");
        } else {
            $cancelled = PracticeSession::where('session_date', $date)->where('is_cancelled', true)->exists();
            $practice  = $cancelled ? 'Practice cancelled on this day' : 'No practice on the schedule';
        }

        return response()->json([
            'success'  => true,
            'date'     => $date,
            'label'    => Carbon::parse($date)->format('l, d M Y'),
            'future'   => $date > today()->toDateString(),
            'practice' => $practice,
            'own'      => $ownDay,
            'extra'    => $session && !$ownDay,
            'status'   => $status,
        ]);
    }

    /**
     * Admin only: mark any day up to today by hand from the profile - Present, Absent or Clear
     * (client, 29 Oct 2026: staff are new to the system, so no practice-day restrictions here).
     * Whatever was recorded is undone first, so sessions always add up (PlayerSessions::setDay).
     * Present always uses a session; absent uses one only while the package has sessions left.
     */
    public function markDay(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), [
            'date'   => 'required|date|before_or_equal:today',
            'status' => 'required|in:present,absent,clear',
        ], [
            'date.required'        => 'Choose a date.',
            'date.before_or_equal' => 'Future days cannot be marked.',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $date    = Carbon::parse($request->input('date'))->toDateString();
        $status  = $request->input('status');
        $session = PracticeSession::forDate($date);
        $ownDay  = $session && $player->trainsOn($session->slot);
        $day     = Carbon::parse($date)->format('D, d M Y');

        $balance = DB::transaction(fn () => PlayerSessions::setDay($player, $date, $status, $request->user()?->id));
        $player->sessions_balance = $balance;

        $what = ['present' => 'present' . ($session && !$ownDay ? ' (extra day)' : ''), 'absent' => 'absent', 'clear' => 'cleared'][$status];

        return response()->json([
            'success' => true,
            'message' => $day . ' marked ' . $what . '. ' . $balance . ' session(s) left.',
            'badge'   => (string) $player->balanceBadge(),
        ]);
    }

    public function toggle(Player $player)
    {
        $player->update(['is_active' => !$player->is_active]);

        return response()->json([
            'success' => true,
            'message' => $player->is_active ? 'Player activated.' : 'Player deactivated.',
        ]);
    }

    /** "Print ID Cards" page: choose all / age group / ID range / hand-picked players, then open the cards. */
    public function printOptions()
    {
        $players = Player::query()
            ->orderBy('id')
            ->get(['id', 'player_no', 'name', 'dob', 'photo_path', 'training_days', 'is_active'])
            ->map(fn (Player $p) => [
                'id'     => $p->id,
                'no'     => $p->player_no,
                'name'   => $p->name,
                'group'  => $p->age_group,
                'days'   => $p->training_days,
                'active' => $p->is_active,
                'photo'  => $p->photo_url,
            ]);

        return view('players.print', [
            'players' => $players,
            'groups'  => AgeGroup::all(),
            'slots'   => PracticeSession::slots(),
            'prefix'  => config('academy.player_prefix', 'MS'),
        ]);
    }

    /**
     * Printable ID cards.
     *  ?ids=1,2,3                 hand-picked players (active or not)
     *  ?from=10&to=25             player number range (MS-0010 ... MS-0025; "MS-0010" also accepted)
     *  ?group=U12  ?days=sun      age group / training day filters
     *  ?inactive=1                include inactive players (range / group / all)
     *  ?sort=no                   order by player number instead of name
     * Nothing = every active player.
     */
    public function cards(Request $request)
    {
        $q = Player::query()
            ->select('id', 'player_no', 'qr_token', 'name', 'dob', 'guardian_phone', 'photo_path', 'training_days');

        if ($request->filled('ids')) {
            $q->whereIn('id', array_filter(array_map('intval', explode(',', (string) $request->query('ids')))));
        } else {
            $from = $this->playerNumber($request->query('from'));
            $to   = $this->playerNumber($request->query('to'));
            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from];
            }

            $q->when(!$request->boolean('inactive'), fn ($w) => $w->where('is_active', true))
              ->when($from, fn ($w) => $w->where('id', '>=', $from))
              ->when($to, fn ($w) => $w->where('id', '<=', $to))
              ->inAgeGroup($request->query('group'))
              ->trainingDays($request->query('days'));
        }

        $request->query('sort') === 'no' ? $q->orderBy('id') : $q->orderBy('name');

        return view('players.cards', ['players' => $q->get()]);
    }

    /* ---------- helpers ---------- */

    /**
     * Payment / earlier sessions section (Add and Edit player):
     *  now    = plan payment today
     *  before = plan payment on "Paid on" (optional, default today) + sessions already done
     *  none   = not paid: only the sessions already done are taken off, so the player shows as payment due
     *  keep   = (Edit only, default) nothing changes
     * Each optional session date becomes an attendance row (see PlayerSessions::addEarlierSessions).
     */
    private function applyPackage(Player $player, string $mode, array $plan, Request $request, string $nowNote): void
    {
        $userId = $request->user()?->id;
        $used   = (int) $request->input('used', 0);
        $dates  = (array) $request->input('used_dates', []);

        if ($mode === 'now') {
            Payment::record($player->id, (float) $plan['fee'], (int) $plan['sessions'], 'cash', $nowNote, $userId);
        } elseif ($mode === 'before') {
            $paidOn = $request->input('paid_on') ? Carbon::parse($request->input('paid_on')) : today();
            [$payment] = Payment::record(
                $player->id,
                (float) $plan['fee'],
                (int) $plan['sessions'],
                'cash',
                'Paid before the system started' . ($used ? ' (' . $used . ' session(s) already used)' : ''),
                $userId
            );
            $payment->update(['paid_at' => $paidOn->copy()->setTime(12, 0)]);
        }

        if (in_array($mode, ['before', 'none'], true) && $used > 0) {
            PlayerSessions::addEarlierSessions($player, $used, $dates, $userId);
        }
    }

    /** Validation for the payment / earlier sessions section. */
    private function packageRules(Request $request, array $plan, bool $isEdit): array
    {
        // a paid package covers at most its own sessions; unpaid sessions can be any number
        $maxUsed = $request->input('payment_mode') === 'before' ? (int) $plan['sessions'] : 64;

        return [
            'payment_mode' => 'nullable|in:' . ($isEdit ? 'keep,' : '') . 'now,before,none',
            'paid_on'      => 'nullable|date|before_or_equal:today',
            'used'         => 'nullable|integer|min:0|max:' . $maxUsed,
            'used_dates'   => 'nullable|array|max:' . $maxUsed,
            'used_dates.*' => 'nullable|date|before_or_equal:today|distinct',
        ];
    }

    private function packageMessages(array $plan): array
    {
        return [
            'used.max'                     => 'This plan has only ' . $plan['sessions'] . ' sessions.',
            'used_dates.*.distinct'        => 'The same session date is entered twice.',
            'used_dates.*.before_or_equal' => 'Session dates cannot be in the future.',
            'paid_on.before_or_equal'      => 'The payment date cannot be in the future.',
        ];
    }

    private function rules(): array
    {
        return [
            'name'           => 'required|string|max:120',
            'dob'            => 'required|date|before:today|after:1950-01-01',
            'phone'          => 'nullable|string|max:20',
            'guardian_name'  => 'required|string|max:120',
            'guardian_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,15}$/'],
            'days'           => 'required|array|min:1',
            'days.*'         => 'in:' . implode(',', array_keys(PracticeSession::slots())),
            'joined_at'      => 'nullable|date',
            'photo'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'collect_fee'    => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'guardian_phone.regex' => 'Enter a valid phone number, for example 0771234567.',
            'dob.before'           => 'Date of birth must be in the past.',
            'photo.max'            => 'The photo is too large (max 8 MB).',
            'days.required'        => 'Choose at least one training day.',
            'days.min'             => 'Choose at least one training day.',
            'days.*.in'            => 'That training day is not one of the academy practice days.',
        ];
    }

    /** "MS-0012", "ms12", "12" -> 12 (player numbers are the id, zero padded). */
    private function playerNumber($value): ?int
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }

    /** 12 chars, A-Z0-9 only: QR "alphanumeric mode" = smallest, fastest-scanning code. */
    private function newToken(): string
    {
        do {
            $token = strtoupper(Str::random(12));
        } while (Player::where('qr_token', $token)->exists());

        return $token;
    }
}
