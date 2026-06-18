import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import ProjectShareAlert from '@/components/project-share-alert';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login/complete';
import type { ProjectShareContext, TeamInvitationContext } from '@/types';

type Props = {
    email: string;
    teamInvitation?: TeamInvitationContext | null;
    projectShare?: ProjectShareContext | null;
};

export default function CompleteAccount({
    email,
    teamInvitation,
    projectShare,
}: Props) {
    return (
        <>
            <Head title="Complete account" />

            {teamInvitation && (
                <TeamInvitationAlert
                    invitation={teamInvitation}
                    action="Log in"
                />
            )}

            {projectShare && (
                <ProjectShareAlert share={projectShare} action="Register" />
            )}

            <Form
                action={store.url()}
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autoFocus
                                autoComplete="name"
                                placeholder="Full name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <p className="text-sm text-muted-foreground">
                            This account will use {email}.
                        </p>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="complete-account-button"
                        >
                            {processing && <Spinner />}
                            Create account
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

CompleteAccount.layout = {
    title: 'Complete your account',
    description: 'Add your name to finish creating your account.',
};
