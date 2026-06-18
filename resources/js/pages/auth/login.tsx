import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request as requestMagicLink } from '@/routes/login/magic';
import type { TeamInvitationContext } from '@/types';

type Props = {
    status?: string;
    teamInvitation?: TeamInvitationContext | null;
};

export default function Login({ status, teamInvitation }: Props) {
    return (
        <>
            <Head title="Log in" />

            {teamInvitation && (
                <TeamInvitationAlert
                    invitation={teamInvitation}
                    action="Log in"
                />
            )}

            <Form
                action={requestMagicLink.url()}
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
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

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    value="1"
                                    tabIndex={2}
                                />
                                <Label htmlFor="remember">
                                    Keep me signed in
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                tabIndex={3}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Send sign-in link
                            </Button>
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
    title: 'Log in to your account',
    description: "Enter your email and we'll send a secure sign-in link.",
};
