import { Check, Copy } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';
import { ChatGPTIcon, ClaudeIcon, CursorIcon, VSCodeIcon } from '@/icons';
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
                <div className={cn('px-1 text-sm font-medium', titleClassName)}>
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

                <div className={cn('flex flex-wrap gap-1', actionsClassName)}>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="max-w-full whitespace-normal"
                    >
                        <a href={claudeUrl}>
                            <ClaudeIcon className="size-4" />
                            Connect to Claude
                        </a>
                    </Button>
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="max-w-full whitespace-normal"
                            >
                                <ChatGPTIcon className="size-4" />
                                Connect to ChatGPT
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Connect Koncat to ChatGPT
                                </DialogTitle>
                                <DialogDescription>
                                    ChatGPT does not support one-click MCP
                                    installation yet. Enable developer mode in
                                    ChatGPT's Apps settings, then add this MCP
                                    URL and log in to connect.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-3">
                                <div className="rounded-md border bg-muted/40 p-3 font-mono text-sm break-all">
                                    {mcpUrl}
                                </div>

                                <ol className="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                                    <li>Open ChatGPT settings.</li>
                                    <li>Under Apps, enable developer mode.</li>
                                    <li>
                                        Add the MCP URL above, then log in when
                                        prompted.
                                    </li>
                                </ol>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="outline">Close</Button>
                                </DialogClose>
                                <Button onClick={copyMcpUrl}>
                                    {mcpUrlCopied ? (
                                        <Check className="size-4" />
                                    ) : (
                                        <Copy className="size-4" />
                                    )}
                                    {mcpUrlCopied ? 'Copied' : 'Copy MCP URL'}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="max-w-full whitespace-normal"
                    >
                        <a href={cursorUrl}>
                            <CursorIcon className="size-4" />
                            Install in Cursor
                        </a>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="max-w-full whitespace-normal"
                    >
                        <a href={vscodeUrl}>
                            <VSCodeIcon className="size-4" />
                            Install in VS Code
                        </a>
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        className="max-w-full whitespace-normal"
                        onClick={copyMcpUrl}
                    >
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
