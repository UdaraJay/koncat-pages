<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAnalyticsEvent;
use App\Models\ProjectShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectAnalytics
{
    public const EVENT_PROJECT_VIEW = 'project.view';

    public function recordProjectView(Project $project, User $user, string $path): ProjectAnalyticsEvent
    {
        return $project->analyticsEvents()->create([
            'user_id' => $user->id,
            'deployment_id' => $project->current_deployment_id,
            'event_type' => self::EVENT_PROJECT_VIEW,
            'path' => $path,
            'occurred_at' => now(),
            'properties' => null,
        ]);
    }

    /**
     * @param  EloquentCollection<int, Project>|Collection<int, Project>  $projects
     * @return array<string, array{viewsTotal: int, uniqueViewersTotal: int, viewsLast7Days: int, lastViewedAt: string|null, dailyViews: array<int, array{date: string, views: int}>, sharedUsers: array<int, array{email: string, name: string|null, permissionLabel: string, pending: bool, viewsTotal: int, lastViewedAt: string|null}>}>
     */
    public function viewSummaries(EloquentCollection|Collection $projects): array
    {
        $projectIds = $projects
            ->pluck('id')
            ->filter()
            ->values();

        if ($projectIds->isEmpty()) {
            return [];
        }

        $sevenDaysAgo = now()->subDays(7);
        $today = now()->startOfDay();
        $dailyStart = $today->copy()->subDays(13);
        $dailyDates = collect(range(0, 13))
            ->map(fn (int $offset) => $dailyStart->copy()->addDays($offset)->toDateString());

        $summaries = ProjectAnalyticsEvent::query()
            ->whereIn('project_id', $projectIds)
            ->where('event_type', self::EVENT_PROJECT_VIEW)
            ->select('project_id')
            ->selectRaw('COUNT(*) as views_total')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_viewers_total')
            ->selectRaw('SUM(CASE WHEN occurred_at >= ? THEN 1 ELSE 0 END) as views_last_7_days', [$sevenDaysAgo])
            ->selectRaw('MAX(occurred_at) as last_viewed_at')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');
        $dailySummaries = ProjectAnalyticsEvent::query()
            ->whereIn('project_id', $projectIds)
            ->where('event_type', self::EVENT_PROJECT_VIEW)
            ->where('occurred_at', '>=', $dailyStart)
            ->select('project_id')
            ->selectRaw('DATE(occurred_at) as viewed_on')
            ->selectRaw('COUNT(*) as views')
            ->groupBy(['project_id', 'viewed_on'])
            ->get()
            ->groupBy('project_id')
            ->map(fn (Collection $views) => $views->keyBy('viewed_on'));
        $sharedUserSummaries = $this->sharedUserSummaries($projects);

        return $projectIds
            ->mapWithKeys(function (string $projectId) use ($dailyDates, $dailySummaries, $summaries, $sharedUserSummaries) {
                $summary = $summaries->get($projectId);
                $lastViewedAt = $summary?->last_viewed_at
                    ? Carbon::parse($summary->last_viewed_at)->toISOString()
                    : null;
                $projectDailyViews = $dailySummaries->get($projectId, collect());

                return [$projectId => [
                    'viewsTotal' => (int) ($summary?->views_total ?? 0),
                    'uniqueViewersTotal' => (int) ($summary?->unique_viewers_total ?? 0),
                    'viewsLast7Days' => (int) ($summary?->views_last_7_days ?? 0),
                    'lastViewedAt' => $lastViewedAt,
                    'dailyViews' => $dailyDates
                        ->map(fn (string $date) => [
                            'date' => $date,
                            'views' => (int) ($projectDailyViews->get($date)?->views ?? 0),
                        ])
                        ->values()
                        ->all(),
                    'sharedUsers' => $sharedUserSummaries[$projectId] ?? [],
                ]];
            })
            ->all();
    }

    /**
     * @return array{viewsTotal: int, uniqueViewersTotal: int, viewsLast7Days: int, lastViewedAt: string|null, dailyViews: array<int, array{date: string, views: int}>, sharedUsers: array<int, array{email: string, name: string|null, permissionLabel: string, pending: bool, viewsTotal: int, lastViewedAt: string|null}>}
     */
    public function emptySummary(): array
    {
        $today = now()->startOfDay();

        return [
            'viewsTotal' => 0,
            'uniqueViewersTotal' => 0,
            'viewsLast7Days' => 0,
            'lastViewedAt' => null,
            'dailyViews' => collect(range(0, 13))
                ->map(fn (int $offset) => [
                    'date' => $today->copy()->subDays(13 - $offset)->toDateString(),
                    'views' => 0,
                ])
                ->values()
                ->all(),
            'sharedUsers' => [],
        ];
    }

    /**
     * @param  EloquentCollection<int, Project>|Collection<int, Project>  $projects
     * @return array<string, array<int, array{email: string, name: string|null, permissionLabel: string, pending: bool, viewsTotal: int, lastViewedAt: string|null}>>
     */
    protected function sharedUserSummaries(EloquentCollection|Collection $projects): array
    {
        $shares = $projects
            ->flatMap(function (Project $project) {
                return $project->relationLoaded('shares')
                    ? $project->shares
                    : collect();
            });

        if ($shares->isEmpty()) {
            return [];
        }

        $userIds = $shares
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $viewCounts = $userIds->isEmpty()
            ? collect()
            : ProjectAnalyticsEvent::query()
                ->whereIn('project_id', $projects->pluck('id')->filter()->values())
                ->whereIn('user_id', $userIds)
                ->where('event_type', self::EVENT_PROJECT_VIEW)
                ->select(['project_id', 'user_id'])
                ->selectRaw('COUNT(*) as views_total')
                ->selectRaw('MAX(occurred_at) as last_viewed_at')
                ->groupBy(['project_id', 'user_id'])
                ->get()
                ->keyBy(fn (ProjectAnalyticsEvent $event) => $event->project_id.':'.$event->user_id);

        return $shares
            ->groupBy('project_id')
            ->map(fn (Collection $projectShares) => $projectShares
                ->map(function (ProjectShare $share) use ($viewCounts) {
                    $summary = $share->user_id
                        ? $viewCounts->get($share->project_id.':'.$share->user_id)
                        : null;
                    $lastViewedAt = $summary?->last_viewed_at
                        ? Carbon::parse($summary->last_viewed_at)->toISOString()
                        : null;

                    return [
                        'email' => $share->email,
                        'name' => $share->user?->name,
                        'permissionLabel' => $share->permission->label(),
                        'pending' => $share->user_id === null,
                        'viewsTotal' => (int) ($summary?->views_total ?? 0),
                        'lastViewedAt' => $lastViewedAt,
                    ];
                })
                ->sortBy([
                    ['viewsTotal', 'desc'],
                    ['email', 'asc'],
                ])
                ->values()
                ->all())
            ->all();
    }
}
