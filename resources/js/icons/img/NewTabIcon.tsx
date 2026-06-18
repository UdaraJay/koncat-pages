import type { FC, SVGProps } from 'react';

export const NewTabIcon: FC<SVGProps<SVGSVGElement>> = (props) => {
    return (
        <svg
            width="880"
            height="880"
            viewBox="0 0 880 880"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            {...props}
        >
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M440 880C197 880 0 683 0 440C0 197 197 0 440 0C683 0 880 197 880 440C880 683 683 880 440 880ZM328 384H232C201.073 384 176 409.073 176 440C176 470.927 201.073 496 232 496H328C358.927 496 384 521.073 384 552V648C384 678.927 409.073 704 440 704C470.927 704 496 678.927 496 648V552C496 521.073 521.073 496 552 496H648C678.927 496 704 470.927 704 440C704 409.073 678.927 384 648 384H552C521.073 384 496 358.927 496 328V232C496 201.073 470.927 176 440 176C409.073 176 384 201.073 384 232V328C384 358.927 358.927 384 328 384Z"
                fill="currentcolor"
            />
        </svg>
    );
};
