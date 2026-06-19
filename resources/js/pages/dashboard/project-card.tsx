import {
    ArrowUpRight,
    CalendarClock,
    Eye,
    HardDrive,
    Share2,
} from 'lucide-react';
import { useState } from 'react';
import EditProjectDialog from '@/components/edit-project-dialog';
import MoveProjectDialog from '@/components/move-project-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    Project,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
} from '@/types';

import { ProjectAnalyticsDialog } from './project-analytics-dialog';
import { ProjectCardMenu } from './project-card-menu';
import { ShareProjectDialog } from './share-project-dialog';
import {
    formatBytes,
    formatDate,
    formatNumber,
    projectEditDialogKey,
    projectMoveDialogKey,
} from './utils';

export function ProjectCard({
    project,
    moveTargets,
    sharePermissions,
}: {
    project: Project;
    moveTargets: ProjectMoveTarget[];
    sharePermissions: ProjectSharePermissionOption[];
}) {
    const [shareDialogOpen, setShareDialogOpen] = useState(false);
    const [analyticsDialogOpen, setAnalyticsDialogOpen] = useState(false);
    const [moveDialogOpen, setMoveDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const deployedAt = project.currentDeployment?.deployedAt
        ? formatDate(project.currentDeployment.deployedAt)
        : null;
    const scope = [project.ownerName, project.workspace?.name]
        .filter(Boolean)
        .join(' / ');
    const isArchived = Boolean(project.deletedAt);

    return (
        <>
            <ShareProjectDialog
                project={project}
                permissions={sharePermissions}
                open={shareDialogOpen}
                onOpenChange={setShareDialogOpen}
            />
            <ProjectAnalyticsDialog
                project={project}
                open={analyticsDialogOpen}
                onOpenChange={setAnalyticsDialogOpen}
            />
            <MoveProjectDialog
                key={projectMoveDialogKey(project)}
                project={project}
                targets={moveTargets}
                open={moveDialogOpen}
                onOpenChange={setMoveDialogOpen}
            />
            <EditProjectDialog
                key={projectEditDialogKey(project)}
                project={project}
                open={editDialogOpen}
                onOpenChange={setEditDialogOpen}
            />
            <article className="group flex overflow-hidden border bg-card transition">
                <div className="flex min-w-0 flex-1 flex-col">
                    <div className="relative">
                        <ProjectPreview project={project} />
                        <div className="absolute top-4 left-4 flex shrink-0 items-center gap-1">
                            {scope ? (
                                <Badge
                                    variant={
                                        project.currentDeployment
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                    className="shrink-0 bg-background/60 text-foreground"
                                >
                                    {scope}
                                </Badge>
                            ) : null}

                            <Badge
                                variant={
                                    project.currentDeployment && !isArchived
                                        ? 'secondary'
                                        : 'outline'
                                }
                                className="shrink-0"
                            >
                                {isArchived
                                    ? 'Archived'
                                    : project.currentDeployment
                                      ? 'Live'
                                      : 'Draft'}
                            </Badge>
                            {project.sharePermissionLabel ? (
                                <Badge
                                    variant="outline"
                                    className="shrink-0 bg-background text-foreground"
                                >
                                    {project.sharePermissionLabel}
                                </Badge>
                            ) : null}
                        </div>
                        <div className="absolute top-3 right-3">
                            <ProjectCardMenu
                                project={project}
                                canMove={moveTargets.length > 0}
                                onAnalytics={() => setAnalyticsDialogOpen(true)}
                                onEdit={() => setEditDialogOpen(true)}
                                onMove={() => setMoveDialogOpen(true)}
                                onShare={() => setShareDialogOpen(true)}
                            />
                        </div>
                    </div>

                    <div className="flex flex-1 flex-col gap-4 p-4">
                        <div className="">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h3 className="truncate font-medium">
                                        {project.name}
                                    </h3>
                                </div>
                            </div>

                            {project.description ? (
                                <div className="line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">
                                    {project.description}
                                </div>
                            ) : (
                                <div className="line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">
                                    No description added.
                                </div>
                            )}
                        </div>

                        <div className="mt-auto flex items-end justify-between gap-3">
                            <ProjectMeta
                                project={project}
                                deployedAt={deployedAt}
                            />

                            {isArchived ? (
                                <Badge variant="outline">Hidden</Badge>
                            ) : (
                                <Button asChild variant="outline" size="sm">
                                    <a
                                        href={project.url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Open
                                        <ArrowUpRight className="h-4 w-4" />
                                    </a>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </article>
        </>
    );
}

function ProjectPreview({ project }: { project: Project }) {
    if (project.currentDeployment && !project.deletedAt) {
        return (
            <div className="relative aspect-video overflow-hidden border-b bg-muted">
                <iframe
                    title={`${project.name} preview`}
                    src={project.previewUrl}
                    loading="lazy"
                    tabIndex={-1}
                    className="pointer-events-none h-[200%] w-[200%] origin-top-left scale-50 border-0 bg-background"
                />
                <div className="pointer-events-none absolute inset-0 ring-1 ring-black/5 ring-inset" />
            </div>
        );
    }

    return (
        <div className="relative aspect-video overflow-hidden border-b bg-muted">
            <div className="absolute inset-0 bg-[linear-gradient(rgba(0,0,0,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,.06)_1px,transparent_1px)] bg-[size:24px_24px] dark:bg-[linear-gradient(rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.08)_1px,transparent_1px)]" />
            <div className="absolute inset-x-6 top-6 h-3 rounded-full bg-background shadow-sm" />
            <div className="absolute right-6 bottom-6 left-6 rounded-md border bg-background/95 p-3 shadow-sm">
                <div className="h-2 w-2/3 rounded-full bg-foreground/20" />
                <div className="mt-2 h-2 w-1/2 rounded-full bg-foreground/10" />
            </div>
        </div>
    );
}

function ProjectMeta({
    project,
    deployedAt,
}: {
    project: Project;
    deployedAt: string | null;
}) {
    const viewsTotal = project.analytics?.viewsTotal ?? 0;

    if (!project.currentDeployment) {
        return (
            <div className="grid gap-1 text-xs text-muted-foreground">
                <div>No deployment yet</div>
                <ProjectCardStats
                    viewsTotal={viewsTotal}
                    sharesCount={project.sharesCount ?? 0}
                />
            </div>
        );
    }

    return (
        <div className="grid gap-1 text-xs text-muted-foreground">
            {deployedAt ? (
                <div className="flex items-center gap-1.5">
                    <CalendarClock className="h-3.5 w-3.5" />
                    <span>Last pushed {deployedAt}</span>
                </div>
            ) : null}
            <div className="flex items-center gap-1.5">
                <HardDrive className="h-3.5 w-3.5" />
                <span>
                    {project.currentDeployment.fileCount}{' '}
                    {project.currentDeployment.fileCount === 1
                        ? 'file'
                        : 'files'}
                    , {formatBytes(project.currentDeployment.totalBytes)}
                </span>
            </div>
            <ProjectCardStats
                viewsTotal={viewsTotal}
                sharesCount={project.sharesCount ?? 0}
            />
        </div>
    );
}

function ProjectCardStats({
    viewsTotal,
    sharesCount,
}: {
    viewsTotal: number;
    sharesCount: number;
}) {
    return (
        <>
            <div className="flex items-center gap-1.5">
                <Eye className="h-3.5 w-3.5" />
                <span>
                    {formatNumber(viewsTotal)}{' '}
                    {viewsTotal === 1 ? 'view' : 'views'}
                </span>
            </div>
            <div className="flex items-center gap-1.5">
                <Share2 className="h-3.5 w-3.5" />
                <span>
                    Shared with {formatNumber(sharesCount)}{' '}
                    {sharesCount === 1 ? 'person' : 'people'}
                </span>
            </div>
        </>
    );
}
