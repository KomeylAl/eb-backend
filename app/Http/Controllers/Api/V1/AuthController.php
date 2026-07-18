<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ChangePasswordWithOtpAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\RequestLoginOtpAction;
use App\Actions\Auth\RequestPasswordChangeOtpAction;
use App\Actions\Auth\VerifyLoginOtpAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordWithOtpRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RequestLoginOtpRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $type = isset($request->validated()['type'])
            ? UserType::from($request->validated()['type'])
            : null;

        $result = $action->execute(
            $request->validated('phone'),
            $request->validated('password'),
            $type,
        );

        return ApiResponse::success([
            'user' => UserResource::make($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 'Logged in successfully.');
    }

    public function requestLoginOtp(RequestLoginOtpRequest $request, RequestLoginOtpAction $action): JsonResponse
    {
        $action->execute(
            $request->validated('phone'),
            UserType::from($request->validated('type')),
        );

        return ApiResponse::success(null, 'کد تأیید ارسال شد.');
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request, VerifyLoginOtpAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->validated('phone'),
            UserType::from($request->validated('type')),
            $request->validated('code'),
        );

        return ApiResponse::success([
            'user' => UserResource::make($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 'Logged in successfully.');
    }

    public function requestPasswordChangeOtp(Request $request, RequestPasswordChangeOtpAction $action): JsonResponse
    {
        $action->execute($request->user());

        return ApiResponse::success(null, 'کد تأیید تغییر رمز ارسال شد.');
    }

    public function changePassword(ChangePasswordWithOtpRequest $request, ChangePasswordWithOtpAction $action): JsonResponse
    {
        $user = $action->execute(
            $request->user(),
            $request->validated('code'),
            $request->validated('password'),
        );

        return ApiResponse::success(
            UserResource::make($user->loadMissing('doctorProfile')),
            'رمز عبور با موفقیت تغییر کرد.',
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('doctorProfile');

        return ApiResponse::success(UserResource::make($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }
}
