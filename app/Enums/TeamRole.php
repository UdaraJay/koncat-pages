<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Creator = 'creator';
    case ReadOnly = 'read_only';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ReadOnly => __('Read-only'),
            default => __(str($this->value)->replace('_', ' ')->title()->toString()),
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::ViewTeam,
                TeamPermission::UpdateTeam,
                TeamPermission::AddMember,
                TeamPermission::UpdateMember,
                TeamPermission::RemoveMember,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::ViewWorkspace,
                TeamPermission::CreateWorkspace,
                TeamPermission::ManageWorkspace,
                TeamPermission::ViewProject,
                TeamPermission::CreateProject,
            ],
            self::Creator => [
                TeamPermission::ViewTeam,
                TeamPermission::ViewWorkspace,
                TeamPermission::ViewProject,
                TeamPermission::CreateProject,
                TeamPermission::UpdateOwnProject,
                TeamPermission::DeleteOwnProject,
                TeamPermission::DeployOwnProject,
                TeamPermission::ShareOwnProject,
            ],
            self::ReadOnly => [
                TeamPermission::ViewTeam,
                TeamPermission::ViewWorkspace,
                TeamPermission::ViewProject,
            ],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
