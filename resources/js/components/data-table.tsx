import { Link } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
} from '@tanstack/react-table';
import { ExternalLink, Folder, MoreHorizontal, Rocket } from 'lucide-react';
import * as React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Project } from '@/types';

type ProjectRow = Project & {
    updatedLabel: string;
    workspaceLabel: string;
    deploymentStatus: 'Deployed' | 'Not deployed';
};

const columns: ColumnDef<ProjectRow>[] = [
    {
        id: 'select',
        header: ({ table }) => (
            <div className="flex items-center justify-center">
                <Checkbox
                    checked={
                        table.getIsAllPageRowsSelected() ||
                        (table.getIsSomePageRowsSelected() && 'indeterminate')
                    }
                    onCheckedChange={(value) =>
                        table.toggleAllPageRowsSelected(!!value)
                    }
                    aria-label="Select all"
                />
            </div>
        ),
        cell: ({ row }) => (
            <div className="flex items-center justify-center">
                <Checkbox
                    checked={row.getIsSelected()}
                    onCheckedChange={(value) => row.toggleSelected(!!value)}
                    aria-label="Select row"
                />
            </div>
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'name',
        header: 'Project',
        cell: ({ row }) => (
            <div className="min-w-48">
                <a
                    href={row.original.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 font-medium hover:underline"
                >
                    {row.original.name}
                    <ExternalLink className="size-3 text-muted-foreground" />
                </a>
                {row.original.description ? (
                    <div className="mt-1 line-clamp-1 text-sm text-muted-foreground">
                        {row.original.description}
                    </div>
                ) : null}
            </div>
        ),
        enableHiding: false,
    },
    {
        accessorKey: 'workspaceLabel',
        header: 'Workspace',
        cell: ({ row }) => {
            const workspace = row.original.workspace;
            const team = row.original.team;

            if (workspace?.slug && team?.slug) {
                return (
                    <Button variant="link" className="h-auto p-0" asChild>
                        <Link
                            href={`/${team.slug}/workspaces/${workspace.slug}`}
                        >
                            {workspace.name}
                        </Link>
                    </Button>
                );
            }

            return (
                <span className="text-muted-foreground">
                    {row.original.workspaceLabel}
                </span>
            );
        },
    },
    {
        accessorKey: 'deploymentStatus',
        header: 'Status',
        cell: ({ row }) => (
            <Badge
                variant={
                    row.original.deploymentStatus === 'Deployed'
                        ? 'default'
                        : 'secondary'
                }
            >
                {row.original.deploymentStatus === 'Deployed' ? (
                    <Rocket className="size-3" />
                ) : (
                    <Folder className="size-3" />
                )}
                {row.original.deploymentStatus}
            </Badge>
        ),
    },
    {
        accessorKey: 'deploymentsCount',
        header: () => <div className="text-right">Deployments</div>,
        cell: ({ row }) => (
            <div className="text-right tabular-nums">
                {row.original.deploymentsCount.toLocaleString()}
            </div>
        ),
    },
    {
        accessorKey: 'updatedLabel',
        header: 'Updated',
        cell: ({ row }) => (
            <span className="text-muted-foreground">
                {row.original.updatedLabel}
            </span>
        ),
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        className="flex size-8 text-muted-foreground data-[state=open]:bg-muted"
                        size="icon"
                    >
                        <MoreHorizontal />
                        <span className="sr-only">Open menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-40">
                    <DropdownMenuItem asChild>
                        <a
                            href={row.original.url}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink />
                            Open project
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        ),
    },
];

export function DataTable({ data }: { data: Project[] }) {
    const rows = React.useMemo<ProjectRow[]>(
        () =>
            data.map((project) => ({
                ...project,
                updatedLabel: formatDate(project.updatedAt),
                workspaceLabel:
                    project.workspace?.name ?? project.ownerName ?? 'Personal',
                deploymentStatus: project.currentDeployment
                    ? 'Deployed'
                    : 'Not deployed',
            })),
        [data],
    );
    const [rowSelection, setRowSelection] = React.useState({});
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>({});
    const [columnFilters, setColumnFilters] =
        React.useState<ColumnFiltersState>([]);
    const [sorting, setSorting] = React.useState<SortingState>([]);

    // TanStack Table returns imperative helpers that React Compiler intentionally skips.
    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data: rows,
        columns,
        state: {
            sorting,
            columnVisibility,
            rowSelection,
            columnFilters,
        },
        getRowId: (row) => String(row.id),
        enableRowSelection: true,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        initialState: {
            pagination: {
                pageSize: 10,
            },
        },
    });

    return (
        <div className="flex w-full flex-col gap-4 px-4 lg:px-6">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-medium">Projects</h2>
                    <p className="text-sm text-muted-foreground">
                        Visible projects across your personal and team scopes.
                    </p>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="sm">
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        {table
                            .getAllColumns()
                            .filter((column) => column.getCanHide())
                            .map((column) => (
                                <DropdownMenuCheckboxItem
                                    key={column.id}
                                    className="capitalize"
                                    checked={column.getIsVisible()}
                                    onCheckedChange={(value) =>
                                        column.toggleVisibility(!!value)
                                    }
                                >
                                    {column.id.replace(/([A-Z])/g, ' $1')}
                                </DropdownMenuCheckboxItem>
                            ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader className="bg-muted">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        colSpan={header.colSpan}
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && 'selected'
                                    }
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    No projects yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <div className="flex items-center justify-between gap-4 text-sm text-muted-foreground">
                <div>
                    {table.getFilteredSelectedRowModel().rows.length} of{' '}
                    {table.getFilteredRowModel().rows.length} selected
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => table.previousPage()}
                        disabled={!table.getCanPreviousPage()}
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => table.nextPage()}
                        disabled={!table.getCanNextPage()}
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    );
}

function formatDate(value?: string | null): string {
    if (!value) {
        return 'Never';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}
