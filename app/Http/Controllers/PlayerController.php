<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Player;
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

        $players = Player::query()
            ->select('id', 'player_no', 'name', 'dob', 'guardian_name', 'guardian_phone', 'photo_path',
                'batch_id', 'sessions_balance', 'reminder_pending', 'is_active')
            ->with('batch:id,name')
            ->search($request->query('q'))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->query('batch_id')))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $batches = Batch::orderBy('name')->get(['id', 'name']);

        return view('players.index', compact('players', 'batches', 'status'));
    }

    public function create()
    {
        return view('players.form', [
            'player'  => null,
            'batches' => Batch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            $player = DB::transaction(function () use ($request) {
                $data = $request->only(['name', 'dob', 'phone', 'guardian_name', 'guardian_phone', 'batch_id']);
                $data['joined_at'] = $request->input('joined_at') ?: today()->toDateString();
                $data['qr_token']  = $this->newToken();

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
            'message'  => 'Player added. You can print the ID card now.',
            'redirect' => route('admin.players.show', $player),
        ]);
    }

    public function show(Player $player)
    {
        $player->load('batch:id,name');

        $attendance    = $player->attendances()->latest('attendance_date')->limit(30)->get(['id', 'attendance_date', 'method', 'created_at']);
        $payments      = $player->payments()->latest('paid_at')->limit(10)->get();
        $totalAttended = $player->attendances()->count();

        return view('players.show', compact('player', 'attendance', 'payments', 'totalAttended'));
    }

    public function edit(Player $player)
    {
        return view('players.form', [
            'player'  => $player,
            'batches' => Batch::where('is_active', true)->orWhere('id', $player->batch_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $request->only(['name', 'dob', 'phone', 'guardian_name', 'guardian_phone', 'batch_id']);
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

    /** Printable ID cards: ?ids=1,2,3  or  ?batch_id=2  or nothing = all active players. */
    public function cards(Request $request)
    {
        $q = Player::query()
            ->select('id', 'player_no', 'qr_token', 'name', 'dob', 'guardian_phone', 'photo_path', 'batch_id')
            ->with('batch:id,name');

        if ($request->filled('ids')) {
            $q->whereIn('id', array_filter(array_map('intval', explode(',', (string) $request->query('ids')))));
        } else {
            $q->where('is_active', true)
              ->when($request->filled('batch_id'), fn ($w) => $w->where('batch_id', $request->query('batch_id')));
        }

        return view('players.cards', ['players' => $q->orderBy('name')->get()]);
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
            'batch_id'       => 'nullable|exists:batches,id',
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
        ];
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