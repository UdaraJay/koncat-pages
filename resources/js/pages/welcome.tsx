import { Head, Link, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Globe2,
    GlobeLock,
    Info,
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
                                <div className="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 font-medium tracking-tight text-muted-foreground">
                                    Watch a video <Play className="size-4" />
                                </div>
                            </div>
                            <BrochurePreviews />
                        </div>

                        <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="flex min-h-84 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-emerald-600">
                                        Dashboards & Reports
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Dashboards that live
                                    </div>
                                </div>
                                <div className="my-6 flex-1">
                                    <div className="p-1 text-sm">
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <div>Weekly revenue</div>
                                            <div>Live</div>
                                        </div>
                                        <div className="mt-4 grid grid-cols-3 gap-2">
                                            <div className="rounded-md bg-foreground/5 p-2">
                                                <div className="text-lg leading-none font-medium tracking-tighter text-foreground">
                                                    $42k
                                                </div>
                                                <div className="mt-1 h-1 rounded-full bg-emerald-600/30"></div>
                                            </div>
                                            <div className="rounded-md bg-foreground/5 p-2">
                                                <div className="text-lg leading-none font-medium tracking-tighter text-foreground">
                                                    18%
                                                </div>
                                                <div className="mt-1 h-1 rounded-full bg-emerald-600/40"></div>
                                            </div>
                                            <div className="rounded-md bg-foreground/5 p-2">
                                                <div className="text-lg leading-none font-medium tracking-tighter text-foreground">
                                                    9
                                                </div>
                                                <div className="mt-1 h-1 rounded-full bg-emerald-600/50"></div>
                                            </div>
                                        </div>
                                        <div className="mt-4 h-22 rounded-md bg-foreground/5 p-2 text-emerald-600">
                                            <svg
                                                className="h-full w-full overflow-visible"
                                                fill="none"
                                                viewBox="0 0 240 88"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <defs>
                                                    <linearGradient
                                                        id="dashboardLineArea"
                                                        x1="0"
                                                        x2="0"
                                                        y1="16"
                                                        y2="88"
                                                        gradientUnits="userSpaceOnUse"
                                                    >
                                                        <stop
                                                            stopColor="currentColor"
                                                            stopOpacity="0.28"
                                                        />
                                                        <stop
                                                            offset="1"
                                                            stopColor="currentColor"
                                                            stopOpacity="0"
                                                        />
                                                    </linearGradient>
                                                </defs>
                                                <path
                                                    d="M8 70H232"
                                                    stroke="currentColor"
                                                    strokeOpacity="0.12"
                                                />
                                                <path
                                                    d="M8 46H232"
                                                    stroke="currentColor"
                                                    strokeOpacity="0.1"
                                                />
                                                <path
                                                    d="M8 22H232"
                                                    stroke="currentColor"
                                                    strokeOpacity="0.08"
                                                />
                                                <path
                                                    d="M10 62C29 58 36 46 54 48C75 50 80 66 101 58C123 50 124 28 146 26C169 24 174 48 194 40C213 32 216 20 232 16V88H10V62Z"
                                                    fill="url(#dashboardLineArea)"
                                                />
                                                <path
                                                    d="M10 62C29 58 36 46 54 48C75 50 80 66 101 58C123 50 124 28 146 26C169 24 174 48 194 40C213 32 216 20 232 16"
                                                    stroke="currentColor"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="3"
                                                />
                                                <circle
                                                    cx="146"
                                                    cy="26"
                                                    fill="currentColor"
                                                    r="4"
                                                />
                                                <circle
                                                    cx="232"
                                                    cy="16"
                                                    fill="currentColor"
                                                    r="4"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    Your finance tracker or weekly report gets a
                                    real link instead of a folder. Push an
                                    update and it refreshes in place —
                                    everyone's always looking at the current
                                    numbers.
                                </div>
                            </div>
                            <div className="flex min-h-84 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-pink-600">
                                        Shared pages
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Pages built for sending
                                    </div>
                                </div>
                                <div className="my-6 flex-1">
                                    <div className="p-1 text-sm">
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <div>launch-plan</div>
                                            <Globe2 className="size-4" />
                                        </div>
                                        <div className="mt-3 rounded-md bg-foreground/5 p-3">
                                            <div className="flex items-center justify-between gap-2">
                                                <div className="flex items-center gap-1.5">
                                                    <div className="size-2 rounded-full bg-pink-600/40"></div>
                                                    <div className="size-2 rounded-full bg-pink-600/25"></div>
                                                    <div className="size-2 rounded-full bg-pink-600/15"></div>
                                                </div>
                                                <div className="h-1.5 w-12 rounded-full bg-foreground/10"></div>
                                            </div>
                                            <div className="mt-4 space-y-2">
                                                <div className="h-2 w-4/5 rounded-full bg-foreground/20"></div>
                                                <div className="h-2 w-3/5 rounded-full bg-foreground/10"></div>
                                            </div>
                                            <div className="mt-4 grid grid-cols-[1fr_auto] gap-2">
                                                <div className="rounded-md bg-background/40 p-2">
                                                    <div className="h-2 rounded-full bg-foreground/15"></div>
                                                    <div className="mt-2 h-2 w-2/3 rounded-full bg-foreground/10"></div>
                                                </div>
                                                <div className="flex w-12 items-center justify-center rounded-md bg-pink-600/15">
                                                    <Share2 className="size-4 text-pink-600" />
                                                </div>
                                            </div>
                                            <div className="mt-3 flex items-center gap-1.5">
                                                <div className="h-5 rounded-full bg-pink-600/15 px-2 text-[10px] leading-5 text-pink-600">
                                                    Team
                                                </div>
                                                <div className="h-5 rounded-full bg-pink-600/10 px-2 text-[10px] leading-5 text-pink-600">
                                                    Client
                                                </div>
                                                <div className="h-5 rounded-full bg-foreground/10 px-2 text-[10px] leading-5 text-muted-foreground">
                                                    +4
                                                </div>
                                            </div>
                                        </div>
                                        <div className="mt-3 flex items-center gap-2">
                                            <div className="flex-1 rounded-sm bg-foreground/5 px-2 py-1 text-xs text-muted-foreground">
                                                koncat.co/launch
                                            </div>
                                            <div className="rounded-sm bg-pink-600 px-2 py-1 text-xs text-background">
                                                Share
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    Trip plans, team resources, a wedding site.
                                    Share one link, set who gets in, and never
                                    deal with "which version is this" again.
                                </div>
                            </div>
                            <div className="flex min-h-84 flex-col justify-between bg-muted p-4">
                                <div>
                                    <div className="font-medium tracking-tight text-amber-600">
                                        Forms & Surveys
                                    </div>
                                    <div className="mt-1 text-2xl leading-tight font-medium tracking-tight text-foreground">
                                        Collect, don't just show
                                    </div>
                                </div>
                                <div className="my-6 flex-1">
                                    <div className="p-1 text-sm">
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <div>RSVP form</div>
                                            <div>36 answers</div>
                                        </div>
                                        <div className="mt-3 space-y-2">
                                            <div className="rounded-md bg-foreground/5 p-2">
                                                <div className="h-2 w-2/3 rounded-full bg-foreground/20"></div>
                                                <div className="mt-2 grid grid-cols-2 gap-2">
                                                    <div className="rounded-sm bg-background/50 px-2 py-1 text-xs text-muted-foreground">
                                                        Yes
                                                    </div>
                                                    <div className="rounded-sm bg-amber-600 px-2 py-1 text-xs text-background">
                                                        Maybe
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 rounded-md bg-foreground/5 p-2">
                                                <div className="size-6 rounded-full bg-amber-600/20"></div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="h-2 rounded-full bg-foreground/20"></div>
                                                    <div className="mt-1.5 h-2 w-1/2 rounded-full bg-foreground/10"></div>
                                                </div>
                                                <div className="h-5 w-9 rounded-sm bg-background/50"></div>
                                            </div>
                                            <div className="flex items-center gap-2 rounded-md bg-foreground/5 p-2">
                                                <div className="size-6 rounded-full bg-amber-600/30"></div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="h-2 w-4/5 rounded-full bg-foreground/20"></div>
                                                    <div className="mt-1.5 h-2 w-2/5 rounded-full bg-foreground/10"></div>
                                                </div>
                                                <div className="h-5 w-9 rounded-sm bg-background/50"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-sm text-muted-foreground">
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

                            <div className="mt-10 grid gap-3 overflow-hidden sm:grid-cols-2 lg:grid-cols-3">
                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Permissions
                                        </div>
                                        <Lock className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>q4-report</div>
                                                <ShieldCheck className="size-4" />
                                            </div>
                                            <div className="mt-3 space-y-2">
                                                <div className="flex items-center justify-between gap-3 rounded-md bg-primary-foreground/10 px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <Globe2 className="size-4" />
                                                        <span>Public link</span>
                                                    </div>
                                                    <span className="rounded-sm bg-primary-foreground/10 px-2 py-0.5 text-xs">
                                                        Off
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-between gap-3 rounded-md bg-primary-foreground/20 px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <KeyRound className="size-4" />
                                                        <span>
                                                            Team workspace
                                                        </span>
                                                    </div>
                                                    <span className="rounded-sm bg-primary-foreground px-2 py-0.5 text-xs text-primary">
                                                        On
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-between gap-3 rounded-md bg-primary-foreground/10 px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <Mail className="size-4" />
                                                        <span>
                                                            Approved email
                                                        </span>
                                                    </div>
                                                    <span className="rounded-sm bg-primary-foreground/10 px-2 py-0.5 text-xs">
                                                        8
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
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

                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Insights
                                        </div>
                                        <Sparkles className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>Team resources</div>
                                                <div>7 days</div>
                                            </div>
                                            <div className="mt-4 grid grid-cols-2 gap-2">
                                                <div className="rounded-md bg-primary-foreground/10 p-2">
                                                    <div className="text-xl leading-none font-medium tracking-tighter">
                                                        284
                                                    </div>
                                                    <div className="mt-1 text-xs text-primary-foreground/70">
                                                        Opens
                                                    </div>
                                                </div>
                                                <div className="rounded-md bg-primary-foreground/10 p-2">
                                                    <div className="text-xl leading-none font-medium tracking-tighter">
                                                        61%
                                                    </div>
                                                    <div className="mt-1 text-xs text-primary-foreground/70">
                                                        Return
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-4 flex h-20 items-end gap-1.5">
                                                <div className="h-8 flex-1 rounded-sm bg-primary-foreground/25"></div>
                                                <div className="h-12 flex-1 rounded-sm bg-primary-foreground/35"></div>
                                                <div className="h-7 flex-1 rounded-sm bg-primary-foreground/25"></div>
                                                <div className="h-16 flex-1 rounded-sm bg-primary-foreground/50"></div>
                                                <div className="h-11 flex-1 rounded-sm bg-primary-foreground/35"></div>
                                                <div className="h-18 flex-1 rounded-sm bg-primary-foreground"></div>
                                            </div>
                                        </div>
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

                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Lifecycle
                                        </div>
                                        <Landmark className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>Workspace pages</div>
                                                <RefreshCw className="size-4" />
                                            </div>
                                            <div className="mt-3 rounded-md bg-primary-foreground/10 p-3">
                                                <div className="flex items-center justify-between gap-3">
                                                    <div>
                                                        <div className="font-medium tracking-tight">
                                                            Board metrics
                                                        </div>
                                                        <div className="mt-1 text-xs text-primary-foreground/70">
                                                            Owner, version, and
                                                            status in one place
                                                        </div>
                                                    </div>
                                                    <span className="rounded-sm bg-primary-foreground px-2 py-0.5 text-xs text-primary">
                                                        Current
                                                    </span>
                                                </div>

                                                <div className="mt-4 space-y-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-foreground/15 text-xs font-medium">
                                                            v4
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-center justify-between gap-2">
                                                                <div className="truncate text-xs font-medium tracking-tight">
                                                                    Updated
                                                                    revenue
                                                                    table
                                                                </div>
                                                                <div className="text-xs text-primary-foreground/70">
                                                                    Now
                                                                </div>
                                                            </div>
                                                            <div className="mt-1 h-1.5 rounded-full bg-primary-foreground/15">
                                                                <div className="h-full w-full rounded-full bg-primary-foreground"></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center gap-3">
                                                        <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-foreground/15 text-xs font-medium">
                                                            v3
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-center justify-between gap-2">
                                                                <div className="truncate text-xs font-medium tracking-tight">
                                                                    Added
                                                                    forecast
                                                                    note
                                                                </div>
                                                                <div className="text-xs text-primary-foreground/70">
                                                                    Tue
                                                                </div>
                                                            </div>
                                                            <div className="mt-1 h-1.5 rounded-full bg-primary-foreground/15">
                                                                <div className="h-full w-3/5 rounded-full bg-primary-foreground/50"></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center justify-between rounded-md bg-primary-foreground/10 px-2 py-1.5 text-xs text-primary-foreground/70">
                                                        <div className="flex items-center gap-2">
                                                            <div className="size-2 rounded-full bg-primary-foreground/60"></div>
                                                            Ready to roll back
                                                        </div>
                                                        <div>2 saved</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Branding
                                        </div>
                                        <Globe2 className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="min-h-48 rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>Company-ready link</div>
                                                <ShieldCheck className="size-4" />
                                            </div>
                                            <div className="mt-4 rounded-sm bg-primary-foreground/10 p-2">
                                                <div className="flex items-center gap-1.5">
                                                    <span className="size-1.5 rounded-full bg-primary-foreground/60"></span>
                                                    <span className="size-1.5 rounded-full bg-primary-foreground/35"></span>
                                                    <span className="size-1.5 rounded-full bg-primary-foreground/20"></span>
                                                </div>
                                                <div className="mt-3 flex items-center gap-2 rounded-sm bg-primary-foreground px-2 py-1.5 text-primary">
                                                    <Globe2 className="size-3.5 shrink-0" />
                                                    <div className="truncate text-xs font-medium">
                                                        company.com/reports/q4-board-update
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-3 flex items-center justify-between gap-2 text-xs">
                                                <div className="flex items-center gap-2">
                                                    <div className="size-5 rounded-sm bg-primary-foreground"></div>
                                                    <div>
                                                        <div className="font-medium">
                                                            Verified
                                                        </div>
                                                        <div className="text-primary-foreground/70">
                                                            Brand footer on
                                                        </div>
                                                    </div>
                                                </div>
                                                <span className="rounded-sm bg-primary-foreground/15 px-2 py-1">
                                                    Domain
                                                </span>
                                            </div>
                                            <div className="mt-5 flex items-center justify-between rounded-sm bg-primary-foreground/10 px-2 py-1.5 text-xs">
                                                <div className="font-medium">
                                                    ACME Workspace
                                                </div>
                                                <div className="text-primary-foreground/70">
                                                    Footer on
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-medium tracking-tighter">
                                            Company-ready links
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            Custom domains, clean URLs,
                                            workspace branding, and no random
                                            file names — every page feels like
                                            an official company resource.
                                        </div>
                                    </div>
                                </div>

                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Safety
                                        </div>
                                        <ShieldCheck className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>Code review</div>
                                                <Lock className="size-4" />
                                            </div>
                                            <div className="mt-4 flex items-center justify-between rounded-sm bg-primary-foreground px-2 py-1.5 text-primary">
                                                <div className="flex items-center gap-2">
                                                    <ShieldCheck className="size-3.5" />
                                                    Cleared
                                                </div>
                                                <span className="text-xs font-medium">
                                                    Safe
                                                </span>
                                            </div>
                                            <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
                                                <div className="rounded-sm bg-primary-foreground/10 px-2 py-1.5">
                                                    <div className="font-medium">
                                                        2
                                                    </div>
                                                    <div className="mt-1 text-primary-foreground/70">
                                                        External calls
                                                    </div>
                                                </div>
                                                <div className="rounded-sm bg-primary-foreground/10 px-2 py-1.5">
                                                    <div className="font-medium">
                                                        0
                                                    </div>
                                                    <div className="mt-1 text-primary-foreground/70">
                                                        Risky scripts
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-medium tracking-tighter">
                                            Check before sharing
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            Review code for suspicious scripts,
                                            risky outbound calls, injected code,
                                            and unsafe changes before they go
                                            live.
                                        </div>
                                    </div>
                                </div>

                                <div className="flex min-h-110 flex-col justify-between bg-primary p-4 text-primary-foreground">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="font-medium tracking-tight">
                                            Collaboration
                                        </div>
                                        <UserRound className="size-5" />
                                    </div>

                                    <div className="my-6 flex-1 text-sm">
                                        <div className="min-h-40 rounded-md bg-primary-foreground/10 p-3">
                                            <div className="flex items-center justify-between text-xs text-primary-foreground/70">
                                                <div>Board metrics</div>
                                                <Share2 className="size-4" />
                                            </div>
                                            <div className="mt-4 grid grid-cols-3 gap-2 text-xs">
                                                <div className="rounded-sm bg-primary-foreground px-2 py-2 text-primary">
                                                    <div className="flex size-6 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground">
                                                        M
                                                    </div>
                                                    <div className="mt-3 font-medium">
                                                        Owner
                                                    </div>
                                                </div>
                                                <div className="rounded-sm bg-primary-foreground/10 px-2 py-2">
                                                    <div className="text-lg leading-none font-medium tracking-tighter">
                                                        4
                                                    </div>
                                                    <div className="mt-3 text-primary-foreground/70">
                                                        Editors
                                                    </div>
                                                </div>
                                                <div className="rounded-sm bg-primary-foreground/10 px-2 py-2">
                                                    <div className="text-lg leading-none font-medium tracking-tighter">
                                                        1
                                                    </div>
                                                    <div className="mt-3 text-primary-foreground/70">
                                                        Review
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-3 flex items-center justify-between rounded-sm bg-primary-foreground/10 px-2 py-1.5">
                                                <span className="text-primary-foreground/70">
                                                    Ready to publish
                                                </span>
                                                <span className="rounded-sm bg-primary-foreground px-2 py-0.5 text-xs text-primary">
                                                    Ready
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-medium tracking-tighter">
                                            Keep pages moving without
                                            bottlenecks
                                        </h3>
                                        <div className="mt-1 max-w-sm text-sm leading-tight">
                                            Assign owners, editors, and
                                            reviewers so the right teammates can
                                            update pages without waiting on the
                                            original creator.
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
                            <div className="mb-4 flex items-center gap-1.5 text-foreground">
                                <Info className="size-5" />
                                Get started today
                            </div>
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
