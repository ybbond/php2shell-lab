import { AbsoluteFill, Series, useVideoConfig } from "remotion";
import { COLORS, FONT } from "../theme";

export type Caption = { text: string; seconds: number };

const Line = ({ text }: { text: string }) => (
  <AbsoluteFill style={{ justifyContent: "flex-end", alignItems: "center", paddingBottom: 44 }}>
    <div
      style={{
        maxWidth: 1500,
        padding: "16px 40px",
        borderRadius: 28,
        background: "rgba(11, 20, 55, 0.84)",
        color: COLORS.white,
        fontFamily: FONT,
        fontSize: 40,
        fontWeight: 600,
        lineHeight: 1.3,
        textAlign: "center",
      }}
    >
      {text}
    </div>
  </AbsoluteFill>
);

export const Captions = ({ captions }: { captions: Caption[] }) => {
  const { fps } = useVideoConfig();
  return (
    <Series>
      {captions.map((c) => (
        <Series.Sequence key={c.text} durationInFrames={c.seconds * fps}>
          <Line text={c.text} />
        </Series.Sequence>
      ))}
    </Series>
  );
};
