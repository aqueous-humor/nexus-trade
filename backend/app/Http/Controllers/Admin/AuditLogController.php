<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Paginated list of audit logs with filter support.
     *
     * Query params:
     *   - actor_id:       filter by actor user ID (optional)
     *   - operation_type: filter by operation type string (optional)
     *   - date_from:      filter created_at >= value (optional)
     *   - date_to:        filter created_at <= value (optional)
     *   - outcome:        filter by outcome string (optional)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'actor_id'       => ['sometimes', 'integer'],
            'operation_type' => ['sometimes', 'string', 'max:255'],
            'date_from'      => ['sometimes', 'date'],
            'date_to'        => ['sometimes', 'date'],
            'outcome'        => ['sometimes', 'string', 'max:255'],
        ]);

        $query = AuditLog::query();

        if ($actorId = $request->query('actor_id')) {
            $query->where('actor_id', (int) $actorId);
        }

        if ($operationType = $request->query('operation_type')) {
            $query->where('operation_type', $operationType);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        if ($outcome = $request->query('outcome')) {
            $query->where('outcome', $outcome);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($logs);
    }
}
