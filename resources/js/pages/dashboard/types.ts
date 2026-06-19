import type {
    DashboardInvitation,
    Project,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
    Team,
} from '@/types';

export type ProjectFilterStatus = 'active' | 'archived' | 'all';
export type ProjectSort = 'updated_desc' | 'created_desc' | 'name_asc';

export type HomeScope = {
    team: Pick<Team, 'id' | 'name' | 'slug' | 'isPersonal'>;
    projectLabel: string;
    emptyTitle: string;
    emptyText: string;
};

export type ProjectFilters = {
    status: ProjectFilterStatus;
    sort: ProjectSort;
};

export type DashboardProps = {
    pendingInvitations?: DashboardInvitation[];
    projects?: Project[];
    sharedProjects?: Project[];
    projectSharePermissions?: ProjectSharePermissionOption[];
    projectFilters?: ProjectFilters;
    homeScope?: HomeScope;
    moveTargets?: ProjectMoveTarget[];
};
