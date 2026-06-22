import { usePage } from '@inertiajs/react';
import { Boxes, KeyRound, LayoutGrid, Settings, Users } from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavProjects } from '@/components/nav-projects';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem, Project } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const { currentTeam, currentTeamProjects, teams } = page.props;
    const projects = currentTeamProjects ?? [];
    const dashboardUrl = dashboard();
    const workspacesUrl = currentTeam ? `/${currentTeam.slug}/workspaces` : '#';
    const teamSettingsUrl = currentTeam
        ? `/${currentTeam.slug}/settings/general`
        : '#';

    const mainNavItems: NavItem[] = [
        {
            title: 'Projects',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Workspaces',
            href: workspacesUrl,
            icon: Boxes,
        },
        {
            title: 'Team settings',
            href: teamSettingsUrl,
            icon: Users,
        },
        {
            title: 'API tokens',
            href: '/settings/api-tokens',
            icon: KeyRound,
        },
        {
            title: 'Settings',
            href: '/settings/profile',
            icon: Settings,
        },
    ];

    const projectItems = projects.slice(0, 5).map((project: Project) => ({
        name: project.name,
        url: project.url,
    }));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <TeamSwitcher currentTeam={currentTeam} teams={teams ?? []} />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavProjects projects={projectItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
