import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { consume } from '@/routes/login/magic';

type Props = {
    challengeId: string;
    token: string;
    maskedEmail: string;
    purpose: 'login' | 'confirm-access' | 'email-change';
};

const copy = {
    login: {
        title: 'Continue signing in',
        description: 'Confirm this browser to finish signing in.',
        button: 'Continue',
    },
    'confirm-access': {
        title: 'Confirm access',
        description: 'Confirm this browser to continue to a secure area.',
        button: 'Confirm access',
    },
    'email-change': {
        title: 'Confirm email address',
        description: 'Confirm this browser to use this email address.',
        button: 'Confirm email',
    },
};

export default function ContinueLogin({
    challengeId,
    token,
    maskedEmail,
    purpose,
}: Props) {
    const content = copy[purpose] ?? copy.login;

    return (
        <>
            <Head title={content.title} />

            <Form
                action={consume.url(challengeId)}
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="token" value={token} />

                        {errors.token && (
                            <p className="text-center text-sm text-destructive">
                                {errors.token}
                            </p>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            autoFocus
                        >
                            {processing && <Spinner />}
                            {content.button}
                        </Button>

                        <p className="text-center text-sm text-muted-foreground">
                            This request is for {maskedEmail}.
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

ContinueLogin.layout = {
    title: 'Continue',
    description: 'Confirm this sign-in request.',
};
