// Credit: https://usehooks-ts.com/
import { useState } from 'react';

export type CopiedValue = string | null;
export type CopyFn = (text: string) => Promise<boolean>;
export type UseClipboardReturn = [CopiedValue, CopyFn];

function fallbackCopy(text: string): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.setAttribute('readonly', '');
    textArea.style.position = 'fixed';
    textArea.style.top = '0';
    textArea.style.left = '-9999px';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        return document.execCommand('copy');
    } finally {
        document.body.removeChild(textArea);
    }
}

export function useClipboard(): UseClipboardReturn {
    const [copiedText, setCopiedText] = useState<CopiedValue>(null);

    const copy: CopyFn = async (text) => {
        if (typeof navigator !== 'undefined' && navigator.clipboard) {
            try {
                await navigator.clipboard.writeText(text);
                setCopiedText(text);

                return true;
            } catch (error) {
                console.warn('Clipboard API copy failed', error);
            }
        }

        if (fallbackCopy(text)) {
            setCopiedText(text);

            return true;
        }

        console.warn('Copy failed');
        setCopiedText(null);

        return false;
    };

    return [copiedText, copy];
}
