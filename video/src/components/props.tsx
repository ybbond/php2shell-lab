import { COLORS } from "../theme";

/** Origin is on the floor, drawn in front of a person. */
export const Desk = () => (
  <g>
    <rect x={-210} y={-100} width={26} height={100} fill={COLORS.wood} />
    <rect x={184} y={-100} width={26} height={100} fill={COLORS.wood} />
    <rect x={-260} y={-118} width={520} height={26} rx={13} fill={COLORS.wood} />
    <rect x={-95} y={-228} width={190} height={112} rx={14} fill={COLORS.silver} />
    <circle cy={-172} r={14} fill={COLORS.teal} />
  </g>
);

/** Light bulb for "thinking": grey at glow 0, lit at glow 1. */
export const Bulb = ({ glow = 1 }: { glow?: number }) => (
  <g>
    <circle r={72} fill={COLORS.yellow} opacity={glow * 0.25} />
    <circle r={38} fill="#6b6f8a" />
    <circle r={38} fill={COLORS.yellow} opacity={glow} />
    <rect x={-16} y={32} width={32} height={22} rx={6} fill={COLORS.silver} />
  </g>
);
