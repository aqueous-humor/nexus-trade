<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    /**
     * GET /api/v1/notifications/preferences
     */
    public function show(Request $request): JsonResponse
    {
        $prefs = $request->user()->notificationPreference
            ?? NotificationPreference::create(['user_id' => $request->user()->id]);

        return response()->json(['data' => $prefs]);
    }

    /**
     * PATCH /api/v1/notifications/preferences
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'investment_created'   => ['boolean'],
            'investment_completed' => ['boolean'],
            'deposit_confirmed'    => ['boolean'],
            'withdrawal_update'    => ['boolean'],
            'account_status_change'=> ['boolean'],
        ]);

        $prefs = $request->user()->notificationPreference
            ?? NotificationPreference::create(['user_id' => $request->user()->id]);

        $prefs->update($data);

        return response()->json(['data' => $prefs->fresh()]);
    }
}
