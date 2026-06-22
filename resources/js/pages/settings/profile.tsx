import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { verify as verifyEmailChange } from '@/routes/profile/email';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    emailChangeChallengeId,
    pendingEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    emailChangeChallengeId?: string | null;
    pendingEmail?: string | null;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    action={ProfileController.update.url()}
                    method="patch"
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            {emailChangeChallengeId && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Confirm new email"
                        description={`Enter the code sent to ${pendingEmail}`}
                    />

                    <Form
                        action={verifyEmailChange.url()}
                        method="post"
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="challenge_id"
                                    value={emailChangeChallengeId}
                                />

                                <div className="grid gap-2">
                                    <Label htmlFor="email_change_code">
                                        Confirmation code
                                    </Label>
                                    <Input
                                        id="email_change_code"
                                        name="code"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={6}
                                        placeholder="123456"
                                        autoComplete="one-time-code"
                                    />
                                    <InputError message={errors.code} />
                                </div>

                                <Button disabled={processing}>
                                    Confirm email
                                </Button>
                            </>
                        )}
                    </Form>

                    {status === 'email-change-link-sent' && (
                        <p className="text-sm font-medium text-green-600">
                            A confirmation link and code were sent to your new
                            email address.
                        </p>
                    )}
                </div>
            )}

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
