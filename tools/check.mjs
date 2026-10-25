import { readFile, mkdtemp, writeFile, rm } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const archive = await readFile(new URL('../3dlens-private/archive.php', import.meta.url), 'utf8');
const scripts = [...archive.matchAll(/<script type="module">([\s\S]*?)<\/script>/g)];
if (scripts.length !== 1) throw new Error('Expected the existing viewer module.');
const temporary = await mkdtemp(join(tmpdir(), '3dlens-check-'));
try {
  const modulePath = join(temporary, 'archive.mjs');
  await writeFile(modulePath, scripts[0][1]);
  const oneDriveSource = await readFile(new URL('../public_html/onedrive.php', import.meta.url), 'utf8');
  const oneDriveScript = oneDriveSource.match(/<script type="module">([\s\S]*?)<\/script>/)?.[1];
  if (!oneDriveScript) throw new Error('The OneDrive migration module is missing.');
  const oneDriveModule = join(temporary, 'onedrive.mjs');
  await writeFile(oneDriveModule, oneDriveScript);
  for (const path of [modulePath, oneDriveModule, fileURLToPath(new URL('../public_html/storage.js', import.meta.url))]) {
    const result = spawnSync(process.execPath, ['--check', path], { encoding: 'utf8' });
    if (result.status !== 0) throw new Error(result.stderr || result.error?.message || 'Syntax check failed.');
  }
  if (/indexedDB\.open|db\.transaction|setCachedGeometry\(/.test(scripts[0][1])) throw new Error('Obsolete storage or undefined geometry call remains.');
  console.log('Viewer, storage and OneDrive JavaScript syntax: OK.');
} finally {
  await rm(temporary, { recursive: true, force: true });
}
