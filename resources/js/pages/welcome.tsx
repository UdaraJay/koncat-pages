import { Head, Link, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Globe2,
    GlobeLock,
    KeyRound,
    Landmark,
    Lock,
    Mail,
    Play,
    RefreshCw,
    Share2,
    ShieldCheck,
    Sparkles,
    UserRound,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import BrochurePreviews from '@/components/brochure-previews';
import { MCPConnectionPanel } from '@/components/mcp-connection-panel';
import { LogoIcon } from '@/icons';
import { dashboard, login } from '@/routes';
import { MCPSetupPanel } from './dashboard/mcp-setup-panel';

const MCP_URL = 'https://koncat.co/mcp';

const accessOptions = [
    {
        description:
            'Private to your account. A personal home for things only you need to reach.',
        icon: UserRound,
        label: 'You',
        title: 'Just you',
    },

    {
        description:
            'Bound to specific email addresses. People sign in with a link sent to their inbox.',
        icon: Mail,
        label: 'Email',
        title: 'Magic link',
    },
    {
        description:
            'Give access to just those in your team with managed workspaces.',
        icon: KeyRound,
        label: 'Team',
        title: 'Workspaces',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();

    return (
        <>
            <Head title="Welcome" />
            <div className="bg-backgroundp-6 flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center lg:p-8 lg:pt-3">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-5xl">
                    <nav className="flex items-center justify-between gap-4">
                        <div>
                            <Link
                                href="/"
                                className="flex items-center gap-2 font-[Koulen] text-3xl leading-0 font-medium tracking-tight"
                            >
                                <AppLogoIcon className="-mt-[3px] h-6.5 w-auto fill-current text-foreground" />
                                <div className="text-foreground">Koncat</div>
                            </Link>
                        </div>
                        <div>
                            {auth.user ? (
                                <Link
                                    href={dashboardUrl}
                                    className="inline-flex items-center gap-1 rounded-full bg-muted px-5 py-2 pr-3 text-base leading-normal font-semibold tracking-tight text-foreground"
                                >
                                    My Projects{' '}
                                    <ChevronRight className="size-5" />
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-flex items-center gap-1 rounded-full bg-muted px-5 py-2 text-base leading-normal font-semibold tracking-tight text-foreground"
                                >
                                    Log in
                                </Link>
                            )}
                        </div>
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="w-full max-w-[335px] lg:max-w-5xl">
                        <div className="mt-12">
                            <div className="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 font-medium tracking-tight text-muted-foreground">
                                Now in early access
                            </div>
                            <h1 className="mt-4 text-5xl leading-14 font-medium tracking-tighter text-foreground">
                                Your agent made it.
                                <br /> Now anyone can use it.
                            </h1>
                            <div className="mt-4 max-w-lg text-xl font-medium tracking-tight text-muted-foreground">
                                Koncat takes the dashboards, sites, and reports
                                you build with AI off your laptop and onto the
                                web–a real link, the access you choose, and
                                update anytime.
                            </div>
                        </div>

                        <div className="mt-8">
                            <div className="flex max-w-4xl divide-x bg-muted">
                                <MCPConnectionPanel
                                    mcpUrl={MCP_URL}
                                    title="Give your agent our MCP and let it publish."
                                    description="Add the MCP to your agent with the url and authenticate with your email to publish."
                                    className="p-4"
                                    titleClassName="mb-2 px-2"
                                    actionsClassName="mt-2"
                                    descriptionClassName="mt-3 px-2"
                                />
                                <div className="flex flex-col justify-between p-4">
                                    <div>
                                        <div className="mb-2 px-2 text-sm font-medium">
                                            Manage your pages, teams and access.
                                        </div>

                                        <div className="flex gap-1">
                                            <Link
                                                href="/login"
                                                className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2 pr-4 text-lg font-medium tracking-tight text-background"
                                            >
                                                Continue with email{' '}
                                                <ChevronRight className="size-6" />
                                            </Link>
                                        </div>
                                    </div>

                                    <div className="mt-2 max-w-md px-2 text-sm text-muted-foreground">
                                        Get granular controls, create team
                                        workspaces and manage all your pages on
                                        the web.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-20">
                            <div className="mb-4 flex items-center justify-between">
                                <div className="font-medium tracking-tight text-muted-foreground">
                                    If your agent can build it, you can share it
                                    with Koncat.
                                </div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 font-medium tracking-tight text-red-600">
                                    Watch a video <Play className="size-4" />
                                </div>
                            </div>
                            <BrochurePreviews />
                        </div>

                        <div className="mt-3 flex gap-3">
                            <div className="flex h-100 w-90 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-emerald-600">
                                        Dashboards & Reports
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Dashboards that live
                                    </div>
                                </div>
                                <div className="leading-tight text-muted-foreground">
                                    Your finance tracker or weekly report gets a
                                    real link instead of a folder. Push an
                                    update and it refreshes in place —
                                    everyone's always looking at the current
                                    numbers.
                                </div>
                            </div>
                            <div className="flex h-100 w-90 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-pink-600">
                                        Shared pages
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Pages built for sending
                                    </div>
                                </div>
                                <div className="leading-tight text-muted-foreground">
                                    Trip plans, team resources, a wedding site.
                                    Share one link, set who gets in, and never
                                    deal with "which version is this" again.
                                </div>
                            </div>
                            <div className="flex h-100 w-90 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-amber-600">
                                        Forms & Surveys
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Collect, don't just show
                                    </div>
                                </div>
                                <div className="leading-tight text-muted-foreground">
                                    Pages that take input, not only display it —
                                    RSVPs, sign-ups, quick polls. Answers come
                                    back to you, no spreadsheet wrangling.
                                </div>
                            </div>
                        </div>

                        <div className="mt-20">
                            <div className="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 font-medium tracking-tight text-muted-foreground">
                                How it works
                            </div>
                            <div className="mt-5 text-5xl leading-14 font-medium tracking-tighter">
                                Publish. Share. Update.
                            </div>
                            <div className="mt-4 max-w-lg text-xl leading-tight font-medium tracking-tight text-muted-foreground">
                                It runs right inside the agent you already use —
                                and there's a page of your own to manage
                                everything you've put up.
                            </div>

                            <div className="mt-10 grid min-h-90 gap-3 overflow-hidden select-none sm:grid-cols-2 lg:grid-cols-3">
                                <div className="flex flex-col justify-between bg-muted p-4">
                                    <div className="flex items-start justify-between gap-4 text-muted-foreground">
                                        <div className="font-mono font-medium tracking-tight">
                                            01
                                        </div>
                                        <GlobeLock className="size-5" />
                                    </div>
                                    <div className="my-3 mt-10 flex-1">
                                        <div className="flex justify-end gap-2 text-right text-sm leading-tight text-muted-foreground">
                                            <div className="max-w-3/5">
                                                Publish the quarterly report we
                                                made.
                                            </div>
                                            <div className="size-5 rounded-full bg-muted-foreground/50"></div>
                                        </div>
                                        <div className="mt-4 flex gap-2 text-sm">
                                            <div className="size-5 shrink-0 rounded-full bg-secondary"></div>
                                            <div>
                                                <div className="animate-pulse font-medium tracking-tight">
                                                    Thinking{' '}
                                                    <ChevronDown className="inline size-4" />
                                                </div>

                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    I am going to take the
                                                    report we created yesterday
                                                    and publish it using Koncat.
                                                </div>

                                                <div className="mt-3 rounded-md bg-foreground/10 p-3">
                                                    <div>
                                                        Do you want to use the
                                                        Koncat tool to publish
                                                        this report?
                                                    </div>

                                                    <div className="mt-4 flex justify-end gap-1">
                                                        <div className="rounded-sm bg-foreground/10 px-2 py-1">
                                                            Cancel
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Allow
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Always Allow
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="mt-32 text-2xl leading-7 font-medium tracking-tighter text-foreground">
                                            Publish
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight text-muted-foreground">
                                            Ask your agent to publish. The file
                                            leaves your laptop and comes back as
                                            a live link.
                                        </div>
                                    </div>
                                </div>
                                <div className="flex flex-col justify-between bg-muted p-4">
                                    <div className="flex items-start justify-between gap-4 text-muted-foreground">
                                        <div className="font-mono font-medium tracking-tight">
                                            02
                                        </div>
                                        <Share2 className="size-5" />
                                    </div>

                                    <div className="my-3 mt-10 flex-1">
                                        <div className="flex gap-2 text-sm leading-tight text-muted-foreground">
                                            <div className="size-5 rounded-full bg-white"></div>
                                            <div className="mt-0.25 max-w-3/5">
                                                <div className="text-foreground">
                                                    I published the report.
                                                </div>
                                                <div className="mt-1">
                                                    You can share this link with
                                                    the team or manage it in
                                                    your dashboard.
                                                </div>
                                            </div>
                                        </div>
                                        <div className="mt-4 flex gap-2 text-sm">
                                            <div className="size-5 shrink-0 rounded-full bg-transparent"></div>
                                            <div className="w-full">
                                                <div className="w-full rounded-md bg-foreground/10 p-3">
                                                    <div className="text-xs">
                                                        ACME Corp quarterly
                                                        report
                                                    </div>

                                                    <div className="mt-2 flex h-25 items-center justify-center rounded-md bg-background">
                                                        <LogoIcon className="size-5 opacity-20" />
                                                    </div>

                                                    <div className="mt-4 flex justify-end gap-1">
                                                        <div className="rounded-sm bg-foreground/10 px-2 py-1">
                                                            Delete
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Copy link
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Share
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-2xl leading-7 font-medium tracking-tighter text-foreground">
                                            Share
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight text-muted-foreground">
                                            Send the link. It opens on any phone
                                            or laptop, looking exactly how you
                                            built it .
                                        </div>
                                    </div>
                                </div>
                                <div className="flex flex-col justify-between bg-muted p-4">
                                    <div className="flex items-start justify-between gap-4 text-muted-foreground">
                                        <div className="font-mono font-medium tracking-tight">
                                            03
                                        </div>
                                        <RefreshCw className="size-5" />
                                    </div>
                                    <div className="my-3 mt-10 flex-1">
                                        <div className="flex justify-end gap-2 text-right text-sm leading-tight text-muted-foreground">
                                            <div className="max-w-4/5">
                                                Can you update the report at{' '}
                                                <span className="border-b text-primary">
                                                    acme.koncat.co/q4-report
                                                </span>{' '}
                                                with the new numbers we just got
                                                in?
                                            </div>
                                            <div className="size-5 rounded-full bg-muted-foreground/50"></div>
                                        </div>
                                        <div className="mt-4 flex gap-2 text-sm">
                                            <div className="size-5 shrink-0 rounded-full bg-secondary"></div>
                                            <div>
                                                <div className="animate-pulse font-medium tracking-tight">
                                                    Thinking{' '}
                                                    <ChevronDown className="inline size-4" />
                                                </div>

                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    I am going to fetch the
                                                    report using Koncat and
                                                    update it with the new
                                                    numbers we just got in.
                                                </div>

                                                <div className="mt-3 rounded-md bg-foreground/10 p-3">
                                                    <div>
                                                        Do you want to use the
                                                        Koncat tool to publish
                                                        this report?
                                                    </div>

                                                    <div className="mt-4 flex justify-end gap-1">
                                                        <div className="rounded-sm bg-foreground/10 px-2 py-1">
                                                            Cancel
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Allow
                                                        </div>
                                                        <div className="rounded-sm bg-background px-2 py-1">
                                                            Always Allow
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-2xl leading-7 font-medium tracking-tighter text-foreground">
                                            Update
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight text-muted-foreground">
                                            Changed something? Push again and
                                            the link updates in place. The
                                            address never moves.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-20">
                            <div className="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-1 font-medium tracking-tight text-primary-foreground">
                                Made for teams
                            </div>
                            <div className="mt-5 text-5xl leading-14 font-medium tracking-tighter text-primary">
                                Close the gap between AI creation
                                <br /> and business use.
                            </div>
                            <div className="mt-4 max-w-lg text-xl leading-tight font-medium tracking-tight text-muted-foreground">
                                Keep AI-generated dashboards, reports, and
                                resources live, current, and controlled — all
                                from the same agents your team already uses.
                            </div>

                            <div className="mt-10 grid min-h-80 gap-3 overflow-hidden sm:grid-cols-2 lg:grid-cols-3">
                                <div className="flex flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Permissions
                                        </div>
                                        <Lock className="size-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl leading-7 font-medium tracking-tighter">
                                            Control who has access
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            Set each page to public, private,
                                            password-protected, team-only, or
                                            approved-email access.
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Insights
                                        </div>
                                        <Sparkles className="size-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-medium tracking-tighter">
                                            Know what gets read
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            See opens, return visits, and
                                            engagement across the reports,
                                            dashboards, and resources your team
                                            publishes.
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Lifecycle
                                        </div>
                                        <Landmark className="size-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-medium tracking-tighter">
                                            Manage every live page
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            Track owners, versions, updates,
                                            rollbacks, and page status from one
                                            shared workspace.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* <div className="mt-20">
                            <div className="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 font-medium tracking-tight text-muted-foreground">
                                Ready for teams
                            </div>
                            <div className="mt-15 text-5xl leading-14 font-medium tracking-tighter">
                                You're making more than ever.
                                <br /> It's all stuck in a folder.
                            </div>
                            <div className="mt-4 max-w-lg text-xl leading-tight font-medium tracking-tight text-muted-foreground">
                                Agents turn out real, useful things in seconds.
                                Then those things land somewhere nobody else can
                                reach them.
                            </div>
                        </div> */}

                        <section className="mt-20 border-t border-border pt-10">
                            <div className="">
                                <div>
                                    <div className="inline-block rounded-full bg-muted px-3 py-1 font-medium tracking-tight text-muted-foreground">
                                        Access controls
                                    </div>
                                    <h2 className="mt-2 max-w-md text-4xl leading-11 font-medium tracking-tighter text-foreground lg:text-5xl lg:leading-14">
                                        One page, four ways to lock the door.
                                    </h2>
                                    <p className="mt-4 max-w-md text-xl leading-tight font-medium tracking-tight text-muted-foreground">
                                        Every page is yours to open up or close
                                        off — set it once, change it whenever.
                                    </p>
                                </div>

                                <div className="mt-10 grid gap-3 overflow-hidden sm:grid-cols-2 lg:grid-cols-4">
                                    {accessOptions.map(
                                        ({
                                            description,
                                            icon: Icon,
                                            label,
                                            title,
                                        }) => (
                                            <div
                                                className="bg-muted p-5"
                                                key={title}
                                            >
                                                <div className="flex items-start justify-between gap-4 text-muted-foreground">
                                                    <div className="font-medium tracking-tight">
                                                        {label}
                                                    </div>
                                                    <Icon className="size-5" />
                                                </div>
                                                <h3 className="mt-32 text-2xl font-medium tracking-tighter text-foreground">
                                                    {title}
                                                </h3>
                                                <div className="mt-1 max-w-sm text-sm leading-tight text-muted-foreground">
                                                    {description}
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>

                        <div className="mt-20">
                            <MCPSetupPanel mcpUrl="https://koncat.co/mcp" />
                        </div>

                        <footer className="mt-3 flex flex-col gap-5 py-2 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href="/"
                                className="flex items-center gap-2 font-[Koulen] text-2xl leading-none tracking-tight text-foreground"
                            >
                                <AppLogoIcon className="-mt-[1px] h-6 w-auto fill-current" />
                                <span>Koncat</span>
                            </Link>

                            <nav className="flex flex-wrap items-center gap-x-5 gap-y-2 font-medium">
                                <Link
                                    href="/terms"
                                    className="transition hover:text-foreground"
                                >
                                    Terms
                                </Link>
                                <Link
                                    href="/privacy"
                                    className="transition hover:text-foreground"
                                >
                                    Privacy
                                </Link>
                            </nav>
                        </footer>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
