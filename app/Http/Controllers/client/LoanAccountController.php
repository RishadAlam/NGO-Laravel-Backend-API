<?php

namespace App\Http\Controllers\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\Guarantor;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\client\LoanAccountStoreRequest;

class LoanAccountController extends Controller
{
    /**
     * Action History Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "loan_Account_id"   => $id,
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
        $loan_registrations = LoanAccount::with('Author:id,name')
            ->with("Field:id,name")
            ->with("Center:id,name")
            ->when(request('fetch_pending'), function ($query) {
                $query->where('is_approved', false);
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
            ->orderBy('id', 'DESC')
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $loan_registrations
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoanAccountStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $guarantors       = $data->guarantors;
        $is_approved    = AppConfig::where('meta_key', 'loan_account_registration_approval')
            ->value('meta_value');

        DB::transaction(function () use ($data, $is_approved, $guarantors) {
            $loan_account = LoanAccount::create(self::set_loan_field_map($data, $is_approved, $data->creator_id));

            foreach ($guarantors as $guarantor) {
                $guarantor  = (object) $guarantor;
                $img        = Helper::storeImage($guarantor->image, "guarantor", "guarantors");
                $signature  = isset($guarantor->signature)
                    ? Helper::storeSignature($guarantor->signature, "guarantor_signature", "guarantors")
                    : (object) ["name" => null, "uri" => null];

                Guarantor::create(self::set_guarantor_field_map(
                    $loan_account->id,
                    $guarantor,
                    true,
                    $img->name,
                    $img->uri,
                    $signature->name,
                    $signature->uri
                ));
            }
        });

        return create_response(__('customValidations.client.loan.successful'));
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Get all Occupations
     */
    public function get_guarantor_occupations()
    {
        $occupations = Guarantor::distinct('occupation')->orderBy('occupation', 'asc')->pluck('occupation');
        return create_response(null, $occupations);
    }

    /**
     * Get all Relation
     */
    public function get_guarantor_relations()
    {
        $relations = Guarantor::distinct('relation')->orderBy('relation', 'asc')->pluck('relation');
        return create_response(null, $relations);
    }

    /**
     * Set Saving Acc Field Map
     * 
     * @param object $data
     * @param boolean $is_approved
     * @param integer $creator_id
     * @return array
     */
    private static function set_loan_field_map($data, $is_approved = null, $creator_id = null)
    {
        $map = [
            'field_id'                          => $data->field_id,
            'center_id'                         => $data->center_id,
            'category_id'                       => $data->category_id,
            'client_registration_id'            => $data->client_registration_id,
            'acc_no'                            => $data->acc_no,
            'start_date'                        => $data->start_date,
            'duration_date'                     => $data->duration_date,
            'payable_installment'               => $data->payable_installment,
            'payable_deposit'                   => $data->payable_deposit,
            'payable_interest'                  => $data->payable_interest,
            'total_payable_interest'            => $data->total_payable_interest,
            'total_payable_loan_with_interest'  => $data->total_payable_loan_with_interest,
            'loan_installment'                  => $data->loan_installment,
            'interest_installment'              => $data->interest_installment,
        ];

        if (isset($is_approved)) {
            $map['is_approved'] = $is_approved;
        }
        if (isset($creator_id)) {
            $map['creator_id'] = $creator_id ?? auth()->id();
        }

        return $map;
    }

    /**
     * Set Nominee Field Map
     * 
     * @param integer $loan_account_id
     * @param object $data
     * @param boolean $jsonAddress
     * @param string $image
     * @param string $image_uri
     * @param string $signature
     * @param string $signature_uri
     * @return array
     */
    private static function set_guarantor_field_map($loan_account_id, $data, $jsonAddress = false, $image = null, $image_uri = null, $signature = null, $signature_uri = null)
    {
        $map = [
            'loan_account_id'           => $loan_account_id,
            'name'                      => $data->name,
            'father_name'               => $data->father_name,
            'husband_name'              => isset($data->husband_name) ? $data->husband_name : '',
            'mother_name'               => $data->mother_name,
            'nid'                       => $data->nid,
            'dob'                       => $data->dob,
            'occupation'                => $data->occupation,
            'relation'                  => $data->relation,
            'gender'                    => $data->gender,
            'primary_phone'             => $data->primary_phone,
            'secondary_phone'           => isset($data->secondary_phone) ? $data->secondary_phone : '',
            'address'                   => $jsonAddress ? json_encode($data->address) : $data->address,
        ];

        if (isset($image, $image_uri)) {
            $map += ['image' => $image, 'image_uri' => $image_uri];
        }
        if (isset($signature, $signature_uri)) {
            $map += ['signature' => $signature, 'signature_uri' => $signature_uri];
        }

        return $map;
    }
}
