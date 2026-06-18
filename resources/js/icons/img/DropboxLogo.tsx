import type { FC, SVGProps } from 'react';

export const DropboxLogo: FC<SVGProps<SVGSVGElement>> = (props) => {
    return (
        <svg
            width="607"
            height="607"
            viewBox="0 0 607 607"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <rect width="607" height="607" fill="white" />
            <path
                d="M210.496 264.125L303.008 205.063L210.496 146L118 205.063L210.496 264.125Z"
                fill="#0061FF"
            />
            <path
                d="M395.504 264.125L488 205.063L395.504 146L303.008 205.063L395.504 264.125Z"
                fill="#0061FF"
            />
            <path
                d="M303.008 323.188L210.496 264.125L118 323.188L210.496 382.25L303.008 323.188Z"
                fill="#0061FF"
            />
            <path
                d="M395.504 382.25L488 323.188L395.504 264.125L303.008 323.188L395.504 382.25Z"
                fill="#0061FF"
            />
            <path
                d="M395.504 401.938L303.008 342.875L210.496 401.938L303.008 461L395.504 401.938Z"
                fill="#0061FF"
            />
        </svg>
    );
};
