<?php

namespace App\Http\Controllers\Audit;

use Illuminate\Http\Request;
use App\Models\Audit\AuditReport;
use App\Http\Controllers\Controller;

class AuditReportController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:cooperative_audit_report_list_view')->only('index');
        $this->middleware('can:cooperative_audit_report_update')->only('update');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = AuditReport::with('LastUpdatedBy:id,name,image_uri')->latest()->get();
        return create_response(null, $reports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
