import { Head, Link, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    Copy,
    Globe2,
    KeyRound,
    Mail,
    Play,
    UserRound,
} from 'lucide-react';
import { toast } from 'sonner';
import AppLogoIcon from '@/components/app-logo-icon';
import BrochurePreviews from '@/components/brochure-previews';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { ClaudeIcon, CursorIcon, VSCodeIcon } from '@/icons';
import { dashboard, login } from '@/routes';

const MCP_URL = 'https://koncat.co/mcp';

const accessOptions = [
    {
        description:
            'Anyone with the link can see it. Good for a blog or a homepage you want found.',
        icon: Globe2,
        label: 'Open',
        title: 'Public',
    },
    {
        description:
            'One shared password to get in — the way a wedding site works.',
        icon: KeyRound,
        label: 'Key',
        title: 'Password',
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
            'Private to your account. A personal home for things only you need to reach.',
        icon: UserRound,
        label: 'You',
        title: 'Just you',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();
    const [copiedText, copy] = useClipboard();
    const mcpUrlCopied = copiedText === MCP_URL;

    const copyMcpUrl = async () => {
        const copied = await copy(MCP_URL);

        if (copied) {
            toast.success('MCP URL copied');
        } else {
            toast.error('Could not copy MCP URL');
        }
    };

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
                                <div className="p-4">
                                    <div className="mb-2 px-2 text-sm font-medium">
                                        Give your agent our MCP and let it
                                        publish.
                                    </div>

                                    <div className="flex gap-1">
                                        <input
                                            className="flex-1 rounded-full border bg-background px-4 py-2 font-medium"
                                            value={MCP_URL}
                                            readOnly
                                        />
                                    </div>

                                    <div className="mt-2 flex flex-wrap gap-1">
                                        <a href="https://claude.ai/customize/connectors?modal=add-custom-connector&connectorName=Koncat&connectorUrl=https%3A%2F%2Fkoncat.co%2Fmcp">
                                            <Button variant="outline" size="sm">
                                                <ClaudeIcon className="size-4" />
                                                Connect to Claude
                                            </Button>
                                        </a>
                                        <a href="https://cursor.com/install-mcp?name=Koncat&config=eyJ1cmwiOiJodHRwczovL2tvbmNhdC5jby9tY3AifQ%3D%3D">
                                            <Button variant="outline" size="sm">
                                                <CursorIcon className="size-4" />
                                                Install in Cursor
                                            </Button>
                                        </a>
                                        <a href="vscode:mcp/install?%7B%22name%22%3A%22koncat%22%2C%22type%22%3A%22http%22%2C%22url%22%3A%22https%3A%2F%2Fkoncat.co%2Fmcp%22%7D">
                                            <Button variant="outline" size="sm">
                                                <VSCodeIcon className="size-4" />
                                                Install in VS Code
                                            </Button>
                                        </a>

                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={copyMcpUrl}
                                        >
                                            {mcpUrlCopied ? (
                                                <Check className="size-4" />
                                            ) : (
                                                <Copy className="size-4" />
                                            )}
                                            {mcpUrlCopied
                                                ? 'Copied'
                                                : 'Copy MCP URL'}
                                        </Button>
                                    </div>

                                    <div className="mt-3 max-w-md px-2 text-sm text-muted-foreground">
                                        Add the MCP to your agent with the url
                                        and authenticate with your email to
                                        publish.
                                    </div>
                                </div>
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
                            <div className="flex h-100 w-90 flex-col justify-between bg-secondary p-4">
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
                            <div className="flex h-100 w-90 flex-col justify-between bg-secondary p-4">
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
                            <div className="flex h-100 w-90 flex-col justify-between bg-secondary p-4">
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

                        {/* <div className="mt-20">
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

                        <footer className="mt-20 flex flex-col gap-5 border-t border-border py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
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
