import { Head, router } from '@inertiajs/react';
import { Plug, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type ConnectedApplication = {
    id: string;
    name: string;
    redirectUris: string[];
    scopes: string[];
    tokenCount: number;
    connectedAt: string | null;
    lastAuthorizedAt: string | null;
    expiresAt: string | null;
};

type Props = {
    applications: ConnectedApplication[];
};

export default function ConnectedApplications({ applications }: Props) {
    const disconnect = (application: ConnectedApplication) => {
        router.delete(`/settings/connected-applications/${application.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Connected applications" />

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="Connected applications"
                    description="Manage OAuth applications with access to your MCP server"
                />

                <div className="space-y-3">
                    {applications.map((application) => (
                        <div
                            key={application.id}
                            className="space-y-4 rounded-lg border bg-card p-4 text-card-foreground"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-background">
                                        <Plug className="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div className="min-w-0 space-y-1">
                                        <div className="font-medium">
                                            {application.name}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {formatDate(
                                                application.lastAuthorizedAt,
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => disconnect(application)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    Disconnect
                                </Button>
                            </div>

                            <div className="grid gap-3 text-sm">
                                <Metadata
                                    label="Scopes"
                                    value={
                                        application.scopes.length > 0
                                            ? application.scopes.join(', ')
                                            : 'none'
                                    }
                                />
                                <Metadata
                                    label="Callback"
                                    value={
                                        application.redirectUris[0] ??
                                        'Not provided'
                                    }
                                />
                                <Metadata
                                    label="Expires"
                                    value={formatDate(application.expiresAt)}
                                />
                            </div>

                            <Badge variant="secondary">
                                {application.tokenCount}{' '}
                                {application.tokenCount === 1
                                    ? 'active token'
                                    : 'active tokens'}
                            </Badge>
                        </div>
                    ))}

                    {applications.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                            No applications are connected.
                        </p>
                    ) : null}
                </div>
            </div>
        </>
    );
}

function Metadata({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1">
            <div className="text-xs font-medium text-muted-foreground">
                {label}
            </div>
            <div className="break-all">{value}</div>
        </div>
    );
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Never';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
