<?php

namespace App\Http\Controllers\client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\client\ClientRegistration;
use App\Http\Requests\client\ClientRegistrationStoreRequest;
use App\Http\Requests\client\ClientRegistrationUpdateRequest;
use App\Models\AppConfig;
use App\Models\client\ClientRegistrationActionHistory;
use Carbon\Carbon;

class ClientRegistrationController extends Controller
{

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "client_registration_id"    => $id,
            "author_id"                 => auth()->id(),
            "name"                      => auth()->user()->name,
            "image_uri"                 => auth()->user()->image_uri,
            "action_type"               => $action,
            "action_details"            => $histData,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $client_registrations = ClientRegistration::when(request('field_id'), function ($query) {
            $query->where('field_id', request('field_id'));
        })
            ->when(request('center_id'), function ($query) {
                $query->where('center_id', request('center_id'));
            })
            ->get(['id', 'name']);

        return response(
            [
                'success'   => true,
                'data'      => $client_registrations
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRegistrationStoreRequest $request)
    {
        $data       = (object) $request->validated();
        $extension  = $data->image->extension();
        $imgName    = 'client_' . time() . '.' . $extension;
        $data->image->move(public_path() . '/storage/client/', $imgName);
        $is_approved = AppConfig::where('meta_key', 'client_registration_approval')
            ->value('meta_value');

        ClientRegistration::create(
            [
                'field_id'          => $data->field_id,
                'center_id'         => $data->center_id,
                'acc_no'            => $data->acc_no,
                'name'              => $data->name,
                'father_name'       => $data->father_name,
                'husband_name'      => $data->husband_name,
                'mother_name'       => $data->mother_name,
                'nid'               => $data->nid,
                'dob'               => date('y-m-d', strtotime($data->dob)),
                'occupation'        => $data->occupation,
                'religion'          => $data->religion,
                'gender'            => $data->gender,
                'primary_phone'     => $data->primary_phone,
                'secondary_phone'   => $data->secondary_phone,
                'image'             => $imgName,
                'image_uri'         => URL::to('/storage/client/', $imgName),
                'annual_income'     => $data->annual_income ?? null,
                'bank_acc_no'       => $data->bank_acc_no ?? null,
                'bank_check_no'     => $data->bank_check_no ?? null,
                'share'             => $data->share,
                'present_address'   => $data->present_address,
                'permanent_address' => $data->permanent_address,
                'is_approved'       => $is_approved,
                'creator_id'        => auth()->id()
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.client.registration.successful'),
            ],
            200
        );
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
    public function update(ClientRegistrationUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $client     = ClientRegistration::find($id);
        $histData   = [];

        $client->name               !== $data->name ? $histData['name'] = "<p class='text-danger'>{$client->name}</p><p class='text-success'>{$data->name}</p>" : '';
        $client->father_name        !== $data->father_name ? $histData['father_name'] = "<p class='text-danger'>{$client->father_name}</p><p class='text-success'>{$data->father_name}</p>" : '';
        $client->husband_name       !== $data->husband_name ? $histData['husband_name'] = "<p class='text-danger'>{$client->husband_name}</p><p class='text-success'>{$data->husband_name}</p>" : '';
        $client->mother_name        !== $data->mother_name ? $histData['mother_name'] = "<p class='text-danger'>{$client->mother_name}</p><p class='text-success'>{$data->mother_name}</p>" : '';
        $client->nid                !== $data->nid ? $histData['nid'] = "<p class='text-danger'>{$client->nid}</p><p class='text-success'>{$data->nid}</p>" : '';
        $client->dob                !== $data->dob ? $histData['dob'] = "<p class='text-danger'>{$client->dob}</p><p class='text-success'>{$data->dob}</p>" : '';
        $client->occupation         !== $data->occupation ? $histData['occupation'] = "<p class='text-danger'>{$client->occupation}</p><p class='text-success'>{$data->occupation}</p>" : '';
        $client->religion           !== $data->religion ? $histData['religion'] = "<p class='text-danger'>{$client->religion}</p><p class='text-success'>{$data->religion}</p>" : '';
        $client->gender             !== $data->gender ? $histData['gender'] = "<p class='text-danger'>{$client->gender}</p><p class='text-success'>{$data->gender}</p>" : '';
        $client->primary_phone      !== $data->primary_phone ? $histData['primary_phone'] = "<p class='text-danger'>{$client->primary_phone}</p><p class='text-success'>{$data->primary_phone}</p>" : '';
        $client->secondary_phone    !== $data->secondary_phone ? $histData['secondary_phone'] = "<p class='text-danger'>{$client->secondary_phone}</p><p class='text-success'>{$data->secondary_phone}</p>" : '';
        $client->share              !== $data->share ? $histData['share'] = "<p class='text-danger'>{$client->share}</p><p class='text-success'>{$data->share}</p>" : '';
        $client->present_address    !== $data->present_address ? $histData['present_address'] = "<p class='text-danger'>{$client->present_address}</p><p class='text-success'>{$data->present_address}</p>" : '';
        $client->permanent_address  !== $data->permanent_address ? $histData['permanent_address'] = "<p class='text-danger'>{$client->permanent_address}</p><p class='text-success'>{$data->permanent_address}</p>" : '';

        if (!empty($data->image)) {
            if (!empty($client->image)) {
                $path = public_path('storage/client/' . $client->image);
                unlink($path);
            }

            $extension  = $data->image->extension();
            $imgName    = 'client_' . time() . '.' . $extension;

            $data->image->move(public_path() . '/storage/client/', $imgName);
            $client->update(
                [
                    'image'     => $imgName,
                    'image_uri' => URL::to('/storage/client/', $imgName),
                ]
            );
        }

        DB::transaction(function () use ($id, $client, $data, $histData) {
            $client->update(
                [
                    'name'              => $data->name,
                    'father_name'       => $data->father_name,
                    'husband_name'      => $data->husband_name,
                    'mother_name'       => $data->mother_name,
                    'nid'               => $data->nid,
                    'dob'               => $data->dob,
                    'occupation'        => $data->occupation,
                    'religion'          => $data->religion,
                    'gender'            => $data->gender,
                    'primary_phone'     => $data->primary_phone,
                    'secondary_phone'   => $data->secondary_phone,
                    'share'             => $data->share,
                    'present_address'   => $data->present_address,
                    'permanent_address' => $data->permanent_address,
                ]
            );
            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.client.registration.update'),
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
            ClientRegistration::find($id)->delete();
            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.client.registration.delete')
            ],
            200
        );
    }

    /**
     * Get all Occupations
     */
    public function get_client_occupations()
    {
        $occupations = ClientRegistration::distinct('occupation')->orderBy('occupation', 'asc')->pluck('occupation');

        return response(
            [
                'success'   => true,
                'data'      => $occupations
            ],
            200
        );
    }
}
