import { Head, router, useForm } from '@inertiajs/react';
import {
    Archive,
    ArrowUpRight,
    CalendarClock,
    Folder,
    HardDrive,
    MoreHorizontal,
    RotateCcw,
    Rocket,
    Share2,
    SlidersHorizontal,
    Trash2,
    Unplug,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type {
    DashboardInvitation,
    Project,
    ProjectSharePermission,
    ProjectSharePermissionOption,
} from '@/types';

type ProjectFilterStatus = 'active' | 'archived' | 'all';
type ProjectSort = 'updated_desc' | 'created_desc' | 'name_asc';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    projects?: Project[];
    sharedProjects?: Project[];
    projectSharePermissions?: ProjectSharePermissionOption[];
    projectFilters?: {
        status: ProjectFilterStatus;
        sort: ProjectSort;
    };
};

export default function Dashboard({
    pendingInvitations = [],
    projects = [],
    sharedProjects = [],
    projectSharePermissions = [
        { value: 'read', label: 'Read only' },
        { value: 'write', label: 'Can edit' },
    ],
    projectFilters = { status: 'active', sort: 'updated_desc' },
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
    const updateProjectFilters = (
        updates: Partial<{ status: ProjectFilterStatus; sort: ProjectSort }>,
    ) => {
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

                <section className="space-y-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <Folder className="h-4 w-4 text-muted-foreground" />
                            <h2 className="font-medium">Your projects</h2>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {projects.length}{' '}
                                {projects.length === 1 ? 'project' : 'projects'}
                            </Badge>

                            <div className="flex items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4 text-muted-foreground" />
                                <Select
                                    value={projectFilters.status}
                                    onValueChange={(status) =>
                                        updateProjectFilters({
                                            status: status as ProjectFilterStatus,
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        size="sm"
                                        className="w-[126px]"
                                        aria-label="Filter projects"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent align="end">
                                        <SelectItem value="active">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="archived">
                                            Archived
                                        </SelectItem>
                                        <SelectItem value="all">All</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={projectFilters.sort}
                                    onValueChange={(sort) =>
                                        updateProjectFilters({
                                            sort: sort as ProjectSort,
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        size="sm"
                                        className="w-[142px]"
                                        aria-label="Sort projects"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent align="end">
                                        <SelectItem value="updated_desc">
                                            Recently updated
                                        </SelectItem>
                                        <SelectItem value="created_desc">
                                            Newest
                                        </SelectItem>
                                        <SelectItem value="name_asc">
                                            Name
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    {projects.length > 0 ? (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {projects.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                    sharePermissions={projectSharePermissions}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-[180px] place-items-center rounded-lg border border-dashed p-8 text-center">
                            <div className="max-w-sm space-y-2">
                                <h3 className="font-medium">
                                    {emptyProjectsTitle(projectFilters.status)}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {emptyProjectsText(projectFilters.status)}
                                </p>
                            </div>
                        </div>
                    )}
                </section>

                {sharedProjects.length > 0 ? (
                    <section className="space-y-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2">
                                <Share2 className="h-4 w-4 text-muted-foreground" />
                                <h2 className="font-medium">
                                    Shared with you
                                </h2>
                            </div>
                            <Badge variant="secondary">
                                {sharedProjects.length}{' '}
                                {sharedProjects.length === 1
                                    ? 'project'
                                    : 'projects'}
                            </Badge>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {sharedProjects.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                    sharePermissions={projectSharePermissions}
                                />
                            ))}
                        </div>
                    </section>
                ) : null}
            </main>
        </>
    );
}

function MCPSetupPanel({ mcpUrl }: { mcpUrl: string }) {
    const config = `{
  "mcpServers": {
    "matterpipe": {
      "url": "${mcpUrl}"
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
                                Add this MCP server to your agent, approve the
                                OAuth prompt, then call deploy-project with an
                                index.html file and any assets.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-3 text-sm md:grid-cols-3">
                        <SetupStep
                            number="1"
                            title="Add the server"
                            text="Use the endpoint in your MCP client."
                        />
                        <SetupStep
                            number="2"
                            title="Approve access"
                            text="Sign in and grant the mcp:use scope."
                        />
                        <SetupStep
                            number="3"
                            title="Deploy files"
                            text="Ask the agent to call deploy-project with inline files."
                        />
                    </div>
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

function ProjectCard({
    project,
    sharePermissions,
}: {
    project: Project;
    sharePermissions: ProjectSharePermissionOption[];
}) {
    const [shareDialogOpen, setShareDialogOpen] = useState(false);
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
            <article className="group flex overflow-hidden border bg-background transition">
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
                            <Badge variant="outline" className="shrink-0">
                                {project.sharePermissionLabel}
                            </Badge>
                        ) : null}
                    </div>
                    <div className="absolute top-3 right-3">
                        <ProjectCardMenu
                            project={project}
                            onShare={() => setShareDialogOpen(true)}
                        />
                    </div>
                </div>

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
                        </div>

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

function ProjectCardMenu({
    project,
    onShare,
}: {
    project: Project;
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
                {project.canManageShares ? (
                    <>
                        <DropdownMenuItem onSelect={onShare}>
                            <Share2 className="h-4 w-4" />
                            Share
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                    </>
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

function ShareProjectDialog({
    project,
    permissions,
    open,
    onOpenChange,
}: {
    project: Project;
    permissions: ProjectSharePermissionOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const form = useForm<{
        email: string;
        permission: ProjectSharePermission;
    }>({
        email: '',
        permission: 'read',
    });

    const submitShare = (event: FormEvent) => {
        event.preventDefault();

        form.post(projectShareUrl(project), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const updateShare = (
        code: string,
        permission: ProjectSharePermission,
    ) => {
        router.patch(
            projectShareUrl(project, code),
            { permission },
            { preserveScroll: true },
        );
    };

    const deleteShare = (code: string) => {
        router.delete(projectShareUrl(project, code), {
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Share project</DialogTitle>
                </DialogHeader>

                <form onSubmit={submitShare} className="grid gap-4">
                    <div className="grid gap-4 sm:grid-cols-[1fr_160px_auto]">
                        <div className="grid gap-2">
                            <Label htmlFor={`share-${project.id}-email`}>
                                Email address
                            </Label>
                            <Input
                                id={`share-${project.id}-email`}
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                placeholder="colleague@example.com"
                                required
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Permission</Label>
                            <Select
                                value={form.data.permission}
                                onValueChange={(value) =>
                                    form.setData(
                                        'permission',
                                        value as ProjectSharePermission,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {permissions.map((permission) => (
                                        <SelectItem
                                            key={permission.value}
                                            value={permission.value}
                                        >
                                            {permission.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.permission} />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" disabled={form.processing}>
                                Share
                            </Button>
                        </div>
                    </div>
                </form>

                <div className="grid gap-3">
                    {(project.shares ?? []).length > 0 ? (
                        (project.shares ?? []).map((share) => (
                            <div
                                key={share.code}
                                className="flex flex-col gap-3 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0">
                                    <div className="truncate font-medium">
                                        {share.name ?? share.email}
                                    </div>
                                    <div className="truncate text-sm text-muted-foreground">
                                        {share.pending
                                            ? `${share.email} - Pending account`
                                            : share.email}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Select
                                        value={share.permission}
                                        onValueChange={(value) =>
                                            updateShare(
                                                share.code,
                                                value as ProjectSharePermission,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-36">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent align="end">
                                            {permissions.map((permission) => (
                                                <SelectItem
                                                    key={permission.value}
                                                    value={permission.value}
                                                >
                                                    {permission.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Remove ${share.email}`}
                                        onClick={() => deleteShare(share.code)}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                            No one has direct access yet.
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                    >
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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

function projectActionUrl(
    project: Project,
    action: 'unpublish' | 'restore' | '',
) {
    const url = `/projects/${project.id}`;

    return action ? `${url}/${action}` : url;
}

function projectShareUrl(project: Project, share?: string) {
    const url = `/projects/${project.id}/shares`;

    return share ? `${url}/${share}` : url;
}

function emptyProjectsTitle(status: ProjectFilterStatus): string {
    if (status === 'archived') {
        return 'No archived projects';
    }

    if (status === 'all') {
        return 'No projects found';
    }

    return 'No projects yet';
}

function emptyProjectsText(status: ProjectFilterStatus): string {
    if (status === 'archived') {
        return 'Archived projects will appear here after you archive them from a project card.';
    }

    if (status === 'all') {
        return 'Try a different filter, or deploy a project from your agent.';
    }

    return 'Set up the MCP server above, then ask your agent to deploy a project.';
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
