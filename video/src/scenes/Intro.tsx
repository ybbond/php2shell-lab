import { Headline, Tag } from "../components/labels";
import { Pop } from "../components/motion";
import { GROUND_Y, Stage } from "../components/Stage";
import { COLORS } from "../theme";

export const Intro = () => (
  <Stage ground={COLORS.violet}>
    <Pop at={0.3} x={960} y={200}>
      <Headline lines={["PHP2Shell", "Analisis Keamanan Memori PHP"]} size={84} />
    </Pop>
    <Pop at={1.2} x={660} y={540} float>
      <Tag text="CVE-2026-109618" color={COLORS.orange} size={46} />
    </Pop>
    <Pop at={2.0} x={1260} y={540} float>
      <Tag text="CVE-2026-109619" color={COLORS.magenta} size={46} textColor={COLORS.white} />
    </Pop>
    <Pop at={4.2} x={960} y={GROUND_Y - 120}>
      <Tag text="PHP 5.4 — 8.5" color={COLORS.teal} size={42} />
    </Pop>
  </Stage>
);
