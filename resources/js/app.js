// ---------------------------------------------------------------------------------------------
// CCDI Lost & Found - progressive enhancement only. Every page still works without JavaScript.
// ---------------------------------------------------------------------------------------------

// 1) Public header menu (mobile): toggle the panel, close on Escape and after following a link
document.querySelectorAll('[data-toggle]').forEach((btn) => {
    const target = document.getElementById(btn.dataset.toggle);
    if (!target) return;
    const set = (open) => {
        target.classList.toggle('hidden', !open);
        btn.setAttribute('aria-expanded', String(open));
        btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };
    btn.addEventListener('click', () => set(target.classList.contains('hidden')));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
    target.addEventListener('click', (e) => { if (e.target.closest('a')) set(false); });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', () => set(false));
});

// 2) Dashboard sidebar drawer (phones and tablets)
const sidebar = document.getElementById('sidebar');
if (sidebar) {
    const overlay = document.getElementById('sidebar-overlay');
    const openers = document.querySelectorAll('[data-open="sidebar"]');
    const setDrawer = (open) => {
        sidebar.dataset.open = String(open);
        if (overlay) overlay.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        openers.forEach((b) => b.setAttribute('aria-expanded', String(open)));
        if (open) sidebar.querySelector('a, button')?.focus({ preventScroll: true });
    };
    openers.forEach((b) => b.addEventListener('click', () => setDrawer(true)));
    document.querySelectorAll('[data-close="sidebar"]').forEach((b) => b.addEventListener('click', () => setDrawer(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.dataset.open === 'true') { setDrawer(false); openers[0]?.focus(); } });
    sidebar.addEventListener('click', (e) => { if (e.target.closest('a')) setDrawer(false); });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (m) => { if (m.matches) setDrawer(false); });
}

// 3) Disable submit buttons after the first click (no double submissions) and show a loading state
const resetLoading = () => {
    document.querySelectorAll('form[data-submitted]').forEach((form) => {
        delete form.dataset.submitted;
        const btn = form.querySelector('button[aria-busy="true"]');
        if (btn) {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        }
    });
};
document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        const btn = form.querySelector('button[type="submit"]');
        if (!btn || form.dataset.submitted) return;
        form.dataset.submitted = '1';
        btn.dataset.original = btn.innerHTML;
        btn.setAttribute('aria-busy', 'true');
        // disable on the next tick so the button's own value is still submitted
        setTimeout(() => { btn.disabled = true; }, 0);
        btn.textContent = form.dataset.loading || 'Please wait…';
    });
});
window.addEventListener('pageshow', (e) => { if (e.persisted) resetLoading(); }); // back button

// 4) Confirmation prompts for consequential actions
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            resetLoading();
        }
    }, true);
});

// 5) Photo upload zone: type/size check, preview, replace and remove (the server re-validates everything)
const photo = document.getElementById('photo');
if (photo) {
    const zone = document.getElementById('photo-zone');
    const empty = document.getElementById('photo-empty');
    const filled = document.getElementById('photo-filled');
    const preview = document.getElementById('photo-preview');
    const note = document.getElementById('photo-note');
    const nameEl = document.getElementById('photo-name');
    const sizeEl = document.getElementById('photo-size');
    const maxBytes = Number(photo.dataset.maxBytes || 31457280);
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    const fmt = (b) => (b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');
    let url = null;

    const show = (file) => {
        if (url) URL.revokeObjectURL(url);
        url = file ? URL.createObjectURL(file) : null;
        if (preview) { if (url) preview.src = url; else preview.removeAttribute('src'); }
        if (nameEl) nameEl.textContent = file ? file.name : '';
        if (sizeEl) sizeEl.textContent = file ? fmt(file.size) : '';
        empty?.classList.toggle('hidden', !!file);
        filled?.classList.toggle('hidden', !file);
        zone?.classList.remove('has-error');
    };
    const error = (msg) => {
        photo.value = '';
        show(null);
        if (note) note.textContent = msg;
        zone?.classList.add('has-error');
    };
    const check = () => {
        const file = photo.files[0];
        if (note) note.textContent = '';
        if (!file) return show(null);
        if (!allowed.includes(file.type)) return error('Please choose a JPEG, PNG or WebP image.');
        if (file.size > maxBytes) return error('That photo is ' + fmt(file.size) + ', which is larger than the ' + Math.round(maxBytes / 1048576) + ' MB limit.');
        show(file);
    };
    photo.addEventListener('change', check);
    document.getElementById('photo-remove')?.addEventListener('click', () => { photo.value = ''; if (note) note.textContent = ''; show(null); photo.focus(); });
    document.getElementById('photo-replace')?.addEventListener('click', () => photo.click());

    if (zone) {
        ['dragenter', 'dragover'].forEach((t) => zone.addEventListener(t, (e) => { e.preventDefault(); zone.dataset.drag = 'true'; }));
        ['dragleave', 'drop'].forEach((t) => zone.addEventListener(t, (e) => { e.preventDefault(); delete zone.dataset.drag; }));
        zone.addEventListener('drop', (e) => {
            if (!e.dataTransfer?.files?.length) return;
            photo.files = e.dataTransfer.files;
            check();
        });
    }
}
