<?php

namespace App\Http\Controllers\category;

use App\Http\Controllers\Controller;
use App\Http\Requests\category\CategoryChangeStatusRequest;
use App\Http\Requests\category\CategoryStoreRequest;
use App\Http\Requests\category\CategoryUpdateRequest;
use App\Models\category\Category;
use App\Models\category\CategoryActionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function update(CategoryUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $category   = Category::find($id);
        $histData   = [];
        $check      = "&#x2611;";
        $cross      = "&#10060;";

        $category->name !== $data->name ? $histData['name'] = "{$category->name} => {$data->name}" : '';
        if (isset($data->description)) {
            $category->description !== $data->description ? $histData['description'] = "{$category->description} => {$data->description}" : '';
        }
        if (isset($data->saving)) {
            $category->saving !== $data->saving ?? false ? $histData['saving'] = $data->saving ?? false ? "{$cross} => {$check}" : "{$check} => {$cross}" : '';
        }
        if (isset($data->loan)) {
            $category->loan !== $data->loan ?? false ? $histData['loan'] = $data->loan ?? false ? "{$cross} => {$check}" : "{$check} => {$cross}" : '';
        }

        DB::transaction(function () use ($id, $data, $category, $histData) {
            $category->update([
                'name'          => $data->name,
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

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.category.update')
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            Category::find($id)->delete();
            CategoryActionHistory::create([
                "category_id"       => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'delete',
                "action_details"    => [],
            ]);
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.category.delete')
            ],
            200
        );
    }

    /**
     * Change Status the specified Category
     */
    public function change_status(CategoryChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? 'Deactive => Active' : 'Active => Deactive';
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

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.category.status')
            ],
            200
        );
    }
}
