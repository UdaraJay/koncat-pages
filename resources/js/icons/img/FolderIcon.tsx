import type { FC, SVGProps } from 'react';

export const FolderIcon: FC<SVGProps<SVGSVGElement>> = (props) => {
    return (
        <svg
            width="1600"
            height="1264"
            viewBox="0 0 1600 1264"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M648.52 113.652H1363.31C1434.31 113.652 1486.39 170.459 1486.39 236.731V407.144C1486.39 411.879 1481.65 416.613 1476.92 416.613H123.05C118.315 416.613 113.581 411.879 113.581 407.144V123.118C113.581 56.8471 170.388 0.0390625 236.66 0.0390625H511.22C586.96 0.0390625 615.36 56.8457 648.5 113.648L648.52 113.652Z"
                fill="currentcolor"
            />
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M94.6774 506.561H1505.34C1566.88 506.561 1614.22 568.097 1595.29 629.639L1429.61 1174.03C1410.67 1226.1 1363.34 1263.97 1311.26 1263.97H288.783C236.709 1263.97 189.376 1226.1 170.439 1174.03L4.75875 629.639C-14.1786 568.103 33.1601 506.561 94.7014 506.561H94.6774Z"
                fill="currentcolor"
            />
        </svg>
    );
};
