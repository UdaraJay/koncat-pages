import { Rocket, Server, SquareActivity, Workflow } from 'lucide-react';
import {
    Card,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type DashboardStats = {
    projects: number;
    deployedProjects: number;
    workspaces: number;
    deployments: number;
};

export function SectionCards({ stats }: { stats: DashboardStats }) {
    const deployedPercent =
        stats.projects > 0
            ? Math.round((stats.deployedProjects / stats.projects) * 100)
            : 0;

    const cards = [
        {
            label: 'Projects',
            value: stats.projects.toLocaleString(),
            icon: Workflow,
            footer: `${stats.deployedProjects.toLocaleString()} deployed`,
        },
        {
            label: 'Deployment coverage',
            value: `${deployedPercent}%`,
            icon: Rocket,
            footer: 'Projects with a current deployment',
        },
        {
            label: 'Workspaces',
            value: stats.workspaces.toLocaleString(),
            icon: Server,
            footer: 'Accessible in the current team',
        },
        {
            label: 'Total deployments',
            value: stats.deployments.toLocaleString(),
            icon: SquareActivity,
            footer: 'Across visible projects',
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
            {cards.map((card) => (
                <Card key={card.label} className="@container/card">
                    <CardHeader>
                        <CardDescription>{card.label}</CardDescription>
                        <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                            {card.value}
                        </CardTitle>
                        <card.icon className="absolute top-5 right-5 size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardFooter className="text-sm text-muted-foreground">
                        {card.footer}
                    </CardFooter>
                </Card>
            ))}
        </div>
    );
}
