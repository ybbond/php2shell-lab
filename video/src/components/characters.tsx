import { COLORS } from "../theme";
import { useSeconds } from "./motion";

const MOUTHS = {
  happy: "M-24 -6 Q0 24 24 -6",
  neutral: "M-16 6 L16 6",
  worried: "M-20 12 Q0 -8 20 12",
};

type PersonProps = {
  mood?: keyof typeof MOUTHS;
  cheer?: boolean;
  hoodie?: string;
  hair?: string;
};

/** Origin is at the feet, so a parent can stand it on the ground. About 390 tall. */
export const Person = ({ mood = "neutral", cheer = false, hoodie = COLORS.orange, hair = COLORS.hair }: PersonProps) => {
  const t = useSeconds();
  const wave = Math.sin(t * 9) * 14;
  const arm = (side: number) =>
    cheer
      ? `M${side * 60} -190 L${side * 120} ${-300 + wave}`
      : `M${side * 62} -190 Q${side * 108} -130 ${side * 84} -78`;

  return (
    <g transform={`scale(1 ${1 + Math.sin(t * 2.4) * 0.012})`}>
      {[-42, 12].map((x) => (
        <rect key={x} x={x} y={-70} width={30} height={70} rx={15} fill={COLORS.night} />
      ))}
      <rect x={-72} y={-225} width={144} height={170} rx={60} fill={hoodie} />
      {[-1, 1].map((side) => (
        <path key={side} d={arm(side)} fill="none" stroke={hoodie} strokeWidth={30} strokeLinecap="round" />
      ))}
      <circle cy={-305} r={85} fill={COLORS.skin} />
      <path d="M-88 -300 A88 88 0 0 1 88 -300 Q30 -345 -88 -300Z" fill={hair} />
      {[-1, 1].map((side) => (
        <g key={side}>
          <circle cx={side * 30} cy={-298} r={9} fill={COLORS.night} />
          <circle cx={side * 52} cy={-272} r={13} fill={COLORS.magenta} opacity={0.25} />
          {mood === "worried" && (
            <path d={`M${side * 16} -322 L${side * 44} -312`} stroke={COLORS.night} strokeWidth={6} strokeLinecap="round" />
          )}
        </g>
      ))}
      <path
        d={MOUTHS[mood]}
        transform="translate(0 -262)"
        fill="none"
        stroke={COLORS.night}
        strokeWidth={6}
        strokeLinecap="round"
      />
    </g>
  );
};

/** Origin is on the ground under its hover shadow. About 370 tall. */
export const Robot = () => {
  const t = useSeconds();
  const hover = Math.sin(t * 2) * 10;
  return (
    <g>
      <ellipse rx={80 - hover} ry={14} fill={COLORS.night} opacity={0.3} />
      <g transform={`translate(0 ${hover - 50})`}>
        <rect x={-60} y={-100} width={120} height={100} rx={36} fill={COLORS.silver} />
        <circle cy={-50} r={16} fill={COLORS.teal} opacity={0.7 + Math.sin(t * 4) * 0.3} />
        <rect x={-4} y={-295} width={8} height={50} fill={COLORS.silver} />
        <circle cy={-302} r={28} fill={COLORS.yellow} opacity={0.3} />
        <circle cy={-302} r={15} fill={COLORS.yellow} />
        <rect x={-95} y={-250} width={190} height={145} rx={50} fill={COLORS.white} />
        <rect x={-75} y={-232} width={150} height={108} rx={34} fill={COLORS.night} />
        {[-1, 1].map((side) => (
          <circle key={side} cx={side * 32} cy={-192} r={13} fill={COLORS.teal} />
        ))}
        <path d="M-24 -162 Q0 -142 24 -162" fill="none" stroke={COLORS.teal} strokeWidth={7} strokeLinecap="round" />
      </g>
    </g>
  );
};
