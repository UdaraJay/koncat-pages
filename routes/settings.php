<?php

use App\Http\Controllers\Settings\ConnectedApplicationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\UsageController;
use App\Http\Controllers\Settings\UserApiTokenController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use App\Http\Middleware\EnsureTeamMembership;
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

    Route::get('settings/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::get('settings/teams/{team}', [TeamController::class, 'edit'])->name('teams.edit');
        Route::patch('settings/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

        Route::patch('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('teams.members.update');
        Route::delete('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');

        Route::post('settings/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
        Route::delete('settings/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy');
    });
});
