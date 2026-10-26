// Server storage for the existing viewer. Binary model data is loaded on demand.
export function createArchiveStorage({ csrf, fetchImpl = globalThis.fetch, onUnauthorized = () => {}, onError = () => {} }) {
  const pending = new Map();
  const buffers = new Map();
  const maxCachedBytes = 128 * 1024 * 1024;
  let cachedBytes = 0;

  function endpoint(store, params = {}) {
    if (!['files', 'collections', 'projects'].includes(store)) throw new Error('Ongeldig opslagtype.');
    return 'api.php?' + new URLSearchParams({ store, ...params });
  }

  async function request(url, options = {}, binary = false) {
    try {
      const response = await fetchImpl(url, {
        ...options, credentials: 'same-origin', cache: 'no-store',
        headers: { ...(options.method && options.method !== 'GET' ? { 'X-CSRF-Token': csrf } : {}), ...options.headers },
      });
      if (!response.ok) {
        if (response.status === 401) onUnauthorized();
        let message = response.status === 413
          ? 'Het bestand overschrijdt de uploadlimiet van je hosting.'
          : 'Opslaan of laden is mislukt. Vernieuw het archief en probeer opnieuw.';
        try { const payload = await response.json(); message = payload.error || message; } catch {}
        throw new Error(message);
      }
      return binary ? response.arrayBuffer() : response.json();
    } catch (error) {
      const result = new Error(error instanceof Error ? error.message : 'Geen verbinding met het archief.', { cause: error });
      result.name = 'ArchiveStorageError';
      onError(result);
      throw result;
    }
  }

  function invalidate(id) {
    if (id && buffers.has(id)) {
      cachedBytes -= buffers.get(id).byteLength;
      buffers.delete(id);
    } else if (!id) {
      buffers.clear();
      cachedBytes = 0;
    }
  }

  return {
    async getAll(store) {
      const items = await request(endpoint(store));
      return store === 'files' ? items.map(item => ({ ...item, _stored: true })) : items;
    },
    async put(store, item) {
      const { data, _stored, ...metadata } = item;
      const form = new FormData();
      form.append('metadata', JSON.stringify(metadata));
      if (store === 'files' && !_stored) {
        if (!(data instanceof ArrayBuffer)) throw new Error('De bestandsinhoud ontbreekt.');
        form.append('file', new Blob([data], { type: 'application/octet-stream' }), 'model.bin');
      }
      const saved = await request(endpoint(store), { method: 'POST', body: form });
      Object.assign(item, saved);
      if (store === 'files') item._stored = true;
      return item;
    },
    async delete(store, id) {
      await request(endpoint(store, { id }), { method: 'DELETE' });
      if (store === 'files') invalidate(id);
    },
    async clear(store) {
      await request(endpoint(store, { confirm: 'clear' }), { method: 'DELETE' });
      if (store === 'files') invalidate();
    },
    async fileData(file) {
      if (file.data instanceof ArrayBuffer) return file.data;
      if (buffers.has(file.id)) {
        const data = buffers.get(file.id);
        buffers.delete(file.id);
        buffers.set(file.id, data);
        return data;
      }
      if (!pending.has(file.id)) {
        const promise = request(endpoint('files', { id: file.id }), {}, true).then(data => {
          if (data.byteLength <= maxCachedBytes) {
            while (cachedBytes + data.byteLength > maxCachedBytes && buffers.size) {
              invalidate(buffers.keys().next().value);
            }
            buffers.set(file.id, data);
            cachedBytes += data.byteLength;
          }
          return data;
        }).finally(() => pending.delete(file.id));
        pending.set(file.id, promise);
      }
      return pending.get(file.id);
    },
    downloadUrl(id) { return endpoint('files', { id }); },
  };
}
