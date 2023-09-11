<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\ChangeStatusRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:staff_list_view')->only('index');
        $this->middleware('can:staff_permissions_view')->only('show');
        $this->middleware('can:staff_registration')->only('store');
        $this->middleware('can:staff_data_update')->only('update');
        $this->middleware('can:staff_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles:id,name')
            ->get(['id', 'name', 'email', 'phone', 'image', 'image_uri', 'email_verified_at as verified_at', 'status']);
        $responseData = [];
        foreach ($users as $key => $user) {
            $responseData[$key] = (object) [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'image'         => $user->image,
                'image_uri'     => $user->image_uri,
                'verified_at'   => $user->verified_at,
                'status'        => $user->status,
                'role_id'       => $user->roles[0]->id ?? null,
                'role_name'     => $user->roles[0]->name ?? null,
            ];
        }

        return response(
            [
                'success'   => true,
                'data'      => $responseData
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Change Status the specified user
     */
    public function change_status(ChangeStatusRequest $request, string $id)
    {
        $id = User::find($id)->update(['status' => $request->validated()['status']]);
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.status')
            ],
            200
        );
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
        User::find($id)->delete();
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.delete')
            ],
            200
        );
    }
}
