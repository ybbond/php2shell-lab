import { Headline, Tag } from "../components/labels";
import { Pop } from "../components/motion";
import { Stage } from "../components/Stage";
import { COLORS } from "../theme";

const BRANCHES = [
  { y: 380, unfixed: "8.5.11 (:8085)", fixed: "8.5.12 (:8185)" },
  { y: 500, unfixed: "8.4.26 (:8084)", fixed: "8.4.27 (:8184)" },
  { y: 620, unfixed: "8.3.35 (:8083)", fixed: "8.3.36 (:8183)" },
  { y: 740, unfixed: "8.2.34 (:8082)", fixed: "8.2.35 (:8182)" },
];

export const PatchMatrix = () => (
  <Stage from={COLORS.night} to={COLORS.indigo}>
    <Pop at={0.2} x={960} y={150}>
      <Headline lines={["Matriks Lab Container: Unfixed vs Fixed"]} size={68} />
    </Pop>
    {BRANCHES.map((row, idx) => (
      <g key={row.unfixed}>
        <Pop at={0.8 + idx * 0.4} x={620} y={row.y}>
          <Tag text={`UNFIXED ${row.unfixed}`} color={COLORS.red} size={34} textColor={COLORS.white} />
        </Pop>
        <Pop at={1.0 + idx * 0.4} x={1300} y={row.y}>
          <Tag text={`FIXED ${row.fixed}`} color={COLORS.green} size={34} textColor={COLORS.night} />
        </Pop>
      </g>
    ))}
  </Stage>
);
