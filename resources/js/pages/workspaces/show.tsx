import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    Eye,
    FileArchive,
    FolderPlus,
    MoreHorizontal,
    MoveRight,
    Pencil,
    Rocket,
    Settings2,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useId, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import EditProjectDialog from '@/components/edit-project-dialog';
import InputError from '@/components/input-error';
import MoveProjectDialog from '@/components/move-project-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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
import type {
    Project,
    ProjectMoveTarget,
    TeamMember,
    Workspace,
    WorkspaceMember,
    WorkspacePermissions,
    WorkspaceRoleOption,
} from '@/types';

type Props = {
    workspace: Workspace;
    members: WorkspaceMember[];
    projects: Project[];
    permissions: WorkspacePermissions;
    availableRoles: WorkspaceRoleOption[];
    teamMembers: Pick<TeamMember, 'id' | 'name' | 'email'>[];
    moveTargets: ProjectMoveTarget[];
    quota: {
        projects: number;
        maxProjects: number;
    };
};

type PageProps = {
    currentTeam?: {
        slug: string;
    } | null;
};

const workspaceUrl = (team: string, workspace: string) =>
    `/${team}/workspaces/${workspace}`;
const membersUrl = (team: string, workspace: string) =>
    `${workspaceUrl(team, workspace)}/members`;
const memberUrl = (team: string, workspace: string, user: string) =>
    `${membersUrl(team, workspace)}/${user}`;
const projectsUrl = (team: string, workspace: string) =>
    `${workspaceUrl(team, workspace)}/projects`;
const projectUrl = (team: string, workspace: string, project: string) =>
    `${projectsUrl(team, workspace)}/${project}`;
const deploymentUrl = (team: string, workspace: string, project: string) =>
    `${projectUrl(team, workspace, project)}/deployments`;

export default function WorkspaceShow({
    workspace,
    members,
    projects,
    permissions,
    availableRoles,
    teamMembers,
    moveTargets,
    quota,
}: Props) {
    const { currentTeam } = usePage<PageProps>().props;
    const teamSlug = currentTeam?.slug ?? '';

    const deployProject = (project: Project, file?: File) => {
        if (!file) {
            return;
        }

        router.post(
            deploymentUrl(teamSlug, workspace.slug, project.id),
            { archive: file },
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title={workspace.name} />

            <div className="flex flex-col gap-8 p-4 sm:p-6">
                <header className="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <div className="text-sm font-medium text-muted-foreground">
                            Workspace
                        </div>
                        <h1 className="text-3xl font-semibold tracking-normal">
                            {workspace.name}
                        </h1>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Badge variant="secondary">
                            {quota.projects} / {quota.maxProjects} projects
                        </Badge>
                        {workspace.roleLabel ? (
                            <Badge variant="outline">
                                {workspace.roleLabel}
                            </Badge>
                        ) : null}
                    </div>
                </header>

                <section className="space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <FolderPlus className="h-4 w-4 text-muted-foreground" />
                            <h2 className="font-medium">Projects</h2>
                        </div>
                        {permissions.canCreateProject ? (
                            <CreateProjectDialog
                                teamSlug={teamSlug}
                                workspace={workspace}
                            />
                        ) : null}
                    </div>

                    {projects.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {projects.map((project) => (
                                <ProjectTile
                                    key={project.id}
                                    project={project}
                                    canDeploy={permissions.canDeployProject}
                                    canEdit={permissions.canUpdateProject}
                                    canMove={permissions.canDeleteProject}
                                    moveTargets={moveTargets}
                                    onDeploy={deployProject}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="grid min-h-[260px] place-items-center rounded-lg border border-dashed p-8 text-center">
                            <div className="max-w-sm space-y-4">
                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-md border bg-background">
                                    <FolderPlus className="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div className="space-y-1">
                                    <h3 className="font-medium">
                                        No projects here yet
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        Add a project to this workspace
                                        collection.
                                    </p>
                                </div>
                                {permissions.canCreateProject ? (
                                    <CreateProjectDialog
                                        teamSlug={teamSlug}
                                        workspace={workspace}
                                    />
                                ) : null}
                            </div>
                        </div>
                    )}
                </section>

                <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <section className="space-y-4">
                        <div className="flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <Users className="h-4 w-4 text-muted-foreground" />
                                <h2 className="font-medium">Members</h2>
                            </div>
                            {permissions.canAddMember ? (
                                <InviteMemberDialog
                                    teamSlug={teamSlug}
                                    workspace={workspace}
                                    availableRoles={availableRoles}
                                    teamMembers={teamMembers}
                                />
                            ) : null}
                        </div>

                        <div className="grid gap-3">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex flex-col gap-3 rounded-lg border p-4 md:flex-row md:items-center md:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="truncate font-medium">
                                            {member.name}
                                        </div>
                                        <div className="truncate text-sm text-muted-foreground">
                                            {member.email}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="secondary">
                                            {member.role_label}
                                        </Badge>
                                        {permissions.canRemoveMember &&
                                        member.role !== 'owner' ? (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    router.delete(
                                                        memberUrl(
                                                            teamSlug,
                                                            workspace.slug,
                                                            member.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    {permissions.canUpdateWorkspace ? (
                        <section className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <Settings2 className="h-4 w-4 text-muted-foreground" />
                                    <h2 className="font-medium">Settings</h2>
                                </div>
                                <EditWorkspaceDialog
                                    teamSlug={teamSlug}
                                    workspace={workspace}
                                />
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">
                                    Workspace name
                                </div>
                                <div className="mt-1 truncate font-medium">
                                    {workspace.name}
                                </div>
                            </div>
                        </section>
                    ) : null}
                </div>
            </div>
        </>
    );
}

function CreateProjectDialog({
    teamSlug,
    workspace,
}: {
    teamSlug: string;
    workspace: Workspace;
}) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', slug: '', description: '' });

    const createProject = (event: FormEvent) => {
        event.preventDefault();
        form.post(projectsUrl(teamSlug, workspace.slug), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <FolderPlus /> New project
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>New project</DialogTitle>
                </DialogHeader>
                <form onSubmit={createProject} className="grid gap-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`${id}-project-name`}>Name</Label>
                            <Input
                                id={`${id}-project-name`}
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`${id}-project-slug`}>Path</Label>
                            <Input
                                id={`${id}-project-slug`}
                                value={form.data.slug}
                                onChange={(event) =>
                                    form.setData('slug', event.target.value)
                                }
                                placeholder="Generated automatically"
                            />
                            <InputError message={form.errors.slug} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-project-description`}>
                            Description
                        </Label>
                        <Input
                            id={`${id}-project-description`}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <InputError
                        message={(form.errors as Record<string, string>).quota}
                    />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            <FolderPlus /> Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function InviteMemberDialog({
    teamSlug,
    workspace,
    availableRoles,
    teamMembers,
}: {
    teamSlug: string;
    workspace: Workspace;
    availableRoles: WorkspaceRoleOption[];
    teamMembers: Pick<TeamMember, 'id' | 'name' | 'email'>[];
}) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const form = useForm({ email: '', role: 'member' });
    const listId = `${id}-team-member-emails`;

    const addMember = (event: FormEvent) => {
        event.preventDefault();
        form.post(membersUrl(teamSlug, workspace.slug), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <UserPlus /> Add member
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add workspace member</DialogTitle>
                </DialogHeader>
                <form onSubmit={addMember} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-member-email`}>
                            Team member email
                        </Label>
                        <Input
                            id={`${id}-member-email`}
                            list={listId}
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            required
                        />
                        <datalist id={listId}>
                            {teamMembers.map((member) => (
                                <option key={member.id} value={member.email}>
                                    {member.name}
                                </option>
                            ))}
                        </datalist>
                        <InputError message={form.errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-member-role`}>Role</Label>
                        <select
                            id={`${id}-member-role`}
                            className="h-9 rounded-md border bg-transparent px-3 text-sm"
                            value={form.data.role}
                            onChange={(event) =>
                                form.setData('role', event.target.value)
                            }
                        >
                            {availableRoles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            <UserPlus /> Add
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditWorkspaceDialog({
    teamSlug,
    workspace,
}: {
    teamSlug: string;
    workspace: Workspace;
}) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const form = useForm({ name: workspace.name });

    const updateWorkspace = (event: FormEvent) => {
        event.preventDefault();
        form.patch(workspaceUrl(teamSlug, workspace.slug), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Settings2 /> Edit
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit workspace</DialogTitle>
                </DialogHeader>
                <form onSubmit={updateWorkspace} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-workspace-name`}>
                            Workspace name
                        </Label>
                        <Input
                            id={`${id}-workspace-name`}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ProjectTile({
    project,
    canDeploy,
    canEdit,
    canMove,
    moveTargets,
    onDeploy,
}: {
    project: Project;
    canDeploy: boolean;
    canEdit: boolean;
    canMove: boolean;
    moveTargets: ProjectMoveTarget[];
    onDeploy: (project: Project, file?: File) => void;
}) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [moveDialogOpen, setMoveDialogOpen] = useState(false);
    const [analyticsDialogOpen, setAnalyticsDialogOpen] = useState(false);
    const uploadInputRef = useRef<HTMLInputElement>(null);

    return (
        <>
            <ProjectAnalyticsDialog
                project={project}
                open={analyticsDialogOpen}
                onOpenChange={setAnalyticsDialogOpen}
            />
            <EditProjectDialog
                key={projectEditDialogKey(project)}
                project={project}
                open={editDialogOpen}
                onOpenChange={setEditDialogOpen}
            />
            <MoveProjectDialog
                key={projectMoveDialogKey(project)}
                project={project}
                targets={moveTargets}
                open={moveDialogOpen}
                onOpenChange={setMoveDialogOpen}
            />
            <article
                id={`project-${project.slug}`}
                className="overflow-hidden rounded-lg border bg-background"
            >
                <div className="relative aspect-video bg-gradient-to-br from-zinc-900 via-sky-600 to-emerald-300">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_25%,rgba(255,255,255,.28),transparent_28%),linear-gradient(rgba(255,255,255,.18)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.18)_1px,transparent_1px)] bg-[size:100%_100%,24px_24px,24px_24px]" />
                    <div className="absolute right-4 bottom-4 left-4 rounded-md border border-white/25 bg-background/90 p-3 shadow-sm">
                        <div className="h-2 w-2/3 rounded-full bg-foreground/25" />
                        <div className="mt-2 h-2 w-1/2 rounded-full bg-foreground/15" />
                    </div>
                    <div className="absolute top-3 right-3">
                        <ProjectTileMenu
                            project={project}
                            canDeploy={canDeploy}
                            canEdit={canEdit}
                            canMove={canMove}
                            hasMoveTargets={moveTargets.length > 0}
                            onAnalytics={() => setAnalyticsDialogOpen(true)}
                            onDeploy={() => uploadInputRef.current?.click()}
                            onEdit={() => setEditDialogOpen(true)}
                            onMove={() => setMoveDialogOpen(true)}
                        />
                        <input
                            ref={uploadInputRef}
                            type="file"
                            accept=".zip,application/zip"
                            className="sr-only"
                            onChange={(event) => {
                                onDeploy(
                                    project,
                                    event.currentTarget.files?.[0],
                                );
                                event.currentTarget.value = '';
                            }}
                        />
                    </div>
                </div>

                <div className="grid gap-4 p-4">
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
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {project.currentDeployment ? 'Live' : 'Draft'}
                            </Badge>
                        </div>
                        {project.description ? (
                            <p className="line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">
                                {project.description}
                            </p>
                        ) : null}
                    </div>

                    <div className="text-xs text-muted-foreground">
                        {project.currentDeployment
                            ? `${project.currentDeployment.fileCount} files · ${project.currentDeployment.totalBytes} bytes`
                            : 'Not deployed'}
                    </div>

                    <ProjectCardStats project={project} />

                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={project.url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ArrowUpRight /> Open
                            </a>
                        </Button>
                        <FileArchive className="ml-auto h-4 w-4 text-muted-foreground" />
                    </div>
                </div>
            </article>
        </>
    );
}

function ProjectCardStats({ project }: { project: Project }) {
    const viewsTotal = project.analytics?.viewsTotal ?? 0;

    return (
        <div className="grid gap-1 text-xs text-muted-foreground">
            <div className="flex items-center gap-1.5">
                <Eye className="h-3.5 w-3.5" />
                <span>
                    {viewsTotal.toLocaleString()}{' '}
                    {viewsTotal === 1 ? 'view' : 'views'}
                </span>
            </div>
            <div className="flex items-center gap-1.5">
                <Users className="h-3.5 w-3.5" />
                <span>
                    Shared with {(project.sharesCount ?? 0).toLocaleString()}{' '}
                    {(project.sharesCount ?? 0) === 1 ? 'person' : 'people'}
                </span>
            </div>
        </div>
    );
}

function ProjectTileMenu({
    project,
    canDeploy,
    canEdit,
    canMove,
    hasMoveTargets,
    onDeploy,
    onAnalytics,
    onEdit,
    onMove,
}: {
    project: Project;
    canDeploy: boolean;
    canEdit: boolean;
    canMove: boolean;
    hasMoveTargets: boolean;
    onDeploy: () => void;
    onAnalytics: () => void;
    onEdit: () => void;
    onMove: () => void;
}) {
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
                {canEdit ? (
                    <DropdownMenuItem onSelect={onEdit}>
                        <Pencil className="h-4 w-4" />
                        Edit
                    </DropdownMenuItem>
                ) : null}
                {canMove ? (
                    <DropdownMenuItem
                        disabled={!hasMoveTargets}
                        onSelect={onMove}
                    >
                        <MoveRight className="h-4 w-4" />
                        Move
                    </DropdownMenuItem>
                ) : null}
                {(canEdit || canMove) && canDeploy ? (
                    <DropdownMenuSeparator />
                ) : null}
                {canDeploy ? (
                    <DropdownMenuItem onSelect={onDeploy}>
                        <Rocket className="h-4 w-4" />
                        Deploy
                    </DropdownMenuItem>
                ) : null}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function ProjectAnalyticsDialog({
    project,
    open,
    onOpenChange,
}: {
    project: Project;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const analytics = project.analytics ?? {
        viewsTotal: 0,
        uniqueViewersTotal: 0,
        viewsLast7Days: 0,
        lastViewedAt: null,
        sharedUsers: [],
    };
    const sharedUsers = analytics.sharedUsers ?? [];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Analytics</DialogTitle>
                </DialogHeader>

                <div className="grid gap-5">
                    <div className="grid gap-3 sm:grid-cols-4">
                        <AnalyticsStat
                            label="Views"
                            value={formatNumber(analytics.viewsTotal)}
                        />
                        <AnalyticsStat
                            label="Viewers"
                            value={formatNumber(analytics.uniqueViewersTotal)}
                        />
                        <AnalyticsStat
                            label="This week"
                            value={formatNumber(analytics.viewsLast7Days)}
                        />
                        <AnalyticsStat
                            label="Shared"
                            value={formatNumber(project.sharesCount ?? 0)}
                        />
                    </div>

                    <div className="grid gap-1 text-sm">
                        <div className="font-medium">{project.name}</div>
                        <div className="text-muted-foreground">
                            {analytics.lastViewedAt
                                ? `Last viewed ${formatDate(analytics.lastViewedAt)}`
                                : 'No views yet'}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center justify-between gap-3">
                            <div className="font-medium">
                                People with access
                            </div>
                            <Badge variant="secondary">
                                Sorted by views
                            </Badge>
                        </div>

                        {sharedUsers.length > 0 ? (
                            <div className="overflow-hidden rounded-md border">
                                {sharedUsers.map((sharedUser) => (
                                    <div
                                        key={sharedUser.email}
                                        className="grid gap-3 border-b p-3 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto]"
                                    >
                                        <div className="min-w-0">
                                            <div className="truncate text-sm font-medium">
                                                {sharedUser.name ??
                                                    sharedUser.email}
                                            </div>
                                            <div className="truncate text-xs text-muted-foreground">
                                                {sharedUser.email}
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                            <Badge variant="outline">
                                                {sharedUser.permissionLabel}
                                            </Badge>
                                            {sharedUser.pending ? (
                                                <Badge variant="secondary">
                                                    Pending
                                                </Badge>
                                            ) : null}
                                            <div className="min-w-24 text-right text-sm">
                                                <span className="font-medium">
                                                    {formatNumber(
                                                        sharedUser.viewsTotal,
                                                    )}
                                                </span>{' '}
                                                {sharedUser.viewsTotal === 1
                                                    ? 'view'
                                                    : 'views'}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                                No directly shared people to break down yet.
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function AnalyticsStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border p-3">
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="mt-1 text-lg font-medium">{value}</div>
        </div>
    );
}

function projectMoveDialogKey(project: Project) {
    return [
        project.id,
        project.ownerType,
        project.team?.id,
        project.workspace?.id,
        project.slug,
    ].join(':');
}

function projectEditDialogKey(project: Project) {
    return [project.id, project.name, project.description ?? ''].join(':');
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

function formatNumber(value: number): string {
    return value.toLocaleString();
}

WorkspaceShow.layout = (props: PageProps & { workspace?: Workspace }) => ({
    breadcrumbs: [
        {
            title: 'Workspaces',
            href: props.currentTeam
                ? `/${props.currentTeam.slug}/workspaces`
                : '/',
        },
        {
            title: props.workspace?.name ?? 'Workspace',
            href:
                props.currentTeam && props.workspace
                    ? workspaceUrl(props.currentTeam.slug, props.workspace.slug)
                    : '/',
        },
    ],
});
