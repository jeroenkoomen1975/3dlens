import test from 'node:test';
import assert from 'node:assert/strict';
import { createArchiveStorage } from '../public_html/storage.js';

function fixture(respond) {
  const calls = [];
  const errors = [];
  let unauthorized = 0;
  const storage = createArchiveStorage({
    csrf: 'session-csrf',
    fetchImpl: async (url, options) => { calls.push({ url, options }); return respond(url, options); },
    onError: error => errors.push(error.message),
    onUnauthorized: () => unauthorized++,
  });
  return { storage, calls, errors, unauthorized: () => unauthorized };
}
const json = (payload, status = 200) => new Response(JSON.stringify(payload), { status, headers: { 'Content-Type': 'application/json' } });

test('lists server metadata without fetching all model bytes', async () => {
  const f = fixture(() => json([{ id: 'a', name: 'part.stl' }]));
  const items = await f.storage.getAll('files');
  assert.equal(items[0]._stored, true);
  assert.equal(items[0].data, undefined);
  assert.equal(f.calls.length, 1);
  assert.equal(f.calls[0].options.credentials, 'same-origin');
  assert.equal(f.calls[0].options.cache, 'no-store');
});

test('upload carries binary bytes and CSRF; later edits send metadata only', async () => {
  const file = { id: 'a', name: 'part.stl', data: new Uint8Array([1, 2, 3]).buffer };
  const f = fixture(() => json({ id: 'a', name: 'part.stl', size: 3 }));
  await f.storage.put('files', file);
  const first = f.calls[0].options;
  assert.equal(first.headers['X-CSRF-Token'], 'session-csrf');
  assert.equal(first.body.get('file').size, 3);
  assert.deepEqual([...new Uint8Array(await first.body.get('file').arrayBuffer())], [1, 2, 3]);
  assert.equal(JSON.parse(first.body.get('metadata')).data, undefined);
  assert.equal(file._stored, true);
  file.description = 'Updated';
  await f.storage.put('files', file);
  assert.equal(f.calls[1].options.body.get('file'), null);
  assert.equal(JSON.parse(f.calls[1].options.body.get('metadata')).description, 'Updated');
  assert.equal(JSON.parse(f.calls[1].options.body.get('metadata'))._stored, undefined);
});

test('failed upload stays unsaved and can be retried with its bytes', async () => {
  let attempt = 0;
  const f = fixture(() => ++attempt === 1 ? json({ error: 'Geen ruimte.' }, 503) : json({ id: 'a' }));
  const file = { id: 'a', data: new ArrayBuffer(3) };
  await assert.rejects(f.storage.put('files', file), /Geen ruimte/);
  assert.equal(file._stored, undefined);
  await f.storage.put('files', file);
  assert.equal(f.calls[1].options.body.get('file').size, 3);
});

test('model content is loaded lazily and concurrent viewers share one request', async () => {
  const f = fixture(() => new Response(new Uint8Array([7, 8, 9])));
  const file = { id: 'lazy' };
  const [one, two] = await Promise.all([f.storage.fileData(file), f.storage.fileData(file)]);
  assert.equal(one, two);
  assert.deepEqual([...new Uint8Array(one)], [7, 8, 9]);
  await f.storage.fileData(file);
  assert.equal(f.calls.length, 1);
  assert.equal(f.calls[0].url, 'api.php?store=files&id=lazy');
});

test('delete and clear invalidate cached binary data and include CSRF', async () => {
  const f = fixture((_url, options) => options.method === 'DELETE' ? json({ ok: true }) : new Response(new Uint8Array([1])));
  await f.storage.fileData({ id: 'a' });
  await f.storage.delete('files', 'a');
  await f.storage.fileData({ id: 'a' });
  await f.storage.clear('files');
  await f.storage.fileData({ id: 'a' });
  assert.equal(f.calls.length, 5);
  assert.equal(f.calls[1].options.headers['X-CSRF-Token'], 'session-csrf');
  assert.match(f.calls[3].url, /confirm=clear/);
});

test('expired sessions trigger login and do not cache an error as a model', async () => {
  const f = fixture(() => json({ error: 'Log opnieuw in.' }, 401));
  await assert.rejects(f.storage.fileData({ id: 'a' }), /Log opnieuw/);
  await assert.rejects(f.storage.fileData({ id: 'a' }), /Log opnieuw/);
  assert.equal(f.unauthorized(), 2);
  assert.equal(f.calls.length, 2);
});

test('hosting HTML errors become an actionable upload limit message', async () => {
  const f = fixture(() => new Response('<html>Too large</html>', { status: 413 }));
  await assert.rejects(f.storage.put('files', { id: 'a', data: new ArrayBuffer(2) }), /uploadlimiet/);
  assert.equal(f.errors.length, 1);
});

test('connection failures keep the original error and notify the interface', async () => {
  const f = fixture(() => { throw new Error('Verbinding verbroken'); });
  await assert.rejects(f.storage.getAll('collections'), /Verbinding verbroken/);
  assert.deepEqual(f.errors, ['Verbinding verbroken']);
});

test('collection writes and model download URLs use the same authenticated endpoint', async () => {
  const f = fixture(() => json({ id: 'c', name: 'Robots' }));
  await f.storage.put('collections', { id: 'c', name: 'Robots' });
  assert.equal(f.calls[0].options.body.get('file'), null);
  assert.equal(f.storage.downloadUrl('a?b'), 'api.php?store=files&id=a%3Fb');
});

test('invalid store is rejected without making a network request', async () => {
  const f = fixture(() => json([]));
  await assert.rejects(f.storage.getAll('../private'), /Ongeldig/);
  assert.equal(f.calls.length, 0);
});
