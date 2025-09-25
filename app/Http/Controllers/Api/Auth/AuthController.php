<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyOTPMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $otp = rand(100000, 999999);
            $otp_expires_at = Carbon::now()->addMinutes(10);
            $email_otp = [
                'userName' => explode('@', $request->email)[0],
                'otp' => $otp,
                'validity' => '10 minute'
            ];

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone_number' => 'nullable|string|max:15',
                'password' => 'required|string|min:8|confirmed',
                'without_otp' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            if ($request->without_otp == 'true') {
                $user = User::create([
                    'role' => 'USER',
                    'full_name' => ucfirst($request->full_name),
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'status' => 'Active'
                ]);

                $tokenExpiry = Carbon::now()->addDays(7);
                $customClaims = ['exp' => $tokenExpiry->timestamp];
                $token = JWTAuth::customClaims($customClaims)->fromUser($user);

                return response()->json([
                    'status' => true,
                    'message' => $user->role . ' Register successfully.',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $tokenExpiry,
                ], 200);
            }

            $user = User::create([
                'role' => 'USER',
                'full_name' => ucfirst($request->full_name),
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'otp' => $otp,
                'otp_expires_at' => $otp_expires_at,
            ]);

            try {
                Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'Register successfully, OTP send you email, please verify your account'
            ], 201);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.' . $e->getMessage(), [], 500);
        }
    }
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $user = User::where('otp', $request->otp)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP'
                ], 401);
            }

            if ($user->otp_expires_at > Carbon::now()) {
                $user->otp = null;
                $user->otp_expires_at = null;
                $user->otp_verified_at = Carbon::now();
                $user->status = 'Active';
                $user->save();

                $tokenExpiry = Carbon::now()->addDays(7);
                $customClaims = ['exp' => $tokenExpiry->timestamp];
                $token = JWTAuth::customClaims($customClaims)->fromUser($user);

                return response()->json([
                    'status' => true,
                    'message' => 'OTP verified successfully',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $tokenExpiry,
                ], 200);
            } else {

                return response()->json([
                    'status' => false,
                    'message' => 'OTP expired time out'
                ], 401);
            }
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.' . $e->getMessage(), [], 500);
        }
    }
    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $otp = rand(100000, 999999);
            $otp_expires_at = Carbon::now()->addMinutes(10);

            $user->otp = $otp;
            $user->otp_expires_at = $otp_expires_at;
            $user->otp_verified_at = null;
            $user->save();

            $data = [
                'userName' => explode('@', $request->email)[0],
                'otp' => $otp,
                'validity' => '10 minute'
            ];

            try {
                Mail::to($user->email)->send(new VerifyOTPMail($data));
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'OTP resend to your email'
            ], 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.' . $e->getMessage(), [], 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $otp = rand(100000, 999999);
            $otp_expires_at = Carbon::now()->addMinutes(10);

            $user->otp = $otp;
            $user->otp_expires_at = $otp_expires_at;
            $user->otp_verified_at = null;
            $user->save();

            $data = [
                'userName' => explode('@', $request->email)[0],
                'otp' => $otp,
                'validity' => '10 minute'
            ];

            try {
                Mail::to($user->email)->send(new VerifyOTPMail($data));
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'OTP resend to your email'
            ], 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.' . $e->getMessage(), [], 500);
        }
    }

     public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::where('id', Auth::id())->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 404);
        }

        if ($user->status == 'Active') {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'Password change successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user'
            ]);
        }
    }
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:8',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid current password!',
            ]);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'remember_me' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status !== 'Active') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        $tokenExpiry = $request->remember_me == '1' ? Carbon::now()->addDays(30) : Carbon::now()->addDays(7);
        $customClaims = ['exp' => $tokenExpiry->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $tokenExpiry,
            // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
            'user' => $user,
        ], 200);
    }
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => true,
                'message' => 'Logged out successful'
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }
    public function getProfile(Request $request)
    {
        try {
            $user = User::find($request->user_id ?? Auth::id());

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found!'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Your profile',
                'data' => $user,
            ], 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.' . $e->getMessage(), [], 500);
        }
    }

}
