<?php

namespace App\Http\Controllers\center;

use App\Http\Controllers\Controller;
use App\Http\Requests\center\CenterChangeStatusRequest;
use App\Http\Requests\center\CenterStoreRequest;
use App\Models\center\Center;
use App\Models\center\CenterActionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store(CenterStoreRequest $request)
    {
        $data = (object) $request->validated();
        Center::create(
            [
                'name'          => $data->name,
                'description'   => $data->description,
                'created_by'    => auth()->id(),
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.center.successful'),
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
        DB::transaction(function () use ($id) {
            Center::find($id)->delete();
            CenterActionHistory::create([
                "center_id"         => $id,
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
                'message'   => __('customValidations.center.delete')
            ],
            200
        );
    }


    /**
     * Change Status the specified user
     */
    public function change_status(CenterChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? 'Deactive => Active.' : 'Active => Deactive.';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Center::find($id)->update(['status' => $status]);
                CenterActionHistory::create([
                    "center_id"         => $id,
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
                'message'   => __('customValidations.field.status')
            ],
            200
        );
    }
}
