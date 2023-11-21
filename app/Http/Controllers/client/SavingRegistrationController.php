<?php

namespace App\Http\Controllers\client;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\client\SavingRegistration;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\client\SavingRegistrationStoreRequest;

class SavingRegistrationController extends Controller
{

    /**
     * Action History Common Function
     */
    private static function setActionHistory($id, $action, $histData)
    {
        return [
            "saving_reg_id"     => $id,
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
        $saving_registrations = SavingRegistration::with('Author:id,name')
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
                'data'      => $saving_registrations
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavingRegistrationStoreRequest $request)
    {
        $errors         = [];
        $data           = (object) $request->validated();
        $nominees       = json_decode($data->nominees);
        $errors         = self::nominee_validation($nominees);
        $is_approved    = AppConfig::where('meta_key', 'saving_registration_approval')
            ->value('meta_value');

        if (!empty($errors)) {
            return response(
                [
                    'success'   => false,
                    "errors"    => $errors
                ],
                401
            );
        }

        SavingRegistration::create(
            [
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
                'total_deposit_without_interest'    => $data->total_deposit_without_interest,
                'total_deposit_with_interest'       => $data->total_deposit_with_interest,
                'is_approved'                       => $is_approved,
                'creator_id'                        => $data->creator_id ?? auth()->id(),
            ]
        );

        return create_response(__('customValidations.client.saving.successful'));
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
     * Nominees Validation
     */
    private static function nominee_validation($nominees)
    {
        $errors = [];
        foreach ($nominees as $key => $nominee) {
            $validated = Validator::make(
                $nominee,
                [
                    'name'          => 'required',
                    'father_name'   => "required_if:husband_name,''",
                    'husband_name'  => "required_if:father_name,''",
                    'mother_name'   => "required",
                    'nid'           => "required|numeric",
                    'dob'           => "required|date",
                    'image'         => "required|mimes:jpeg,png,jpg,webp|max:5120",
                    'address'       => "required|json"
                ]
            );

            if ($validated->fails()) {
                $errors['nominees'[$key]] = $validated->errors()->toArray();
            } else {
                $result = self::address_validation($nominee->address);
                if ($result) {
                    $errors['nominees'[$key]] = $result;
                }
            }
        }
        return $errors;
    }

    /**
     * Address Validation
     */
    private static function address_validation($address)
    {
        $validated = Validator::make(
            $address,
            [
                'street_address'    => 'required',
                'city'              => "required",
                'post_office'       => "required",
                'police_station'    => "required",
                'state'             => "required",
                'division'          => "required",
            ]
        );

        if ($validated->fails()) {
            return $validated->errors()->toArray();
        }
    }
}
