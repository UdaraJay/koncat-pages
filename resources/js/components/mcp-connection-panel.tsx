import { Check, Copy } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { ClaudeIcon, CursorIcon, VSCodeIcon } from '@/icons';
import { cn } from '@/lib/utils';

export function MCPConnectionPanel({
    mcpUrl,
    title,
    description,
    className,
    contentClassName,
    titleClassName,
    inputWrapperClassName,
    inputClassName,
    actionsClassName,
    descriptionClassName,
}: {
    mcpUrl: string;
    title: string;
    description: string;
    className?: string;
    contentClassName?: string;
    titleClassName?: string;
    inputWrapperClassName?: string;
    inputClassName?: string;
    actionsClassName?: string;
    descriptionClassName?: string;
}) {
    const [copiedText, copy] = useClipboard();
    const mcpUrlCopied = copiedText === mcpUrl;
    const encodedMcpUrl = encodeURIComponent(mcpUrl);
    const cursorConfig =
        typeof window === 'undefined'
            ? ''
            : window.btoa(JSON.stringify({ url: mcpUrl }));
    const claudeUrl = `https://claude.ai/customize/connectors?modal=add-custom-connector&connectorName=Koncat&connectorUrl=${encodedMcpUrl}`;
    const cursorUrl = `https://cursor.com/install-mcp?name=Koncat&config=${encodeURIComponent(cursorConfig)}`;
    const vscodeUrl = `vscode:mcp/install?${encodeURIComponent(
        JSON.stringify({
            name: 'koncat',
            type: 'http',
            url: mcpUrl,
        }),
    )}`;

    const copyMcpUrl = async () => {
        const copied = await copy(mcpUrl);

        if (copied) {
            toast.success('MCP URL copied');
        } else {
            toast.error('Could not copy MCP URL');
        }
    };

    return (
        <div className={className}>
            <div className={contentClassName}>
                <div
                    className={cn(
                        'px-1 text-sm font-medium',
                        titleClassName,
                    )}
                >
                    {title}
                </div>

                <div className={cn('flex gap-1', inputWrapperClassName)}>
                    <input
                        className={cn(
                            'flex-1 rounded-full border bg-background px-4 py-2 font-medium',
                            inputClassName,
                        )}
                        value={mcpUrl}
                        readOnly
                    />
                </div>

                <div
                    className={cn('flex flex-wrap gap-1', actionsClassName)}
                >
                    <Button asChild variant="outline" size="sm">
                        <a href={claudeUrl}>
                            <ClaudeIcon className="size-4" />
                            Connect to Claude
                        </a>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <a href={cursorUrl}>
                            <CursorIcon className="size-4" />
                            Install in Cursor
                        </a>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <a href={vscodeUrl}>
                            <VSCodeIcon className="size-4" />
                            Install in VS Code
                        </a>
                    </Button>

                    <Button variant="outline" size="sm" onClick={copyMcpUrl}>
                        {mcpUrlCopied ? (
                            <Check className="size-4" />
                        ) : (
                            <Copy className="size-4" />
                        )}
                        {mcpUrlCopied ? 'Copied' : 'Copy MCP URL'}
                    </Button>
                </div>
            </div>

            <p
                className={cn(
                    'max-w-md px-1 text-sm text-muted-foreground',
                    descriptionClassName,
                )}
            >
                {description}
            </p>
        </div>
    );
}
