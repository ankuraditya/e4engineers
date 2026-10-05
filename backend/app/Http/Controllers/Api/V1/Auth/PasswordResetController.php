<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\EmailSetting;
use App\Models\User;
use App\Services\Notifications\DynamicMailConfigurator;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class PasswordResetController extends Controller
{
    use ApiResponse;

    public function forgot(ForgotPasswordRequest $request, DynamicMailConfigurator $mail): JsonResponse
    {
        $settings = EmailSetting::current();
        if ($settings->is_enabled && $settings->host && $settings->from_email) {
            $mail->apply($settings);
            config(['mail.default' => 'dynamic']);
        }
        Password::sendResetLink($request->only('email'));

        return $this->successResponse(null, 'If an account exists for this email, password reset instructions have been sent.');
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'password_setup_required' => false,
                ])->save();

                DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->errorResponse('The password reset token is invalid or has expired.', [
                'email' => [__($status)],
            ], 422);
        }

        return $this->successResponse(null, 'Password reset successfully.');
    }
}
