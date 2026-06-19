import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { dashboard } from '@/routes';

import { MCPSetupPanel } from './dashboard/mcp-setup-panel';
import { ProjectSection } from './dashboard/project-section';
import { SharedProjectsSection } from './dashboard/shared-projects-section';
import type { DashboardProps, ProjectFilters } from './dashboard/types';

export default function Dashboard({
    pendingInvitations = [],
    projects = [],
    sharedProjects = [],
    projectSharePermissions = [
        { value: 'read', label: 'Read only' },
        { value: 'write', label: 'Can edit' },
    ],
    projectFilters = { status: 'active', sort: 'updated_desc' },
    homeScope,
    moveTargets = [],
}: DashboardProps) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const hasPushedProject = projects.some(
        (project) => project.currentDeployment,
    );
    const showSharedProjectsSection =
        Boolean(homeScope?.team.isPersonal) || sharedProjects.length > 0;
    const mcpUrl = useMemo(() => {
        if (typeof window === 'undefined') {
            return '/mcp';
        }

        return `${window.location.origin}/mcp`;
    }, []);
    const updateProjectFilters = (updates: Partial<ProjectFilters>) => {
        const nextFilters = { ...projectFilters, ...updates };

        router.get(
            dashboard.url({ query: nextFilters }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <>
            <Head title="Projects" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <main className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                {!hasPushedProject ? <MCPSetupPanel mcpUrl={mcpUrl} /> : null}

                <ProjectSection
                    projects={projects}
                    projectFilters={projectFilters}
                    homeScope={homeScope}
                    moveTargets={moveTargets}
                    sharePermissions={projectSharePermissions}
                    onFilterChange={updateProjectFilters}
                />

                {showSharedProjectsSection ? (
                    <SharedProjectsSection
                        projects={sharedProjects}
                        moveTargets={moveTargets}
                        sharePermissions={projectSharePermissions}
                    />
                ) : null}
            </main>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: dashboard(),
        },
    ],
});
