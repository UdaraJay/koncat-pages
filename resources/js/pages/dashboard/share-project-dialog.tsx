import { router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
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
import type {
    Project,
    ProjectSharePermission,
    ProjectSharePermissionOption,
} from '@/types';

import { projectShareUrl } from './utils';

export function ShareProjectDialog({
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

    const updateShare = (code: string, permission: ProjectSharePermission) => {
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
