import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Project } from '@/types';

import { projectActionUrl } from './utils';

export function DeleteProjectDialog({
    project,
    open,
    onOpenChange,
}: {
    project: Project;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [confirmationName, setConfirmationName] = useState('');
    const canDeleteProject = confirmationName === project.name;

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            setConfirmationName('');
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    action={projectActionUrl(project, 'permanent')}
                    method="delete"
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                    onSuccess={() => handleOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Delete permanently?</DialogTitle>
                                <DialogDescription>
                                    This action cannot be undone. This will
                                    permanently delete the archived project{' '}
                                    <strong className="select-text">
                                        "{project.name}"
                                    </strong>{' '}
                                    and its stored files.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4 py-4">
                                <InputError message={errors.project} />
                                <div className="grid gap-2">
                                    <Label htmlFor="delete-project-name">
                                        Type{' '}
                                        <strong className="select-text">
                                            "{project.name}"
                                        </strong>{' '}
                                        to confirm
                                    </Label>
                                    <Input
                                        id="delete-project-name"
                                        name="name"
                                        value={confirmationName}
                                        onChange={(event) =>
                                            setConfirmationName(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Enter project name"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>

                                <Button
                                    variant="destructive"
                                    type="submit"
                                    disabled={!canDeleteProject || processing}
                                >
                                    Delete permanently
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
