<?php

namespace App\Http\Controllers\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\client\ClientRegistration;
use Illuminate\Support\Facades\Validator;
use App\Models\client\ClientRegistrationActionHistory;
use App\Http\Requests\client\ClientRegistrationStoreRequest;
use App\Http\Requests\client\ClientRegistrationUpdateRequest;

class ClientRegistrationController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:pending_client_registration_list_view,pending_client_registration_list_view_as_admin')->only('index');
        $this->middleware('can:client_registration')->only('store');
        $this->middleware('can:pending_client_registration_update')->only('update');
        $this->middleware('can:client_registration_soft_delete')->only('destroy');
        $this->middleware('can:pending_client_registration_permanently_delete')->only('permanently_destroy');
        $this->middleware('can:pending_client_registration_approval')->only('approved');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "registration_id"   => $id,
            "author_id"         => auth()->id(),
            "name"              => auth()->user()->name,
            "image_uri"         => auth()->user()->image_uri,
            "action_type"       => $action,
            "action_details"    => $histData,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $client_registrations = ClientRegistration::with('Author:id,name')
            ->with("Field:id,name")
            ->with("Center:id,name")
            ->when(request('fetch_pending'), function ($query) {
                $query->where('is_approved', false);
                if (!Auth::user()->can('pending_client_registration_list_view_as_admin')) {
                    $query->where('creator_id', Auth::user()->id);
                }
            })
            ->when(request('field_id'), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->where('center_id', request('center_id'));
            })
            ->when(request('user_id'), function ($query) {
                $query->where('creator_id', request('user_id'));
            })
            ->when(request('form'), function ($query) {
                $query->select('id', 'acc_no', 'name', 'image_uri');
            })
            ->orderBy('id', 'DESC')
            ->get();

        return self::create_response(null, $client_registrations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRegistrationStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::where('meta_key', 'client_registration_approval')->value('meta_value');
        $img            = Helper::storeImage($data->image, "client", "client");
        $signature      = isset($data->signature)
            ? Helper::storeSignature($data->signature, "client_signature", "client")
            : (object) ["name" => null, "uri" => null];

        ClientRegistration::create(self::set_field_map($data, $img->name, $img->uri, $signature->name, $signature->uri, $is_approved, auth()->id()));
        return self::create_response(__('customValidations.client.registration.successful'));
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

        DB::transaction(function () use ($id, $client, $data, $histData) {
            if (!empty($data->image)) {
                if (!empty($client->image)) {
                    $path = public_path('storage/client/' . $client->image);
                    unlink($path);
                }

                $histData['image']  = "<p class='text-danger'>********</p><p class='text-success'>********</p>";
                $extension          = $data->image->extension();
                $imgName            = 'client_' . time() . '.' . $extension;

                $data->image->move(public_path() . '/storage/client/', $imgName);
                $client->update(
                    [
                        'image'     => $imgName,
                        'image_uri' => URL::to('/storage/client/', $imgName),
                    ]
                );
            }
            if (!empty($data->signature)) {
                if (!empty($client->signature)) {
                    $path = public_path('storage/client/' . $client->signature);
                    unlink($path);
                }

                $histData['signature']  = "<p class='text-danger'>********</p><p class='text-success'>********</p>";
                $folderPath             = public_path() . '/storage/client/';
                $image_parts            = explode(";base64,", $data->signature);
                $image_type_aux         = explode("image/", $image_parts[0]);
                $image_type             = $image_type_aux[1];
                $image_base64           = base64_decode($image_parts[1]);
                $sign                   = 'client_signature_' . time() . '.' . $image_type;

                file_put_contents($folderPath . $sign, $image_base64);
                $client->update(
                    [
                        'signature'     => $sign,
                        'signature_uri' => URL::to('/storage/client/', $sign),
                    ]
                );
            }

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

        return self::create_response(__('customValidations.client.registration.update'));
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

        return self::create_response(__('customValidations.client.registration.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        ClientRegistration::find($id)->forceDelete();
        return self::create_response(__('customValidations.client.registration.p_delete'));
    }

    /**
     * Get all Occupations
     */
    public function get_client_occupations()
    {
        $occupations = ClientRegistration::distinct('occupation')->orderBy('occupation', 'asc')->pluck('occupation');
        return self::create_response(null, $occupations);
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        ClientRegistration::find($id)->update(['is_approved' => true]);
        return self::create_response(__('customValidations.client.registration.approved'));
    }

    /**
     * Create success Response
     */
    private static function create_response($message = null, $data = null, $code = '200', $success = true)
    {
        $res = ['success' => $success];

        if (!empty($message)) {
            $res['message'] = $message;
        }
        if (!empty($data)) {
            $res['data'] = $data;
        }

        return response($res, $code);
    }

    /**
     * Set Saving Acc Field Map
     * 
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * @return array
     */
    private static function set_field_map($data, $image, $image_uri, $signature = null, $signature_uri = null, $is_approved = null, $creator_id = null)
    {
        $map = [
            'field_id'          => $data->field_id,
            'center_id'         => $data->center_id,
            'acc_no'            => $data->acc_no,
            'name'              => $data->name,
            'father_name'       => $data->father_name,
            'husband_name'      => isset($data->husband_name) ? $data->husband_name : '',
            'mother_name'       => $data->mother_name,
            'nid'               => $data->nid,
            'dob'               => date('Y-m-d', strtotime($data->dob)),
            'occupation'        => $data->occupation,
            'religion'          => $data->religion,
            'gender'            => $data->gender,
            'primary_phone'     => $data->primary_phone,
            'secondary_phone'   => isset($data->secondary_phone) ? $data->secondary_phone : '',
            'image'             => $image,
            'image_uri'         => $image_uri,
            'annual_income'     => isset($data->annual_income) ? $data->annual_income : '',
            'bank_acc_no'       => isset($data->bank_acc_no) ? $data->bank_acc_no : '',
            'bank_check_no'     => isset($data->bank_check_no) ? $data->bank_check_no : '',
            'share'             => $data->share,
            'present_address'   => $data->present_address,
            'permanent_address' => $data->permanent_address,
        ];

        if (isset($signature) && isset($signature_uri)) {
            $map['signature'] = $signature;
            $map['signature_uri'] = $signature_uri;
        }
        if (isset($is_approved)) {
            $map['is_approved'] = $is_approved;
        }
        if (isset($creator_id)) {
            $map['creator_id'] = $creator_id ?? auth()->id();
        }

        return $map;
    }
}
