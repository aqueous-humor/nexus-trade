<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrokerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Broker::orderByDesc('created_at')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:150'],
            'platform_type'          => ['required', 'in:MT4,MT5'],
            'connection_credentials' => ['required', 'array'],
            'default_leverage'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status'                 => ['nullable', 'in:active,inactive'],
        ]);

        $broker = Broker::create($data);

        return response()->json(['data' => $broker], 201);
    }

    public function show(Broker $broker): JsonResponse
    {
        return response()->json(['data' => $broker]);
    }

    public function update(Request $request, Broker $broker): JsonResponse
    {
        $data = $request->validate([
            'name'                   => ['sometimes', 'string', 'max:150'],
            'platform_type'          => ['sometimes', 'in:MT4,MT5'],
            'connection_credentials' => ['sometimes', 'array'],
            'default_leverage'       => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'status'                 => ['sometimes', 'in:active,inactive'],
        ]);

        $broker->update($data);

        return response()->json(['data' => $broker->fresh()]);
    }

    public function destroy(Broker $broker): JsonResponse
    {
        $broker->update(['status' => 'inactive']);
        $broker->delete();

        return response()->json(null, 204);
    }
}
