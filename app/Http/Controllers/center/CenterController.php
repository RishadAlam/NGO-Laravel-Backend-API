<?php

namespace App\Http\Controllers\center;

use App\Http\Controllers\Controller;
use App\Models\center\Center;
use Illuminate\Http\Request;

class CenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = Center::with('Author:id,name')
            ->with(['CenterActionHistory:id,center_id,author_id,name,image_uri,action_type,action_details', 'CenterActionHistory.Author:id,name,image_uri'])
            ->get(['id', 'name', 'description', 'status', 'created_by', 'created_at', 'updated_at']);

        return response(
            [
                'success'   => true,
                'data'      => $fields
            ],
            200
        );
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
