import { useForm } from '@inertiajs/react';
import { MoveRight } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Project, ProjectMoveTarget } from '@/types';

type MoveDestination = {
    value: string;
    label: string;
    ownerType: 'user' | 'team';
    teamId: string | null;
    workspaceId: string | null;
};

export default function MoveProjectDialog({
    project,
    targets,
    open,
    onOpenChange,
}: {
    project: Project;
    targets: ProjectMoveTarget[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const destinations = projectMoveDestinations(targets);
    const initialDestination =
        currentProjectDestination(project, destinations) ?? destinations[0];
    const form = useForm<{
        owner_type: 'user' | 'team';
        team_id: string | null;
        workspace_id: string | null;
        slug: string;
    }>({
        owner_type: initialDestination?.ownerType ?? 'user',
        team_id: initialDestination?.teamId ?? null,
        workspace_id: initialDestination?.workspaceId ?? null,
        slug: project.slug,
    });
    const selectedValue = moveDestinationValue(
        form.data.owner_type,
        form.data.team_id,
        form.data.workspace_id,
    );

    const selectDestination = (value: string) => {
        const destination = destinations.find(
            (candidate) => candidate.value === value,
        );

        if (!destination) {
            return;
        }

        form.setData('owner_type', destination.ownerType);
        form.setData('team_id', destination.teamId);
        form.setData('workspace_id', destination.workspaceId);
    };

    const moveProject = (event: FormEvent) => {
        event.preventDefault();

        form.patch(projectMoveUrl(project), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Move project</DialogTitle>
                </DialogHeader>

                <form onSubmit={moveProject} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label>Destination</Label>
                        <Select
                            value={selectedValue}
                            onValueChange={selectDestination}
                            disabled={destinations.length === 0}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {destinations.map((destination) => (
                                    <SelectItem
                                        key={destination.value}
                                        value={destination.value}
                                    >
                                        {destination.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.owner_type} />
                        <InputError message={form.errors.team_id} />
                        <InputError message={form.errors.workspace_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`move-${project.id}-slug`}>Path</Label>
                        <Input
                            id={`move-${project.id}-slug`}
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <InputError
                        message={(form.errors as Record<string, string>).quota}
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing || destinations.length === 0
                            }
                        >
                            <MoveRight /> Move
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function projectMoveUrl(project: Project) {
    return `/projects/${project.id}/move`;
}

function projectMoveDestinations(
    targets: ProjectMoveTarget[],
): MoveDestination[] {
    return targets.flatMap((target) => {
        if (target.type === 'user') {
            return [
                {
                    value: moveDestinationValue('user', null, null),
                    label: target.label,
                    ownerType: 'user' as const,
                    teamId: null,
                    workspaceId: null,
                },
            ];
        }

        const destinations: MoveDestination[] = [];

        if (target.canCreateProject) {
            destinations.push({
                value: moveDestinationValue('team', target.id, null),
                label: `${target.label} / Team projects`,
                ownerType: 'team',
                teamId: target.id,
                workspaceId: null,
            });
        }

        target.workspaces.forEach((workspace) => {
            destinations.push({
                value: moveDestinationValue('team', target.id, workspace.id),
                label: `${target.label} / ${workspace.name}`,
                ownerType: 'team',
                teamId: target.id,
                workspaceId: workspace.id,
            });
        });

        return destinations;
    });
}

function currentProjectDestination(
    project: Project,
    destinations: MoveDestination[],
): MoveDestination | undefined {
    const value =
        project.ownerType === 'team'
            ? moveDestinationValue(
                  'team',
                  project.team?.id ?? null,
                  project.workspace?.id ?? null,
              )
            : moveDestinationValue('user', null, null);

    return destinations.find((destination) => destination.value === value);
}

function moveDestinationValue(
    ownerType: 'user' | 'team',
    teamId: string | null,
    workspaceId: string | null,
) {
    return ownerType === 'user'
        ? 'user'
        : `team:${teamId ?? ''}:${workspaceId ?? ''}`;
}
