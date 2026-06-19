import { Head } from '@inertiajs/react';
import { BarChart3, Boxes, FileArchive } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';

type Meter = {
    used: number;
    limit: number;
};

type WorkspaceUsage = {
    id: string;
    name: string;
    projects: Meter;
};

type Usage = {
    account: {
        projects: Meter;
    };
    team: {
        id: string;
        name: string;
        isPersonal: boolean;
        canSeeTeamTotals: boolean;
        projects?: Meter;
        workspaces?: Meter;
        visibleWorkspaces: WorkspaceUsage[];
    } | null;
    deploymentLimits: {
        files: number;
        bytes: number;
        fileBytes: number;
    };
};

type Props = {
    usage: Usage;
};

export default function Usage({ usage }: Props) {
    return (
        <>
            <Head title="Usage" />

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="Usage"
                    description="Review your current usage against account and team limits"
                />

                <section className="space-y-3">
                    <SectionHeader icon={BarChart3} title="Account" />
                    <UsageMeter
                        label="Personal projects"
                        meter={usage.account.projects}
                    />
                </section>

                {usage.team ? (
                    <section className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <SectionHeader icon={Boxes} title="Current team" />
                            <Badge variant="secondary">
                                {usage.team.isPersonal
                                    ? 'Personal'
                                    : usage.team.name}
                            </Badge>
                        </div>

                        {usage.team.canSeeTeamTotals &&
                        usage.team.projects &&
                        usage.team.workspaces ? (
                            <div className="grid gap-3 sm:grid-cols-2">
                                <UsageMeter
                                    label="Team projects"
                                    meter={usage.team.projects}
                                />
                                <UsageMeter
                                    label="Workspaces"
                                    meter={usage.team.workspaces}
                                />
                            </div>
                        ) : (
                            <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                Team-wide totals are limited to team admins.
                                Visible workspace usage is shown below.
                            </p>
                        )}

                        <div className="space-y-2">
                            {usage.team.visibleWorkspaces.map((workspace) => (
                                <UsageMeter
                                    key={workspace.id}
                                    label={workspace.name}
                                    detail="Workspace projects"
                                    meter={workspace.projects}
                                />
                            ))}

                            {usage.team.visibleWorkspaces.length === 0 ? (
                                <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                    No visible workspaces.
                                </p>
                            ) : null}
                        </div>
                    </section>
                ) : null}

                <section className="space-y-3">
                    <SectionHeader
                        icon={FileArchive}
                        title="Deployment limits"
                    />
                    <div className="grid gap-2 sm:grid-cols-3">
                        <LimitPill
                            label="Files"
                            value={formatLimit(usage.deploymentLimits.files)}
                        />
                        <LimitPill
                            label="Archive"
                            value={formatByteLimit(
                                usage.deploymentLimits.bytes,
                            )}
                        />
                        <LimitPill
                            label="Single file"
                            value={formatByteLimit(
                                usage.deploymentLimits.fileBytes,
                            )}
                        />
                    </div>
                </section>
            </div>
        </>
    );
}

function SectionHeader({
    icon: Icon,
    title,
}: {
    icon: LucideIcon;
    title: string;
}) {
    return (
        <div className="flex items-center gap-2">
            <Icon className="h-4 w-4 text-muted-foreground" />
            <h2 className="font-medium">{title}</h2>
        </div>
    );
}

function UsageMeter({
    label,
    detail,
    meter,
    formatter = formatNumber,
}: {
    label: string;
    detail?: string;
    meter: Meter;
    formatter?: (value: number) => string;
}) {
    return (
        <div className="rounded-lg border bg-card p-4 text-card-foreground">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="truncate font-medium">{label}</div>
                    {detail ? (
                        <div className="text-sm text-muted-foreground">
                            {detail}
                        </div>
                    ) : null}
                </div>
                <div className="shrink-0 text-right text-sm text-muted-foreground">
                    {formatMeter(meter, formatter)}
                </div>
            </div>
            <MeterBar meter={meter} />
        </div>
    );
}

function MeterBar({ meter }: { meter: Meter }) {
    const percent =
        meter.limit > 0 ? Math.min((meter.used / meter.limit) * 100, 100) : 100;

    return (
        <div className="mt-4 h-2 overflow-hidden rounded-full bg-muted">
            <div
                className="h-full rounded-full bg-foreground transition-[width]"
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}

function LimitPill({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border p-3">
            <div className="text-xs font-medium text-muted-foreground">
                {label}
            </div>
            <div className="mt-1 font-medium">{value}</div>
        </div>
    );
}

function formatMeter(
    meter: Meter,
    formatter: (value: number) => string = formatNumber,
) {
    const used = formatter(meter.used);

    if (meter.limit <= 0) {
        return `${used} of unlimited`;
    }

    return `${used} of ${formatter(meter.limit)}`;
}

function formatLimit(limit: number) {
    return limit > 0 ? formatNumber(limit) : 'Unlimited';
}

function formatByteLimit(limit: number) {
    return limit > 0 ? formatBytes(limit) : 'Unlimited';
}

function formatNumber(value: number) {
    return new Intl.NumberFormat().format(value);
}

function formatBytes(value: number) {
    if (value === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const index = Math.min(
        Math.floor(Math.log(value) / Math.log(1024)),
        units.length - 1,
    );
    const amount = value / 1024 ** index;

    return `${new Intl.NumberFormat(undefined, {
        maximumFractionDigits: amount >= 10 ? 0 : 1,
    }).format(amount)} ${units[index]}`;
}
