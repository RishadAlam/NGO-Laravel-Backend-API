<?php

namespace App\Http\Controllers\category;

use Illuminate\Http\Request;
use App\Models\category\Category;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;
use App\Models\category\CategoryActionHistory;
use App\Http\Requests\category\CategoryStoreRequest;
use App\Http\Requests\category\CategoryUpdateRequest;
use App\Http\Requests\category\CategoryChangeStatusRequest;

class CategoryController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:category_list_view')->only('index');
        $this->middleware('can:category_registration')->only('store');
        $this->middleware('can:category_data_update')->only(['update', 'change_status']);
        $this->middleware('can:category_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('Author:id,name')
            ->with(['CategoryActionHistory', 'CategoryActionHistory.Author:id,name,image_uri'])
            ->get(['id', 'name', 'group', 'description', 'saving', 'loan', 'status', 'is_default', 'creator_id', 'created_at', 'updated_at']);

        return create_response(null, $categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request)
    {
        $data = (object) $request->validated();
        $category = Category::create(
            [
                'name'          => $data->name,
                'group'         => $data->group,
                'description'   => $data->description ?? null,
                'saving'        => $data->saving ?? false,
                'loan'          => $data->loan ?? false,
                'creator_id'    => auth()->id(),
            ]
        );

        CategoryConfig::create(['category_id' => $category->id]);
        return create_response(__('customValidations.category.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $category   = Category::find($id);
        $histData   = [];

        $category->name     !== $data->name ? $histData['name'] = "<p class='text-danger'>{$category->name}</p><p class='text-success'>{$data->name}</p>" : '';
        $category->group    !== $data->group ? $histData['group'] = "<p class='text-danger'>{$category->group}</p><p class='text-success'>{$data->group}</p>" : '';
        if (isset($data->description)) {
            $category->description !== $data->description ? $histData['description'] = "<p class='text-danger'>{$category->description}</p><p class='text-success'>{$data->description}</p>" : '';
        }
        if (isset($data->saving)) {
            $category->saving !== $data->saving ?? false ? $histData['saving'] = $data->saving ?? false ? "<p class='text-danger'>UnChecked</p><p class='text-danger'>Checked</p>" : "<p class='text-danger'>Checked</p><p class='text-danger'>UnChecked</p>" : '';
        }
        if (isset($data->loan)) {
            $category->loan !== $data->loan ?? false ? $histData['loan'] = $data->loan ?? false ? "<p class='text-danger'>UnChecked</p><p class='text-danger'>Checked</p>" : "<p class='text-danger'>Checked</p><p class='text-danger'>UnChecked</p>" : '';
        }

        DB::transaction(function () use ($id, $data, $category, $histData) {
            $category->update([
                'name'          => $data->name,
                'group'         => $data->group,
                'description'   => $data->description ?? null,
                'saving'        => $data->saving ?? false,
                'loan'          => $data->loan ?? false,
            ]);

            CategoryActionHistory::create([
                "category_id"       => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'update',
                "action_details"    => $histData,
            ]);
        });

        return create_response(__('customValidations.category.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            Category::find($id)->delete();
            CategoryConfig::where('category_id', $id)->delete();
            CategoryActionHistory::create([
                "category_id"       => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'delete',
                "action_details"    => [],
            ]);
        });

        return create_response(__('customValidations.category.delete'));
    }

    /**
     * Change Status the specified Category
     */
    public function change_status(CategoryChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? '<p class="text-danger">Deactive</p><p class="text-success">Active</p>' : '<p class="text-danger">Active</p><p class="text-success">Deactive</p>';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Category::find($id)->update(['status' => $status]);
                CategoryActionHistory::create([
                    "category_id"       => $id,
                    "author_id"         => auth()->id(),
                    "name"              => auth()->user()->name,
                    "image_uri"         => auth()->user()->image_uri,
                    "action_type"       => 'update',
                    "action_details"    => ['status' => $changeStatus],
                ]);
            }
        );

        return create_response(__('customValidations.category.status'));
    }

    /**
     * Get all category groups
     */
    public function get_category_groups()
    {
        $groups = Category::distinct('group')->orderBy('group', 'asc')->pluck('group');
        return create_response(null, $groups);
    }

    /**
     * Get all active Categories
     */
    public function get_active_Categories()
    {
        $categories = Category::where('status', true)
            ->when(request('saving'), function ($query) {
                $query->where('saving', true);
            })
            ->when(request('loan'), function ($query) {
                $query->where('loan', true);
            })
            ->get(['id', 'name', 'group', 'is_default']);

        return create_response(null, $categories);
    }
}
