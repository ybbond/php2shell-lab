import { Bubble, Headline, Tag } from "../components/labels";
import { Pop } from "../components/motion";
import { Stage } from "../components/Stage";
import { COLORS } from "../theme";

export const MemoryModel = () => (
  <Stage from={COLORS.night} to={COLORS.violet}>
    <Pop at={0.2} x={960} y={160}>
      <Headline lines={["Siklus Memori & Worker PHP"]} size={76} />
    </Pop>
    <Pop at={0.8} x={560} y={430}>
      <Tag text="CVE-2026-109618" color={COLORS.orange} size={40} />
    </Pop>
    <Pop at={1.2} x={560} y={600}>
      <Bubble lines={["Out-of-Memory (zend_bailout)", "State Belum Di-rollback"]} size={34} />
    </Pop>
    <Pop at={4.2} x={1360} y={430}>
      <Tag text="CVE-2026-109619" color={COLORS.magenta} size={40} textColor={COLORS.white} />
    </Pop>
    <Pop at={4.6} x={1360} y={600}>
      <Bubble lines={["Use-After-Free pada Worker", "State Terbawa ke Request Berikut"]} tail="right" size={34} />
    </Pop>
  </Stage>
);
