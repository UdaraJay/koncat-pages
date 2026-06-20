<?php

use App\Http\Controllers\Api\DeployApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hosted\HostedProjectController;
use App\Http\Controllers\Hosted\HostedProjectRenderController;
use App\Http\Controllers\Hosted\MatterpipeDocumentController;
use App\Http\Controllers\Hosted\MatterpipeFileController;
use App\Http\Controllers\Hosted\MatterpipeIdentityController;
use App\Http\Controllers\Hosted\MatterpipeSdkController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\Projects\DeploymentController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectShareController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Workspaces\WorkspaceController;
use App\Http\Controllers\Workspaces\WorkspaceMemberController;
use App\Http\Middleware\AllowHostedProjectFrames;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::get('terms', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::redirect('tos', '/terms')->name('legal.tos');
Route::get('privacy', [LegalPageController::class, 'privacy'])->name('legal.privacy');

Route::domain('{team}.'.config('matterpipe.render_domain'))
    ->group(function () {
        Route::get('/{project}/__matterpipe/sdk.js', MatterpipeSdkController::class)->name('matterpipe.sdk');
        Route::get('/{project}/{path?}', HostedProjectRenderController::class)->where('path', '.*')->name('hosted.project.render');
    });

Route::domain('{team}.'.config('matterpipe.hosting_domain'))
    ->middleware(['auth', 'verified', AllowHostedProjectFrames::class])
    ->group(function () {
        Route::get('/{project}/__matterpipe/identity', MatterpipeIdentityController::class)->name('matterpipe.identity');
        Route::get('/{project}/__matterpipe/db/{collection}', [MatterpipeDocumentController::class, 'index'])->name('matterpipe.db.index');
        Route::post('/{project}/__matterpipe/db/{collection}', [MatterpipeDocumentController::class, 'store'])->name('matterpipe.db.store');
        Route::get('/{project}/__matterpipe/db/{collection}/{document}', [MatterpipeDocumentController::class, 'show'])->name('matterpipe.db.show');
        Route::patch('/{project}/__matterpipe/db/{collection}/{document}', [MatterpipeDocumentController::class, 'update'])->name('matterpipe.db.update');
        Route::delete('/{project}/__matterpipe/db/{collection}/{document}', [MatterpipeDocumentController::class, 'destroy'])->name('matterpipe.db.destroy');
        Route::post('/{project}/__matterpipe/files', [MatterpipeFileController::class, 'store'])->name('matterpipe.files.store');
        Route::get('/{project}/__matterpipe/files/{file}', [MatterpipeFileController::class, 'show'])->name('matterpipe.files.show');
        Route::delete('/{project}/__matterpipe/files/{file}', [MatterpipeFileController::class, 'destroy'])->name('matterpipe.files.destroy');
        Route::get('/{project}/{path?}', HostedProjectController::class)->where('path', '.*')->name('hosted.project');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('home', DashboardController::class)->name('dashboard');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->withTrashed()->name('projects.show');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::patch('projects/{project}', [ProjectController::class, 'updateDetails'])->name('projects.update');
    Route::post('projects/{project}/deployments', [DeploymentController::class, 'storeGlobal'])->name('projects.deployments.store');
    Route::post('projects/{project}/deployments/{deployment}/activate', [DeploymentController::class, 'activate'])->name('projects.deployments.activate');
    Route::post('projects/{project}/shares', [ProjectShareController::class, 'store'])->name('projects.shares.store');
    Route::patch('projects/{project}/shares/{share}', [ProjectShareController::class, 'update'])->name('projects.shares.update');
    Route::delete('projects/{project}/shares/{share}', [ProjectShareController::class, 'destroy'])->name('projects.shares.destroy');
    Route::patch('projects/{project}/move', [ProjectController::class, 'move'])->name('projects.move');
    Route::post('projects/{project}/unpublish', [ProjectController::class, 'unpublish'])->name('projects.unpublish');
    Route::delete('projects/{project}', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->withTrashed()->name('projects.restore');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->scopeBindings()
    ->group(function () {
        Route::redirect('dashboard', '/home');

        Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
        Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::patch('workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
        Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');

        Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])->name('workspaces.members.store');
        Route::patch('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])->name('workspaces.members.update');
        Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])->name('workspaces.members.destroy');

        Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'storeInWorkspace'])->name('workspaces.projects.store');
        Route::patch('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'update'])->name('workspaces.projects.update');
        Route::delete('workspaces/{workspace}/projects/{project}', [ProjectController::class, 'destroy'])->name('workspaces.projects.destroy');
        Route::post('workspaces/{workspace}/projects/{project}/deployments', [DeploymentController::class, 'store'])->name('workspaces.projects.deployments.store');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::prefix('api')->name('api.')->group(function () {
    Route::post('projects/{project}/deployments', [DeployApiController::class, 'store'])->name('projects.deployments.store');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
