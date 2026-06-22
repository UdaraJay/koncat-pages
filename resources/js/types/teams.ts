export type TeamRole = 'owner' | 'admin' | 'creator' | 'read_only';

export type Team = {
    id: string;
    name: string;
    slug: string;
    subdomain: string;
    hostingDomain?: string;
    hostingScheme?: string;
    isPersonal: boolean;
    brandLogoUrl?: string | null;
    brandBackgroundColor?: string | null;
    brandForegroundColor?: string | null;
    role?: TeamRole;
    roleLabel?: string;
    canUpdateTeam: boolean;
    isCurrent?: boolean;
};

export type TeamMember = {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamInvitationContext = {
    code: string;
    teamName: string;
};

export type ProjectShareContext = {
    code: string;
    projectName: string;
    sharerName: string;
};

export type DashboardInvitation = {
    code: string;
    inviterName: string;
    team: {
        name: string;
        slug: string;
    };
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
