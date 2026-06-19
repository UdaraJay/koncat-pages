import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Project } from '@/types';

import { formatDate, formatNumber } from './utils';

export function ProjectAnalyticsDialog({
    project,
    open,
    onOpenChange,
}: {
    project: Project;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const analytics = project.analytics ?? {
        viewsTotal: 0,
        uniqueViewersTotal: 0,
        viewsLast7Days: 0,
        lastViewedAt: null,
        sharedUsers: [],
    };
    const sharedUsers = analytics.sharedUsers ?? [];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Analytics</DialogTitle>
                </DialogHeader>

                <div className="grid gap-5">
                    <div className="grid gap-3 sm:grid-cols-4">
                        <AnalyticsStat
                            label="Views"
                            value={formatNumber(analytics.viewsTotal)}
                        />
                        <AnalyticsStat
                            label="Viewers"
                            value={formatNumber(analytics.uniqueViewersTotal)}
                        />
                        <AnalyticsStat
                            label="This week"
                            value={formatNumber(analytics.viewsLast7Days)}
                        />
                        <AnalyticsStat
                            label="Shared"
                            value={formatNumber(project.sharesCount ?? 0)}
                        />
                    </div>

                    <div className="grid gap-1 text-sm">
                        <div className="font-medium">{project.name}</div>
                        <div className="text-muted-foreground">
                            {analytics.lastViewedAt
                                ? `Last viewed ${formatDate(analytics.lastViewedAt)}`
                                : 'No views yet'}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center justify-between gap-3">
                            <div className="font-medium">
                                People with access
                            </div>
                            <Badge variant="secondary">Sorted by views</Badge>
                        </div>

                        {sharedUsers.length > 0 ? (
                            <div className="overflow-hidden rounded-md border">
                                {sharedUsers.map((sharedUser) => (
                                    <div
                                        key={sharedUser.email}
                                        className="grid gap-3 border-b p-3 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto]"
                                    >
                                        <div className="min-w-0">
                                            <div className="truncate text-sm font-medium">
                                                {sharedUser.name ??
                                                    sharedUser.email}
                                            </div>
                                            <div className="truncate text-xs text-muted-foreground">
                                                {sharedUser.email}
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                            <Badge variant="outline">
                                                {sharedUser.permissionLabel}
                                            </Badge>
                                            {sharedUser.pending ? (
                                                <Badge variant="secondary">
                                                    Pending
                                                </Badge>
                                            ) : null}
                                            <div className="min-w-24 text-right text-sm">
                                                <span className="font-medium">
                                                    {formatNumber(
                                                        sharedUser.viewsTotal,
                                                    )}
                                                </span>{' '}
                                                {sharedUser.viewsTotal === 1
                                                    ? 'view'
                                                    : 'views'}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                                No directly shared people to break down yet.
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function AnalyticsStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border p-3">
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="mt-1 text-lg font-medium">{value}</div>
        </div>
    );
}
