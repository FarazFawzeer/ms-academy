<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Player;
use App\Models\PracticeSession;
use App\Support\AgeGroup;
use App\Support\ImageUpload;
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
        $v = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            $player = DB::transaction(function () use ($request) {
                $data = $request->only(['name', 'dob', 'phone', 'guardian_name', 'guardian_phone']);
                $data['training_days'] = Player::normaliseDays($request->input('days'));
                $data['joined_at']     = $request->input('joined_at') ?: today()->toDateString();
                $data['qr_token']      = $this->newToken();

                if ($request->hasFile('photo')) {
                    $data['photo_path'] = ImageUpload::square($request->file('photo'), 'uploads/players');
                }

                $player = Player::create($data);
                $player->update(['player_no' => sprintf('%s-%04d', config('academy.player_prefix', 'MS'), $player->id)]);

                if ($request->boolean('collect_fee')) {
                    Payment::record(
                        $player->id,
                        (float) config('academy.fee'),
                        Player::perPackage(),
                        'cash',
                        'First payment on joining',
                        $request->user()?->id
                    );
                }

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
        $attendance    = $player->attendances()->latest('attendance_date')->limit(30)
            ->get(['id', 'attendance_date', 'age_group', 'method', 'is_extra', 'created_at']);
        $payments      = $player->payments()->latest('paid_at')->limit(10)->get();
        $totalAttended = $player->attendances()->count();
        $nextMove      = AgeGroup::nextMove($player->dob);

        return view('players.show', compact('player', 'attendance', 'payments', 'totalAttended', 'nextMove'));
    }

    public function edit(Player $player)
    {
        return view('players.form', ['player' => $player, 'slots' => PracticeSession::slots()]);
    }

    public function update(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), $this->rules(), $this->messages());
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

        $player->update($data);

        return response()->json([
            'success'  => true,
            'message'  => 'Player updated.',
            'redirect' => route('admin.players.show', $player),
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
