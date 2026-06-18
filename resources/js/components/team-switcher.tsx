import { router } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import CreateTeamModal from '@/components/create-team-modal';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { switchMethod } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    currentTeam: Team | null;
    teams: Team[];
    variant?: 'sidebar' | 'header';
};

type TeamMarkProps = {
    label: string;
    icon?: ReactNode;
    size?: 'default' | 'sm';
    variant?: 'sidebar' | 'header';
};

export function TeamSwitcher({
    currentTeam,
    teams,
    variant = 'sidebar',
}: Props) {
    if (variant === 'header') {
        return <HeaderTeamSwitcher currentTeam={currentTeam} teams={teams} />;
    }

    return <SidebarTeamSwitcher currentTeam={currentTeam} teams={teams} />;
}

function SidebarTeamSwitcher({ currentTeam, teams }: Props) {
    const { isMobile, setOpenMobile } = useSidebar();
    const getInitials = useInitials();
    const activeTeam = currentTeam ?? teams.find((team) => team.isCurrent);
    const renderTeamMark = (props: TeamMarkProps) =>
        renderTeamAvatar({ ...props, getInitials });

    const switchTeam = (team: Team) => {
        if (team.slug === activeTeam?.slug) {
            return;
        }

        router.post(
            switchMethod.url(team.slug),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setOpenMobile(false),
            },
        );
    };

    if (!activeTeam) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <CreateTeamModal>
                        <SidebarMenuButton size="lg">
                            {renderTeamMark({
                                label: 'Team',
                                icon: <Users />,
                            })}
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    Create team
                                </span>
                                <span className="truncate text-xs">
                                    Start collaborating
                                </span>
                            </div>
                            <Plus className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </CreateTeamModal>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            {renderTeamMark({
                                label: activeTeam.name,
                                variant: 'sidebar',
                            })}
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {activeTeam.name}
                                </span>
                                <span className="truncate text-xs">
                                    {activeTeam.isPersonal
                                        ? 'Personal'
                                        : (activeTeam.roleLabel ?? 'Team')}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={isMobile ? 'bottom' : 'right'}
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Teams
                        </DropdownMenuLabel>
                        {teams.map((team) => (
                            <DropdownMenuItem
                                key={team.id}
                                onClick={() => switchTeam(team)}
                                className="gap-2 p-2"
                                disabled={team.slug === activeTeam.slug}
                            >
                                {renderTeamMark({
                                    label: team.name,
                                    size: 'sm',
                                    variant: 'sidebar',
                                })}
                                <div className="grid min-w-0 flex-1">
                                    <span className="truncate">
                                        {team.name}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {team.isPersonal
                                            ? 'Personal'
                                            : (team.roleLabel ?? 'Team')}
                                    </span>
                                </div>
                                {team.slug === activeTeam.slug ? (
                                    <Check className="size-4" />
                                ) : null}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <CreateTeamModal>
                            <DropdownMenuItem
                                className="gap-2 p-2"
                                onSelect={(event) => event.preventDefault()}
                            >
                                {renderTeamMark({
                                    label: 'Add',
                                    size: 'sm',
                                    icon: <Plus className="size-4" />,
                                    variant: 'sidebar',
                                })}
                                <div className="font-medium text-muted-foreground">
                                    Add team
                                </div>
                            </DropdownMenuItem>
                        </CreateTeamModal>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

function HeaderTeamSwitcher({ currentTeam, teams }: Props) {
    const getInitials = useInitials();
    const activeTeam = currentTeam ?? teams.find((team) => team.isCurrent);
    const renderTeamMark = (props: TeamMarkProps) =>
        renderTeamAvatar({ ...props, getInitials, variant: 'header' });

    const switchTeam = (team: Team) => {
        if (team.slug === activeTeam?.slug) {
            return;
        }

        router.post(switchMethod.url(team.slug), {}, { preserveScroll: true });
    };

    if (!activeTeam) {
        return (
            <CreateTeamModal>
                <Button
                    variant="ghost"
                    className="h-9 max-w-[180px] min-w-0 justify-start gap-2 px-1 text-muted-foreground"
                >
                    {renderTeamMark({
                        label: 'Team',
                        icon: <Users className="size-3.5" />,
                        size: 'sm',
                    })}
                    <span className="truncate">Create team</span>
                    <Plus className="ml-auto size-4 shrink-0" />
                </Button>
            </CreateTeamModal>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="h-9 max-w-[220px] min-w-0 justify-start gap-2 px-2 text-foreground data-[state=open]:bg-accent"
                >
                    {renderTeamMark({
                        label: activeTeam.name,
                        size: 'sm',
                    })}
                    <span className="truncate font-medium">
                        {activeTeam.name}
                    </span>
                    <ChevronsUpDown className="ml-auto size-4 shrink-0 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                align="start"
                side="bottom"
                sideOffset={8}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Teams
                </DropdownMenuLabel>
                {teams.map((team) => (
                    <DropdownMenuItem
                        key={team.id}
                        onClick={() => switchTeam(team)}
                        className="gap-2 p-2"
                        disabled={team.slug === activeTeam.slug}
                    >
                        {renderTeamMark({
                            label: team.name,
                            size: 'sm',
                        })}
                        <div className="grid min-w-0 flex-1">
                            <span className="truncate">{team.name}</span>
                            <span className="truncate text-xs text-muted-foreground">
                                {team.isPersonal
                                    ? 'Personal'
                                    : (team.roleLabel ?? 'Team')}
                            </span>
                        </div>
                        {team.slug === activeTeam.slug ? (
                            <Check className="size-4" />
                        ) : null}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <CreateTeamModal>
                    <DropdownMenuItem
                        className="gap-2 p-2"
                        onSelect={(event) => event.preventDefault()}
                    >
                        {renderTeamMark({
                            label: 'Add',
                            size: 'sm',
                            icon: <Plus className="size-4" />,
                        })}
                        <div className="font-medium text-muted-foreground">
                            Add team
                        </div>
                    </DropdownMenuItem>
                </CreateTeamModal>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function renderTeamAvatar({
    label,
    icon = null,
    size = 'default',
    variant = 'sidebar',
    getInitials,
}: TeamMarkProps & { getInitials: (name: string) => string }) {
    const isSmall = size === 'sm';
    const isHeader = variant === 'header';

    return (
        <Avatar
            size={isSmall ? 'sm' : 'default'}
            className={cn(
                isSmall ? 'rounded-sm border' : 'rounded-md',
                isHeader
                    ? 'border-violet-200 bg-violet-50'
                    : isSmall
                      ? 'bg-background'
                      : 'bg-sidebar-primary',
            )}
        >
            <AvatarFallback
                className={cn(
                    'font-medium',
                    isSmall ? 'rounded-sm text-xs' : 'rounded-md text-xs',
                    isHeader
                        ? 'bg-violet-50 text-violet-700'
                        : isSmall
                          ? 'bg-background text-foreground'
                          : 'bg-sidebar-primary text-sidebar-primary-foreground',
                )}
            >
                {icon ?? getInitials(label)}
            </AvatarFallback>
        </Avatar>
    );
}
