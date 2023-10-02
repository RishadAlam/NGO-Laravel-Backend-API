<?php

namespace App\Http\Controllers\category;

use App\Http\Controllers\Controller;
use App\Http\Requests\category\CategoryStoreRequest;
use App\Models\category\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('Author:id,name')
            ->with(['CategoryActionHistory:id,category_id,author_id,name,image_uri,action_type,action_details', 'CategoryActionHistory.Author:id,name,image_uri'])
            ->get(['id', 'name', 'description', 'saving', 'loan', 'status', 'is_default', 'creator_id', 'created_at', 'updated_at']);

        return response(
            [
                'success'   => true,
                'data'      => $categories
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request)
    {
        $data = (object) $request->validated();
        Category::create(
            [
                'name'          => $data->name,
                'description'   => $data->description ?? null,
                'saving'        => $data->saving ?? false,
                'loan'          => $data->loan ?? false,
                'creator_id'    => auth()->id(),
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.category.successful'),
            ],
            200
        );
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
