import { Form, Head, Link } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { login } from '@/routes';
import { verify } from '@/routes/login/code';

type Props = {
    challengeId: string;
    maskedEmail: string;
    expiresInMinutes: number;
};

export default function CheckEmail({
    challengeId,
    maskedEmail,
    expiresInMinutes,
}: Props) {
    const [code, setCode] = useState('');

    return (
        <>
            <Head title="Check your email" />

            <Form
                action={verify.url()}
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input
                            type="hidden"
                            name="challenge_id"
                            value={challengeId}
                        />

                        <div className="flex flex-col items-center justify-center space-y-3 text-center">
                            <InputOTP
                                name="code"
                                maxLength={OTP_MAX_LENGTH}
                                value={code}
                                onChange={setCode}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                                autoFocus
                            >
                                <InputOTPGroup>
                                    {Array.from(
                                        { length: OTP_MAX_LENGTH },
                                        (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                            />
                                        ),
                                    )}
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={
                                processing || code.length < OTP_MAX_LENGTH
                            }
                        >
                            {processing && <Spinner />}
                            Continue
                        </Button>

                        <p className="text-center text-sm text-muted-foreground">
                            Sent to {maskedEmail}. The link and code expire in{' '}
                            {expiresInMinutes} minutes.
                        </p>

                        <div className="text-center text-sm">
                            <Link
                                href={login()}
                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Use a different email
                            </Link>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

CheckEmail.layout = {
    title: 'Check your email',
    description: 'Open the secure link or enter the 6-digit code.',
};
