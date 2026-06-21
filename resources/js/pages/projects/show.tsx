import { Head, router } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    CalendarClock,
    Check,
    Folder,
    HardDrive,
    Share2,
} from 'lucide-react';
import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
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

const analyticsChartConfig = {
    views: {
        label: 'Views',
        color: 'var(--muted-foreground)',
    },
} satisfies ChartConfig;

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
                                <AnalyticsCard project={project} />

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
                    <h2 className="font-medium">Current version</h2>
                </div>

                <div className="rounded-lg border bg-background p-3 py-2">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-start gap-2">
                                <div className="flex min-w-0 items-center gap-1.5">
                                    <DeploymentTimestamp
                                        value={currentDeployment.deployedAt}
                                        dateClassName="font-medium tracking-tight"
                                        timeClassName="font-medium tracking-tight text-muted-foreground"
                                    />
                                    <PolicyScreeningIndicator
                                        deployment={currentDeployment}
                                    />
                                </div>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                {formatNumber(currentDeployment.fileCount)}{' '}
                                {currentDeployment.fileCount === 1
                                    ? 'file'
                                    : 'files'}
                                , {formatBytes(currentDeployment.totalBytes)}
                            </div>
                        </div>
                        <a href={project.url} target="_blank" rel="noreferrer">
                            <Button
                                variant="outline"
                                size="sm"
                                className="max-w-full whitespace-normal"
                            >
                                Open
                            </Button>
                        </a>
                    </div>
                </div>

                {previousDeployments.length > 0 ? (
                    <div className="space-y-2">
                        <div className="text-sm font-medium">Past versions</div>
                        <div className="divide-y rounded-lg border">
                            {previousDeployments.map((deployment) => (
                                <div
                                    key={deployment.id}
                                    className="flex flex-col gap-3 p-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-start gap-2">
                                            <div className="flex min-w-0 items-center gap-1.5">
                                                <DeploymentTimestamp
                                                    value={
                                                        deployment.deployedAt
                                                    }
                                                />
                                                <PolicyScreeningIndicator
                                                    deployment={deployment}
                                                />
                                            </div>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
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

function DeploymentTimestamp({
    value,
    dateClassName = 'text-sm font-medium tracking-tight',
    timeClassName = 'text-sm font-medium tracking-tight text-muted-foreground',
}: {
    value: string;
    dateClassName?: string;
    timeClassName?: string;
}) {
    const timestamp = formatDeploymentTimestamp(value);

    return (
        <time
            dateTime={value}
            className="inline-flex min-w-0 flex-wrap items-baseline gap-x-1.5 leading-tight"
        >
            <span className={dateClassName}>{timestamp.date}</span>
            {timestamp.time ? (
                <span className={timeClassName}>{timestamp.time}</span>
            ) : null}
        </time>
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
                    <Check className="size-3.5 stroke-3" />
                </span>
            </TooltipTrigger>
            <TooltipContent side="top" className="text-center">
                Automated policy screening reduces common risk; it is not a
                guarantee of safety.
            </TooltipContent>
        </Tooltip>
    );
}

function AnalyticsCard({ project }: { project: Project }) {
    const analytics = project.analytics ?? {
        viewsTotal: 0,
        uniqueViewersTotal: 0,
        viewsLast7Days: 0,
        lastViewedAt: null,
        dailyViews: defaultDailyViews(),
    };
    const repeatViews = Math.max(
        analytics.viewsTotal - analytics.uniqueViewersTotal,
        0,
    );
    const repeatRate =
        analytics.viewsTotal > 0
            ? Math.round((repeatViews / analytics.viewsTotal) * 100)
            : 0;
    const dailyViews =
        analytics.dailyViews && analytics.dailyViews.length > 0
            ? analytics.dailyViews
            : defaultDailyViews();

    return (
        <section className="space-y-3 border-t pt-5 first:border-t-0 first:pt-0">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-center gap-2">
                    <Activity className="h-4 w-4 text-muted-foreground" />
                    <h2 className="font-medium">Analytics</h2>
                </div>
                <div className="text-xs text-muted-foreground sm:text-right">
                    <div>Last 14 days</div>
                    <div className="mt-1">
                        {analytics.lastViewedAt
                            ? `Last viewed ${formatDate(analytics.lastViewedAt)}`
                            : 'No views yet'}
                    </div>
                </div>
            </div>

            <div className="relative min-w-0">
                <dl className="absolute top-0 left-0 z-10 flex w-full max-w-md flex-wrap gap-9">
                    <AnalyticsMetricTile
                        label="Total views"
                        value={formatNumber(analytics.viewsTotal)}
                    />
                    <AnalyticsMetricTile
                        label="Repeat views"
                        value={`${formatNumber(repeatRate)}%`}
                    />
                    <AnalyticsMetricTile
                        label="Last 7 days"
                        value={formatNumber(analytics.viewsLast7Days)}
                    />
                </dl>

                <ChartContainer
                    config={analyticsChartConfig}
                    className="aspect-auto h-56 w-full"
                >
                    <BarChart
                        accessibilityLayer
                        data={dailyViews}
                        margin={{ top: 92, right: 4, bottom: 0, left: 4 }}
                    >
                        <CartesianGrid vertical={false} strokeDasharray="3 3" />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={20}
                            tickFormatter={formatShortChartDate}
                        />
                        <YAxis
                            hide
                            allowDecimals={false}
                            domain={[0, 'dataMax + 1']}
                        />
                        <ChartTooltip
                            cursor={{
                                fill: 'var(--muted)',
                                fillOpacity: 0.5,
                            }}
                            content={
                                <ChartTooltipContent
                                    indicator="dot"
                                    labelFormatter={(value) =>
                                        formatChartDate(String(value))
                                    }
                                />
                            }
                        />
                        <Bar
                            dataKey="views"
                            fill="var(--color-views)"
                            fillOpacity={0.35}
                            radius={[5, 5, 0, 0]}
                            maxBarSize={44}
                        />
                    </BarChart>
                </ChartContainer>
            </div>
        </section>
    );
}

function AnalyticsMetricTile({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="min-w-0">
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 truncate text-2xl font-medium tracking-tight sm:text-3xl">
                {value}
            </dd>
        </div>
    );
}

function defaultDailyViews() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Array.from({ length: 14 }, (_, index) => {
        const date = new Date(today);
        date.setDate(today.getDate() - (13 - index));

        return {
            date: localDateKey(date),
            views: 0,
        };
    });
}

function localDateKey(date: Date): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function formatShortChartDate(value: string): string {
    const date = chartDate(value);

    return date
        ? new Intl.DateTimeFormat(undefined, {
              month: 'short',
              day: 'numeric',
          }).format(date)
        : value;
}

function formatChartDate(value: string): string {
    const date = chartDate(value);

    return date
        ? new Intl.DateTimeFormat(undefined, {
              weekday: 'short',
              month: 'short',
              day: 'numeric',
          }).format(date)
        : value;
}

function chartDate(value: string): Date | null {
    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
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

function formatNullableDate(value?: string | null): string {
    return value ? formatDate(value) : 'Never';
}

function formatDeploymentTimestamp(value: string): {
    date: string;
    time: string;
} {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return {
            date: value,
            time: '',
        };
    }

    return {
        date: new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(date),
        time: new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
            timeZoneName: 'short',
        }).format(date),
    };
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
