<?php

namespace App\Http\Controllers\Signal;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use Illuminate\Http\JsonResponse;

class SignalController extends Controller
{
    /**
     * GET /api/v1/signals
     * List active signals for users.
     */
    public function index(): JsonResponse
    {
        $signals = Signal::where('status', 'active')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $signals]);
    }
}
