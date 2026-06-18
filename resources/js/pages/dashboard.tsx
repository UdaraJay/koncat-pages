import { Head, Link } from '@inertiajs/react';
import {
    CalendarClock,
    ExternalLink,
    Folder,
    HardDrive,
    KeyRound,
    Rocket,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { DashboardInvitation, Project } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    projects?: Project[];
};

export default function Dashboard({
    pendingInvitations = [],
    projects = [],
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const hasPushedProject = projects.some(
        (project) => project.currentDeployment,
    );
    const mcpUrl = useMemo(() => {
        if (typeof window === 'undefined') {
            return '/mcp';
        }

        return `${window.location.origin}/mcp`;
    }, []);

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

                <section className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Folder className="h-4 w-4 text-muted-foreground" />
                            <h2 className="font-medium">Your projects</h2>
                        </div>
                        <Badge variant="secondary">
                            {projects.length}{' '}
                            {projects.length === 1 ? 'project' : 'projects'}
                        </Badge>
                    </div>

                    {projects.length > 0 ? (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {projects.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-[180px] place-items-center rounded-lg border border-dashed p-8 text-center">
                            <div className="max-w-sm space-y-2">
                                <h3 className="font-medium">No projects yet</h3>
                                <p className="text-sm text-muted-foreground">
                                    Set up the MCP server above, then ask your
                                    agent to deploy a project.
                                </p>
                            </div>
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

function MCPSetupPanel({ mcpUrl }: { mcpUrl: string }) {
    const config = `{
  "mcpServers": {
    "mcp-server": {
      "url": "${mcpUrl}",
      "headers": {
        "Authorization": "Bearer mp_your_token"
      }
    }
  }
}`;

    return (
        <section className="rounded-lg border bg-card p-5 text-card-foreground">
            <div className="grid gap-5 lg:grid-cols-[1fr_minmax(320px,460px)]">
                <div className="space-y-4">
                    <div className="flex items-start gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border bg-background">
                            <Rocket className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <h2 className="font-semibold">Deploy with MCP</h2>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Create an API token, add this MCP server to your
                                agent, then call deploy-project with an
                                index.html file and any assets.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-3 text-sm md:grid-cols-3">
                        <SetupStep
                            number="1"
                            title="Create a token"
                            text="Generate a user API token for your agent."
                        />
                        <SetupStep
                            number="2"
                            title="Add the server"
                            text="Use the endpoint and bearer token in your MCP client."
                        />
                        <SetupStep
                            number="3"
                            title="Deploy files"
                            text="Ask the agent to call deploy-project with inline files."
                        />
                    </div>

                    <Button asChild size="sm" className="w-fit">
                        <Link href="/settings/api-tokens">
                            <KeyRound className="h-4 w-4" />
                            Create API token
                        </Link>
                    </Button>
                </div>

                <div className="min-w-0 rounded-md border bg-muted/40 p-3">
                    <div className="mb-2 text-xs font-medium text-muted-foreground">
                        MCP client config
                    </div>
                    <pre className="overflow-x-auto text-xs leading-relaxed">
                        <code>{config}</code>
                    </pre>
                </div>
            </div>
        </section>
    );
}

function SetupStep({
    number,
    title,
    text,
}: {
    number: string;
    title: string;
    text: string;
}) {
    return (
        <div className="rounded-md border bg-background p-3">
            <div className="mb-2 flex items-center gap-2">
                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[11px] font-medium text-primary-foreground">
                    {number}
                </span>
                <span className="font-medium">{title}</span>
            </div>
            <p className="text-muted-foreground">{text}</p>
        </div>
    );
}

function ProjectCard({ project }: { project: Project }) {
    const deployedAt = project.currentDeployment?.deployedAt
        ? formatDate(project.currentDeployment.deployedAt)
        : null;
    const scope = [project.ownerName, project.workspace?.name]
        .filter(Boolean)
        .join(' / ');

    return (
        <article className="group flex overflow-hidden rounded-lg border bg-background shadow-sm transition hover:-translate-y-0.5 hover:border-foreground/20 hover:shadow-md">
            <div className="flex min-w-0 flex-1 flex-col">
                <ProjectPreview project={project} />

                <div className="flex flex-1 flex-col gap-4 p-4">
                    <div className="space-y-2">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="truncate font-medium">
                                    {project.name}
                                </h3>
                                <p className="truncate text-sm text-muted-foreground">
                                    {project.slug}
                                </p>
                            </div>
                            <Badge
                                variant={
                                    project.currentDeployment
                                        ? 'secondary'
                                        : 'outline'
                                }
                                className="shrink-0"
                            >
                                {project.currentDeployment ? 'Live' : 'Draft'}
                            </Badge>
                        </div>

                        {scope ? (
                            <div className="truncate text-sm text-muted-foreground">
                                {scope}
                            </div>
                        ) : null}

                        {project.description ? (
                            <p className="line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">
                                {project.description}
                            </p>
                        ) : (
                            <p className="line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">
                                No description added.
                            </p>
                        )}
                    </div>

                    <div className="mt-auto flex items-end justify-between gap-3">
                        <ProjectMeta
                            project={project}
                            deployedAt={deployedAt}
                        />

                        <Button asChild variant="outline" size="sm">
                            <a
                                href={project.url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink className="h-4 w-4" />
                                Open
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </article>
    );
}

function ProjectPreview({ project }: { project: Project }) {
    if (project.currentDeployment) {
        return (
            <div className="relative aspect-video overflow-hidden border-b bg-muted">
                <iframe
                    title={`${project.name} preview`}
                    src={project.url}
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
    if (!project.currentDeployment) {
        return (
            <div className="text-xs text-muted-foreground">
                No deployment yet
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
        </div>
    );
}

function formatDate(value: string): string {
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

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: dashboard(),
        },
    ],
});
