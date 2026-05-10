<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SignalDeactivatedJob;
use App\Models\Signal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SignalController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Signal::with('creator')->orderByDesc('created_at')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:150', Rule::unique('signals', 'name')->where('status', 'active')],
            'description'       => ['nullable', 'string'],
            'provider_metadata' => ['nullable', 'array'],
            'status'            => ['nullable', 'in:active,inactive'],
        ]);

        $signal = Signal::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status'     => $data['status'] ?? 'active',
        ]);

        return response()->json(['data' => $signal], 201);
    }

    public function show(Signal $signal): JsonResponse
    {
        return response()->json(['data' => $signal->load('creator')]);
    }

    public function update(Request $request, Signal $signal): JsonResponse
    {
        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:150'],
            'description'       => ['nullable', 'string'],
            'provider_metadata' => ['nullable', 'array'],
        ]);

        $signal->update($data);

        return response()->json(['data' => $signal->fresh()]);
    }

    public function activate(Signal $signal): JsonResponse
    {
        $signal->update(['status' => 'active']);

        return response()->json(['data' => $signal->fresh()]);
    }

    public function deactivate(Signal $signal): JsonResponse
    {
        $signal->update(['status' => 'inactive']);

        // Unsubscribe all accounts and notify users asynchronously
        SignalDeactivatedJob::dispatch($signal->id);

        return response()->json(['data' => $signal->fresh()]);
    }

    public function destroy(Signal $signal): JsonResponse
    {
        if ($signal->status === 'active') {
            SignalDeactivatedJob::dispatch($signal->id);
        }

        $signal->delete();

        return response()->json(null, 204);
    }
}
