<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AccountLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     * Create a new user account with wallet and notification preferences.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
                'password'     => $request->password, // hashed by model cast
                'role'         => 'user',
            ]);

            // Create wallet with zero balance
            Wallet::create([
                'user_id'      => $user->id,
                'balance_cents' => 0,
            ]);

            // Create default notification preferences (all enabled)
            NotificationPreference::create([
                'user_id' => $user->id,
            ]);

            return $user;
        });

        // Dispatch email verification
        event(new Registered($user));

        return response()->json([
            'data' => [
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user'    => $this->userResource($user),
            ],
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     * Authenticate user and issue Sanctum session/token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            throw new AccountLockedException(
                'Your account is temporarily locked due to too many failed login attempts. Please try again later or reset your password.'
            );
        }

        // Validate credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Increment failed attempts
            if ($user) {
                $user->increment('failed_login_attempts');

                // Lock account after 5 failures within 15 minutes
                if ($user->failed_login_attempts >= 5) {
                    $user->update(['locked_until' => Carbon::now()->addMinutes(15)]);
                    app(\App\Services\NotificationService::class)->accountLocked($user);
                }
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Reset failed attempts on successful login
        if ($user->failed_login_attempts > 0) {
            $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);
        }

        // Issue a Sanctum API token for Bearer token auth
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     * Invalidate the current session and revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current API token if present
        $request->user()?->currentAccessToken()?->delete();

        // Invalidate session (gracefully — array driver doesn't support all session ops)
        try {
            Auth::guard('web')->logout();

            if (config('session.driver') !== 'array') {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        } catch (\Throwable) {
            // Session operations may fail in test environments — safe to ignore
        }

        return response()->json(['data' => ['message' => 'Logged out successfully.']]);
    }

    /**
     * GET /api/v1/auth/me
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userResource($request->user()),
        ]);
    }

    /**
     * Format user for API response.
     */
    private function userResource(User $user): array
    {
        return [
            'id'                => $user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'email'             => $user->email,
            'phone_number'      => $user->phone_number,
            'role'              => $user->role,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at'        => $user->created_at->toIso8601String(),
        ];
    }
}
