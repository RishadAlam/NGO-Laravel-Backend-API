<?php

namespace App\Http\Controllers\Collections;

use App\Models\field\Field;
use Illuminate\Http\Request;
use App\Models\center\Center;
use App\Models\category\Category;
use App\Http\Controllers\Controller;

class LoanCollectionController extends Controller
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

    /**
     * Regular Category Report
     */
    public function regularCategoryReport()
    {
        $categoryReport = Category::RegularCategoryLoanReport()
            ->get(['id', 'name', 'is_default']);

        return response([
            'success'   => true,
            'data'      => $categoryReport
        ], 200);
    }

    /**
     * Regular Field Report
     */
    public function regularFieldReport($category_id)
    {
        $fieldReport = Field::regularFieldLoanReport($category_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $fieldReport
        ], 200);
    }

    /**
     * Regular Collection Sheet
     */
    public function regularCollectionSheet($category_id, $field_id)
    {
        $collections = Center::scopeRegularCollectionSheet($category_id, $field_id)->get(['id', 'name']);

        return response([
            'success'   => true,
            'data'      => $collections
        ], 200);
    }
}
