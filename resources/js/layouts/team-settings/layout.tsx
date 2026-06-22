import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import type { NavItem, Team } from '@/types';

type PageProps = {
    currentTeam?: Team | null;
};

export default function TeamSettingsLayout({ children }: PropsWithChildren) {
    const { currentTeam } = usePage<PageProps>().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const teamSettingsUrl = (path: string) =>
        currentTeam ? `/${currentTeam.slug}/settings/${path}` : '#';

    const sidebarNavItems: NavItem[] = [
        {
            title: 'General',
            href: teamSettingsUrl('general'),
            icon: null,
        },
        {
            title: 'Members',
            href: teamSettingsUrl('members'),
            icon: null,
        },
    ];

    return (
        <div className="px-4 py-6">
            <Heading
                title="Team settings"
                description={
                    currentTeam
                        ? `Manage ${currentTeam.name}`
                        : 'Manage team settings'
                }
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Team settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
