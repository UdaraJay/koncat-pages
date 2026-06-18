import type { ReactNode } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import BrochureWebglPreview from '@/components/brochure-webgl-preview';

type Size = {
    width: number;
    height: number;
};

type BottomTabPathOptions = Size & {
    inset?: number;
    tabWidth?: number;
    tabHeight?: number;
    tabRadius?: number;
    shoulder?: number;
};

type PreviewExample = {
    accentClassName: string;
    label: string;
    node: ReactNode;
    title: string;
};

const FALLBACK_SIZE = {
    width: 960,
    height: 648,
};

const PREVIEW_EXAMPLES: PreviewExample[] = [
    {
        accentClassName: 'bg-emerald-500',
        label: 'WebGL',
        node: <BrochureWebglPreview />,
        title: 'Interactive product demo',
    },
    {
        accentClassName: 'bg-sky-500',
        label: 'Report',
        node: <ReportPreview />,
        title: 'Revenue report',
    },
    {
        accentClassName: 'bg-amber-500',
        label: 'Form',
        node: <FormPreview />,
        title: 'Client intake form',
    },
    {
        accentClassName: 'bg-rose-500',
        label: 'Wedding',
        node: <WeddingPreview />,
        title: 'Maya and Theo',
    },
];

function useElementSize<T extends HTMLElement>() {
    const ref = useRef<T>(null);
    const [size, setSize] = useState<Size>(FALLBACK_SIZE);

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const updateSize = () => {
            const rect = element.getBoundingClientRect();

            setSize({
                width: Math.max(1, rect.width),
                height: Math.max(1, rect.height),
            });
        };

        updateSize();

        if (typeof ResizeObserver === 'undefined') {
            window.addEventListener('resize', updateSize);

            return () => window.removeEventListener('resize', updateSize);
        }

        const observer = new ResizeObserver(updateSize);
        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return [ref, size] as const;
}

function makeRoundedPreviewPath({
    width,
    height,
    radius = 24,
    inset = 1,
}: Size & { radius?: number; inset?: number }) {
    const x0 = inset;
    const x1 = width - inset;
    const y0 = inset;
    const y1 = height - inset;
    const r = Math.min(radius, width / 2, height / 2);

    return `
        M ${x0 + r} ${y0}
        H ${x1 - r}
        Q ${x1} ${y0} ${x1} ${y0 + r}
        V ${y1 - r}
        Q ${x1} ${y1} ${x1 - r} ${y1}
        H ${x0 + r}
        Q ${x0} ${y1} ${x0} ${y1 - r}
        V ${y0 + r}
        Q ${x0} ${y0} ${x0 + r} ${y0}
        Z
    `;
}

function makeInsideBottomTabPath({
    width,
    height,
    inset = 1,
    tabWidth = 560,
    tabHeight = 46,
    tabRadius = 22,
    shoulder = 18,
}: BottomTabPathOptions) {
    const effectiveTabWidth = Math.min(tabWidth, Math.max(280, width - 44));
    const yBottom = height - inset;
    const yTop = yBottom - tabHeight;
    const tabLeft = (width - effectiveTabWidth) / 2;
    const tabRight = tabLeft + effectiveTabWidth;
    const r = Math.min(tabRadius, tabHeight / 2);
    const s = Math.min(shoulder, tabHeight - r);

    return {
        d: `
            M ${tabLeft - s} ${yBottom}
            Q ${tabLeft} ${yBottom} ${tabLeft} ${yBottom - s}
            V ${yTop + r}
            Q ${tabLeft} ${yTop} ${tabLeft + r} ${yTop}
            H ${tabRight - r}
            Q ${tabRight} ${yTop} ${tabRight} ${yTop + r}
            V ${yBottom - s}
            Q ${tabRight} ${yBottom} ${tabRight + s} ${yBottom}
            H ${tabLeft - s}
            Z
        `,
        height: tabHeight,
        left: tabLeft,
        top: yTop,
        width: effectiveTabWidth,
    };
}

function ReportPreview() {
    return (
        <div className="absolute inset-0 overflow-hidden bg-[#f4f6f8] text-[#101820]">
            <div className="grid h-full grid-cols-[176px_1fr]">
                <aside className="relative border-r border-[#dfe5e8] bg-[#fbfcfc] px-5 py-6">
                    <div className="flex items-center gap-2">
                        <div className="grid size-8 place-items-center rounded-md bg-[#101820] text-xs font-semibold text-white">
                            AC
                        </div>
                        <div>
                            <div className="text-sm font-semibold">
                                Atlas Coffee
                            </div>
                            <div className="text-xs font-medium text-[#748089]">
                                Board pack
                            </div>
                        </div>
                    </div>

                    <div className="mt-7 space-y-1">
                        {['Summary', 'Channels', 'Inventory', 'Notes'].map(
                            (item, index) => (
                                <div
                                    className={`rounded-md px-3 py-2 text-sm font-medium ${
                                        index === 0
                                            ? 'bg-[#dcefe8] text-[#175c4d]'
                                            : 'text-[#6f7b84]'
                                    }`}
                                    key={item}
                                >
                                    {item}
                                </div>
                            ),
                        )}
                    </div>

                    <div className="absolute right-5 bottom-18 left-5 rounded-md border border-[#dfe5e8] bg-white p-3">
                        <div className="text-xs font-medium text-[#748089]">
                            Forecast confidence
                        </div>
                        <div className="mt-2 flex items-end gap-1">
                            {[38, 52, 64, 58, 76, 82].map((height, index) => (
                                <div
                                    className="w-3 rounded-sm bg-[#175c4d]"
                                    key={`${height}-${index}`}
                                    style={{ height: `${height / 4}px` }}
                                />
                            ))}
                        </div>
                    </div>
                </aside>

                <main className="min-w-0 px-8 py-6 pb-16">
                    <div className="flex items-start justify-between gap-6">
                        <div>
                            <div className="text-xs font-semibold text-[#3d7b6c] uppercase">
                                Week 38 executive report
                            </div>
                            <div className="mt-2 text-3xl font-semibold">
                                Wholesale margin recovered to 31.8%
                            </div>
                        </div>
                        <div className="rounded-md border border-[#dfe5e8] bg-white px-4 py-2 text-sm font-medium text-[#59656e]">
                            Synced 09:41
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-4 gap-3">
                        {[
                            ['Revenue', '$482k', '+14.2%', 'text-[#176a4b]'],
                            [
                                'Gross margin',
                                '31.8%',
                                '+4.6%',
                                'text-[#176a4b]',
                            ],
                            ['Open orders', '1,284', '+188', 'text-[#7b4d12]'],
                            ['Late stock', '3.1%', '-1.2%', 'text-[#176a4b]'],
                        ].map(([label, value, change, color]) => (
                            <div
                                className="rounded-md border border-[#dfe5e8] bg-white p-4"
                                key={label}
                            >
                                <div className="text-xs font-medium text-[#748089]">
                                    {label}
                                </div>
                                <div className="mt-2 text-2xl font-semibold">
                                    {value}
                                </div>
                                <div
                                    className={`mt-1 text-xs font-semibold ${color}`}
                                >
                                    {change}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 grid grid-cols-[1fr_220px] gap-4">
                        <section className="rounded-md border border-[#dfe5e8] bg-white p-5">
                            <div className="flex items-center justify-between">
                                <div className="text-sm font-semibold">
                                    Net revenue by channel
                                </div>
                                <div className="flex gap-2 text-xs font-medium text-[#748089]">
                                    <span>Retail</span>
                                    <span>Wholesale</span>
                                </div>
                            </div>
                            <div className="mt-5 flex h-44 items-end gap-2 border-b border-[#dfe5e8] pb-2">
                                {[42, 48, 55, 61, 74, 71, 86, 92, 88, 97].map(
                                    (height, index) => (
                                        <div
                                            className="flex flex-1 items-end gap-1"
                                            key={`${height}-${index}`}
                                        >
                                            <div
                                                className="w-full rounded-t-sm bg-[#b7d8cc]"
                                                style={{
                                                    height: `${height}%`,
                                                }}
                                            />
                                            <div
                                                className="w-full rounded-t-sm bg-[#101820]"
                                                style={{
                                                    height: `${Math.max(height - 18, 20)}%`,
                                                }}
                                            />
                                        </div>
                                    ),
                                )}
                            </div>
                            <div className="mt-3 grid grid-cols-5 text-xs font-medium text-[#748089]">
                                <span>Apr</span>
                                <span>May</span>
                                <span>Jun</span>
                                <span>Jul</span>
                                <span>Aug</span>
                            </div>
                        </section>

                        <section className="rounded-md border border-[#dfe5e8] bg-white p-5">
                            <div className="text-sm font-semibold">
                                Watch list
                            </div>
                            <div className="mt-4 space-y-3">
                                {[
                                    ['Ethiopia lots', '7 days cover'],
                                    ['Cafe renewal', '$42k upside'],
                                    ['Freight cost', '2.4 pts high'],
                                ].map(([title, detail]) => (
                                    <div
                                        className="border-b border-[#edf1f2] pb-3 last:border-0 last:pb-0"
                                        key={title}
                                    >
                                        <div className="text-sm font-semibold">
                                            {title}
                                        </div>
                                        <div className="mt-1 text-xs font-medium text-[#748089]">
                                            {detail}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    );
}

function FormPreview() {
    return (
        <div className="absolute inset-0 overflow-hidden bg-[#eef4f1] text-[#17201c]">
            <div className="grid h-full grid-cols-[0.92fr_1.08fr]">
                <section className="flex flex-col justify-between border-r border-[#d5ded9] bg-[#fbfcfa] px-10 py-8 pb-18">
                    <div>
                        <div className="inline-flex rounded-full bg-[#d9eadf] px-3 py-1 text-xs font-semibold text-[#1f624a]">
                            Client intake
                        </div>
                        <h2 className="mt-5 max-w-sm text-5xl leading-[0.95] font-semibold">
                            Scope the project before the first call.
                        </h2>
                        <p className="mt-4 max-w-sm text-base leading-snug font-medium text-[#65736d]">
                            Field logic, budget ranges, file requests, and
                            follow-up routing in one hosted form.
                        </p>
                    </div>

                    <div className="grid max-w-sm grid-cols-3 gap-2">
                        {['Brief', 'Budget', 'Files'].map((item, index) => (
                            <div
                                className={`rounded-md border px-3 py-2 text-sm font-semibold ${
                                    index === 1
                                        ? 'border-[#17201c] bg-[#17201c] text-white'
                                        : 'border-[#d5ded9] bg-white text-[#65736d]'
                                }`}
                                key={item}
                            >
                                {item}
                            </div>
                        ))}
                    </div>
                </section>

                <section className="px-8 py-7 pb-16">
                    <div className="rounded-md border border-[#d5ded9] bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b border-[#e5ebe8] px-5 py-4">
                            <div>
                                <div className="text-sm font-semibold">
                                    Project request
                                </div>
                                <div className="mt-1 text-xs font-medium text-[#718079]">
                                    Step 2 of 4
                                </div>
                            </div>
                            <div className="h-2 w-28 rounded-full bg-[#e5ebe8]">
                                <div className="h-full w-1/2 rounded-full bg-[#1f624a]" />
                            </div>
                        </div>

                        <div className="p-5">
                            <div className="grid grid-cols-2 gap-3">
                                {[
                                    ['Company', 'Northstar Studio'],
                                    ['Contact', 'Rina Patel'],
                                ].map(([label, value]) => (
                                    <label className="block" key={label}>
                                        <span className="text-xs font-semibold text-[#718079]">
                                            {label}
                                        </span>
                                        <span className="mt-1 block rounded-md border border-[#d5ded9] bg-[#fbfcfa] px-3 py-2.5 text-sm font-medium">
                                            {value}
                                        </span>
                                    </label>
                                ))}
                            </div>

                            <label className="mt-4 block">
                                <span className="text-xs font-semibold text-[#718079]">
                                    What are you launching?
                                </span>
                                <span className="mt-1 grid grid-cols-3 gap-2">
                                    {['Portal', 'Report', 'Workflow'].map(
                                        (label, index) => (
                                            <span
                                                className={`rounded-md border px-3 py-2 text-center text-sm font-semibold ${
                                                    index === 2
                                                        ? 'border-[#1f624a] bg-[#e8f3ed] text-[#1f624a]'
                                                        : 'border-[#d5ded9] text-[#65736d]'
                                                }`}
                                                key={label}
                                            >
                                                {label}
                                            </span>
                                        ),
                                    )}
                                </span>
                            </label>

                            <label className="mt-4 block">
                                <span className="text-xs font-semibold text-[#718079]">
                                    Budget range
                                </span>
                                <span className="mt-1 grid grid-cols-[1fr_auto_1fr] items-center gap-3 rounded-md border border-[#d5ded9] bg-[#fbfcfa] px-3 py-3">
                                    <span className="h-2 rounded-full bg-[#cdd9d4]">
                                        <span className="block h-full w-3/4 rounded-full bg-[#1f624a]" />
                                    </span>
                                    <span className="text-sm font-semibold">
                                        $18k
                                    </span>
                                    <span className="text-right text-sm font-medium text-[#718079]">
                                        $30k
                                    </span>
                                </span>
                            </label>

                            <label className="mt-4 block">
                                <span className="text-xs font-semibold text-[#718079]">
                                    Key context
                                </span>
                                <span className="mt-1 block rounded-md border border-[#d5ded9] bg-[#fbfcfa] p-3">
                                    <span className="block h-2 w-11/12 rounded-full bg-[#cdd9d4]" />
                                    <span className="mt-2 block h-2 w-4/5 rounded-full bg-[#dce5e0]" />
                                    <span className="mt-2 block h-2 w-2/3 rounded-full bg-[#dce5e0]" />
                                </span>
                            </label>

                            <div className="mt-5 flex items-center justify-between">
                                <div className="flex items-center gap-2 text-sm font-semibold text-[#1f624a]">
                                    <span className="grid size-6 place-items-center rounded-full bg-[#e8f3ed]">
                                        3
                                    </span>
                                    files attached
                                </div>
                                <div className="rounded-full bg-[#17201c] px-5 py-2 text-sm font-semibold text-white">
                                    Continue
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}

function WeddingPreview() {
    return (
        <div className="absolute inset-0 overflow-hidden bg-[#f8faf7] text-[#23191a]">
            <div className="grid h-full grid-cols-[1fr_0.95fr]">
                <main className="flex flex-col justify-between px-12 py-8 pb-18">
                    <nav className="flex items-center justify-between text-sm font-semibold text-[#6d7a64]">
                        <span>Maya & Theo</span>
                        <div className="flex gap-5">
                            <span>Story</span>
                            <span>Weekend</span>
                            <span>RSVP</span>
                        </div>
                    </nav>

                    <section>
                        <div className="font-serif text-lg text-[#a75862] italic">
                            Willow House, Hudson Valley
                        </div>
                        <h2 className="mt-5 max-w-lg font-serif text-7xl leading-[0.9]">
                            We are getting married.
                        </h2>
                        <p className="mt-5 max-w-md text-lg leading-snug font-medium text-[#746862]">
                            Join us for dinner under the trees, late-summer
                            music, and a quiet weekend with the people we love.
                        </p>
                    </section>

                    <div className="grid max-w-xl grid-cols-3 gap-3">
                        {[
                            ['Fri', 'Welcome drinks', '7:00 PM'],
                            ['Sat', 'Ceremony', '5:30 PM'],
                            ['Sun', 'Brunch', '10:30 AM'],
                        ].map(([day, title, time]) => (
                            <div
                                className="rounded-md border border-[#dfe6d9] bg-white px-4 py-3"
                                key={day}
                            >
                                <div className="text-xs font-semibold text-[#a75862]">
                                    {day}
                                </div>
                                <div className="mt-1 text-sm font-semibold">
                                    {title}
                                </div>
                                <div className="mt-1 text-xs font-medium text-[#746862]">
                                    {time}
                                </div>
                            </div>
                        ))}
                    </div>
                </main>

                <aside className="relative overflow-hidden bg-[#dfe8d9] px-8 py-8 pb-18">
                    <div className="absolute inset-x-8 top-8 bottom-18 overflow-hidden rounded-md bg-[#829477]">
                        <div className="absolute inset-0 bg-[linear-gradient(160deg,#43513f,#829477_38%,#c3b194_70%,#f3d4c2)]" />
                        <div className="absolute inset-x-0 bottom-0 h-1/2 bg-[linear-gradient(180deg,transparent,#34402f)]" />
                        <div className="absolute top-8 left-6 h-32 w-16 rounded-t-full bg-[#263223]/70" />
                        <div className="absolute top-2 left-26 h-48 w-20 rounded-t-full bg-[#33452f]/60" />
                        <div className="absolute top-10 right-10 h-40 w-18 rounded-t-full bg-[#465d40]/60" />
                        <div className="absolute right-8 bottom-10 left-8 rounded-md border border-white/35 bg-white/18 p-4 text-white backdrop-blur-sm">
                            <div className="text-sm font-semibold">
                                Black-tie garden ceremony
                            </div>
                            <div className="mt-1 text-xs font-medium opacity-85">
                                Shuttle pickup at 4:45 PM
                            </div>
                        </div>
                    </div>

                    <div className="absolute right-12 bottom-18 left-12 rounded-md bg-white p-4 shadow-xl shadow-[#65745c]/20">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <div className="text-sm font-semibold">
                                    RSVP received
                                </div>
                                <div className="mt-1 text-xs font-medium text-[#746862]">
                                    118 guests confirmed
                                </div>
                            </div>
                            <div className="rounded-full bg-[#23191a] px-4 py-2 text-sm font-semibold text-white">
                                Reply
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}

export default function BrochurePreviews({
    projectName,
}: {
    projectName?: string;
}) {
    const [previewRef, size] = useElementSize<HTMLDivElement>();
    const [activeExampleIndex, setActiveExampleIndex] = useState(0);
    const previewPath = useMemo(() => makeRoundedPreviewPath(size), [size]);
    const tab = useMemo(() => makeInsideBottomTabPath(size), [size]);
    const activeExample = PREVIEW_EXAMPLES[activeExampleIndex];
    const activeProjectName = projectName ?? activeExample.title;

    useEffect(() => {
        const reducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (reducedMotion) {
            return;
        }

        const interval = window.setInterval(() => {
            setActiveExampleIndex(
                (current) => (current + 1) % PREVIEW_EXAMPLES.length,
            );
        }, 4200);

        return () => window.clearInterval(interval);
    }, []);

    return (
        <div className="h-170 w-full bg-muted p-4">
            <div ref={previewRef} className="relative h-full overflow-hidden">
                <svg
                    className="pointer-events-none absolute inset-0 z-0 h-full w-full"
                    viewBox={`0 0 ${size.width} ${size.height}`}
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d={previewPath}
                        fill="var(--color-background)"
                        stroke="none"
                        strokeWidth="0"
                        vectorEffect="non-scaling-stroke"
                    />
                </svg>

                <div className="absolute inset-px z-10 overflow-hidden rounded-[23px] border-3 border-background bg-background">
                    {PREVIEW_EXAMPLES.map((example, index) => (
                        <div
                            className={`absolute inset-0 transition duration-700 ${
                                index === activeExampleIndex
                                    ? 'opacity-100'
                                    : 'pointer-events-none opacity-0'
                            }`}
                            key={example.label}
                            aria-hidden={index !== activeExampleIndex}
                        >
                            {example.node}
                        </div>
                    ))}
                </div>

                <svg
                    className="pointer-events-none absolute inset-0 z-20 h-full w-full"
                    viewBox={`0 0 ${size.width} ${size.height}`}
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d={tab.d}
                        fill="var(--color-background)"
                        stroke="none"
                        strokeWidth="0"
                        vectorEffect="non-scaling-stroke"
                    />
                </svg>

                <div
                    className="absolute z-30 flex items-center justify-between gap-3 px-4"
                    style={{
                        height: tab.height,
                        left: tab.left,
                        top: tab.top,
                        width: tab.width,
                    }}
                >
                    <div className="flex min-w-0 items-center gap-2">
                        <AppLogoIcon className="h-5 w-auto shrink-0 text-foreground" />
                        <span className="truncate text-sm font-medium text-foreground">
                            {activeProjectName}
                        </span>
                    </div>

                    <div className="flex shrink-0 items-center gap-2">
                        <div
                            className="hidden items-center gap-1.5 sm:flex"
                            aria-label="Preview examples"
                        >
                            {PREVIEW_EXAMPLES.map((example, index) => (
                                <button
                                    className={`h-2.5 rounded-full transition ${
                                        index === activeExampleIndex
                                            ? `w-6 ${example.accentClassName}`
                                            : 'w-2.5 bg-muted-foreground/25 hover:bg-muted-foreground/45'
                                    }`}
                                    key={example.label}
                                    type="button"
                                    aria-label={`Show ${example.label} preview`}
                                    aria-pressed={index === activeExampleIndex}
                                    onClick={() => setActiveExampleIndex(index)}
                                />
                            ))}
                        </div>

                        <button
                            className="hidden h-8 rounded-full px-3 text-sm font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground sm:inline-flex sm:items-center"
                            type="button"
                        >
                            Manage
                        </button>
                        <button
                            className="inline-flex h-8 items-center rounded-full bg-foreground px-3 text-sm font-medium text-background transition hover:bg-foreground/90"
                            type="button"
                        >
                            Share
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
