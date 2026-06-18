import { useEffect, useRef } from 'react';

const VERTEX_SHADER_SOURCE = `
    attribute vec2 a_position;
    varying vec2 v_uv;

    void main() {
        v_uv = a_position * 0.5 + 0.5;
        gl_Position = vec4(a_position, 0.0, 1.0);
    }
`;

const FRAGMENT_SHADER_SOURCE = `
    precision highp float;

    uniform vec2 u_resolution;
    uniform vec2 u_pointer;
    uniform float u_pointer_active;
    uniform float u_time;
    varying vec2 v_uv;

    mat2 rotate2(float angle) {
        float c = cos(angle);
        float s = sin(angle);

        return mat2(c, -s, s, c);
    }

    float sdSphere(vec3 p, float radius) {
        return length(p) - radius;
    }

    float sdRoundedBox(vec3 p, vec3 bounds, float radius) {
        vec3 q = abs(p) - bounds;

        return length(max(q, 0.0)) + min(max(q.x, max(q.y, q.z)), 0.0) - radius;
    }

    float sdCapsule(vec3 p, vec3 a, vec3 b, float radius) {
        vec3 pa = p - a;
        vec3 ba = b - a;
        float h = clamp(dot(pa, ba) / dot(ba, ba), 0.0, 1.0);

        return length(pa - ba * h) - radius;
    }

    float sdTorus(vec3 p, vec2 radius) {
        vec2 q = vec2(length(p.xz) - radius.x, p.y);

        return length(q) - radius.y;
    }

    vec2 closestSurface(vec2 a, vec2 b) {
        return a.x < b.x ? a : b;
    }

    vec2 mapScene(vec3 p) {
        float t = u_time * 0.45;

        vec3 boxPoint = p - vec3(-0.9, 0.18, 0.0);
        boxPoint.xz *= rotate2(t * 0.45);
        boxPoint.xy *= rotate2(0.25 + t * 0.18);
        vec2 result = vec2(
            sdRoundedBox(boxPoint, vec3(0.38, 0.62, 0.38), 0.13),
            1.0
        );

        vec3 torusPoint = p - vec3(0.72, -0.04, -0.05);
        torusPoint.xy *= rotate2(0.7 + t * 0.7);
        torusPoint.yz *= rotate2(0.5);
        result = closestSurface(
            result,
            vec2(sdTorus(torusPoint, vec2(0.46, 0.105)), 2.0)
        );

        vec3 spherePoint = p - vec3(0.06, 0.78, -0.25);
        result = closestSurface(
            result,
            vec2(sdSphere(spherePoint, 0.31), 3.0)
        );

        vec3 capsulePoint = p - vec3(0.06, -0.7, -0.05);
        capsulePoint.xz *= rotate2(-0.35 - t * 0.38);
        result = closestSurface(
            result,
            vec2(
                sdCapsule(
                    capsulePoint,
                    vec3(-0.58, 0.0, 0.0),
                    vec3(0.58, 0.0, 0.0),
                    0.17
                ),
                4.0
            )
        );

        result = closestSurface(result, vec2(p.y + 1.13, 5.0));

        return result;
    }

    vec3 estimateNormal(vec3 p) {
        vec2 e = vec2(0.0016, 0.0);

        return normalize(
            vec3(
                mapScene(p + e.xyy).x - mapScene(p - e.xyy).x,
                mapScene(p + e.yxy).x - mapScene(p - e.yxy).x,
                mapScene(p + e.yyx).x - mapScene(p - e.yyx).x
            )
        );
    }

    float softShadow(vec3 origin, vec3 direction) {
        float shade = 1.0;
        float travel = 0.04;

        for (int i = 0; i < 26; i++) {
            float distanceToScene = mapScene(origin + direction * travel).x;
            shade = min(shade, 9.0 * distanceToScene / travel);
            travel += clamp(distanceToScene, 0.025, 0.2);

            if (shade < 0.02 || travel > 5.2) {
                break;
            }
        }

        return clamp(shade, 0.0, 1.0);
    }

    float ambientOcclusion(vec3 p, vec3 normal) {
        float occlusion = 0.0;
        float scale = 1.0;

        for (int i = 1; i <= 4; i++) {
            float stepSize = 0.045 * float(i);
            float distanceToScene = mapScene(p + normal * stepSize).x;
            occlusion += (stepSize - distanceToScene) * scale;
            scale *= 0.55;
        }

        return clamp(1.0 - occlusion * 1.8, 0.0, 1.0);
    }

    vec3 materialColor(float material) {
        if (material < 1.5) {
            return vec3(0.05, 0.62, 0.52);
        }

        if (material < 2.5) {
            return vec3(0.12, 0.28, 0.88);
        }

        if (material < 3.5) {
            return vec3(0.98, 0.46, 0.18);
        }

        if (material < 4.5) {
            return vec3(0.93, 0.18, 0.44);
        }

        return vec3(0.82, 0.9, 0.82);
    }

    vec2 raymarch(vec3 origin, vec3 direction) {
        float travel = 0.0;
        float material = -1.0;

        for (int i = 0; i < 72; i++) {
            vec3 p = origin + direction * travel;
            vec2 scene = mapScene(p);

            if (scene.x < 0.0018 || travel > 16.0) {
                material = scene.y;
                break;
            }

            travel += scene.x * 0.82;
        }

        return vec2(travel, material);
    }

    void main() {
        vec2 screen = v_uv * 2.0 - 1.0;
        screen.x *= u_resolution.x / max(u_resolution.y, 1.0);

        vec2 pointer = mix(vec2(0.5), u_pointer, u_pointer_active);
        float yaw = (pointer.x - 0.5) * 0.55;
        float pitch = (pointer.y - 0.5) * 0.28;

        vec3 origin = vec3(0.0, 0.05, 4.05);
        origin.xz *= rotate2(yaw);
        origin.y += pitch;

        vec3 target = vec3(0.0, -0.06, 0.0);
        vec3 forward = normalize(target - origin);
        vec3 right = normalize(cross(vec3(0.0, 1.0, 0.0), forward));
        vec3 up = cross(forward, right);
        vec3 direction = normalize(right * screen.x + up * screen.y + forward * 1.78);

        vec3 color = mix(
            vec3(0.94, 0.965, 0.94),
            vec3(0.83, 0.9, 0.96),
            smoothstep(-0.55, 0.95, screen.y)
        );

        vec2 hit = raymarch(origin, direction);

        if (hit.x < 16.0) {
            vec3 p = origin + direction * hit.x;
            vec3 normal = estimateNormal(p);
            vec3 light = normalize(
                vec3(-0.45 + pointer.x * 0.8, 0.86, 0.72 + pointer.y * 0.2)
            );
            vec3 halfVector = normalize(light - direction);
            vec3 base = materialColor(hit.y);
            float diffuse = max(dot(normal, light), 0.0);
            float shadow = softShadow(p + normal * 0.006, light);
            float ao = ambientOcclusion(p, normal);
            float fresnel = pow(1.0 - max(dot(normal, -direction), 0.0), 4.0);
            float specular = pow(max(dot(normal, halfVector), 0.0), 34.0) * shadow;

            if (hit.y > 4.5) {
                vec2 grid = abs(fract(p.xz * 2.7) - 0.5);
                float line = 1.0 - smoothstep(0.0, 0.025, min(grid.x, grid.y));
                base = mix(base, vec3(0.7, 0.82, 0.74), line * 0.28);
            }

            color = base * (0.28 + diffuse * shadow * 0.82) * ao;
            color += vec3(0.78, 0.92, 1.0) * fresnel * 0.34;
            color += vec3(1.0, 0.95, 0.78) * specular * 0.38;
            color = mix(color, vec3(0.94, 0.965, 0.94), smoothstep(6.0, 13.0, hit.x));
        }

        float grain = fract(sin(dot(v_uv * u_resolution, vec2(12.9898, 78.233))) * 43758.5453);
        color += (grain - 0.5) * 0.018;

        gl_FragColor = vec4(pow(color, vec3(0.92)), 1.0);
    }
`;

function createShader(gl: WebGLRenderingContext, type: number, source: string) {
    const shader = gl.createShader(type);

    if (!shader) {
        return null;
    }

    gl.shaderSource(shader, source);
    gl.compileShader(shader);

    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        gl.deleteShader(shader);

        return null;
    }

    return shader;
}

function createProgram(gl: WebGLRenderingContext) {
    const vertexShader = createShader(
        gl,
        gl.VERTEX_SHADER,
        VERTEX_SHADER_SOURCE,
    );
    const fragmentShader = createShader(
        gl,
        gl.FRAGMENT_SHADER,
        FRAGMENT_SHADER_SOURCE,
    );

    if (!vertexShader || !fragmentShader) {
        return null;
    }

    const program = gl.createProgram();

    if (!program) {
        return null;
    }

    gl.attachShader(program, vertexShader);
    gl.attachShader(program, fragmentShader);
    gl.linkProgram(program);
    gl.deleteShader(vertexShader);
    gl.deleteShader(fragmentShader);

    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        gl.deleteProgram(program);

        return null;
    }

    return program;
}

export default function BrochureWebglPreview() {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const gl = canvas.getContext('webgl', {
            alpha: false,
            antialias: false,
            depth: false,
            powerPreference: 'high-performance',
            stencil: false,
        });

        if (!gl) {
            return;
        }

        const program = createProgram(gl);
        const positionBuffer = gl.createBuffer();

        if (!program || !positionBuffer) {
            return;
        }

        const positionLocation = gl.getAttribLocation(program, 'a_position');
        const resolutionLocation = gl.getUniformLocation(
            program,
            'u_resolution',
        );
        const pointerLocation = gl.getUniformLocation(program, 'u_pointer');
        const pointerActiveLocation = gl.getUniformLocation(
            program,
            'u_pointer_active',
        );
        const timeLocation = gl.getUniformLocation(program, 'u_time');

        if (
            positionLocation < 0 ||
            !resolutionLocation ||
            !pointerLocation ||
            !pointerActiveLocation ||
            !timeLocation
        ) {
            gl.deleteProgram(program);

            return;
        }

        const reducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        const pointer = {
            active: 0,
            targetX: 0.5,
            targetY: 0.5,
            x: 0.5,
            y: 0.5,
        };
        let animationFrame = 0;
        let canvasWidth = 0;
        let canvasHeight = 0;

        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
        gl.bufferData(
            gl.ARRAY_BUFFER,
            new Float32Array([-1, -1, 3, -1, -1, 3]),
            gl.STATIC_DRAW,
        );
        gl.useProgram(program);
        gl.enableVertexAttribArray(positionLocation);
        gl.vertexAttribPointer(positionLocation, 2, gl.FLOAT, false, 0, 0);

        const resize = () => {
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
            const nextWidth = Math.max(
                1,
                Math.floor(canvas.clientWidth * pixelRatio),
            );
            const nextHeight = Math.max(
                1,
                Math.floor(canvas.clientHeight * pixelRatio),
            );

            if (nextWidth === canvasWidth && nextHeight === canvasHeight) {
                return;
            }

            canvasWidth = nextWidth;
            canvasHeight = nextHeight;
            canvas.width = canvasWidth;
            canvas.height = canvasHeight;
            gl.viewport(0, 0, canvasWidth, canvasHeight);
        };

        const draw = (now: number) => {
            resize();

            pointer.x += (pointer.targetX - pointer.x) * 0.08;
            pointer.y += (pointer.targetY - pointer.y) * 0.08;

            gl.useProgram(program);
            gl.uniform2f(resolutionLocation, canvasWidth, canvasHeight);
            gl.uniform2f(pointerLocation, pointer.x, pointer.y);
            gl.uniform1f(pointerActiveLocation, pointer.active);
            gl.uniform1f(timeLocation, now * 0.001);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
        };

        const animate = (now: number) => {
            draw(now);
            animationFrame = window.requestAnimationFrame(animate);
        };

        const handlePointerMove = (event: PointerEvent) => {
            const rect = canvas.getBoundingClientRect();

            pointer.targetX =
                (event.clientX - rect.left) / Math.max(rect.width, 1);
            pointer.targetY =
                1 - (event.clientY - rect.top) / Math.max(rect.height, 1);
            pointer.active = 1;

            if (reducedMotion) {
                draw(performance.now());
            }
        };

        const handlePointerLeave = () => {
            pointer.active = 0;

            if (reducedMotion) {
                draw(performance.now());
            }
        };

        canvas.addEventListener('pointermove', handlePointerMove, {
            passive: true,
        });
        canvas.addEventListener('pointerleave', handlePointerLeave);
        window.addEventListener('resize', resize);

        if (reducedMotion) {
            draw(performance.now());
        } else {
            animationFrame = window.requestAnimationFrame(animate);
        }

        return () => {
            window.cancelAnimationFrame(animationFrame);
            canvas.removeEventListener('pointermove', handlePointerMove);
            canvas.removeEventListener('pointerleave', handlePointerLeave);
            window.removeEventListener('resize', resize);
            gl.deleteBuffer(positionBuffer);
            gl.deleteProgram(program);
        };
    }, []);

    return (
        <canvas
            ref={canvasRef}
            className="absolute inset-0 h-full w-full"
            style={{
                background:
                    'radial-gradient(circle at 28% 30%, rgb(110 231 183 / 45%), transparent 34%), radial-gradient(circle at 76% 42%, rgb(244 114 182 / 35%), transparent 30%), linear-gradient(135deg, #f8faf5, #e6f4f1)',
            }}
        />
    );
}
