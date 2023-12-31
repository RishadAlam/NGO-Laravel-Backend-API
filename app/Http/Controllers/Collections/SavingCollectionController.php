<?php

namespace App\Http\Controllers\Collections;

use Illuminate\Http\Request;
use App\Models\category\Category;
use App\Http\Controllers\Controller;
use App\Models\Collections\SavingCollection;

class SavingCollectionController extends Controller
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
        $categoryReport = Category::regularCategoryReport()->get();

        return response([
            'success'   => true,
            'data'      => $categoryReport
        ], 200);
    }
}
