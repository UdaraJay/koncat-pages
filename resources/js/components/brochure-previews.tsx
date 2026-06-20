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
        <div className="absolute inset-0 overflow-hidden bg-[#0d1210] text-[#f5fff9]">
            <div className="flex h-full flex-col p-6 pb-18 sm:p-8 sm:pb-18">
                <div className="flex items-start justify-between gap-6">
                    <div>
                        <div className="text-sm font-medium tracking-tight text-[#41d88b]">
                            Atlas Coffee Co.
                        </div>
                        <h2 className="mt-1 text-4xl leading-[0.95] font-medium tracking-tight sm:text-5xl">
                            Week 38 operating report
                        </h2>
                    </div>
                    <div className="flex items-center gap-2 text-xs font-medium text-[#8ca597]">
                        <span className="rounded-full bg-[#17201b] px-3 py-1">
                            Sep 16-22
                        </span>
                        <span className="rounded-full bg-[#41d88b] px-3 py-1 text-[#07100b]">
                            Live
                        </span>
                    </div>
                </div>

                <div className="mt-6 grid grid-cols-4 gap-3">
                    {[
                        ['$482k', 'Net revenue', '+14.2% vs last week'],
                        ['31.8%', 'Gross margin', '+4.6 pts recovered'],
                        ['1,284', 'Open orders', '188 new wholesale'],
                        ['3.1%', 'Late stock', 'down from 4.3%'],
                    ].map(([value, label, detail]) => (
                        <div
                            className="border border-[#223128] bg-[#141c18] p-4"
                            key={label}
                        >
                            <div className="text-2xl leading-none font-medium tracking-tight">
                                {value}
                            </div>
                            <div className="mt-2 text-xs text-[#8ca597]">
                                {label}
                            </div>
                            <div className="mt-4 text-xs font-medium text-[#41d88b]">
                                {detail}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-3 grid min-h-0 flex-1 grid-cols-[1fr_14rem] gap-3">
                    <section className="border border-[#223128] bg-[#101713] p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm font-medium tracking-tight">
                                    Net revenue by channel
                                </div>
                                <div className="mt-1 text-xs text-[#8ca597]">
                                    Retail stores and wholesale accounts
                                </div>
                            </div>
                            <div className="flex gap-3 text-xs font-medium text-[#8ca597]">
                                <span>Retail</span>
                                <span>Wholesale</span>
                            </div>
                        </div>

                        <div className="mt-6 h-48 bg-[#0a0f0c] p-4 text-[#41d88b]">
                            <svg
                                className="h-full w-full overflow-visible"
                                fill="none"
                                viewBox="0 0 560 188"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <defs>
                                    <linearGradient
                                        id="reportRevenueArea"
                                        x1="0"
                                        x2="0"
                                        y1="16"
                                        y2="188"
                                        gradientUnits="userSpaceOnUse"
                                    >
                                        <stop
                                            stopColor="currentColor"
                                            stopOpacity="0.26"
                                        />
                                        <stop
                                            offset="1"
                                            stopColor="currentColor"
                                            stopOpacity="0"
                                        />
                                    </linearGradient>
                                    <filter
                                        id="reportRevenueGlow"
                                        x="-12%"
                                        y="-35%"
                                        width="124%"
                                        height="170%"
                                        colorInterpolationFilters="sRGB"
                                    >
                                        <feGaussianBlur
                                            stdDeviation="5"
                                            result="blur"
                                        />
                                        <feColorMatrix
                                            in="blur"
                                            type="matrix"
                                            values="0 0 0 0 0.02 0 0 0 0 0.55 0 0 0 0 0.28 0 0 0 0.32 0"
                                        />
                                        <feBlend
                                            in="SourceGraphic"
                                            mode="normal"
                                        />
                                    </filter>
                                </defs>

                                {[36, 74, 112, 150].map((y) => (
                                    <path
                                        d={`M8 ${y}H552`}
                                        key={y}
                                        stroke="currentColor"
                                        strokeOpacity="0.1"
                                    />
                                ))}
                                {[52, 154, 256, 358, 460].map((x) => (
                                    <path
                                        d={`M${x} 16V172`}
                                        key={x}
                                        stroke="currentColor"
                                        strokeOpacity="0.06"
                                    />
                                ))}

                                <path
                                    d="M12 142C44 136 58 116 88 119C120 122 130 150 164 139C198 128 204 78 238 74C274 70 286 108 318 103C354 98 362 54 396 49C434 43 448 82 482 69C512 57 526 32 548 26V188H12V142Z"
                                    fill="url(#reportRevenueArea)"
                                />
                                <path
                                    d="M12 142C44 136 58 116 88 119C120 122 130 150 164 139C198 128 204 78 238 74C274 70 286 108 318 103C354 98 362 54 396 49C434 43 448 82 482 69C512 57 526 32 548 26"
                                    filter="url(#reportRevenueGlow)"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="5"
                                />
                                <path
                                    d="M12 156C52 151 79 147 116 149C164 152 183 159 226 151C269 143 296 129 342 132C400 136 436 116 480 107C514 100 534 98 548 94"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeOpacity="0.28"
                                    strokeWidth="3"
                                />
                                {[
                                    [238, 74],
                                    [396, 49],
                                    [548, 26],
                                ].map(([cx, cy]) => (
                                    <g key={`${cx}-${cy}`}>
                                        <circle
                                            cx={cx}
                                            cy={cy}
                                            r="9"
                                            fill="#0a0f0c"
                                        />
                                        <circle
                                            cx={cx}
                                            cy={cy}
                                            r="5"
                                            fill="currentColor"
                                        />
                                    </g>
                                ))}
                                <g transform="translate(430 18)">
                                    <rect
                                        width="98"
                                        height="34"
                                        fill="#141c18"
                                    />
                                    <text
                                        x="12"
                                        y="14"
                                        fill="currentColor"
                                        fontSize="10"
                                        fontWeight="600"
                                    >
                                        Friday
                                    </text>
                                    <text
                                        x="12"
                                        y="27"
                                        fill="currentColor"
                                        fontSize="13"
                                        fontWeight="700"
                                    >
                                        $118.4k
                                    </text>
                                </g>
                            </svg>
                        </div>
                        <div className="mt-3 grid grid-cols-5 font-mono text-xs font-medium text-[#6d8175]">
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                        </div>
                    </section>

                    <aside className="flex min-h-0 flex-col gap-3">
                        <div className="border border-[#223128] bg-[#141c18] p-4">
                            <div className="text-sm font-medium tracking-tight">
                                Forecast confidence
                            </div>
                            <div className="mt-3 flex items-end gap-1.5">
                                {[38, 52, 64, 58, 76, 82].map(
                                    (height, index) => (
                                        <div
                                            className="w-4 bg-[#41d88b]"
                                            key={`${height}-${index}`}
                                            style={{
                                                height: `${height / 3}px`,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                            <div className="mt-3 text-xs font-medium text-[#8ca597]">
                                Next week: $516k expected
                            </div>
                        </div>

                        <div className="flex-1 border border-[#223128] bg-[#141c18] p-4">
                            <div className="text-sm font-medium tracking-tight">
                                Notes for Monday
                            </div>
                            <div className="mt-4 space-y-3">
                                {[
                                    [
                                        'Ethiopia Guji is at 7 days cover.',
                                        'Move 18 bags from reserve.',
                                    ],
                                    [
                                        'Cafe renewal closes Friday.',
                                        '$42k upside if signed.',
                                    ],
                                    [
                                        'Freight cost is 2.4 pts high.',
                                        'Ask Linehaul for revised lane.',
                                    ],
                                ].map(([title, detail]) => (
                                    <div key={title}>
                                        <div className="text-sm font-medium">
                                            {title}
                                        </div>
                                        <div className="mt-1 text-xs text-[#8ca597]">
                                            {detail}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2 text-xs font-medium">
                            {['PDF', 'CSV', 'Share'].map((item, index) => (
                                <div
                                    className={`px-2 py-2 ${
                                        index === 2
                                            ? 'bg-[#41d88b] text-[#07100b]'
                                            : 'bg-[#141c18] text-[#8ca597]'
                                    }`}
                                    key={item}
                                >
                                    {item}
                                </div>
                            ))}
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    );
}

function FormPreview() {
    return (
        <div className="absolute inset-0 overflow-hidden bg-[#f7f9fc] text-[#151515]">
            <div className="grid h-full grid-cols-[13rem_1fr_13rem]">
                <aside className="flex flex-col justify-between bg-[#f2b705] p-6 pb-18">
                    <div>
                        <div className="text-sm font-semibold tracking-tight text-[#151515]/70">
                            Northstar Studio
                        </div>
                        <h2 className="mt-3 text-4xl leading-[0.92] font-medium tracking-tight">
                            Client project intake
                        </h2>
                    </div>
                    <div className="space-y-2 text-sm font-medium">
                        <div className="bg-[#151515] px-3 py-2 text-[#f2b705]">
                            12 new submissions
                        </div>
                        <div className="bg-[#fff3c4] px-3 py-2">CRM routed</div>
                    </div>
                </aside>

                <main className="flex min-h-0 flex-col p-6 pb-18">
                    <header className="flex items-start justify-between gap-6 border-b border-[#dbe1ea] pb-4">
                        <div>
                            <div className="max-w-md text-sm font-medium text-[#667085]">
                                A hosted request form for qualified client work,
                                routing, files, and launch dates.
                            </div>
                        </div>
                        <div className="h-2 w-28 bg-[#dbe1ea]">
                            <div className="h-full w-1/2 bg-[#f2b705]" />
                        </div>
                    </header>

                    <section className="mt-5 flex min-h-0 flex-1 flex-col bg-white p-5 shadow-sm shadow-[#98a2b3]/15">
                        <div className="flex min-h-0 flex-col">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="text-sm font-medium tracking-tight">
                                        Website redesign request
                                    </div>
                                    <div className="mt-1 text-xs text-[#667085]">
                                        Step 2 of 4: scope and budget
                                    </div>
                                </div>
                            </div>

                            <div className="mt-5 grid grid-cols-2 gap-3">
                                {[
                                    ['Company', 'Brightline Health'],
                                    ['Contact', 'Rina Patel'],
                                ].map(([label, value]) => (
                                    <div key={label}>
                                        <div className="text-xs font-medium text-muted-foreground">
                                            {label}
                                        </div>
                                        <div className="mt-1 truncate rounded-md bg-muted px-3 py-2.5 text-sm font-medium">
                                            {value}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-5">
                                <div className="text-xs font-medium text-muted-foreground">
                                    What should the agent build?
                                </div>
                                <div className="mt-2 grid grid-cols-3 gap-2 text-center text-sm font-medium">
                                    {['Portal', 'Report', 'Workflow'].map(
                                        (label, index) => (
                                            <div
                                                className={`rounded-md px-3 py-2 ${
                                                    index === 2
                                                        ? 'bg-amber-600 text-background'
                                                        : 'bg-muted text-muted-foreground'
                                                }`}
                                                key={label}
                                            >
                                                {label}
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>

                            <div className="mt-5 grid grid-cols-[1fr_9rem] gap-3">
                                <div>
                                    <div className="text-xs font-medium text-[#667085]">
                                        Budget range
                                    </div>
                                    <div className="mt-2 bg-[#f2f4f7] p-3">
                                        <div className="h-2 bg-[#dbe1ea]">
                                            <div className="h-full w-3/4 bg-[#f2b705]" />
                                        </div>
                                        <div className="mt-3 flex justify-between text-xs font-medium text-[#667085]">
                                            <span>$18k</span>
                                            <span>$30k</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium text-[#667085]">
                                        Launch
                                    </div>
                                    <div className="mt-2 bg-[#f2f4f7] px-3 py-3 text-sm font-medium">
                                        Oct 14
                                    </div>
                                </div>
                            </div>

                            <div className="mt-5 bg-[#f2f4f7] p-3">
                                <div className="text-xs font-medium text-[#667085]">
                                    Project context
                                </div>
                                <div className="mt-2 text-sm leading-snug font-medium">
                                    Need a private intake portal for clinic
                                    partners, with file upload and weekly status
                                    summaries.
                                </div>
                            </div>

                            <div className="mt-auto flex items-center justify-between pt-5">
                                <div className="bg-[#fff3c4] px-3 py-1 text-xs font-medium text-[#8a5b00]">
                                    3 files attached
                                </div>
                                <div className="bg-[#151515] px-5 py-2 text-sm font-medium text-white">
                                    Submit request
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <aside className="flex min-h-0 flex-col gap-3 bg-[#eef2f7] p-6 pb-18">
                    <div className="bg-white p-4">
                        <div className="text-xs font-medium text-[#667085]">
                            Intake quality
                        </div>
                        <div className="mt-2 text-3xl leading-none font-medium tracking-tight">
                            84%
                        </div>
                        <div className="mt-3 h-2 bg-[#dbe1ea]">
                            <div className="h-full w-4/5 bg-[#f2b705]" />
                        </div>
                    </div>

                    <div className="bg-white p-4">
                        <div className="text-xs font-medium text-[#667085]">
                            Assigned owner
                        </div>
                        <div className="mt-3 flex items-center gap-2">
                            <div className="grid size-8 place-items-center rounded-full bg-[#f2b705] text-xs font-medium">
                                RP
                            </div>
                            <div className="min-w-0">
                                <div className="truncate text-sm font-medium">
                                    Rina Patel
                                </div>
                                <div className="text-xs text-[#667085]">
                                    Enterprise lead
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex-1 bg-white p-4">
                        <div className="text-xs font-medium text-[#667085]">
                            Automations
                        </div>
                        <div className="mt-3 space-y-2 text-sm font-medium">
                            <div className="bg-[#f2f4f7] px-2 py-1">
                                brief.pdf saved
                            </div>
                            <div className="bg-[#f2f4f7] px-2 py-1">
                                Slack ping sent
                            </div>
                            <div className="bg-[#151515] px-2 py-1 text-white">
                                CRM task created
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}

function WeddingPreview() {
    return (
        <div className="absolute inset-0 overflow-hidden bg-[#f7f1ec] text-[#2d2421]">
            <div className="grid h-full grid-cols-[0.92fr_1.08fr] p-7 pb-18 sm:p-9 sm:pb-18">
                <main className="flex min-w-0 flex-col justify-between pr-8">
                    <nav className="flex items-center justify-between gap-5 text-xs font-medium tracking-[0.22em] text-[#9a716b] uppercase">
                        <span>Maya & Theo</span>
                        <span>Hudson Valley</span>
                    </nav>

                    <section>
                        <div className="font-[Porpora] text-xl text-[#a85f68] italic">
                            September 21, 2026
                        </div>
                        <h2 className="mt-5 max-w-md font-[Porpora] text-7xl leading-[0.82] font-normal tracking-tight">
                            Maya
                            <br />& Theo
                        </h2>
                        <div className="mt-6 max-w-sm text-lg leading-snug font-medium tracking-tight">
                            Willow House, Hudson Valley
                        </div>
                        <div className="mt-3 max-w-sm text-sm leading-snug font-medium text-[#7b625c]">
                            Dinner under the trees, late-summer music, and buses
                            back after the last dance.
                        </div>
                    </section>

                    <div>
                        <div className="mb-3 flex items-center justify-between border-b border-[#d9c9c0] pb-2 text-xs font-medium tracking-[0.18em] text-[#9a716b] uppercase">
                            <span>Weekend</span>
                            <span>RSVP by August 20</span>
                        </div>
                        <div className="space-y-0">
                            {[
                                ['Fri', 'Welcome drinks', '7:00 PM'],
                                ['Sat', 'Ceremony', '5:30 PM'],
                                ['Sun', 'Farewell brunch', '10:30 AM'],
                            ].map(([day, title, time], index) => (
                                <div
                                    className={`grid grid-cols-[2.5rem_1fr_auto] items-baseline gap-4 border-b border-[#e5d8d0] py-3 ${
                                        index === 1
                                            ? 'text-[#a85f68]'
                                            : 'text-[#2d2421]'
                                    }`}
                                    key={day}
                                >
                                    <div className="text-xs font-medium tracking-[0.16em] uppercase">
                                        {day}
                                    </div>
                                    <div className="font-[Porpora] text-lg leading-tight">
                                        {title}
                                    </div>
                                    <div className="text-xs font-medium text-[#7b625c]">
                                        {time}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </main>

                <aside className="relative min-w-0 bg-[#fffaf6] p-3 shadow-sm shadow-[#7b625c]/10">
                    <div className="relative h-full overflow-hidden">
                        <img
                            className="h-full w-full object-cover"
                            src="/assets/wedding.png"
                            alt=""
                            aria-hidden="true"
                        />
                        <div className="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-[#2d2421]/55 to-transparent" />

                        <div className="absolute top-4 left-4 bg-[#fffaf6]/92 px-4 py-3 text-[#2d2421] backdrop-blur-sm">
                            <div className="font-[Porpora] text-lg leading-none">
                                Willow House
                            </div>
                            <div className="mt-1 text-xs font-medium text-[#7b625c]">
                                Garden ceremony at 5:30 PM
                            </div>
                        </div>

                        <div className="absolute right-4 bottom-4 left-4 bg-[#fffaf6]/92 p-4 backdrop-blur-sm">
                            <div className="flex items-end justify-between gap-4">
                                <div>
                                    <div className="text-xs font-medium tracking-[0.16em] text-[#9a716b] uppercase">
                                        Your reply
                                    </div>
                                    <div className="mt-1 font-[Porpora] text-xl leading-tight">
                                        Two seats saved
                                    </div>
                                </div>
                                <div className="text-right text-xs leading-tight font-medium text-[#7b625c]">
                                    chicken
                                    <br />
                                    vegetarian
                                </div>
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
        <div className="h-[28rem] w-full bg-muted p-3 sm:h-[34rem] lg:h-170">
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

                <div className="absolute inset-px z-10 overflow-hidden rounded-[23px] border-3 border-white bg-background">
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
                        fill="white"
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
