import { Head, router } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    CalendarClock,
    CheckCircle2,
    Folder,
    HardDrive,
    RotateCcw,
    Share2,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import type {
    Project,
    ProjectDeployment,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
} from '@/types';

import { ProjectCard } from '../dashboard/project-card';
import { formatBytes, formatDate, formatNumber } from '../dashboard/utils';

type Props = {
    project: Project;
    deployments: ProjectDeployment[];
    projectSharePermissions: ProjectSharePermissionOption[];
    moveTargets: ProjectMoveTarget[];
};

export default function ProjectShow({
    project,
    deployments,
    projectSharePermissions,
    moveTargets,
}: Props) {
    return (
        <>
            <Head title={project.name} />

            <main className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="grid gap-6 xl:grid-cols-[minmax(320px,420px)_minmax(0,1fr)]">
                    <aside className="min-w-0 xl:sticky xl:top-24 xl:self-start">
                        <ProjectCard
                            project={project}
                            moveTargets={moveTargets}
                            sharePermissions={projectSharePermissions}
                        />
                    </aside>

                    <div className="min-w-0 space-y-6">
                        <OverviewCard project={project} />

                        <DeploymentSummary
                            project={project}
                            deployments={deployments}
                        />

                        <section className="border bg-card">
                            <div className="grid gap-6 p-5">
                                <DetailGroup
                                    icon={Activity}
                                    title="Analytics"
                                    rows={analyticsRows(project)}
                                />

                                <SharingSummary project={project} />
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </>
    );
}

function OverviewCard({ project }: { project: Project }) {
    const details = [
        {
            label: 'Created',
            value: formatNullableDate(project.createdAt),
            icon: CalendarClock,
        },
        {
            label: 'Updated',
            value: formatNullableDate(project.updatedAt),
            icon: Activity,
        },
        {
            label: 'Deployments',
            value: formatNumber(project.deploymentsCount),
            icon: HardDrive,
        },
    ];

    return (
        <section className="border bg-card">
            <div className="p-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-center gap-2">
                        <Folder className="h-4 w-4 text-muted-foreground" />
                        <h2 className="font-medium">Overview</h2>
                    </div>
                    <a
                        href={project.url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex max-w-full items-center gap-2 text-sm font-medium text-foreground underline-offset-4 hover:underline"
                    >
                        <span className="truncate">{project.url}</span>
                        <ArrowUpRight className="h-4 w-4 shrink-0" />
                    </a>
                </div>

                <dl className="mt-5 grid gap-4 sm:grid-cols-3">
                    {details.map(({ icon: Icon, ...detail }) => (
                        <div
                            key={detail.label}
                            className="flex min-w-0 items-center gap-3"
                        >
                            <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />
                            <div className="min-w-0">
                                <dt className="text-xs text-muted-foreground">
                                    {detail.label}
                                </dt>
                                <dd className="truncate text-sm font-medium">
                                    {detail.value}
                                </dd>
                            </div>
                        </div>
                    ))}
                </dl>

                {project.deletedAt ? (
                    <div className="mt-4 text-sm text-muted-foreground">
                        Archived {formatNullableDate(project.deletedAt)}
                    </div>
                ) : null}
            </div>
        </section>
    );
}

function DeploymentSummary({
    project,
    deployments,
}: {
    project: Project;
    deployments: ProjectDeployment[];
}) {
    const [rollbackId, setRollbackId] = useState<string | null>(null);
    const currentDeploymentId = project.currentDeployment?.id ?? null;
    const currentDeployment = project.currentDeployment;
    const previousDeployments = deployments.filter(
        (deployment) => deployment.id !== currentDeploymentId,
    );

    const rollback = (deployment: ProjectDeployment) => {
        setRollbackId(deployment.id);
        router.post(
            `/projects/${project.id}/deployments/${deployment.id}/activate`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setRollbackId(null),
            },
        );
    };

    if (!currentDeployment) {
        return (
            <section className="border bg-card">
                <div className="space-y-3 p-5">
                    <div className="flex items-center gap-2">
                        <HardDrive className="h-4 w-4 text-muted-foreground" />
                        <h2 className="font-medium">Versions</h2>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        No deployment is live yet.
                    </p>
                </div>
            </section>
        );
    }

    return (
        <section className="border bg-card">
            <div className="space-y-4 p-5">
                <div className="flex items-center gap-2">
                    <HardDrive className="h-4 w-4 text-muted-foreground" />
                    <h2 className="font-medium">Versions</h2>
                </div>

                <div className="border bg-background p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <div className="flex items-center gap-1.5 font-medium">
                                    {formatNullableDate(
                                        currentDeployment.deployedAt,
                                    )}
                                    <PolicyScreeningIndicator
                                        deployment={currentDeployment}
                                    />
                                </div>
                                <Badge variant="secondary">Live</Badge>
                            </div>
                            <div className="mt-1 text-sm text-muted-foreground">
                                {formatNumber(currentDeployment.fileCount)}{' '}
                                {currentDeployment.fileCount === 1
                                    ? 'file'
                                    : 'files'}
                                , {formatBytes(currentDeployment.totalBytes)}
                            </div>
                        </div>
                        <a
                            href={project.url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 text-sm font-medium underline-offset-4 hover:underline"
                        >
                            Open
                            <ArrowUpRight className="h-4 w-4" />
                        </a>
                    </div>
                </div>

                {previousDeployments.length > 0 ? (
                    <div className="space-y-2">
                        <div className="text-sm font-medium">History</div>
                        <div className="divide-y border">
                            {previousDeployments.map((deployment) => (
                                <div
                                    key={deployment.id}
                                    className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="flex items-center gap-1.5 text-sm font-medium">
                                                {formatNullableDate(
                                                    deployment.deployedAt,
                                                )}
                                                <PolicyScreeningIndicator
                                                    deployment={deployment}
                                                />
                                            </span>
                                        </div>
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            {formatNumber(deployment.fileCount)}{' '}
                                            {deployment.fileCount === 1
                                                ? 'file'
                                                : 'files'}
                                            ,{' '}
                                            {formatBytes(deployment.totalBytes)}
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={rollbackId === deployment.id}
                                        onClick={() => rollback(deployment)}
                                    >
                                        <RotateCcw className="h-4 w-4" />
                                        Rollback
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                ) : null}
            </div>
        </section>
    );
}

function PolicyScreeningIndicator({
    deployment,
}: {
    deployment: ProjectDeployment;
}) {
    if (!deployment.securityScan) {
        return null;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="inline-flex rounded-sm text-emerald-600 outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    aria-label="Automated policy screening"
                >
                    <CheckCircle2 className="h-4 w-4" />
                </span>
            </TooltipTrigger>
            <TooltipContent side="top" className="max-w-64">
                <p>
                    Automated policy screening reduces common risk; it is not a
                    guarantee of safety.
                </p>
            </TooltipContent>
        </Tooltip>
    );
}

function DetailGroup({
    icon: Icon,
    title,
    rows,
}: {
    icon: typeof Folder;
    title: string;
    rows: { label: string; value: string }[];
}) {
    return (
        <section className="space-y-3 border-t pt-5 first:border-t-0 first:pt-0">
            <div className="flex items-center gap-2">
                <Icon className="h-4 w-4 text-muted-foreground" />
                <h2 className="font-medium">{title}</h2>
            </div>
            <dl className="grid gap-3 text-sm">
                {rows.map((row) => (
                    <div
                        key={`${title}-${row.label}`}
                        className="grid gap-1 sm:grid-cols-[160px_minmax(0,1fr)]"
                    >
                        <dt className="text-muted-foreground">{row.label}</dt>
                        <dd className="min-w-0 font-medium break-words">
                            {row.value}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

function SharingSummary({ project }: { project: Project }) {
    const sharedUsers = project.analytics?.sharedUsers ?? [];
    const rows = [
        {
            label: 'Shared with',
            value: `${formatNumber(project.sharesCount ?? 0)} ${
                project.sharesCount === 1 ? 'person' : 'people'
            }`,
        },
        ...(project.sharedByName
            ? [
                  {
                      label: 'Shared by',
                      value: project.sharedByName,
                  },
              ]
            : []),
    ];

    return (
        <section className="space-y-3 border-t pt-5">
            <div className="flex items-center gap-2">
                <Share2 className="h-4 w-4 text-muted-foreground" />
                <h2 className="font-medium">Sharing</h2>
            </div>
            <dl className="grid gap-3 text-sm">
                {rows.map((row) => (
                    <div
                        key={`Sharing-${row.label}`}
                        className="grid gap-1 sm:grid-cols-[160px_minmax(0,1fr)]"
                    >
                        <dt className="text-muted-foreground">{row.label}</dt>
                        <dd className="min-w-0 font-medium break-words">
                            {row.value}
                        </dd>
                    </div>
                ))}
            </dl>
            {sharedUsers.length > 0 ? (
                <div className="divide-y border-y text-sm">
                    {sharedUsers.map((user) => (
                        <div
                            key={user.email}
                            className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0">
                                <div className="truncate font-medium">
                                    {user.name ?? user.email}
                                </div>
                                <div className="truncate text-muted-foreground">
                                    {user.email}
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-2 text-muted-foreground">
                                <Badge variant="outline">
                                    {user.permissionLabel}
                                </Badge>
                                <span>
                                    {formatNumber(user.viewsTotal)} views
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            ) : null}
        </section>
    );
}

function analyticsRows(project: Project) {
    const analytics = project.analytics;

    return [
        {
            label: 'Views',
            value: formatNumber(analytics?.viewsTotal ?? 0),
        },
        {
            label: 'Unique viewers',
            value: formatNumber(analytics?.uniqueViewersTotal ?? 0),
        },
        {
            label: 'Last 7 days',
            value: formatNumber(analytics?.viewsLast7Days ?? 0),
        },
        {
            label: 'Last viewed',
            value: formatNullableDate(analytics?.lastViewedAt),
        },
    ];
}

function formatNullableDate(value?: string | null): string {
    return value ? formatDate(value) : 'Never';
}

ProjectShow.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: dashboard(),
        },
        {
            title: props.project.name,
            href: `/projects/${props.project.id}`,
        },
    ],
});
