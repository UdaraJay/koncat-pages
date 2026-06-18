<?php

namespace App\Services;

use App\Enums\ProjectSharePermission;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;
use App\Notifications\Projects\ProjectShared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectShareService
{
    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function share(Project $project, User $sharer, string $email, ProjectSharePermission $permission): ProjectShare
    {
        $email = $this->normalizeEmail($email);
        $recipient = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($recipient?->is($sharer) || ($recipient && $project->hasInheritedAccess($recipient))) {
            throw ValidationException::withMessages([
                'email' => __('This user already has access to this project.'),
            ]);
        }

        $share = DB::transaction(function () use ($project, $sharer, $email, $recipient, $permission) {
            $share = ProjectShare::query()->firstOrNew([
                'project_id' => $project->id,
                'email' => $email,
            ]);

            $share->fill([
                'user_id' => $recipient?->id,
                'permission' => $permission,
                'shared_by' => $sharer->id,
            ]);

            $share->save();

            return $share->fresh(['project.hostingTeam', 'sharer', 'user']) ?? $share;
        });

        Notification::route('mail', $share->email)
            ->notify(new ProjectShared($share));

        return $share;
    }

    public function claimPendingForUser(User $user): void
    {
        $email = $this->normalizeEmail($user->email);

        ProjectShare::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->update(['user_id' => $user->id]);

        ProjectShare::query()
            ->where('user_id', $user->id)
            ->where('email', '!=', $email)
            ->update(['email' => $email]);
    }

    public function deleteInheritedSharesForUser(User $user): void
    {
        ProjectShare::query()
            ->with(['project.owner', 'project.workspace.team'])
            ->where('user_id', $user->id)
            ->get()
            ->each(function (ProjectShare $share) use ($user) {
                if ($share->project->hasInheritedAccess($user)) {
                    $share->delete();
                }
            });
    }
}
