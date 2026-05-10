<?php

namespace App\Http\Controllers\Terms;

use App\Http\Controllers\Controller;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * GET /api/v1/terms/current
     * Return the current (latest) terms version.
     */
    public function current(Request $request): JsonResponse
    {
        $terms = TermsVersion::orderByDesc('effective_at')->first();

        if (! $terms) {
            return response()->json(['data' => null]);
        }

        // Include whether the authenticated user has accepted this version
        $accepted = false;
        if ($request->user()) {
            $accepted = TermsAcceptance::where('user_id', $request->user()->id)
                ->where('terms_version', $terms->version)
                ->exists();
        }

        return response()->json([
            'data' => [
                'version'      => $terms->version,
                'content'      => $terms->content,
                'effective_at' => $terms->effective_at,
                'accepted'     => $accepted,
            ],
        ]);
    }

    /**
     * POST /api/v1/terms/accept
     * Record the authenticated user's acceptance of the current terms.
     */
    public function accept(Request $request): JsonResponse
    {
        $request->validate([
            'version' => ['required', 'string', 'exists:terms_versions,version'],
        ]);

        TermsAcceptance::firstOrCreate(
            [
                'user_id'       => $request->user()->id,
                'terms_version' => $request->version,
            ],
            [
                'accepted_at' => now(),
                'ip_address'  => $request->ip(),
            ]
        );

        return response()->json([
            'data' => ['message' => 'Terms accepted successfully.'],
        ]);
    }
}
