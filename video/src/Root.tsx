import { Composition } from "remotion";
import { Main } from "./Main";
import { FPS, TOTAL_FRAMES } from "./timeline";

export const Root = () => (
  <Composition
    id="Main"
    component={Main}
    durationInFrames={TOTAL_FRAMES}
    fps={FPS}
    width={1920}
    height={1080}
  />
);
