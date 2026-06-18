import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Boxes, Plus } from 'lucide-react';
import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Workspace } from '@/types';

type Props = {
    workspaces: Workspace[];
    canCreateWorkspace: boolean;
    quota: {
        workspaces: number;
        maxWorkspaces: number;
    };
};

type PageProps = {
    currentTeam?: {
        slug: string;
    } | null;
};

const workspacesUrl = (team: string) => `/${team}/workspaces`;
const workspaceUrl = (team: string, workspace: string) =>
    `/${team}/workspaces/${workspace}`;

export default function WorkspacesIndex({
    workspaces,
    canCreateWorkspace,
    quota,
}: Props) {
    const { currentTeam } = usePage<PageProps>().props;
    const teamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Workspaces" />

            <div className="flex flex-col gap-8 p-4 sm:p-6">
                <div className="flex flex-col gap-4 border-b pb-6 md:flex-row md:items-end md:justify-between">
                    <Heading
                        variant="small"
                        title="Workspaces"
                        description="Team collections for related projects"
                    />

                    <div className="flex items-center gap-3">
                        <div className="rounded-md border px-3 py-1.5 text-sm text-muted-foreground">
                            {quota.workspaces} of {quota.maxWorkspaces}{' '}
                            workspaces
                        </div>
                        {canCreateWorkspace ? (
                            <CreateWorkspaceDialog teamSlug={teamSlug} />
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {workspaces.map((workspace) => (
                        <div
                            key={workspace.id}
                            className="group overflow-hidden rounded-lg border bg-background"
                        >
                            <div className="relative aspect-video bg-gradient-to-br from-slate-900 via-cyan-700 to-lime-300">
                                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.18)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.18)_1px,transparent_1px)] bg-[size:24px_24px]" />
                                <div className="absolute right-4 bottom-4 left-4 rounded-md border border-white/25 bg-background/90 p-3 shadow-sm">
                                    <div className="flex items-center gap-2">
                                        <Boxes className="h-4 w-4 text-muted-foreground" />
                                        <div className="h-2 w-28 rounded-full bg-foreground/25" />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-between gap-3 p-4">
                                <div className="min-w-0">
                                    <div className="truncate font-medium">
                                        {workspace.name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {workspace.projectsCount} projects
                                        {workspace.roleLabel
                                            ? ` · ${workspace.roleLabel}`
                                            : ''}
                                    </div>
                                </div>

                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={workspaceUrl(
                                            teamSlug,
                                            workspace.slug,
                                        )}
                                    >
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ))}

                    {workspaces.length === 0 ? (
                        <div className="grid min-h-[260px] place-items-center rounded-lg border border-dashed p-8 text-center sm:col-span-2 xl:col-span-3">
                            <div className="max-w-sm space-y-4">
                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-md border bg-background">
                                    <Boxes className="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div className="space-y-1">
                                    <h2 className="font-medium">
                                        No workspaces yet
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Create a collection for related team
                                        projects.
                                    </p>
                                </div>
                                {canCreateWorkspace ? (
                                    <CreateWorkspaceDialog
                                        teamSlug={teamSlug}
                                    />
                                ) : null}
                            </div>
                        </div>
                    ) : null}
                </div>
            </div>
        </>
    );
}

function CreateWorkspaceDialog({ teamSlug }: { teamSlug: string }) {
    const id = useId();
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '' });

    const createWorkspace = (event: FormEvent) => {
        event.preventDefault();
        form.post(workspacesUrl(teamSlug), {
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
                    <Plus /> New workspace
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New workspace</DialogTitle>
                </DialogHeader>
                <form onSubmit={createWorkspace} className="grid gap-5">
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
                        <InputError
                            message={
                                (form.errors as Record<string, string>).quota
                            }
                        />
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
                            <Plus /> Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

WorkspacesIndex.layout = (props: PageProps) => ({
    breadcrumbs: [
        {
            title: 'Workspaces',
            href: props.currentTeam
                ? workspacesUrl(props.currentTeam.slug)
                : '/',
        },
    ],
});
