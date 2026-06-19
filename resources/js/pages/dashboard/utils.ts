import type { Project } from '@/types';

import type { ProjectFilterStatus } from './types';

export function projectActionUrl(
    project: Project,
    action: 'unpublish' | 'restore' | '',
) {
    const url = `/projects/${project.id}`;

    return action ? `${url}/${action}` : url;
}

export function projectMoveDialogKey(project: Project) {
    return [
        project.id,
        project.ownerType,
        project.team?.id,
        project.workspace?.id,
        project.slug,
    ].join(':');
}

export function projectEditDialogKey(project: Project) {
    return [project.id, project.name, project.description ?? ''].join(':');
}

export function projectShareUrl(project: Project, share?: string) {
    const url = `/projects/${project.id}/shares`;

    return share ? `${url}/${share}` : url;
}

export function emptyProjectsTitle(status: ProjectFilterStatus): string {
    if (status === 'archived') {
        return 'No archived projects';
    }

    if (status === 'all') {
        return 'No projects found';
    }

    return 'No projects yet';
}

export function emptyProjectsText(status: ProjectFilterStatus): string {
    if (status === 'archived') {
        return 'Archived projects will appear here after you archive them from a project card.';
    }

    if (status === 'all') {
        return 'Try a different filter, or deploy a project from your agent.';
    }

    return 'Set up the MCP server above, then ask your agent to deploy a project.';
}

export function formatDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

export function formatNumber(value: number): string {
    return value.toLocaleString();
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
