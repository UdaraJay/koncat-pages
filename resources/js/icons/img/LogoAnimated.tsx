const LogoShape = ({ fill = 'white', className = '', style = {} }) => {
    return (
        <svg
            viewBox="0 0 309 157"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            style={style}
        >
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M230.816 0C238.994 0 243.083 7.43308e-05 246.526 0.344013C279.382 3.62556 305.369 29.5765 308.655 62.3865C309 65.8254 309 69.9085 309 78.0747C309 86.241 309 90.3241 308.655 93.7629C305.369 126.573 279.382 152.524 246.526 155.805C243.083 156.149 238.994 156.149 230.816 156.149H78.1839C70.0063 156.149 65.9174 156.149 62.4738 155.805C29.6179 152.524 3.63063 126.573 0.344494 93.7629C7.44348e-05 90.3241 0 86.241 0 78.0747C0 69.9085 7.44348e-05 65.8254 0.344494 62.3865C3.63063 29.5765 29.6179 3.62556 62.4738 0.344013C65.9174 7.43626e-05 70.0063 0 78.1839 0H230.816ZM78.1839 52.2275C63.8889 52.2275 52.3005 63.7997 52.3005 78.0747C52.3005 92.3498 63.8889 103.922 78.1839 103.922H230.816C245.111 103.922 256.699 92.3498 256.699 78.0747C256.699 63.7997 245.111 52.2275 230.816 52.2275H78.1839Z"
                fill={fill}
            />
        </svg>
    );
};

export default function LogoAnimated({ className = 'h-32', animate = true }) {
    return (
        <>
            <style>{`
        @keyframes shadowMoveDown {
          0% {
            transform: translateY(0);
            opacity: 0;
          }
          100% {
            transform: translateY(4%);
            opacity: 1;
          }
        }

        @keyframes logoMoveUp {
          0% {
            transform: translateY(0);
          }
          60% {
            transform: translateY(-5%);
          }
          80% {
            transform: translateY(-3.5%);
          }
          100% {
            transform: translateY(-5%);
          }
        }

        .shadow-layer {
          animation: ${animate ? 'shadowMoveDown 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards' : 'none'};
          opacity: ${animate ? '0' : '1'};
          transform: translateY(${animate ? '0' : '5%'});
        }

        .logo-layer {
          animation: ${animate ? 'logoMoveUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards' : 'none'};
          transform: translateY(${animate ? '0' : '-5%'});
        }
      `}</style>

            <div className={`relative ${className} w-auto`}>
                {/* Shadow layer - behind, moves down */}
                <div className="shadow-layer absolute inset-0">
                    <LogoShape fill="#AFAFAF" className="h-full w-auto" />
                </div>

                {/* Logo layer - front, moves up */}
                <div className="logo-layer absolute inset-0">
                    <LogoShape
                        fill="#fff"
                        className="h-full w-auto"
                        style={{
                            filter: 'drop-shadow(0 2px 2px rgba(0,0,0,0.1))',
                        }}
                    />
                </div>

                {/* Invisible spacer to give the container proper dimensions */}
                <LogoShape fill="transparent" className="h-full w-auto" />
            </div>
        </>
    );
}
