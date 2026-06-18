import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { UserApiToken } from '@/types';

type Props = {
    tokens: UserApiToken[];
    plainTextToken?: string | null;
};

export default function ApiTokens({ tokens, plainTextToken }: Props) {
    const form = useForm({ name: '' });

    const createToken = (event: FormEvent) => {
        event.preventDefault();
        form.post('/settings/api-tokens', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="API tokens" />

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="API tokens"
                    description="Create tokens for CLI and agent deploys"
                />

                {plainTextToken ? (
                    <div className="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-200/20 dark:bg-amber-950/30 dark:text-amber-100">
                        <div className="font-medium">New token</div>
                        <Input readOnly value={plainTextToken} />
                    </div>
                ) : null}

                <form onSubmit={createToken} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="token-name">Token name</Label>
                        <Input
                            id="token-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        <Plus /> Create token
                    </Button>
                </form>

                <div className="space-y-3">
                    {tokens.map((token) => (
                        <div
                            key={token.id}
                            className="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
                                    <KeyRound className="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div>
                                    <div className="font-medium">
                                        {token.name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Last used {token.lastUsedAt ?? 'never'}
                                    </div>
                                </div>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    router.delete(
                                        `/settings/api-tokens/${token.id}`,
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </div>
                    ))}

                    {tokens.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                            No API tokens have been created.
                        </p>
                    ) : null}
                </div>
            </div>
        </>
    );
}
