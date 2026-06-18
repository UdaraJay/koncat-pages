export type WorkspaceRole = 'owner' | 'admin' | 'member';

export type Workspace = {
    id: string;
    teamId: string;
    name: string;
    slug: string;
    role?: WorkspaceRole;
    roleLabel?: string;
    projectsCount: number;
};

export type WorkspacePermissions = {
    canUpdateWorkspace: boolean;
    canDeleteWorkspace: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateProject: boolean;
    canUpdateProject: boolean;
    canDeleteProject: boolean;
    canDeployProject: boolean;
};

export type WorkspaceMember = {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
    role: WorkspaceRole;
    role_label: string;
};

export type WorkspaceRoleOption = {
    value: WorkspaceRole;
    label: string;
};

export type Project = {
    id: string;
    name: string;
    slug: string;
    description?: string | null;
    url: string;
    ownerType?: 'user' | 'team';
    ownerName?: string | null;
    team?: {
        id: string;
        name: string;
        slug: string;
    } | null;
    workspace?: {
        id: string;
        name: string;
        slug?: string;
    } | null;
    deploymentsCount: number;
    canDeploy?: boolean;
    createdAt?: string | null;
    updatedAt?: string | null;
    currentDeployment?: {
        id: string;
        fileCount: number;
        totalBytes: number;
        deployedAt: string;
    } | null;
};

export type UserApiToken = {
    id: string;
    name: string;
    lastUsedAt?: string | null;
    expiresAt?: string | null;
    createdAt: string;
};
