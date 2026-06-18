<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return array<WorkspacePermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => WorkspacePermission::cases(),
            self::Admin => [
                WorkspacePermission::UpdateWorkspace,
                WorkspacePermission::AddMember,
                WorkspacePermission::UpdateMember,
                WorkspacePermission::RemoveMember,
                WorkspacePermission::CreateProject,
                WorkspacePermission::UpdateProject,
                WorkspacePermission::DeployProject,
            ],
            self::Member => [
                WorkspacePermission::CreateProject,
                WorkspacePermission::UpdateProject,
                WorkspacePermission::DeployProject,
            ],
        };
    }

    public function hasPermission(WorkspacePermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
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
