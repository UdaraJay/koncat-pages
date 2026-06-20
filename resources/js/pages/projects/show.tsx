import { Head } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    CalendarClock,
    Folder,
    Globe,
    HardDrive,
    Share2,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type {
    Project,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
} from '@/types';

import { ProjectCard } from '../dashboard/project-card';
import { formatBytes, formatDate, formatNumber } from '../dashboard/utils';

type Props = {
    project: Project;
    projectSharePermissions: ProjectSharePermissionOption[];
    moveTargets: ProjectMoveTarget[];
};

export default function ProjectShow({
    project,
    projectSharePermissions,
    moveTargets,
}: Props) {
    const status = project.deletedAt
        ? 'Archived'
        : project.currentDeployment
          ? 'Live'
          : 'Draft';
    const scope = [project.ownerName, project.workspace?.name]
        .filter(Boolean)
        .join(' / ');

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

                    <section className="min-w-0 border bg-card">
                        <div className="grid gap-6 p-5">
                            <DetailGroup
                                icon={Folder}
                                title="Overview"
                                rows={[
                                    {
                                        label: 'Description',
                                        value:
                                            project.description ??
                                            'No description added.',
                                    },
                                    {
                                        label: 'Path',
                                        value: project.slug,
                                    },
                                    {
                                        label: 'Scope',
                                        value: scope || 'Personal',
                                    },
                                    {
                                        label: 'Owner',
                                        value: project.ownerName ?? 'Unknown',
                                    },
                                ]}
                            />

                            <DetailGroup
                                icon={HardDrive}
                                title="Deployment"
                                rows={deploymentRows(project)}
                            />

                            <DetailGroup
                                icon={Activity}
                                title="Analytics"
                                rows={analyticsRows(project)}
                            />

                            <SharingSummary project={project} />

                            <DetailGroup
                                icon={CalendarClock}
                                title="Timeline"
                                rows={[
                                    {
                                        label: 'Created',
                                        value: formatNullableDate(
                                            project.createdAt,
                                        ),
                                    },
                                    {
                                        label: 'Updated',
                                        value: formatNullableDate(
                                            project.updatedAt,
                                        ),
                                    },
                                    ...(project.deletedAt
                                        ? [
                                              {
                                                  label: 'Archived',
                                                  value: formatNullableDate(
                                                      project.deletedAt,
                                                  ),
                                              },
                                          ]
                                        : []),
                                ]}
                            />

                            <section className="space-y-3 border-t pt-5">
                                <div className="flex items-center gap-2">
                                    <Globe className="h-4 w-4 text-muted-foreground" />
                                    <h2 className="font-medium">Hosted URL</h2>
                                </div>
                                <a
                                    href={project.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex max-w-full items-center gap-2 text-sm font-medium text-foreground underline-offset-4 hover:underline"
                                >
                                    <span className="truncate">
                                        {project.url}
                                    </span>
                                    <ArrowUpRight className="h-4 w-4 shrink-0" />
                                </a>
                            </section>
                        </div>
                    </section>
                </div>
            </main>
        </>
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

function deploymentRows(project: Project) {
    if (!project.currentDeployment) {
        return [
            {
                label: 'Status',
                value: 'No deployment yet',
            },
            {
                label: 'Deployments',
                value: formatNumber(project.deploymentsCount),
            },
        ];
    }

    return [
        {
            label: 'Last pushed',
            value: formatNullableDate(project.currentDeployment.deployedAt),
        },
        {
            label: 'Files',
            value: formatNumber(project.currentDeployment.fileCount),
        },
        {
            label: 'Size',
            value: formatBytes(project.currentDeployment.totalBytes),
        },
        {
            label: 'Deployments',
            value: formatNumber(project.deploymentsCount),
        },
    ];
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
