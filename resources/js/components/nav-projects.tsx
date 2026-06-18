'use client';

import { ExternalLink, Folder, MoreHorizontal } from 'lucide-react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

type ProjectItem = {
    name: string;
    url: string;
};

export function NavProjects({ projects }: { projects: ProjectItem[] }) {
    if (projects.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel>Recent projects</SidebarGroupLabel>
            <SidebarMenu>
                {projects.map((project) => (
                    <SidebarMenuItem key={`${project.name}-${project.url}`}>
                        <SidebarMenuButton asChild>
                            <a
                                href={project.url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Folder />
                                <span>{project.name}</span>
                                <ExternalLink className="ml-auto size-3 text-sidebar-foreground/50" />
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
                {projects.length >= 5 ? (
                    <SidebarMenuItem>
                        <SidebarMenuButton className="text-sidebar-foreground/70">
                            <MoreHorizontal className="text-sidebar-foreground/70" />
                            <span>More in dashboard</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ) : null}
            </SidebarMenu>
        </SidebarGroup>
    );
}
