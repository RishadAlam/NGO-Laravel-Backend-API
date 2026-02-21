<?php

namespace App\Http\Controllers\Audit;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Audit\InternalAuditReportService;

class InternalAuditReportController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:internal_audit_report_view')->only('index');
    }

    /**
     * Display the internal audit report summary.
     */
    public function index(Request $request, InternalAuditReportService $internalAuditReportService)
    {
        $validated = $request->validate([
            'fromDate' => ['nullable', 'date'],
            'toDate' => ['nullable', 'date', 'after_or_equal:fromDate'],
            'date_range' => ['nullable', 'string'],
            'field_id' => ['nullable', 'integer', 'exists:fields,id'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'fieldId' => ['nullable', 'integer', 'exists:fields,id'],
            'centerId' => ['nullable', 'integer', 'exists:centers,id'],
        ]);

        $filters = [
            'fromDate' => $validated['fromDate'] ?? null,
            'toDate' => $validated['toDate'] ?? null,
            'date_range' => $validated['date_range'] ?? null,
            'field_id' => $validated['field_id'] ?? $validated['fieldId'] ?? null,
            'center_id' => $validated['center_id'] ?? $validated['centerId'] ?? null,
        ];

        return create_response(null, $internalAuditReportService->generate($filters));
    }
}
