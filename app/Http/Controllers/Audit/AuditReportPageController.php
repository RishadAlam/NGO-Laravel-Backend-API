<?php

namespace App\Http\Controllers\Audit;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Audit\AuditReportPage;

class AuditReportPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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

    /**
     * Get Audit Pages
     */
    public function get_all_pages()
    {
        $pages = AuditReportPage::get(['id', 'name', 'is_default']);
        return create_response(null, $pages);
    }
}
