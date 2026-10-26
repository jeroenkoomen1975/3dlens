// Project records reference existing private models: a model can belong to many projects.
export function createProjectsUI({ storage, getFiles, getSelected, selectFile, toast, escapeHtml: h }) {
  let projects = [], current = null, dirty = false, busy = false;
  const dialog = document.createElement('dialog');
  dialog.className = 'archive-dialog';
  dialog.setAttribute('aria-label', 'Projecten met meerdere onderdelen');
  document.body.append(dialog);
  const leave = () => !dirty || confirm('Niet-opgeslagen projectwijzigingen weggooien?');
  dialog.addEventListener('cancel', e => { if (busy || !leave()) e.preventDefault(); });

  function draw() {
    dialog.innerHTML = `<div class="feature-heading"><h2>Projecten</h2><button class="btn" data-close>Sluiten</button></div>
      <p>Groepeer modellen tot een bouwproject met aantallen en montagenotities. Modellen blijven in hun eigen collectie.</p>
      <div class="project-layout"><section><button class="btn btn-primary" data-new>+ Nieuw project uit selectie</button>
      <div class="project-list">${projects.map(p => `<button class="btn" data-project="${h(p.id)}">${h(p.name)} (${p.parts.length})</button>`).join('') || '<p>Nog geen projecten.</p>'}</div></section>
      <section id="project-editor">${current ? `<label>Projectnaam<input class="tag-input" id="project-name" maxlength="200" value="${h(current.name)}"></label>
      <label>Montagenotities<textarea class="description-textarea" id="project-description" maxlength="8000">${h(current.description)}</textarea></label>
      <h3>Onderdelen</h3><div id="project-parts"></div>
      <label>Model zoeken<input class="tag-input" id="project-search" type="search" placeholder="Zoek op bestandsnaam"></label>
      <select class="tag-input" id="project-model" aria-label="Onderdeel kiezen"></select><button class="btn" data-add>Onderdeel toevoegen</button>
      <p id="project-status" role="status"></p><div class="feature-actions"><button class="btn btn-primary" data-save>Project opslaan</button>
      <button class="btn" data-delete>Project verwijderen</button></div>` : '<p>Kies een project of maak een nieuw project.</p>'}</section></div>`;
    dialog.querySelector('[data-close]').onclick = () => { if (!busy && leave()) dialog.close(); };
    dialog.querySelector('[data-new]').onclick = () => {
      if (!leave()) return;
      const ids = [...new Set(getSelected())].filter(id => getFiles().some(f => f.id === id));
      current = { id: crypto.randomUUID(), name: '', description: '', parts: ids.slice(0,500).map(fileId => ({ fileId, quantity: 1, notes: '' })) };
      dirty = true; draw(); dialog.querySelector('#project-name').focus();
    };
    dialog.querySelectorAll('[data-project]').forEach(button => button.onclick = () => {
      if (!leave()) return;
      current = structuredClone(projects.find(p => p.id === button.dataset.project)); dirty = false; draw();
    });
    if (!current) return;
    for (const [id, field] of [['project-name','name'],['project-description','description']]) {
      dialog.querySelector('#' + id).oninput = e => { current[field] = e.target.value; dirty = true; };
    }
    drawParts();
    const picker = dialog.querySelector('#project-model');
    const filter = () => {
      const q = dialog.querySelector('#project-search').value.toLowerCase();
      picker.innerHTML = getFiles().filter(f => f.name.toLowerCase().includes(q) && !current.parts.some(p => p.fileId === f.id))
        .map(f => `<option value="${h(f.id)}">${h(f.name)}</option>`).join('');
    };
    dialog.querySelector('#project-search').oninput = filter; filter();
    dialog.querySelector('[data-add]').onclick = () => {
      if (!picker.value || current.parts.length >= 500) return;
      current.parts.push({ fileId: picker.value, quantity: 1, notes: '' }); dirty = true; drawParts(); filter();
    };
    dialog.querySelector('[data-save]').onclick = () => mutate(async () => {
      if (!current.name.trim()) throw new Error('Geef het project een naam.');
      if (current.parts.some(p => !Number.isInteger(p.quantity) || p.quantity < 1 || p.quantity > 9999)) throw new Error('Vul aantallen van 1 tot 9999 in.');
      await storage.put('projects', current); dirty = false;
      projects = await storage.getAll('projects'); draw(); toast('Project opgeslagen.', 'success');
    });
    dialog.querySelector('[data-delete]').onclick = () => mutate(async () => {
      if (!confirm('Project verwijderen? De modelbestanden blijven behouden.')) return;
      if (projects.some(p => p.id === current.id)) await storage.delete('projects', current.id);
      projects = projects.filter(p => p.id !== current.id); current = null; dirty = false; draw();
    });
  }
  function drawParts() {
    const list = dialog.querySelector('#project-parts');
    list.innerHTML = current.parts.map((part,i) => {
      const file = getFiles().find(f => f.id === part.fileId);
      return `<div class="project-part"><button class="btn" data-open="${i}">${h(file?.name || 'Model ontbreekt')}</button>
        <label>Aantal<input class="tag-input" type="number" min="1" max="9999" step="1" value="${h(part.quantity)}" data-quantity="${i}"></label>
        <label>Onderdeelnotitie<input class="tag-input" maxlength="2000" value="${h(part.notes)}" data-note="${i}"></label>
        <button class="btn" data-remove="${i}" aria-label="Onderdeel uit project halen">×</button></div>`;
    }).join('') || '<p>Nog geen onderdelen. Voeg modellen uit het archief toe.</p>';
    list.querySelectorAll('[data-quantity]').forEach(input => input.oninput = () => { current.parts[Number(input.dataset.quantity)].quantity = input.value === '' ? null : Number(input.value); dirty = true; });
    list.querySelectorAll('[data-note]').forEach(input => input.oninput = () => { current.parts[Number(input.dataset.note)].notes = input.value; dirty = true; });
    list.querySelectorAll('[data-remove]').forEach(button => button.onclick = () => { current.parts.splice(Number(button.dataset.remove),1); dirty = true; drawParts(); dialog.querySelector('#project-search').dispatchEvent(new Event('input')); });
    list.querySelectorAll('[data-open]').forEach(button => button.onclick = () => {
      if (!leave()) return;
      const id = current.parts[Number(button.dataset.open)].fileId;
      if (getFiles().some(f => f.id === id)) { dialog.close(); selectFile(id); }
    });
  }
  async function mutate(action) {
    if (busy) return;
    busy = true;
    dialog.querySelectorAll('button,input,textarea,select').forEach(el => el.disabled = true);
    try { await action(); }
    catch (error) { toast(error.message, 'warn', 10000); }
    finally { busy = false; dialog.querySelectorAll('button,input,textarea,select').forEach(el => el.disabled = false); }
  }
  return { async open() {
    if (busy) return;
    busy = true;
    try { projects = await storage.getAll('projects'); current = null; dirty = false; draw(); dialog.showModal(); }
    finally { busy = false; }
  } };
}
