<?php

namespace App\Http\Controllers\field;

use App\Http\Controllers\Controller;
use App\Models\field\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = Field::with('Author:id,name')
            ->with(['FieldActionHistory:id,field_id,author_id,name,image_uri,action_type,action_details', 'FieldActionHistory.Author:id,name,image_uri'])
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
