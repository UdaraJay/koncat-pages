import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import ProjectShareAlert from '@/components/project-share-alert';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request as requestMagicLink } from '@/routes/login/magic';
import type { ProjectShareContext, TeamInvitationContext } from '@/types';

type Props = {
    status?: string;
    teamInvitation?: TeamInvitationContext | null;
    projectShare?: ProjectShareContext | null;
};

export default function Login({ status, teamInvitation, projectShare }: Props) {
    return (
        <>
            <Head title="Log in" />

            {teamInvitation && (
                <TeamInvitationAlert
                    invitation={teamInvitation}
                    action="Log in"
                />
            )}

            {projectShare && (
                <ProjectShareAlert share={projectShare} action="Log in" />
            )}

            <Form
                action={requestMagicLink.url()}
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {teamInvitation && (
                                <input
                                    type="hidden"
                                    name="invitation"
                                    value={teamInvitation.code}
                                />
                            )}

                            {projectShare && (
                                <input
                                    type="hidden"
                                    name="project_share"
                                    value={projectShare.code}
                                />
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                size="lg"
                                tabIndex={2}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Send sign-in link
                            </Button>

                            <p className="mx-auto max-w-2xs text-center text-sm leading-5 text-muted-foreground">
                                By using Koncat, you agree to the{' '}
                                <Link
                                    href="/terms"
                                    className="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Terms
                                </Link>{' '}
                                and{' '}
                                <Link
                                    href="/privacy"
                                    className="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Privacy Policy
                                </Link>
                                .
                            </p>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Continue with your email',
    description: "Enter your email and we'll send a secure sign-in link.",
};
