<?php

use App\Http\Controllers\Auth\ConfirmAccessController;
use App\Http\Controllers\Auth\EmailChangeController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\PasswordlessLoginController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\RoutePath;

Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
    $guard = config('fortify.guard');
    $authMiddleware = config('fortify.auth_middleware', 'auth').':'.$guard;
    $twoFactorLimiter = config('fortify.limiters.two-factor');
    $verificationLimiter = config('fortify.limiters.verification', '6,1');
    $magicLimiter = config('fortify.limiters.magic-login', 'magic-login');
    $magicVerifyLimiter = config('fortify.limiters.magic-login-verify', 'magic-login-verify');

    Route::get(RoutePath::for('login', '/login'), [PasswordlessLoginController::class, 'show'])
        ->middleware(['guest:'.$guard])
        ->name('login');

    Route::post('/login/magic', [PasswordlessLoginController::class, 'request'])
        ->middleware(array_filter(['guest:'.$guard, $magicLimiter ? 'throttle:'.$magicLimiter : null]))
        ->name('login.magic.request');

    Route::get('/login/check-email/{challenge}', [PasswordlessLoginController::class, 'checkEmail'])
        ->middleware(['guest:'.$guard])
        ->name('login.magic.check');

    Route::get('/login/magic/{challenge}/{token}', [MagicLinkController::class, 'show'])
        ->name('login.magic.link');

    Route::post('/login/magic/{challenge}', [MagicLinkController::class, 'consume'])
        ->middleware(array_filter([$magicVerifyLimiter ? 'throttle:'.$magicVerifyLimiter : null]))
        ->name('login.magic.consume');

    Route::post('/login/code', [PasswordlessLoginController::class, 'verifyCode'])
        ->middleware(array_filter(['guest:'.$guard, $magicVerifyLimiter ? 'throttle:'.$magicVerifyLimiter : null]))
        ->name('login.code.verify');

    Route::get('/login/complete', [PasswordlessLoginController::class, 'complete'])
        ->middleware(['guest:'.$guard])
        ->name('login.complete');

    Route::post('/login/complete', [PasswordlessLoginController::class, 'storeCompleted'])
        ->middleware(['guest:'.$guard])
        ->name('login.complete.store');

    Route::post(RoutePath::for('logout', '/logout'), [AuthenticatedSessionController::class, 'destroy'])
        ->middleware([$authMiddleware])
        ->name('logout');

    if (Features::enabled(Features::emailVerification())) {
        Route::get(RoutePath::for('verification.notice', '/email/verify'), [EmailVerificationPromptController::class, '__invoke'])
            ->middleware([$authMiddleware])
            ->name('verification.notice');

        Route::get(RoutePath::for('verification.verify', '/email/verify/{id}/{hash}'), [VerifyEmailController::class, '__invoke'])
            ->middleware([$authMiddleware, 'signed', 'throttle:'.$verificationLimiter])
            ->name('verification.verify');

        Route::post(RoutePath::for('verification.send', '/email/verification-notification'), [EmailVerificationNotificationController::class, 'store'])
            ->middleware([$authMiddleware, 'throttle:'.$verificationLimiter])
            ->name('verification.send');
    }

    Route::get(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmAccessController::class, 'show'])
        ->middleware([$authMiddleware])
        ->name('password.confirm');

    Route::post('/user/confirm-access/request', [ConfirmAccessController::class, 'request'])
        ->middleware([$authMiddleware, 'throttle:'.$magicLimiter])
        ->name('password.confirm.request');

    Route::post(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmAccessController::class, 'verify'])
        ->middleware([$authMiddleware, 'throttle:'.$magicVerifyLimiter])
        ->name('password.confirm.store');

    Route::get(RoutePath::for('password.confirmation', '/user/confirmed-password-status'), [ConfirmedPasswordStatusController::class, 'show'])
        ->middleware([$authMiddleware])
        ->name('password.confirmation');

    Route::post('/settings/profile/email/verify', [EmailChangeController::class, 'verify'])
        ->middleware([$authMiddleware, 'password.confirm', 'throttle:'.$magicVerifyLimiter])
        ->name('profile.email.verify');

    if (Features::enabled(Features::twoFactorAuthentication())) {
        Route::get(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'create'])
            ->middleware(['guest:'.$guard])
            ->name('two-factor.login');

        Route::post(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:'.$guard,
                $twoFactorLimiter ? 'throttle:'.$twoFactorLimiter : null,
            ]))
            ->name('two-factor.login.store');

        $twoFactorMiddleware = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [$authMiddleware, 'password.confirm']
            : [$authMiddleware];

        Route::post(RoutePath::for('two-factor.enable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.enable');

        Route::post(RoutePath::for('two-factor.confirm', '/user/confirmed-two-factor-authentication'), [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.confirm');

        Route::delete(RoutePath::for('two-factor.disable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.disable');

        Route::get(RoutePath::for('two-factor.qr-code', '/user/two-factor-qr-code'), [TwoFactorQrCodeController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.qr-code');

        Route::get(RoutePath::for('two-factor.secret-key', '/user/two-factor-secret-key'), [TwoFactorSecretKeyController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.secret-key');

        Route::get(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'index'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.recovery-codes');

        Route::post(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.regenerate-recovery-codes');
    }
});
