// Run against a DISPOSABLE, installed PHP/MySQL test site, never your live archive.
import test from 'node:test';
import assert from 'node:assert/strict';
import { randomUUID, createHash } from 'node:crypto';

const enabled = process.env.LENS_TEST_CONFIRM === 'disposable'
  && process.env.LENS_TEST_URL && process.env.LENS_TEST_USERNAME && process.env.LENS_TEST_PASSWORD;

test('PHP/MySQL login, private downloads and storage permissions', {
  skip: enabled ? false : 'Requires a disposable PHP/MySQL site and LENS_TEST_* variables; no live server was tested.',
}, async t => {
  const base = new URL(process.env.LENS_TEST_URL.replace(/\/?$/, '/'));
  let cookie = '';
  const request = async (path, options = {}, authenticated = true) => {
    const response = await fetch(new URL(path, base), {
      ...options, redirect: 'manual',
      headers: { ...(authenticated && cookie ? { Cookie: cookie } : {}), ...options.headers },
    });
    if (authenticated && response.headers.getSetCookie().length) {
      cookie = response.headers.getSetCookie().map(value => value.split(';')[0]).join('; ');
    }
    return response;
  };
  let token;
  const collectionId = randomUUID();
  const fileId = randomUUID();
  const projectId = randomUUID();
  const stl = 'solid fixture\nfacet normal 0 0 1\nouter loop\nvertex 0 0 0\nvertex 1 0 0\nvertex 0 1 0\nendloop\nendfacet\nendsolid fixture\n';
  const write = async (store, metadata, binary) => {
    const body = new FormData();
    body.append('metadata', JSON.stringify(metadata));
    if (binary) body.append('file', new Blob([binary]), 'model.bin');
    return request('api.php?store=' + store, { method: 'POST', body, headers: { 'X-CSRF-Token': token } });
  };
  const remove = async (store, id) => request('api.php?' + new URLSearchParams({ store, id }), {
    method: 'DELETE', headers: { 'X-CSRF-Token': token },
  });
  try {
    await t.test('anonymous page, API reads, writes and model bytes are blocked', async () => {
      const page = await request('index.php', {}, false);
      assert.equal(page.status, 303);
      assert.equal(page.headers.get('Location'), 'login.php');
      for (const path of ['api.php?store=files', 'api.php?store=collections', 'api.php?store=projects', 'api.php?store=files&id=' + fileId]) {
        const response = await request(path, {}, false);
        assert.equal(response.status, 401);
        assert.match(response.headers.get('Cache-Control'), /no-store/);
      }
      assert.equal((await request('api.php?store=files', { method: 'POST' }, false)).status, 401);
    });
    await t.test('login requires CSRF, rotates the session and protects the cookie', async () => {
      const page = await request('login.php');
      const html = await page.text();
      const csrf = html.match(/name="csrf" value="([a-f0-9]+)"/)?.[1];
      assert.ok(csrf);
      const headers = page.headers.getSetCookie().join(';');
      assert.match(headers, /HttpOnly/i);
      assert.match(headers, /SameSite=Lax/i);
      if (base.protocol === 'https:') assert.match(headers, /Secure/i);
      const before = cookie;
      const body = new URLSearchParams({
        csrf, username: process.env.LENS_TEST_USERNAME, password: process.env.LENS_TEST_PASSWORD,
      });
      assert.equal((await request('login.php', { method: 'POST', body: new URLSearchParams({ username: 'x' }) })).status, 403);
      const result = await request('login.php', { method: 'POST', body });
      assert.equal(result.status, 303);
      assert.notEqual(cookie, before);
      const archive = await request('index.php');
      assert.equal(archive.status, 200);
      token = (await archive.text()).match(/name="csrf-token" content="([a-f0-9]+)"/)?.[1];
      assert.ok(token);
      assert.equal((await request('setup.php')).status, 404);
      assert.equal((await request('logout.php')).status, 405);
    });
    await t.test('creates collection and model, verifies bytes and server-calculated hash', async () => {
      assert.equal((await write('collections', { id: collectionId, name: 'Test <b> & collection' })).status, 200);
      const result = await write('files', {
        id: fileId, name: 'fixture.stl', collectionId, size: 999, hash: 'forged', description: '', tags: [],
      }, stl);
      assert.equal(result.status, 200);
      const saved = await result.json();
      assert.equal(saved.size, Buffer.byteLength(stl));
      assert.equal(saved.hash, createHash('sha256').update(stl).digest('hex'));
      assert.equal(saved.storage_key, undefined);
      const download = await request('api.php?store=files&id=' + fileId);
      assert.equal(await download.text(), stl);
      assert.equal(download.headers.get('Content-Type'), 'application/octet-stream');
      assert.match(download.headers.get('Content-Disposition'), /^attachment/);
    });
    await t.test('rejects writes without CSRF, unsafe IDs, executable uploads and duplicate bytes', async () => {
      assert.equal((await request('api.php?store=files&id=' + fileId, { method: 'DELETE' })).status, 403);
      assert.equal((await request('api.php?store=files&id=..%2Fconfig')).status, 422);
      assert.equal((await write('files', { id: randomUUID(), name: 'shell.php' }, '<?php echo 1;')).status, 422);
      assert.equal((await write('files', { id: randomUUID(), name: 'duplicate.stl' }, stl)).status, 409);
      assert.equal((await write('files', {
        id: randomUUID(), name: 'evil.stl', thumbnail: 'data:image/svg+xml,<svg onload=alert(1) />',
      }, stl)).status, 422);
    });
    await t.test('projects store quantities and notes and reject missing parts and writes without CSRF', async () => {
      const project = {id:projectId,name:'Assembly',description:'Mount',parts:[{fileId,quantity:2,notes:'Pair'}]};
      const response = await write('projects',project);
      assert.equal(response.status,200);
      assert.deepEqual((await response.json()).parts,project.parts);
      const projects = await (await request('api.php?store=projects')).json();
      assert.equal(projects.find(p=>p.id===projectId).description,'Mount');
      assert.equal((await write('projects',{...project,parts:[{fileId:randomUUID(),quantity:1}]})).status,422);
      assert.equal((await write('projects',{...project,parts:[{fileId,quantity:0}]})).status,422);
      assert.equal((await request('api.php?store=projects&id='+projectId,{method:'DELETE'})).status,403);
    });
    await t.test('metadata updates preserve bytes and collection deletion uncategorizes models', async () => {
      const update = await write('files', { id: fileId, name: 'fixture.stl', collectionId, description: 'Saved', tags: ['test'] });
      assert.equal(update.status, 200);
      assert.equal((await update.json()).description, 'Saved');
      assert.equal((await remove('collections', collectionId)).status, 200);
      const files = await (await request('api.php?store=files')).json();
      assert.equal(files.find(file => file.id === fileId).collectionId, null);
      assert.equal(await (await request('api.php?store=files&id=' + fileId)).text(), stl);
    });
    await t.test('deletion removes the model and logout invalidates the old session', async () => {
      assert.equal((await remove('files', fileId)).status, 200);
      assert.equal((await request('api.php?store=files&id=' + fileId)).status, 404);
      const projects = await (await request('api.php?store=projects')).json();
      assert.deepEqual(projects.find(p=>p.id===projectId).parts,[]);
      assert.equal((await remove('projects',projectId)).status,200);
      const oldCookie = cookie;
      assert.equal((await request('logout.php', { method: 'POST', body: new URLSearchParams({ csrf: token }) })).status, 303);
      const oldSession = await fetch(new URL('api.php?store=files', base), { redirect: 'manual', headers: { Cookie: oldCookie } });
      assert.equal(oldSession.status, 401);
    });
  } finally {
    if (token) {
      // Best effort only: remove exactly our UUIDs, never clear a library.
      await remove('files', fileId).catch(() => {});
      await remove('collections', collectionId).catch(() => {});
      await remove('projects', projectId).catch(() => {});
    }
  }
});
