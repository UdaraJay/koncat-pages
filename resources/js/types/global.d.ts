import type { Auth } from '@/types/auth';
import type { Project, Workspace } from '@/types/matterpipe';
import type { Team } from '@/types/teams';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            currentTeamProjects: Project[];
            currentTeamWorkspaces: Workspace[];
            [key: string]: unknown;
        };
    }
}
