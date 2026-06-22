import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { TeamSwitcher } from '@/components/team-switcher';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { ChevronRightIcon, Slash } from 'lucide-react';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

export function AppHeader({ breadcrumbs = [] }: Props) {
    const page = usePage();
    const { auth, currentTeam, teams } = page.props;
    const getInitials = useInitials();
    const dashboardUrl = dashboard();

    return (
        <>
            <div className="bg-yellow-100 p-2 px-6 text-sm text-yellow-800">
                Koncat is in early-access. Some features may be limited.
            </div>
            <header className="sticky top-0 z-40 flex h-14 w-full items-center bg-linear-to-b from-background from-60% to-transparent px-4">
                <div className="flex min-w-0 flex-1 items-center gap-1">
                    <Button
                        asChild
                        variant="ghost"
                        className="rounded-md pl-2! select-none"
                    >
                        <Link
                            href={dashboardUrl}
                            prefetch
                            aria-label="Koncat home"
                            className="flex items-center"
                        >
                            <AppLogoIcon className="-mt-[2px] size-5.75 fill-current text-foreground" />
                            <div className="font-[Koulen] text-2xl">Koncat</div>
                        </Link>
                    </Button>

                    <TeamSwitcher
                        currentTeam={currentTeam}
                        teams={teams ?? []}
                        variant="header"
                    />

                    {breadcrumbs.length > 0 ? (
                        <div className="hidden min-w-0 items-center gap-2 sm:flex">
                            <ChevronRightIcon className="mr-2 size-4 text-muted-foreground/50" />
                            <Breadcrumbs breadcrumbs={breadcrumbs} />
                        </div>
                    ) : null}
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className="ml-3 size-10 rounded-full p-1"
                            aria-label="Open user menu"
                        >
                            <Avatar className="size-8 overflow-hidden rounded-full">
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                />
                                <AvatarFallback className="bg-violet-200 text-violet-700 ring-1 ring-violet-200">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56" align="end">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </header>
        </>
    );
}
