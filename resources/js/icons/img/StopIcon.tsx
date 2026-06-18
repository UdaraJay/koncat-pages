import type { FC, SVGProps } from 'react';

export const StopIcon: FC<SVGProps<SVGSVGElement>> = (props) => {
    return (
        <svg
            width="1280"
            height="1280"
            viewBox="0 0 1280 1280"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M640 0C286.56 0 0 286.56 0 640C0 993.44 286.56 1280 640 1280C993.44 1280 1280 993.44 1280 640C1280 286.56 993.44 0 640 0ZM480 336C400.5 336 336 400.5 336 480V800C336 879.5 400.5 944 480 944H800C879.5 944 944 879.5 944 800V480C944 400.5 879.5 336 800 336H480Z"
                fill="currentcolor"
            />
        </svg>
    );
};
