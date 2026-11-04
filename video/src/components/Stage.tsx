import type { ReactNode } from "react";
import { AbsoluteFill, random, useCurrentFrame } from "remotion";
import { COLORS, FONT } from "../theme";

export const GROUND_Y = 860;

const STARS = Array.from({ length: 70 }, (_, i) => ({
  x: random(`x${i}`) * 1920,
  y: random(`y${i}`) * 800,
  r: 1.5 + random(`r${i}`) * 3,
  phase: random(`p${i}`) * 6,
}));

export const Ground = ({ color }: { color: string }) => (
  <ellipse cx={960} cy={1240} rx={1500} ry={420} fill={color} />
);

type StageProps = {
  from?: string;
  to?: string;
  ground?: string;
  stars?: boolean;
  children: ReactNode;
};

/** Scene backdrop: gradient sky, twinkling stars, optional hill, and a 1920x1080 SVG canvas with a slow zoom. */
export const Stage = ({ from = COLORS.night, to = COLORS.indigo, ground, stars = true, children }: StageProps) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill style={{ background: `linear-gradient(${from}, ${to})` }}>
      <svg
        viewBox="0 0 1920 1080"
        style={{ width: "100%", height: "100%", fontFamily: FONT, transform: `scale(${1 + frame * 0.0001})` }}
      >
        {stars &&
          STARS.map((s) => (
            <circle
              key={`${s.x}`}
              cx={s.x}
              cy={s.y}
              r={s.r}
              fill={COLORS.white}
              opacity={0.45 + Math.sin(frame / 20 + s.phase) * 0.35}
            />
          ))}
        {ground && <Ground color={ground} />}
        {children}
      </svg>
    </AbsoluteFill>
  );
};
