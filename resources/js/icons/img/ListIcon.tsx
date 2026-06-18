import type { FC, SVGProps } from 'react';

export const ListIcon: FC<SVGProps<SVGSVGElement>> = (props) => {
    return (
        <svg
            width="1286"
            height="1202"
            viewBox="0 0 1286 1202"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <ellipse
                cx="643"
                cy="300.5"
                rx="300"
                ry="300.5"
                fill="currentcolor"
            />
            <circle cx="300.5" cy="901.5" r="300.5" fill="currentcolor" />
            <circle cx="985.5" cy="901.5" r="300.5" fill="currentcolor" />
        </svg>
    );
};
