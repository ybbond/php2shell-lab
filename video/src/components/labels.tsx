import { COLORS } from "../theme";

const CHAR_WIDTH = 0.6; // rough em width, SVG has no auto-sizing box for text

type TagProps = { text: string; color?: string; size?: number; textColor?: string };

export const Tag = ({ text, color = COLORS.teal, size = 44, textColor = COLORS.night }: TagProps) => {
  const width = text.length * size * CHAR_WIDTH + size * 1.6;
  const height = size * 1.9;
  return (
    <g>
      <rect x={-width / 2} y={-height / 2} width={width} height={height} rx={height / 2} fill={color} />
      <text textAnchor="middle" dominantBaseline="central" fontSize={size} fontWeight={700} fill={textColor}>
        {text}
      </text>
    </g>
  );
};

type BubbleProps = { lines: string[]; tail?: "left" | "right"; size?: number };

export const Bubble = ({ lines, tail = "left", size = 40 }: BubbleProps) => {
  const width = Math.max(...lines.map((l) => l.length)) * size * CHAR_WIDTH + 80;
  const height = lines.length * size * 1.3 + 50;
  const side = tail === "left" ? -1 : 1;
  const tailX = side * width * 0.25;
  return (
    <g>
      <rect x={-width / 2} y={-height / 2} width={width} height={height} rx={36} fill={COLORS.white} />
      <path d={`M${tailX - 24} ${height / 2 - 2} L${tailX + 24} ${height / 2 - 2} L${tailX + side * 34} ${height / 2 + 46}Z`} fill={COLORS.white} />
      {lines.map((line, i) => (
        <text
          key={line}
          y={(i - (lines.length - 1) / 2) * size * 1.3}
          textAnchor="middle"
          dominantBaseline="central"
          fontSize={size}
          fontWeight={600}
          fill={COLORS.night}
        >
          {line}
        </text>
      ))}
    </g>
  );
};

type HeadlineProps = { lines: string[]; size?: number; color?: string };

export const Headline = ({ lines, size = 96, color = COLORS.white }: HeadlineProps) => (
  <g>
    {lines.map((line, i) => (
      <text key={line} y={i * size * 1.2} textAnchor="middle" fontSize={size} fontWeight={800} fill={color}>
        {line}
      </text>
    ))}
  </g>
);
