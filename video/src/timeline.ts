import type { ComponentType } from "react";
import type { Caption } from "./components/Captions";
import { Intro } from "./scenes/Intro";
import { MemoryModel } from "./scenes/MemoryModel";
import { PatchMatrix } from "./scenes/PatchMatrix";

export const FPS = 30;
const TARGET_SECONDS = 24;
const MAX_CHARS_PER_SECOND = 18;

type Scene = { Visual: ComponentType; captions: Caption[] };

// A scene lasts as long as its captions, so its animation cues follow these seconds.
export const TIMELINE: Scene[] = [
  {
    Visual: Intro,
    captions: [
      { text: "Pada Oktober 2026, ditemukan celah memori pada PHP.", seconds: 4 },
      { text: "Dua celah ini dikenal sebagai PHP2Shell.", seconds: 4 },
    ],
  },
  {
    Visual: MemoryModel,
    captions: [
      { text: "CVE-2026-109618 terkait penanganan kondisi Out-of-Memory.", seconds: 4 },
      { text: "CVE-2026-109619 terkait Use-After-Free pada state worker.", seconds: 4 },
    ],
  },
  {
    Visual: PatchMatrix,
    captions: [
      { text: "Mari kita bandingkan versi PHP sebelum dan sesudah patch.", seconds: 4 },
      { text: "Seluruh pengujian dilakukan di lingkungan container terisolasi.", seconds: 4 },
    ],
  },
];

const captions = TIMELINE.flatMap((scene) => scene.captions);
const totalSeconds = captions.reduce((sum, c) => sum + c.seconds, 0);

if (totalSeconds !== TARGET_SECONDS) {
  throw new Error(`Timeline is ${totalSeconds}s, expected ${TARGET_SECONDS}s`);
}
for (const c of captions) {
  if (c.text.length / c.seconds > MAX_CHARS_PER_SECOND) {
    throw new Error(`Caption too fast to read: "${c.text}"`);
  }
}

export const TOTAL_FRAMES = totalSeconds * FPS;
