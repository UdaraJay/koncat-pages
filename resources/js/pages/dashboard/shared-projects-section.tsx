import { Share2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type {
    Project,
    ProjectMoveTarget,
    ProjectSharePermissionOption,
} from '@/types';

import { ProjectCard } from './project-card';

export function SharedProjectsSection({
    projects,
    moveTargets,
    sharePermissions,
}: {
    projects: Project[];
    moveTargets: ProjectMoveTarget[];
    sharePermissions: ProjectSharePermissionOption[];
}) {
    return (
        <section className="space-y-3">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2">
                    <Share2 className="h-4 w-4 text-muted-foreground" />
                    <h2 className="font-medium">Shared with me</h2>
                </div>
                <Badge variant="secondary">
                    {projects.length}{' '}
                    {projects.length === 1 ? 'project' : 'projects'}
                </Badge>
            </div>

            {projects.length > 0 ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {projects.map((project) => (
                        <ProjectCard
                            key={project.id}
                            project={project}
                            href={`/projects/${project.id}`}
                            moveTargets={moveTargets}
                            sharePermissions={sharePermissions}
                        />
                    ))}
                </div>
            ) : (
                <div className="grid min-h-80 place-items-center bg-muted p-8 text-center">
                    <div className="max-w-sm space-y-2">
                        <Share2 className="mx-auto mb-5 size-8 text-border" />
                        <h3 className="font-medium">No shared projects yet</h3>
                        <p className="text-sm text-muted-foreground">
                            Projects shared directly with your email address
                            will appear here.
                        </p>
                    </div>
                </div>
            )}
        </section>
    );
}
