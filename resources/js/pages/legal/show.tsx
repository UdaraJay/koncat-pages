import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

type LegalPageProps = {
    description: string;
    html: string;
    title: string;
    updatedAt: string;
};

export default function LegalShow({
    description,
    html,
    title,
    updatedAt,
}: LegalPageProps) {
    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-background px-6 py-6 text-foreground lg:px-8">
                <header className="mx-auto flex w-full max-w-4xl items-center justify-between gap-4">
                    <Link
                        href="/"
                        className="flex items-center gap-2 font-[Koulen] text-3xl leading-0 font-medium tracking-tight"
                    >
                        <AppLogoIcon className="-mt-[2px] h-7 w-auto fill-current text-foreground" />
                        <span>Koncat</span>
                    </Link>
                    <Link
                        href="/"
                        className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Home
                    </Link>
                </header>

                <main className="mx-auto mt-20 w-full max-w-4xl">
                    <div className="border-b border-border pb-10">
                        <div className="text-sm font-medium tracking-tight text-muted-foreground">
                            Updated {updatedAt}
                        </div>
                        <h1 className="mt-3 text-5xl leading-14 font-medium tracking-tighter text-foreground">
                            {title}
                        </h1>
                        <p className="mt-4 max-w-2xl text-xl leading-tight font-medium tracking-tight text-muted-foreground">
                            {description}
                        </p>
                    </div>

                    <article
                        className="mt-10 max-w-3xl text-base leading-7 text-muted-foreground [&_a]:font-medium [&_a]:text-foreground [&_a]:underline [&_a]:underline-offset-4 [&_h1]:hidden [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:leading-tight [&_h2]:font-medium [&_h2]:tracking-tight [&_h2]:text-foreground [&_h3]:mt-8 [&_h3]:font-medium [&_h3]:text-foreground [&_li]:mt-2 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-4 [&_strong]:font-medium [&_strong]:text-foreground [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-5"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                </main>

                <footer className="mx-auto mt-20 flex w-full max-w-4xl flex-col gap-3 border-t border-border py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <div>&copy; {new Date().getFullYear()} Koncat.</div>
                    <nav className="flex gap-5">
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
            </div>
        </>
    );
}
