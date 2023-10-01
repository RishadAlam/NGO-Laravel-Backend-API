<?php

namespace App\Http\Controllers\field;

use App\Http\Controllers\Controller;
use App\Http\Requests\field\FieldChangeStatusRequest;
use App\Http\Requests\field\FieldStoreRequest;
use App\Http\Requests\field\FieldUpdateRequest;
use App\Models\field\Field;
use App\Models\field\FieldActionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store(FieldStoreRequest $request)
    {
        $data = (object) $request->validated();
        Field::create(
            [
                'name'          => $data->name,
                'description'   => $data->description,
                'created_by'    => auth()->id(),
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.field.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FieldUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $field      = Field::find($id);
        $histData   = [];

        $field->name        !== $data->name ? $histData['name'] = "{$field->name} => {$data->name}" : '';
        $field->description !== $data->description ? $histData['description'] = "{$field->description} => {$data->description}" : '';

        DB::transaction(function () use ($id, $data, $field, $histData) {
            $field->update([
                'name'          => $data->name,
                'description'   => $data->description,
            ]);

            FieldActionHistory::create([
                "field_id"          => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'update',
                "action_details"    => json_encode($histData),
            ]);
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.field.update')
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
            Field::find($id)->delete();
            FieldActionHistory::create([
                "field_id"          => $id,
                "author_id"         => auth()->id(),
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'delete',
                "action_details"    => json_encode([]),
            ]);
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.field.delete')
            ],
            200
        );
    }

    /**
     * Change Status the specified user
     */
    public function change_status(FieldChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? 'Deactive => Active.' : 'Active => Deactive.';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Field::find($id)->update(['status' => $status]);
                FieldActionHistory::create([
                    "field_id"          => $id,
                    "author_id"         => auth()->id(),
                    "name"              => auth()->user()->name,
                    "image_uri"         => auth()->user()->image_uri,
                    "action_type"       => 'update',
                    "action_details"    => json_encode(['status' => $changeStatus]),
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
}
