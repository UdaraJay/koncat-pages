'use client';

import * as React from 'react';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useIsMobile } from '@/hooks/use-mobile';

export type ActivityPoint = {
    date: string;
    created: number;
    updated: number;
};

const chartConfig = {
    created: {
        label: 'Created',
        color: 'var(--primary)',
    },
    updated: {
        label: 'Updated',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

export function ChartAreaInteractive({ data }: { data: ActivityPoint[] }) {
    const isMobile = useIsMobile();
    const [timeRange, setTimeRange] = React.useState('90d');
    const activeTimeRange = isMobile && timeRange === '90d' ? '7d' : timeRange;

    const filteredData = React.useMemo(() => {
        const daysToKeep =
            activeTimeRange === '7d' ? 7 : activeTimeRange === '30d' ? 30 : 90;

        return data.slice(Math.max(data.length - daysToKeep, 0));
    }, [activeTimeRange, data]);

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardTitle>Project Activity</CardTitle>
                <CardDescription>
                    <span className="hidden @[540px]/card:block">
                        Created and updated projects across the selected window
                    </span>
                    <span className="@[540px]/card:hidden">
                        Recent project changes
                    </span>
                </CardDescription>
                <CardAction>
                    <ToggleGroup
                        type="single"
                        value={activeTimeRange}
                        onValueChange={(value) => {
                            if (value) {
                                setTimeRange(value);
                            }
                        }}
                        variant="outline"
                        className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
                    >
                        <ToggleGroupItem value="90d">90 days</ToggleGroupItem>
                        <ToggleGroupItem value="30d">30 days</ToggleGroupItem>
                        <ToggleGroupItem value="7d">7 days</ToggleGroupItem>
                    </ToggleGroup>
                    <Select
                        value={activeTimeRange}
                        onValueChange={setTimeRange}
                    >
                        <SelectTrigger
                            className="flex w-32 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
                            size="sm"
                            aria-label="Select time range"
                        >
                            <SelectValue placeholder="90 days" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl">
                            <SelectItem value="90d" className="rounded-lg">
                                90 days
                            </SelectItem>
                            <SelectItem value="30d" className="rounded-lg">
                                30 days
                            </SelectItem>
                            <SelectItem value="7d" className="rounded-lg">
                                7 days
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardAction>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer
                    config={chartConfig}
                    className="aspect-auto h-[250px] w-full"
                >
                    <AreaChart data={filteredData}>
                        <defs>
                            <linearGradient
                                id="fillCreated"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-created)"
                                    stopOpacity={0.9}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-created)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                            <linearGradient
                                id="fillUpdated"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-updated)"
                                    stopOpacity={0.5}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-updated)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={32}
                            tickFormatter={(value) =>
                                new Date(
                                    `${value}T00:00:00`,
                                ).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                })
                            }
                        />
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    labelFormatter={(value) =>
                                        new Date(
                                            `${value}T00:00:00`,
                                        ).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                    }
                                    indicator="dot"
                                />
                            }
                        />
                        <Area
                            dataKey="updated"
                            type="natural"
                            fill="url(#fillUpdated)"
                            stroke="var(--color-updated)"
                            stackId="a"
                        />
                        <Area
                            dataKey="created"
                            type="natural"
                            fill="url(#fillCreated)"
                            stroke="var(--color-created)"
                            stackId="a"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
