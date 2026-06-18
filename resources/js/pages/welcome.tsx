import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';
import AppLogoIcon from '@/components/app-logo-icon';
import { ArrowRightIcon, ChevronRight } from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 lg:pt-3 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-5xl">
                    <nav className="flex items-center justify-between gap-4">
                        <div>
                            <Link
                                href="/"
                                className="flex items-center gap-2 font-[Koulen] text-3xl leading-0 font-medium tracking-tight"
                            >
                                <AppLogoIcon className="-mt-[2px] h-7 w-auto fill-current text-foreground" />
                                <div>Koncat</div>
                            </Link>
                        </div>
                        <div>
                            {auth.user ? (
                                <Link
                                    href={dashboardUrl}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#454544] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Home
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Log in
                                </Link>
                            )}
                        </div>
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="w-full max-w-[335px] lg:max-w-5xl">
                        <div>
                            <div className="inline-flex rounded-full bg-muted px-3 py-1 font-medium tracking-tight">
                                Living pages
                            </div>
                            <div className="mt-4 text-5xl leading-14 font-medium tracking-tighter">
                                Your agent made it.
                                <br /> Now everyone can see it.
                            </div>
                            <div className="mt-4 max-w-lg text-xl font-medium tracking-tight text-muted-foreground">
                                Koncat takes the dashboards, sites, and reports
                                you build with AI off your laptop and onto the
                                web–a real link, the access you choose, and
                                update anytime.
                            </div>
                            <div className="mt-6 flex items-center gap-4">
                                <div className="inline-flex items-center gap-2 rounded-full bg-foreground px-5 py-2 pr-4 font-medium tracking-tight text-background">
                                    Push your first page{' '}
                                    <ChevronRight className="size-5" />
                                </div>

                                <div className="font-medium tracking-tight text-muted-foreground">
                                    See how it works
                                </div>
                            </div>
                        </div>
                        <div className="mt-15 flex gap-3">
                            <div className="h-120 w-90 bg-secondary p-4">
                                <div className="font-medium tracking-tight text-emerald-600">
                                    Dashboards & Reports
                                </div>
                                <div className="mt-1 text-2xl leading-tight font-medium tracking-tight">
                                    Dashboards that live
                                </div>
                                <div className="text-muted-foreground">
                                    Your finance tracker or weekly report gets a
                                    real link instead of a folder. Push an
                                    update and it refreshes in place —
                                    everyone's always looking at the current
                                    numbers.
                                </div>
                            </div>
                            <div className="h-120 w-90 bg-secondary p-4">
                                <div className="font-medium tracking-tight text-pink-600">
                                    Shared pages
                                </div>
                                <div className="mt-1 text-2xl leading-tight font-medium tracking-tight">
                                    Pages built for sending
                                </div>
                                <div className="text-muted-foreground">
                                    Trip plans, team resources, a wedding site.
                                    Share one link, set who gets in, and never
                                    deal with "which version is this" again.
                                </div>
                            </div>
                            <div className="h-120 w-90 bg-secondary p-4">
                                <div className="font-medium tracking-tight text-amber-600">
                                    Forms & Surveys
                                </div>
                                <div className="mt-1 text-2xl leading-tight font-medium tracking-tight">
                                    Collect, don't just show
                                </div>
                                <div className="text-muted-foreground">
                                    Pages that take input, not only display it —
                                    RSVPs, sign-ups, quick polls. Answers come
                                    back to you, no spreadsheet wrangling.
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
