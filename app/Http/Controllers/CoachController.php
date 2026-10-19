<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoachController extends Controller
{
    public function index()
    {
        $coaches = Coach::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('coaches.index', compact('coaches'));
    }

    public function create()
    {
        return view('coaches.form', ['coach' => null]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $request->only(['name', 'phone', 'nic', 'daily_rate']);

        try {
            if ($request->hasFile('photo')) {
                $data['photo_path'] = ImageUpload::square($request->file('photo'), 'uploads/coaches');
            }
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'errors' => ['photo' => [$e->getMessage()]]], 422);
        }

        Coach::create($data);

        return response()->json(['success' => true, 'message' => 'Coach added.', 'redirect' => route('admin.coaches.index')]);
    }

    public function edit(Coach $coach)
    {
        return view('coaches.form', compact('coach'));
    }

    public function update(Request $request, Coach $coach)
    {
        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $request->only(['name', 'phone', 'nic', 'daily_rate']);

        try {
            if ($request->hasFile('photo')) {
                $new = ImageUpload::square($request->file('photo'), 'uploads/coaches');
                ImageUpload::delete($coach->photo_path);
                $data['photo_path'] = $new;
            }
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'errors' => ['photo' => [$e->getMessage()]]], 422);
        }

        // Past days keep the rate they were saved with; the new rate applies from now on.
        $coach->update($data);

        return response()->json(['success' => true, 'message' => 'Coach updated.', 'redirect' => route('admin.coaches.index')]);
    }

    public function toggle(Coach $coach)
    {
        $coach->update(['is_active' => !$coach->is_active]);

        return response()->json([
            'success' => true,
            'message' => $coach->is_active ? 'Coach activated.' : 'Coach deactivated.',
        ]);
    }

    private function rules(): array
    {
        return [
            'name'       => 'required|string|max:120',
            'phone'      => 'nullable|string|max:20',
            'nic'        => 'nullable|string|max:20',
            'daily_rate' => 'required|numeric|min:0|max:1000000',
            'photo'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ];
    }
}
