<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UsersVerify;
use App\Mail\EmailVerifyMail;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\OtpResendRequest;
use App\Mail\RegistrationGreetingsMail;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\OTPVerificationRequest;
use Illuminate\Support\Facades\URL;

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
     * @return boolean
     */
    public static function sendOTP($email, $name, $otp, $expired)
    {
        Mail::to($email)
            ->send(
                new EmailVerifyMail(
                    $name,
                    $email,
                    $otp,
                    $expired
                )
            );
    }

    /**
     * Send Login Credentrials
     *
     * @param $email
     * @return boolean
     */
    public static function sendCredentrials($name, $email, $password)
    {
        Mail::to($email)
            ->send(
                new RegistrationGreetingsMail(
                    $name,
                    $email,
                    $password
                )
            );
    }

    /**
     * Create OTP
     *
     * @param $userId
     * @return array
     */
    public static function createOTP($userId)
    {
        $otp        = rand(111111, 999999);
        $expired    = Carbon::now()->addMinutes(5);
        UsersVerify::create(
            [
                'user_id'       => $userId,
                'otp'           => $otp,
                'expired_at'    => $expired
            ]
        );

        return [
            'otp'       => $otp,
            'expired'   => $expired
        ];
    }

    /**
     * Create Authorized Response
     *
     * @param $userData
     * @return array
     */
    public static function createAuthorizedRes($userData, $message)
    {
        return [
            'success'           => true,
            'message'           => $message,
            'id'                => $userData->id,
            'name'              => $userData->name,
            'email'             => $userData->email,
            'email_verified_at' => $userData->email_verified_at,
            'phone'             => $userData->phone,
            'image'             => $userData->image,
            'image_uri'         => $userData->image_uri,
            'status'            => $userData->status,
            'role'              => $userData->getRoleNames(),
            'permissions'       => $userData->getPermissionNames()
        ];
    }

    /**
     * User authorization
     *
     * @return Illuminate\Http\Response
     */
    public function authorization()
    {
        return response(
            self::createAuthorizedRes(auth()->user(), __('customValidations.authorize.successful')),
            200
        );
    }

    /**
     * User Registration
     *
     * @param App\Http\Requests\RegistrationRequest
     * @return Illuminate\Http\Response
     */
    public function registration(RegistrationRequest $request)
    {
        $data = (object) $request->validated();
        User::create(
            [
                'name'      => $data->name,
                'email'     => $data->email,
                'password'  => bcrypt($data->password),
                'phone'     => $request->phone
            ]
        );

        self::sendCredentrials($data->name, $data->email, $data->password);
        return $this->create_response('Registration Successful');
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
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            return $this->create_validation_error_response(
                'email',
                __('customValidations.login.incorrectEmail')
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
                __('customValidations.login.incorrectPassword')
            );
        } elseif ($user && !$user->email_verified_at) {
            // Create OTP & send it to user email address
            $otpResponse = self::createOTP($user->id);
            self::sendOTP($user->email, $user->name, $otpResponse['otp'], $otpResponse['expired']);

            return response(
                [
                    'success'   => false,
                    "errors"    => [
                        'message'   => __('customValidations.otp.otpSent'),
                    ],
                    "otp_sended"    => true,
                    'user_id'       => $user->id
                ],
                202
            );
        } elseif ($user && !$user->status) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.login.accDeactivate'),
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
                __('customValidations.common.somethingWentWrong'),
                '500'
            );
        }

        $user   = Auth::user();
        $token  = $user->createToken('auth_token')->plainTextToken;
        return response(
            [
                'success'           => true,
                'message'           => __('customValidations.login.successful'),
                'access_token'      => $token,
                'token_type'        => "Bearer",
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'phone'             => $user->phone,
                'image'             => $user->image,
                'image_uri'         => $user->image_uri,
                'status'            => $user->status,
                'role'              => $user->getRoleNames(),
                'permissions'       => $user->getPermissionNames()
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
        return $this->create_response(__('customValidations.logout.successful'));
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
                __('customValidations.passwordChange.notMatch')
            );
        }

        User::find(Auth::user()->id)
            ->update(
                [
                    'password' => bcrypt($data->new_password),
                ]
            );

        return $this->create_response(
            __('customValidations.passwordChange.successful')
        );
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
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.forgotPassword.accountNotFound'),
                404
            );
        } elseif ($user && !$user->status) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.login.accDeactivate'),
                202
            );
        }

        $otpResponse = self::createOTP($user->id);
        self::sendOTP($user->email, $user->name, $otpResponse['otp'], $otpResponse['expired']);
        return response(
            [
                'success'       => true,
                'message'       => __('customValidations.forgotPassword.successful'),
                'id'        => $user->id
            ]
        );
    }

    /**
     * Forget Password
     *
     * @param App\Http\Requests\OtpResendRequest $request
     * @return Illuminate\Http\Response
     */
    public function otp_resend($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.forgotPassword.accountNotFound'),
                404
            );
        } elseif ($user && !$user->status) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.login.accDeactivate'),
                202
            );
        }

        $otpResponse = self::createOTP($user->id);
        self::sendOTP($user->email, $user->name, $otpResponse['otp'], $otpResponse['expired']);
        return response(
            [
                'success'       => true,
                'message'       => __('customValidations.otp.otpResend'),
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
        $userOtp = UsersVerify::where('otp', $data->otp)->latest()->first(['id', 'user_id', 'expired_at']);

        if (!$userOtp) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.otp.otpIsInvalid'),
                '202'
            );
        } elseif ($userOtp->expired_at < Carbon::now()) {
            return $this->create_validation_error_response(
                'message',
                __('customValidations.otp.otpIsExpired'),
                '202'
            );
        }

        User::find($userOtp->user_id)->update(['email_verified_at' => Carbon::now()]);
        UsersVerify::where('user_id', $userOtp->user_id)->delete();

        return response(
            [
                'success'       => true,
                'message'       => __('customValidations.otp.successful')
            ],
            200
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
        $user = User::find($data->user_id);
        $user->update(['password' => bcrypt($request->new_password)]);
        $user->tokens()->delete();

        return $this->create_response(__('customValidations.resetPassword.successful'));
    }

    /**
     * Profile Update
     *
     * @param App\Http\Requests\ProfileUpdateRequest $request
     * @return Illuminate\Http\Response
     */
    public function profile_update(ProfileUpdateRequest $request)
    {
        $data = (object) $request->validated();
        if (!empty($data->image)) {
            if (!empty(auth()->user()->image)) {
                $path = public_path('storage/staff/' . auth()->user()->image . '');
                unlink($path);
            }
            $extension  = $data->image->extension();
            $imgName    = 'staff_' . time() . '.' . $extension;
            $data->image->move(public_path() . '/storage/staff/', $imgName);

            User::find(auth()->user()->id)
                ->update(
                    [
                        'image'     => $imgName,
                        'image_uri' => URL::to('/storage/staff/', $imgName),
                    ]
                );
        }

        User::find(auth()->user()->id)
            ->update(
                [
                    'name'  => $data->name,
                    'phone' => $data->phone
                ]
            );
        $userData = User::findOrFail(auth()->user()->id);

        return response(self::createAuthorizedRes($userData, __('customValidations.staff.profile_update')), 200);
    }
}
