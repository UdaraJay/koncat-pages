<?php

use App\Http\Controllers\Settings\ConnectedApplicationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\UsageController;
use App\Http\Controllers\Settings\UserApiTokenController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Team;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware(RequirePassword::class)
        ->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->middleware(RequirePassword::class)
        ->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::get('settings/usage', [UsageController::class, 'index'])->name('usage.index');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/api-tokens', [UserApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('settings/api-tokens', [UserApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('settings/api-tokens/{token}', [UserApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('settings/connected-applications', [ConnectedApplicationController::class, 'index'])->name('connected-applications.index');
    Route::delete('settings/connected-applications/{application}', [ConnectedApplicationController::class, 'destroy'])->name('connected-applications.destroy');

    Route::redirect('settings/teams', '/teams');

    Route::get('settings/teams/{team}', fn (Team $team) => to_route('team-settings.general', ['current_team' => $team]))
        ->middleware(EnsureTeamMembership::class);
});
