// Autosave for application steps: PATCH on change (debounced) and every 10s when dirty.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-autosave]');
    if (!form) return;
    const url = form.dataset.autosave;
    const indicator = document.querySelector('[data-save-indicator]');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let dirty = false, timer = null, saving = false;
    const setText = (t, cls) => { if (indicator) { indicator.textContent = t; indicator.className = 'text-[0.8125rem] ' + (cls || 'text-ink-500'); } };
    const save = async () => {
        if (!dirty || saving) return;
        saving = true; setText('Saving…');
        try {
            const fd = new FormData(form);
            const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
            if (res.ok) { dirty = false; setText('Saved · just now', 'text-success-600'); }
            else setText('Could not save — your entries are kept on this page', 'text-danger-600');
        } catch (e) { setText('Offline — will retry', 'text-warning-600'); }
        saving = false;
    };
    form.addEventListener('input', () => { dirty = true; setText('Unsaved changes'); clearTimeout(timer); timer = setTimeout(save, 1500); });
    form.addEventListener('change', () => { dirty = true; clearTimeout(timer); timer = setTimeout(save, 600); });
    setInterval(save, 10000);
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // Repeatable rows: [data-repeat] container with a <template data-row>
    document.querySelectorAll('[data-repeat]').forEach((wrap) => {
        const tpl = wrap.querySelector('template[data-row]');
        const addBtn = wrap.querySelector('[data-add-row]');
        const list = wrap.querySelector('[data-rows]');
        const renumber = () => list.querySelectorAll('[data-row-item]').forEach((row, i) => {
            row.querySelectorAll('[name]').forEach((el) => { el.name = el.name.replace(/\[(\d+|__i__)\]/, `[${i}]`); });
            row.querySelectorAll('label[for]').forEach((l) => { l.htmlFor = l.htmlFor.replace(/__i__|\d+$/, i); });
        });
        addBtn?.addEventListener('click', () => { list.appendChild(tpl.content.cloneNode(true)); renumber(); dirty = true; });
        list.addEventListener('click', (e) => { const b = e.target.closest('[data-remove-row]'); if (b) { b.closest('[data-row-item]').remove(); renumber(); dirty = true; clearTimeout(timer); timer = setTimeout(save, 600); } });
    });
});
