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
    previewUrl: string;
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
    sharesCount?: number;
    analytics?: {
        viewsTotal: number;
        uniqueViewersTotal: number;
        viewsLast7Days: number;
        lastViewedAt?: string | null;
    };
    canUpdate?: boolean;
    canDeploy?: boolean;
    canUnpublish?: boolean;
    canArchive?: boolean;
    canRestore?: boolean;
    canMove?: boolean;
    canManageShares?: boolean;
    sharePermission?: ProjectSharePermission | null;
    sharePermissionLabel?: string | null;
    sharedByName?: string | null;
    shares?: ProjectShare[];
    createdAt?: string | null;
    updatedAt?: string | null;
    deletedAt?: string | null;
    currentDeployment?: {
        id: string;
        fileCount: number;
        totalBytes: number;
        deployedAt: string;
    } | null;
};

export type ProjectMoveTarget = {
    type: 'user' | 'team';
    id: string;
    teamId: string;
    name: string;
    label: string;
    isPersonal: boolean;
    canCreateProject: boolean;
    workspaces: {
        id: string;
        name: string;
    }[];
};

export type ProjectSharePermission = 'read' | 'write';

export type ProjectShare = {
    code: string;
    email: string;
    name?: string | null;
    permission: ProjectSharePermission;
    permissionLabel: string;
    pending: boolean;
};

export type ProjectSharePermissionOption = {
    value: ProjectSharePermission;
    label: string;
};

export type UserApiToken = {
    id: string;
    name: string;
    lastUsedAt?: string | null;
    expiresAt?: string | null;
    createdAt: string;
};
