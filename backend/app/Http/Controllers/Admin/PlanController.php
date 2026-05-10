<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Duration;
use App\Models\InvestmentPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => InvestmentPlan::withTrashed()->with('durations')->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:150'],
            'description'          => ['nullable', 'string'],
            'min_amount_cents'     => ['required', 'integer', 'min:1'],
            'max_amount_cents'     => ['required', 'integer', 'gt:min_amount_cents'],
            'roi_percentage'       => ['required', 'numeric', 'min:0', 'max:1000'],
            'profit_min_pct'       => ['required', 'numeric', 'min:0'],
            'profit_max_pct'       => ['required', 'numeric', 'gte:profit_min_pct'],
            'is_trending'          => ['boolean'],
            'trending_image_url'   => ['nullable', 'url', 'max:500'],
            'trending_title'       => ['nullable', 'string', 'max:255'],
            'trending_description' => ['nullable', 'string'],
            'status'               => ['nullable', 'in:active,inactive'],
            'duration_ids'         => ['nullable', 'array'],
            'duration_ids.*'       => ['integer', 'exists:durations,id'],
        ]);

        $plan = InvestmentPlan::create($data);

        if (! empty($data['duration_ids'])) {
            $plan->durations()->sync($data['duration_ids']);
        }

        return response()->json(['data' => $plan->load('durations')], 201);
    }

    public function show(InvestmentPlan $plan): JsonResponse
    {
        return response()->json(['data' => $plan->load('durations')]);
    }

    public function update(Request $request, InvestmentPlan $plan): JsonResponse
    {
        $data = $request->validate([
            'name'                 => ['sometimes', 'string', 'max:150'],
            'description'          => ['nullable', 'string'],
            'min_amount_cents'     => ['sometimes', 'integer', 'min:1'],
            'max_amount_cents'     => ['sometimes', 'integer'],
            'roi_percentage'       => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'profit_min_pct'       => ['sometimes', 'numeric', 'min:0'],
            'profit_max_pct'       => ['sometimes', 'numeric'],
            'is_trending'          => ['boolean'],
            'trending_image_url'   => ['nullable', 'url', 'max:500'],
            'trending_title'       => ['nullable', 'string', 'max:255'],
            'trending_description' => ['nullable', 'string'],
            'status'               => ['sometimes', 'in:active,inactive'],
            'duration_ids'         => ['nullable', 'array'],
            'duration_ids.*'       => ['integer', 'exists:durations,id'],
        ]);

        $plan->update($data);

        if (array_key_exists('duration_ids', $data)) {
            $plan->durations()->sync($data['duration_ids'] ?? []);
        }

        return response()->json(['data' => $plan->fresh()->load('durations')]);
    }

    public function destroy(InvestmentPlan $plan): JsonResponse
    {
        // Soft delete — preserves historical investment records
        $plan->delete();

        return response()->json(null, 204);
    }

    // ----------------------------------------------------------------
    // Duration management
    // ----------------------------------------------------------------

    public function storeDuration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unit'  => ['required', 'in:hour,day,week,month'],
            'value' => ['required', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:50'],
        ]);

        $duration = Duration::firstOrCreate(
            ['unit' => $data['unit'], 'value' => $data['value']],
            ['label' => $data['label']]
        );

        return response()->json(['data' => $duration], 201);
    }

    public function attachDuration(Request $request, InvestmentPlan $plan): JsonResponse
    {
        $data = $request->validate([
            'duration_id' => ['required', 'integer', 'exists:durations,id'],
        ]);

        $plan->durations()->syncWithoutDetaching([$data['duration_id']]);

        return response()->json(['data' => $plan->load('durations')]);
    }

    public function detachDuration(InvestmentPlan $plan, Duration $duration): JsonResponse
    {
        // Detach only — existing active investments using this duration are preserved
        $plan->durations()->detach($duration->id);

        return response()->json(['data' => $plan->load('durations')]);
    }
}
