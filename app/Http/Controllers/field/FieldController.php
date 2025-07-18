<?php

namespace App\Http\Controllers\field;

use App\Models\field\Field;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\field\FieldActionHistory;
use App\Http\Requests\field\FieldStoreRequest;
use App\Http\Requests\field\FieldUpdateRequest;
use App\Http\Requests\field\FieldChangeStatusRequest;

class FieldController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:field_list_view')->only('index');
        $this->middleware('can:field_registration')->only('store');
        $this->middleware('can:field_data_update')->only(['update', 'change_status']);
        $this->middleware('can:field_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = Field::with('Author:id,name')
            ->with(['FieldActionHistory', 'FieldActionHistory.Author:id,name,image_uri'])
            ->get(['id', 'name', 'description', 'status', 'creator_id', 'created_at', 'updated_at']);

        return create_response(null, $fields);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FieldStoreRequest $request)
    {
        $data = (object) $request->validated();
        Field::create(
            [
                'name'          => $data->name,
                'description'   => $data->description ?? null,
                'creator_id'    => auth()->id(),
            ]
        );

        return create_response(__('customValidations.field.successful'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FieldUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $field      = Field::find($id);
        $histData   = [];

        $field->name        !== $data->name ? $histData['name'] = "<p class='text-danger'>- {$field->name}</p><p class='text-success'>+ {$data->name}</p>" : '';
        $field->description !== $data->description ? $histData['description'] = "<p class='text-danger'>{$field->description}</p><p class='text-success'>{$data->description}</p>" : '';

        DB::transaction(function () use ($id, $data, $field, $histData) {
            $field->update([
                'name'          => $data->name,
                'description'   => $data->description ?? null,
            ]);

            FieldActionHistory::create([
                "field_id"          => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'update',
                "action_details"    => $histData,
            ]);
        });

        return create_response(__('customValidations.field.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            Field::find($id)->delete();
            FieldActionHistory::create([
                "field_id"          => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'delete',
                "action_details"    => [],
            ]);
        });

        return create_response(__('customValidations.field.delete'));
    }

    /**
     * Change Status the specified Field
     */
    public function change_status(FieldChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? '<p class="text-danger">Deactive</p><p class="text-success">Active</p>' : '<p class="text-danger">Active</p><p class="text-success">Deactive</p>';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Field::find($id)->update(['status' => $status]);
                FieldActionHistory::create([
                    "field_id"          => $id,
                    "author_id"         => auth()->id(),
                    "name"              => auth()->user()->name,
                    "image_uri"         => auth()->user()->image_uri,
                    "action_type"       => 'update',
                    "action_details"    => ['status' => $changeStatus],
                ]);
            }
        );

        return create_response(__('customValidations.field.status'));
    }

    /**
     * Get all active fields
     */
    public function get_active_fields()
    {
        $fields = Field::where('status', true)->get(['id', 'name']);
        return create_response(null, $fields);
    }
}
