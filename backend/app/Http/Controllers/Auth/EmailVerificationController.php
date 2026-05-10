<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * POST /api/v1/email/verify/{id}/{hash}
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'data' => ['message' => 'Email already verified.'],
            ]);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return response()->json([
            'data' => ['message' => 'Email verified successfully.'],
        ]);
    }

    /**
     * POST /api/v1/email/resend
     * Resend the email verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'data' => ['message' => 'Email already verified.'],
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'data' => ['message' => 'Verification email resent.'],
        ]);
    }
}
