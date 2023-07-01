<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Create validation error Response
     *
     * @param $key Massage key, $message Message Body, $code Error Code, $success Message Status
     * @return Illuminate\Http\Response
     */
    public function create_validation_error_response($key, $message, $code = '401', $success = false)
    {
        return response(
            [
                'success'   => $success,
                "errors"    => [
                    $key    => $message,
                ],
            ],
            $code
        );
    }

    /**
     * Login User
     *
     * @param App\Http\Requests\LoginRequest $request
     * @return Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        $data = (object) $request->validated();
        $user = User::where(function ($query) use ($data) {
            if (is_numeric($data->emailPhone)) {
                $query->where('phone', $data->emailPhone);
            } else {
                $query->where('email', $data->emailPhone);
            }
        })->first();

        if (!$user) {
            return $this->create_validation_error_response(
                'emailPhone',
                'The email or phone is incorrect.'
            );
        } elseif (
            $user
            &&
            !Hash::check(
                $data->password,
                $user->password
            )
        ) {
            return $this->create_validation_error_response(
                'password',
                'The password is incorrect.'
            );
        } elseif ($user && !$user->email_verified_at) {
            return $this->create_validation_error_response(
                'message',
                'You need to verified your Phone Number!',
                '202'
            );
        } elseif ($user && !$user->status) {
            return $this->create_validation_error_response(
                false,
                'message',
                'Your Account is temporary deactivate!',
                '202'
            );
        } elseif (!Auth::attempt(
            [
                'email'     => $user->email,
                'password'  => $data->password,
                'status'    => true
            ]
        )) {
            return $this->create_validation_error_response(
                'message',
                'Something went wrong!',
                '500'
            );
        }

        $user   = auth::user();
        $token  = $user->createToken('auth_token')->plainTextToken;
        return response(
            [
                'success'           => true,
                'message'           => "login Successful",
                'access_token'      => $token,
                'token_type'        => "Bearer",
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'phone'             => $user->phone,
                'status'            => $user->status,
            ],
            200
        );
    }
}
