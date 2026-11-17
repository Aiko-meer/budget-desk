const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const ffmpeg = require('ffmpeg-static');
const scenes = require('./scenes.cjs');

const ROOT = __dirname;
const AUDIO = path.join(ROOT, 'audio');
const SEGMENTS = path.join(ROOT, 'segments');
const FRAMES = path.join(ROOT, 'frames');
const FINAL = path.join(ROOT, '..', 'budget-desk-tour.mp4');
const SRT = path.join(ROOT, '..', 'budget-desk-tour.srt');

function run(cmd, args, label) {
  const res = spawnSync(cmd, args, { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });
  if (res.status !== 0) {
    console.error(`FAILED: ${label}`);
    console.error(res.stdout?.slice(-3000));
    console.error(res.stderr?.slice(-5000));
    process.exit(1);
  }
  return res;
}

function wavDuration(file) {
  const b = fs.readFileSync(file);
  const byteRate = b.readUInt32LE(28);
  let off = 12;
  while (off + 8 <= b.length) {
    const id = b.toString('ascii', off, off + 4);
    const size = b.readUInt32LE(off + 4);
    if (id === 'data') return size / byteRate;
    off += 8 + size + (size % 2);
  }
  return (b.length - 44) / byteRate;
}

function srtTime(seconds) {
  const ms = Math.round(seconds * 1000);
  const h = String(Math.floor(ms / 3600000)).padStart(2, '0');
  const m = String(Math.floor((ms % 3600000) / 60000)).padStart(2, '0');
  const s = String(Math.floor((ms % 60000) / 1000)).padStart(2, '0');
  const r = String(ms % 1000).padStart(3, '0');
  return `${h}:${m}:${s},${r}`;
}

fs.mkdirSync(AUDIO, { recursive: true });
fs.mkdirSync(SEGMENTS, { recursive: true });

// 1. Write narration text files
for (const scene of scenes) {
  fs.writeFileSync(path.join(AUDIO, `${scene.id}.txt`), scene.narration, 'utf8');
}

console.log('Synthesizing narration...');
run('powershell', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join(ROOT, 'tts.ps1')], 'TTS');

// 2. Build one video segment per scene
const srtBlocks = [];
let cursor = 0;

for (const scene of scenes) {
  const wav = path.join(AUDIO, `${scene.id}.wav`);
  const frame = path.join(FRAMES, `${scene.id}.png`);
  const segment = path.join(SEGMENTS, `${scene.id}.mp4`);

  if (!fs.existsSync(wav)) {
    console.error(`Missing narration: ${wav}`);
    process.exit(1);
  }
  if (!fs.existsSync(frame)) {
    console.error(`Missing frame: ${frame}`);
    process.exit(1);
  }

  const audioDur = wavDuration(wav);
  const dur = Math.max(audioDur + 1.7, 6.5);
  const frames = Math.round(dur * 30);
  const fadeOutAt = (dur - 0.55).toFixed(2);

  const filter =
    `[0:v]scale=3840:2160,` +
    `zoompan=z='min(1+0.00019*on,1.10)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=${frames}:s=1920x1080:fps=30,` +
    `format=yuv420p,fade=t=in:st=0:d=0.45,fade=t=out:st=${fadeOutAt}:d=0.55[v];` +
    `[1:a]adelay=450:all=1,apad=pad_dur=2,aresample=44100,` +
    `afade=t=in:st=0.45:d=0.30,afade=t=out:st=${(dur - 0.65).toFixed(2)}:d=0.65[a]`;

  console.log(`Rendering ${scene.id} (${dur.toFixed(1)}s)...`);
  run(ffmpeg, [
    '-y',
    '-loop', '1',
    '-framerate', '30',
    '-i', frame,
    '-i', wav,
    '-filter_complex', filter,
    '-map', '[v]',
    '-map', '[a]',
    '-t', dur.toFixed(3),
    '-c:v', 'libx264',
    '-preset', 'medium',
    '-crf', '19',
    '-pix_fmt', 'yuv420p',
    '-r', '30',
    '-c:a', 'aac',
    '-b:a', '160k',
    '-ar', '44100',
    '-ac', '2',
    '-movflags', '+faststart',
    segment,
  ], `segment ${scene.id}`);

  srtBlocks.push(
    `${srtBlocks.length + 1}\n${srtTime(cursor)} --> ${srtTime(cursor + dur - 0.1)}\n${scene.title}\n${scene.narration}\n`
  );
  cursor += dur;
}

// 3. Concatenate
const list = path.join(SEGMENTS, 'list.txt');
fs.writeFileSync(
  list,
  scenes.map((s) => `file '${path.join(SEGMENTS, `${s.id}.mp4`).replace(/\\/g, '/')}'`).join('\n') + '\n',
  'utf8'
);

console.log('Concatenating...');
run(ffmpeg, [
  '-y',
  '-f', 'concat',
  '-safe', '0',
  '-i', list,
  '-c', 'copy',
  '-movflags', '+faststart',
  '-metadata', 'title=Budget Desk — System Tour',
  FINAL,
], 'concat');

fs.writeFileSync(SRT, srtBlocks.join('\n'), 'utf8');

const stats = fs.statSync(FINAL);
console.log('');
console.log(`Video: ${FINAL}`);
console.log(`Duration: ${Math.floor(cursor / 60)}m ${Math.round(cursor % 60)}s`);
console.log(`Size: ${(stats.size / 1024 / 1024).toFixed(1)} MB`);
console.log(`Subtitles: ${SRT}`);
