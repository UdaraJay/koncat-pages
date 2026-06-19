import { Folder, SlidersHorizontal } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { LogoIcon } from '@/icons';
import type {
    Project,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
} from '@/types';

import { ProjectCard } from './project-card';
import type {
    HomeScope,
    ProjectFilters,
    ProjectFilterStatus,
    ProjectSort,
} from './types';
import { emptyProjectsText, emptyProjectsTitle } from './utils';

export function ProjectSection({
    projects,
    projectFilters,
    homeScope,
    moveTargets,
    sharePermissions,
    onFilterChange,
}: {
    projects: Project[];
    projectFilters: ProjectFilters;
    homeScope?: HomeScope;
    moveTargets: ProjectMoveTarget[];
    sharePermissions: ProjectSharePermissionOption[];
    onFilterChange: (updates: Partial<ProjectFilters>) => void;
}) {
    return (
        <section className="space-y-3">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2">
                    <Folder className="size-5 text-muted-foreground" />
                    <h2 className="font-medium tracking-tight">
                        {homeScope?.projectLabel ?? 'My projects'}
                    </h2>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">
                        {projects.length}{' '}
                        {projects.length === 1 ? 'project' : 'projects'}
                    </Badge>

                    <div className="flex items-center gap-2">
                        <SlidersHorizontal className="h-4 w-4 text-muted-foreground" />
                        <Select
                            value={projectFilters.status}
                            onValueChange={(status) =>
                                onFilterChange({
                                    status: status as ProjectFilterStatus,
                                })
                            }
                        >
                            <SelectTrigger
                                size="sm"
                                className="w-[126px]"
                                aria-label="Filter projects"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent align="end">
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="archived">
                                    Archived
                                </SelectItem>
                                <SelectItem value="all">All</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            value={projectFilters.sort}
                            onValueChange={(sort) =>
                                onFilterChange({
                                    sort: sort as ProjectSort,
                                })
                            }
                        >
                            <SelectTrigger
                                size="sm"
                                className="w-[142px]"
                                aria-label="Sort projects"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent align="end">
                                <SelectItem value="updated_desc">
                                    Recently updated
                                </SelectItem>
                                <SelectItem value="created_desc">
                                    Newest
                                </SelectItem>
                                <SelectItem value="name_asc">Name</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            {projects.length > 0 ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {projects.map((project) => (
                        <ProjectCard
                            key={project.id}
                            project={project}
                            moveTargets={moveTargets}
                            sharePermissions={sharePermissions}
                        />
                    ))}
                </div>
            ) : (
                <div className="grid min-h-100 place-items-center bg-muted p-8 text-center">
                    <div className="max-w-sm space-y-2">
                        <LogoIcon className="mx-auto mb-5 size-10 text-border" />
                        <h3 className="font-medium">
                            {homeScope?.emptyTitle ??
                                emptyProjectsTitle(projectFilters.status)}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {homeScope?.emptyText ??
                                emptyProjectsText(projectFilters.status)}
                        </p>
                    </div>
                </div>
            )}
        </section>
    );
}
