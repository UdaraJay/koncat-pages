import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    request as requestConfirmation,
    store as verifyConfirmation,
} from '@/routes/password/confirm';

type Props = {
    maskedEmail: string;
    challengeId?: string | null;
    status?: string;
};

export default function ConfirmAccess({
    maskedEmail,
    challengeId,
    status,
}: Props) {
    return (
        <>
            <Head title="Confirm access" />

            <div className="space-y-6">
                <Form action={requestConfirmation.url()} method="post">
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Send confirmation code
                        </Button>
                    )}
                </Form>

                {challengeId && (
                    <Form
                        action={verifyConfirmation.url()}
                        method="post"
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="challenge_id"
                                    value={challengeId}
                                />

                                <div className="grid gap-2">
                                    <Label htmlFor="code">
                                        Confirmation code
                                    </Label>
                                    <Input
                                        id="code"
                                        name="code"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={6}
                                        placeholder="123456"
                                        autoComplete="one-time-code"
                                    />
                                    <InputError message={errors.code} />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Confirm access
                                </Button>
                            </>
                        )}
                    </Form>
                )}

                {(status || maskedEmail) && (
                    <p className="text-center text-sm text-muted-foreground">
                        {status ?? `Codes are sent to ${maskedEmail}.`}
                    </p>
                )}
            </div>
        </>
    );
}

ConfirmAccess.layout = {
    title: 'Confirm access',
    description: 'Confirm this is you before continuing.',
};
