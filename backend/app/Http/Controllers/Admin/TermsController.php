<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * GET /api/v1/admin/terms
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => TermsVersion::orderByDesc('effective_at')->get(),
        ]);
    }

    /**
     * POST /api/v1/admin/terms
     * Create a new terms version (becomes current immediately).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version'      => ['required', 'string', 'max:50', 'unique:terms_versions,version'],
            'content'      => ['required', 'string'],
            'effective_at' => ['nullable', 'date'],
        ]);

        $terms = TermsVersion::create([
            'version'      => $data['version'],
            'content'      => $data['content'],
            'effective_at' => $data['effective_at'] ?? now(),
        ]);

        return response()->json(['data' => $terms], 201);
    }

    /**
     * PATCH /api/v1/admin/terms/{terms}
     */
    public function update(Request $request, TermsVersion $terms): JsonResponse
    {
        $data = $request->validate([
            'content'      => ['sometimes', 'string'],
            'effective_at' => ['sometimes', 'date'],
        ]);

        $terms->update($data);

        return response()->json(['data' => $terms->fresh()]);
    }
}
