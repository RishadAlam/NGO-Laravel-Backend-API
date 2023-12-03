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
        $histData   = self::set_update_hist($data, $client);

        DB::transaction(function () use ($id, $client, $data, $histData) {
            if (!empty($data->image)) {
                if (!empty($client->image)) {
                    Helper::unlinkImage(public_path('storage/client/' . $client->image));
                }

                $img                = Helper::storeImage($data->image, "client", "client");
                $histData['image']  = "<p class='text-danger'>********</p><p class='text-success'>********</p>";
                $client->update(
                    [
                        'image'     => $img->name,
                        'image_uri' => $img->uri,
                    ]
                );
            }
            if (!empty($data->signature)) {
                if (!empty($client->signature)) {
                    Helper::unlinkImage(public_path('storage/client/' . $client->signature));
                }

                $signature              = Helper::storeSignature($data->signature, "client_signature", "client");
                $histData['signature']  = "<p class='text-danger'>********</p><p class='text-success'>********</p>";
                $client->update(
                    [
                        'signature'     => $signature->name,
                        'signature_uri' => $signature->uri,
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

    /**
     * Set Saving Acc update hist
     * 
     * @param object $data
     * @param object $client
     * @return array
     */
    private static function set_update_hist($data, $client)
    {
        $histData                   = [];
        $data->present_address      = (object) $data->present_address;
        $data->permanent_address    = (object) $data->permanent_address;
        $fieldsToCompare            = ['name', 'husband_name', 'father_name', 'mother_name', 'nid', 'dob', 'occupation', 'religion', 'gender', 'primary_phone', 'secondary_phone', 'share', 'present_address', 'permanent_address'];
        $addressFields              = ['street_address', 'city', 'word_no', 'post_office', 'police_station', 'district', 'division'];

        foreach ($fieldsToCompare as $field) {
            if ($field === 'present_address' || $field === 'permanent_address') {
                $clientValue = '';
                $dataValue = '';

                foreach ($addressFields as $subField) {
                    $clientValue = $client->{$field}->{$subField};
                    $dataValue = $data->{$field}->{$subField};
                    !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
                }
            } else {
                $clientValue = $client->{$field};
                $dataValue = $data->{$field};
                !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
            }
        }

        return $histData;
    }
}
