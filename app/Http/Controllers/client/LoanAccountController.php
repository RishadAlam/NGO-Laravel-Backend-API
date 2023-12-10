<?php

namespace App\Http\Controllers\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\Guarantor;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\client\LoanAccountActionHistory;
use App\Http\Requests\client\LoanAccountStoreRequest;
use App\Http\Requests\client\LoanAccountUpdateRequest;

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
        $query = LoanAccount::with([
            'Author:id,name',
            'ClientRegistration:id,acc_no,name,image_uri',
            'Field:id,name',
            'Center:id,name',
            'Category:id,name,is_default',
            'Guarantors:id,loan_account_id,name,father_name,husband_name,mother_name,nid,dob,occupation,relation,gender,primary_phone,secondary_phone,image,image_uri,signature,signature_uri,address',
        ])
            ->when(request('fetch_pending_forms'), function ($query) {
                $query->where('is_approved', false)
                    ->when(!Auth::user()->can('pending_loan_acc_list_view_as_admin'), function ($query) {
                        $query->where('creator_id', Auth::id());
                    });
            })
            ->when(request('field_id'), function ($query) {
                $query->where('field_id', request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->where('center_id', request('center_id'));
            })
            ->when(request('category_id'), function ($query) {
                $query->where('category_id', request('category_id'));
            })
            ->when(request('user_id'), function ($query) {
                $query->where('creator_id', request('user_id'));
            })
            ->orderBy('id', 'DESC');

        $loan_registrations = $query->get();
        return response([
            'success' => true,
            'data' => $loan_registrations,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoanAccountStoreRequest $request)
    {
        $data               = (object) $request->validated();
        $guarantors         = $data->guarantors;
        $is_approved        = AppConfig::get_config('loan_account_registration_approval');
        $is_loan_approved   = AppConfig::get_config('loan_approval');

        DB::transaction(function () use ($data, $is_approved, $is_loan_approved, $guarantors) {
            $loan_account = LoanAccount::create(
                self::set_loan_field_map(
                    $data,
                    $data->field_id,
                    $data->center_id,
                    $data->category_id,
                    $data->acc_no,
                    $data->client_registration_id,
                    $is_approved,
                    $is_loan_approved,
                    $data->creator_id
                )
            );

            foreach ($guarantors as $guarantor) {
                $guarantor  = (object) $guarantor;
                $img        = Helper::storeImage($guarantor->image, "guarantor", "guarantors");
                $signature  = isset($guarantor->signature)
                    ? Helper::storeSignature($guarantor->signature, "guarantor_signature", "guarantors")
                    : (object) ["name" => null, "uri" => null];

                Guarantor::create(Helper::set_nomi_field_map(
                    $guarantor,
                    'loan_account_id',
                    $loan_account->id,
                    false,
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
     * Update the specified resource in storage.
     */
    public function update(LoanAccountUpdateRequest $request, string $id)
    {
        $data           = (object) $request->validated();
        $guarantors     = $data->guarantors;
        $loan_account   = LoanAccount::find($id);
        $histData       = self::set_update_hist($data, $loan_account);

        DB::transaction(
            function () use ($id, $loan_account, $data, $guarantors, $histData) {
                $loan_account->update(self::set_loan_field_map($data));

                foreach ($guarantors as $index => $guarantorData) {
                    $guarantorData                    = (object) $guarantorData;
                    $guarantor                        = Guarantor::find($guarantorData->id);
                    $histData['guarantors'][$index]   = [];

                    Helper::set_update_nomiguarantor_hist($histData['guarantors'][$index], $guarantorData, $guarantor);
                    self::update_file($guarantor, $guarantorData->image ?? '', 'guarantor_image', 'image', 'image_uri', 'guarantors', $histData['guarantors'][$index]);
                    self::update_file($guarantor, $guarantorData->signature ?? '', 'guarantor_signature', 'signature', 'signature_uri', 'guarantors', $histData['guarantors'][$index]);

                    $guarantor->update(Helper::set_nomi_field_map($guarantorData));
                }
                LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'update', $histData));
            }
        );

        return create_response(__('customValidations.client.loan.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            LoanAccount::find($id)->delete();
            LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $id, 'delete', []));
        });

        return create_response(__('customValidations.client.loan.delete'));
    }

    /**
     * Permanently Remove the specified resource from storage.
     */
    public function permanently_destroy(string $id)
    {
        LoanAccount::find($id)->forceDelete();
        return create_response(__('customValidations.client.loan.p_delete'));
    }

    /**
     * Approved the specified Resource
     */
    public function approved(string $id)
    {
        LoanAccount::find($id)->update(['is_approved' => true]);
        return create_response(__('customValidations.client.loan.approved'));
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
    private static function set_loan_field_map($data, $field_id = null, $center_id = null, $category_id = null, $acc_no = null, $client_registration_id = null,  $is_approved = null, $is_loan_approved = null, $creator_id = null)
    {
        $map = [
            'start_date'                        => $data->start_date,
            'duration_date'                     => $data->duration_date,
            'loan_given'                        => $data->loan_given,
            'payable_installment'               => $data->payable_installment,
            'payable_deposit'                   => $data->payable_deposit,
            'payable_interest'                  => $data->payable_interest,
            'total_payable_interest'            => $data->total_payable_interest,
            'total_payable_loan_with_interest'  => $data->total_payable_loan_with_interest,
            'loan_installment'                  => $data->loan_installment,
            'interest_installment'              => $data->interest_installment,
        ];

        if (isset($field_id)) {
            $map['field_id'] = $field_id;
        }
        if (isset($center_id)) {
            $map['center_id'] = $center_id;
        }
        if (isset($category_id)) {
            $map['category_id'] = $category_id;
        }
        if (isset($acc_no, $client_registration_id)) {
            $map += ['acc_no' => $acc_no, 'client_registration_id' => $client_registration_id];
        }
        if (isset($is_approved)) {
            $map['is_approved'] = $is_approved;
        }
        if (isset($is_loan_approved)) {
            $map['is_loan_approved'] = $is_loan_approved;
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
     * @param object $account
     * 
     * @return array
     */
    private static function set_update_hist($data, $account)
    {
        $histData           = [];
        $fieldsToCompare    = [
            'start_date', 'duration_date', 'loan_given',
            'payable_deposit',
            'payable_installment',
            'payable_interest',
            'total_payable_interest',
            'total_payable_loan_with_interest',
            'loan_installment',
            'interest_installment',
        ];

        foreach ($fieldsToCompare as $field) {
            $clientValue    = $account->{$field} ?? '';
            $dataValue      = $data->{$field} ?? '';
            !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
        }

        return $histData;
    }

    // /**
    //  * Set Saving Acc update Nominee hist
    //  * 
    //  * @param array $histData
    //  * @param object $nomineeData
    //  * @param object $nominee
    //  * 
    //  * @return array
    //  */
    // private static function set_update_guarantors_hist(&$histData, $nomineeData, $nominee)
    // {
    //     $nomineeData->address   = (object) $nomineeData->address;
    //     $nominee->address       = (object) $nominee->address;
    //     $fieldsToCompare        = ['name', 'husband_name', 'father_name', 'mother_name', 'nid', 'dob', 'occupation', 'relation', 'gender', 'primary_phone', 'secondary_phone', 'address'];
    //     $addressFields          = ['street_address', 'city', 'word_no', 'post_office', 'police_station', 'district', 'division'];

    //     foreach ($fieldsToCompare as $field) {
    //         if ($field === 'address') {
    //             foreach ($addressFields as $subField) {
    //                 $clientValue    = $nominee->{$field}->{$subField} ?? '';
    //                 $dataValue      = $nomineeData->{$field}->{$subField} ?? '';
    //                 !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$subField] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
    //             }
    //         } else {
    //             $clientValue    = $nominee->{$field} ?? '';
    //             $dataValue      = $nomineeData->{$field} ?? '';
    //             !Helper::areValuesEqual($clientValue, $dataValue) ? $histData[$field] = "<p class='text-danger'>{$clientValue}</p><p class='text-success'>{$dataValue}</p>" : '';
    //         }
    //     }
    // }

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
                ? Helper::storeImage($newImg, $histKey, $directory)
                : Helper::storeSignature($newImg, $histKey, $directory);
            $histData[$histKey] = "<p class='text-danger'>********</p><p class='text-success'>********</p>";

            $model->update([
                $fieldName     => $file->name,
                $uriFieldName  => $file->uri,
            ]);
        }
    }
}
