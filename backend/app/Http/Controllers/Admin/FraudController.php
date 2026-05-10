<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudAssessment;
use App\Models\Transaction;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FraudController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * GET /api/v1/admin/fraud
     * List transactions in pending_review with their fraud assessments.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $flagged = Transaction::where('status', 'pending_review')
            ->with(['user', 'fraudAssessment'])
            ->orderByDesc('created_at')
            ->paginate((int) ($request->per_page ?? 20));

        return response()->json([
            'data' => $flagged->items(),
            'meta' => [
                'current_page' => $flagged->currentPage(),
                'last_page'    => $flagged->lastPage(),
                'per_page'     => $flagged->perPage(),
                'total'        => $flagged->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/fraud/{assessment}/approve
     */
    public function approve(Request $request, FraudAssessment $assessment): JsonResponse
    {
        $assessment->update([
            'review_decision' => 'approved',
            'reviewed_by'     => $request->user()->id,
            'reviewed_at'     => now(),
        ]);

        // Mark the transaction as completed
        if ($assessment->assessable_type === 'transaction') {
            $assessment->assessable?->update(['status' => 'completed']);
        }

        $this->audit->log(
            operationType: 'fraud.approve',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: 'fraud_assessment',
            targetId: $assessment->id,
            outcome: 'success',
        );

        return response()->json(['data' => $assessment->fresh()]);
    }

    /**
     * POST /api/v1/admin/fraud/{assessment}/reject
     */
    public function reject(Request $request, FraudAssessment $assessment): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $assessment->update([
            'review_decision' => 'rejected',
            'review_reason'   => $data['reason'],
            'reviewed_by'     => $request->user()->id,
            'reviewed_at'     => now(),
        ]);

        // Mark the transaction as failed
        if ($assessment->assessable_type === 'transaction') {
            $assessment->assessable?->update(['status' => 'failed']);
        }

        $this->audit->log(
            operationType: 'fraud.reject',
            actorType: 'admin',
            actorId: $request->user()->id,
            targetType: 'fraud_assessment',
            targetId: $assessment->id,
            outcome: 'success',
            metadata: ['reason' => $data['reason']],
        );

        return response()->json(['data' => $assessment->fresh()]);
    }
}
