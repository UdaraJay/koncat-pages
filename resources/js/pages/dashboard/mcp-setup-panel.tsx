import { MCPConnectionPanel } from '@/components/mcp-connection-panel';

export function MCPSetupPanel({ mcpUrl }: { mcpUrl: string }) {
    return (
        <section className="border p-4 text-card-foreground">
            <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(320px,540px)] lg:divide-x">
                <MCPConnectionPanel
                    mcpUrl={mcpUrl}
                    title="Give your agent this MCP URL and let it publish."
                    description="Add the MCP to your agent with the URL and authenticate with your email to publish."
                    className="flex min-w-0 flex-col justify-between space-y-3 lg:pr-4"
                    contentClassName="space-y-3"
                />

                <div className="max-w-sm space-y-4">
                    <div className="flex items-start gap-3 px-1">
                        <div className="flex size-7 shrink-0 items-center justify-center rounded-sm bg-muted text-sm font-medium text-foreground">
                            1
                        </div>
                        <div className="space-y-1">
                            <h2 className="mt-0.25 font-medium tracking-tight">
                                Install the MCP
                            </h2>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Add this MCP server to your agent, approve the
                                OAuth prompt to login.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-start gap-3 px-1">
                        <div className="flex size-7 shrink-0 items-center justify-center rounded-sm bg-muted text-sm font-medium text-foreground">
                            2
                        </div>
                        <div className="space-y-1">
                            <h2 className="mt-0.25 font-medium tracking-tight">
                                Tell your agent to publish
                            </h2>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Ask your agent to publish it's work using the
                                Koncat and it will appear here as a project.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-start gap-3 px-1">
                        <div className="flex size-7 shrink-0 items-center justify-center rounded-sm bg-muted text-sm font-medium text-foreground">
                            3
                        </div>
                        <div className="space-y-1">
                            <h2 className="mt-0.25 font-medium tracking-tight">
                                Use Koncat to manage
                            </h2>
                            <p className="max-w-2xl text-sm text-muted-foreground">
                                Share with specific people, create teams and
                                organize your pages.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
