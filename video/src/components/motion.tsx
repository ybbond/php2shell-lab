import type { ReactNode } from "react";
import { Easing, interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";

export const useSeconds = () => useCurrentFrame() / useVideoConfig().fps;

/** Eases from `from` to `to` while t moves from a to b, and holds outside that range. */
export const ramp = (t: number, a: number, b: number, from: number, to: number) =>
  interpolate(t, [a, b], [from, to], {
    easing: Easing.inOut(Easing.cubic),
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

type PopProps = {
  at?: number;
  until?: number;
  x?: number;
  y?: number;
  scale?: number;
  float?: boolean;
  children: ReactNode;
};

/** Springs an SVG element in at `at` seconds, and optionally back out at `until`. */
export const Pop = ({ at = 0, until, x = 0, y = 0, scale = 1, float = false, children }: PopProps) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const shown = spring({ frame: frame - at * fps, fps, config: { damping: 12 } });
  const hidden = until === undefined ? 0 : spring({ frame: frame - until * fps, fps, config: { damping: 20 } });
  const size = scale * Math.max(0, shown - hidden);
  if (size === 0) return null;
  const drift = float ? Math.sin((frame / fps) * 1.5 + at) * 8 : 0;
  return <g transform={`translate(${x} ${y + drift}) scale(${size})`}>{children}</g>;
};
