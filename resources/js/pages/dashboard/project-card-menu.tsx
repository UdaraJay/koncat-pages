import { router } from '@inertiajs/react';
import {
    Archive,
    Eye,
    MoreHorizontal,
    MoveRight,
    Pencil,
    RotateCcw,
    Share2,
    Unplug,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Project } from '@/types';

import { projectActionUrl } from './utils';

export function ProjectCardMenu({
    project,
    canMove,
    onAnalytics,
    onEdit,
    onMove,
    onShare,
}: {
    project: Project;
    canMove: boolean;
    onAnalytics: () => void;
    onEdit: () => void;
    onMove: () => void;
    onShare: () => void;
}) {
    const archiveProject = () => {
        router.delete(projectActionUrl(project, ''), {
            preserveScroll: true,
        });
    };
    const restoreProject = () => {
        router.post(
            projectActionUrl(project, 'restore'),
            {},
            {
                preserveScroll: true,
            },
        );
    };
    const unpublishProject = () => {
        router.post(
            projectActionUrl(project, 'unpublish'),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="secondary"
                    size="icon-sm"
                    className="bg-background/85 shadow-sm backdrop-blur"
                    aria-label={`${project.name} actions`}
                >
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuItem onSelect={onAnalytics}>
                    <Eye className="h-4 w-4" />
                    Analytics
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                {project.canUpdate ? (
                    <DropdownMenuItem onSelect={onEdit}>
                        <Pencil className="h-4 w-4" />
                        Edit
                    </DropdownMenuItem>
                ) : null}
                {project.canManageShares ? (
                    <>
                        <DropdownMenuItem onSelect={onShare}>
                            <Share2 className="h-4 w-4" />
                            Share
                        </DropdownMenuItem>
                    </>
                ) : null}
                {project.canMove ? (
                    <DropdownMenuItem disabled={!canMove} onSelect={onMove}>
                        <MoveRight className="h-4 w-4" />
                        Move
                    </DropdownMenuItem>
                ) : null}
                {project.canUpdate ||
                project.canManageShares ||
                project.canMove ? (
                    <DropdownMenuSeparator />
                ) : null}
                {project.canRestore ? (
                    <DropdownMenuItem onSelect={restoreProject}>
                        <RotateCcw className="h-4 w-4" />
                        Restore
                    </DropdownMenuItem>
                ) : (
                    <>
                        <DropdownMenuItem
                            disabled={!project.canUnpublish}
                            onSelect={unpublishProject}
                        >
                            <Unplug className="h-4 w-4" />
                            Unpublish
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            disabled={!project.canArchive}
                            variant="destructive"
                            onSelect={archiveProject}
                        >
                            <Archive className="h-4 w-4" />
                            Archive
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
