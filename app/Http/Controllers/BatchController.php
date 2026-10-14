<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::query()
            ->with('coach:id,name')
            ->withCount(['players as players_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $coaches = Coach::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('batches.index', compact('batches', 'coaches'));
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        Batch::create($this->data($request));

        return response()->json(['success' => true, 'message' => 'Batch created.']);
    }

    public function update(Request $request, Batch $batch)
    {
        $v = Validator::make($request->all(), $this->rules($batch->id));
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $batch->update($this->data($request));

        return response()->json(['success' => true, 'message' => 'Batch updated.']);
    }

    public function destroy(Batch $batch)
    {
        if ($batch->players()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This batch still has players. Move them to another batch, or set the batch to Inactive instead.',
            ], 422);
        }

        $batch->delete();

        return response()->json(['success' => true, 'message' => 'Batch deleted.']);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name'      => 'required|string|max:80|unique:batches,name' . ($ignoreId ? ',' . $ignoreId : ''),
            'schedule'  => 'nullable|string|max:120',
            'coach_id'  => 'nullable|exists:coaches,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    private function data(Request $request): array
    {
        return [
            'name'      => $request->input('name'),
            'schedule'  => $request->input('schedule'),
            'coach_id'  => $request->input('coach_id') ?: null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
