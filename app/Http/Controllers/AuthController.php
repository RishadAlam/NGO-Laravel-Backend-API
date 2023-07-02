<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Hamcrest\Type\IsNumeric;
use Illuminate\Http\Request;
use App\Mail\EmailVerifyMail;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\OTPVerificationRequest;

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
     * Create Response
     *
     * @param $message Message Body, $code Error Code, $success Message Status
     * @return Illuminate\Http\Response
     */
    public function create_response($message, $code = '200', $success = true)
    {
        return response(
            [
                'success' => $success,
                'message' => $message,
            ],
            $code
        );
    }

    /**
     * Send Otp Mail
     * 
     * @param $email
     * @return Boolean
     */
    public function sendMail($email, $name, $otp)
    {
        Mail::to($email)
            ->send(
                new EmailVerifyMail(
                    $name,
                    $email,
                    $otp
                )
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

    /**
     * User Logout
     * Revoke the token that was used to authenticate the current request...
     *
     * @return Illuminate\Http\Response
     */
    public function logout()
    {
        auth::user()->currentAccessToken()->delete();
        return $this->create_response('Logout Successful');
    }


    /**
     * Change Password
     *
     * @param App\Http\Requests\ChangePasswordRequest $request
     * @return Illuminate\Http\Response
     */
    public function change_password(ChangePasswordRequest $request)
    {
        $data = (object) $request->validated();
        if (!Hash::check($data->current_password, Auth::user()->password)) {
            return $this->create_validation_error_response(
                'current_password',
                'Current password did not match.'
            );
        }

        User::find(Auth::user()->id)
            ->update(
                [
                    'password' => bcrypt($data->new_password),
                ]
            );

        return $this->create_response('Password change Successfully');
    }

    /**
     * Forget Password
     *
     * @param App\Http\Requests\ForgetPasswordRequest $request
     * @return Illuminate\Http\Response
     */
    public function forget_password(ForgetPasswordRequest $request)
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
                'message',
                'Account not found!',
                404
            );
        } elseif ($user && !$user->status) {
            return $this->create_validation_error_response(
                'message',
                'Your Account is temporary deactivate!',
                202
            );
        }

        $otp = rand(111111, 999999);
        User::find($user->id)->update(['otp' => bcrypt($otp)]);
        is_numeric($data->emailPhone) ? "" : $this->sendMail($user->email, $user->name, $otp);

        return response(
            [
                'success'       => true,
                'message'       => "OTP Send Successful",
                'email'          => $user->email
            ]
        );
    }

    /**
     * Verified Email and OTP
     *
     * @param App\Http\Requests\OTPVerificationRequest $request
     * @return Illuminate\Http\Response
     */
    public function otp_verification(OTPVerificationRequest $request)
    {
        $data = (object) $request->validated();
        $user = User::where('email', $data->email)->first();

        if (!$user || !HASH::check($data->otp, $user->otp)) {
            return $this->create_validation_error_response(
                false,
                'message',
                'OTP isInvalid!',
                '202'
            );
        }

        User::find($user->id)
            ->update(
                [
                    'email_verified_at' => Carbon::now(),
                    'otp'               => NULL
                ]
            );

        return response(
            [
                'success'       => true,
                'message'       => "Email Verification Successful",
                'email'          => $user->email
            ]
        );
    }

    /**
     * reset Password
     *
     * @param App\Http\Requests\ResetPasswordRequest $request
     * @return Illuminate\Http\Response
     */
    public function reset_password(ResetPasswordRequest $request)
    {
        $data = (object) $request->validated();
        User::where('email', $data->email)->first()
            ->update(
                [
                    'password' => bcrypt($request->new_password),
                ]
            );

        return $this->create_response('Password reset Successfully');
    }
}
