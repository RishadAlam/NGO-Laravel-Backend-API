<?php

namespace App\Http\Controllers\client;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
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
        $this->middleware('permission:pending_client_registration_list_view|pending_client_registration_list_view_as_admin')->only('pending_forms');
        $this->middleware('can:client_registration')->only('store');
        $this->middleware('can:pending_client_registration_update')->only('update');
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
        $client = ClientRegistration::approve()
            ->field('id', 'name')
            ->center('id', 'name')
            ->author('id', 'name')
            ->filter()
            ->orderedBy()
            ->get();

        return create_response(null, $client);
    }

    /**
     * Show the specified resource from storage.
     */
    public function show(string $id)
    {
        $client = ClientRegistration::client()->find($id);
        return create_response(null, $client);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRegistrationStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('client_registration_approval');
        $img            = Helper::storeImage($data->image, "client", "client");
        $signature      = isset($data->signature)
            ? Helper::storeSignature($data->signature, "client_signature", "client")
            : (object) ["name" => null, "uri" => null];

        ClientRegistration::create(
            self::set_field_map(
                $data,
                $data->field_id,
                $data->center_id,
                $data->acc_no,
                $img->name,
                $img->uri,
                $signature->name,
                $signature->uri,
                $is_approved,
                auth()->id()
            )
        );

        return create_response(__('customValidations.client.registration.successful'));
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
            self::update_file($client, $data->image, 'image', 'image', 'image_uri', 'client', $histData);
            self::update_file($client, $data->signature, 'signature', 'signature', 'signature_uri', 'client', $histData);

            $client->update(self::set_field_map($data));
            ClientRegistrationActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return create_response(__('customValidations.client.registration.update'));
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

        return create_response(__('customValidations.client.registration.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        ClientRegistration::find($id)->forceDelete();
        return create_response(__('customValidations.client.registration.p_delete'));
    }

    /**
     * Count All Account
     */
    public function countAccounts(string $id)
    {
        $data = (object) [
            // Saving Accounts
            "activeSavings"  => SavingAccount::clientRegistrationID($id)->approve()->active()->count(),
            "pendingSavings" => SavingAccount::clientRegistrationID($id)->pending()->count(),
            "holdSavings"    => SavingAccount::clientRegistrationID($id)->approve()->hold()->count(),
            "closedSavings"  => SavingAccount::clientRegistrationID($id)->approve()->closed()->count(),

            // Loan Accounts
            "activeLoans"    => LoanAccount::clientRegistrationID($id)->approve()->active()->count(),
            "pendingLoans"   => LoanAccount::clientRegistrationID($id)->pending()->count(),
            "holdLoans"      => LoanAccount::clientRegistrationID($id)->approve()->hold()->count(),
            "closedLoans"    => LoanAccount::clientRegistrationID($id)->approve()->closed()->count(),
        ];

        return create_response(null, $data);
    }

    /**
     * Get all Occupations
     */
    public function get_client_occupations()
    {
        $occupations = ClientRegistration::distinct('occupation')
            ->orderBy('occupation', 'asc')
            ->pluck('occupation');

        return create_response(null, $occupations);
    }

    /**
     * Pending Forms
     */
    public function pending_forms()
    {
        $pendingForms = ClientRegistration::fetchPendingForms()->get();
        return create_response(null, $pendingForms);
    }

    /**
     * Pending Forms
     */
    public function clientAccounts($field_id = null, $center_id = null)
    {
        $clientAccounts = ClientRegistration::fetchAccounts($field_id, $center_id)
            ->get(['id', 'acc_no', 'name', 'image_uri']);

        return create_response(null, $clientAccounts);
    }

    /**
     * Pending Forms
     */
    public function clientInfo()
    {
        $clientInfo = ClientRegistration::info()->get();
        return create_response(null, $clientInfo);
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        ClientRegistration::find($id)->update(
            [
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now('Asia/Dhaka')
            ]
        );
        return create_response(__('customValidations.client.registration.approved'));
    }

    /**
     * Set Saving Acc Field Map
     * 
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * @return array
     */
    private static function set_field_map($data, $field_id = null, $center_id = null, $acc_no = null, $image = null, $image_uri = null, $signature = null, $signature_uri = null, $is_approved = null, $creator_id = null)
    {
        $map = [
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
            'secondary_phone'   => $data->secondary_phone,
            'annual_income'     => $data->annual_income,
            'bank_acc_no'       => $data->bank_acc_no,
            'bank_check_no'     => $data->bank_check_no,
            'share'             => $data->share,
            'present_address'   => $data->present_address,
            'permanent_address' => $data->permanent_address,
        ];

        if (isset($field_id)) {
            $map['field_id'] = $field_id;
        }
        if (isset($center_id)) {
            $map['center_id'] = $center_id;
        }
        if (isset($acc_no)) {
            $map['acc_no'] = $acc_no;
        }
        if (isset($image, $image_uri)) {
            $map += ['image' => $image, 'image_uri' => $image_uri];
        }
        if (isset($signature, $signature_uri)) {
            $map += ['signature' => $signature, 'signature_uri' => $signature_uri];
        }
        if (isset($is_approved)) {
            $map += ['is_approved' => $is_approved, 'approved_by' => auth()->id()];
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
                    $clientValue = $client->{$field}->{$subField} ?? '';
                    $dataValue = $data->{$field}->{$subField} ?? '';
                    !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field][$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
                }
            } else {
                $clientValue = $client->{$field} ?? '';
                $dataValue = $data->{$field} ?? '';
                !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
            }
        }

        return $histData;
    }

    /**
     * Update Files
     * 
     * @param object $model
     * @param object $newImg
     * @param string $histKey
     * @param string $fieldName
     * @param string $uriFieldName
     * @param string $directory
     * 
     * @return void
     */
    private static function update_file($model, $newImg, $histKey, $fieldName, $uriFieldName, $directory, &$histData)
    {
        if (!empty($newImg) && !empty($model->{$fieldName})) {
            Helper::unlinkImage(public_path("storage/{$directory}/{$model->{$fieldName}}"));
        }

        if (!empty($newImg)) {
            $file = $fieldName === 'image'
                ? Helper::storeImage($newImg, $fieldName, $directory)
                : Helper::storeSignature($newImg, $fieldName, $directory);
            $histData[$histKey] = "<p class='text-danger'>********</p><p class='text-success'>********</p>";

            $model->update([
                $fieldName     => $file->name,
                $uriFieldName  => $file->uri,
            ]);
        }
    }
}
