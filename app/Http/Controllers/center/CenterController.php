<?php

namespace App\Http\Controllers\center;

use App\Models\center\Center;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\center\CenterActionHistory;
use App\Http\Requests\center\CenterStoreRequest;
use App\Http\Requests\center\CenterUpdateRequest;
use App\Http\Requests\center\CenterChangeStatusRequest;

class CenterController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:center_list_view')->only('index');
        $this->middleware('can:center_registration')->only('store');
        $this->middleware('can:center_data_update')->only(['update', 'change_status']);
        $this->middleware('can:center_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $centers = Center::with('Author:id,name')
            ->with('Field:id,name')
            ->with(['CenterActionHistory', 'CenterActionHistory.Author:id,name,image_uri'])
            ->when(!empty(request('field_id')), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->get(['id', 'field_id', 'name', 'description', 'status', 'creator_id', 'created_at', 'updated_at']);

        return response(
            [
                'success'   => true,
                'data'      => $centers
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
                'field_id'      => $data->field_id,
                'description'   => $data->description ?? null,
                'creator_id'    => auth()->id(),
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
    public function update(CenterUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $center     = Center::with('Field:id,name')->find($id);
        $histData   = [];

        $center->name        !== $data->name ? $histData['name'] = "<p class='text-danger'>{$center->name}</p><p class='text-success'>{$data->name}</p>" : '';
        $center->field_id    !== $data->field_id ? $histData['field'] = "<p class='text-danger'>{$center->field->name}</p><p class='text-success'>{$request->field['name']}</p>" : '';
        $center->description !== $data->description ? $histData['description'] = "<p class='text-danger'>{$center->description}</p><p class='text-success'>{$data->description}</p>" : '';

        DB::transaction(function () use ($id, $data, $center, $histData) {
            $center->update([
                'name'          => $data->name,
                'field_id'      => $data->field_id,
                'description'   => $data->description ?? null,
            ]);

            CenterActionHistory::create([
                "center_id"         => $id,
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
                'message'   => __('customValidations.center.update')
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
     * Change Status the specified Center
     */
    public function change_status(CenterChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? '<p class="text-danger">Deactive</p><p class="text-success">Active</p>' : '<p class="text-danger">Active</p><p class="text-success">Deactive</p>';
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
                'message'   => __('customValidations.center.status')
            ],
            200
        );
    }

    /**
     * Get all active centers
     */
    public function get_active_centers()
    {
        $centers = Center::where('status', true)
            ->when(!empty(request('field_id')), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->get(['id', 'field_id', 'name']);

        return response(
            [
                'success'   => true,
                'data'      => $centers
            ],
            200
        );
    }
}
