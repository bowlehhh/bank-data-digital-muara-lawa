const drawer = document.getElementById('menu-drawer');
const menuButton = document.querySelector('[data-open-menu]');

menuButton?.addEventListener('click', () => {
    drawer.showModal();
    menuButton.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
});
document.querySelector('[data-close-menu]')?.addEventListener('click', () => drawer.close());
drawer?.addEventListener('close', () => {
    menuButton.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    menuButton.focus();
});
drawer?.addEventListener('click', (event) => {
    if (event.target === drawer && event.clientX >= drawer.getBoundingClientRect().right) drawer.close();
});
window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
    if (event.matches && drawer?.open) drawer.close();
});

document.querySelector('[data-toggle-password]')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    const input = document.getElementById(button.getAttribute('aria-controls'));
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.querySelector('span').textContent = visible ? 'Sembunyikan' : 'Tampilkan';
});

const uploadForm = document.getElementById('document-upload');
if (uploadForm) {
    const field = (name) => uploadForm.elements.namedItem(name);
    let groupOptions = [...field('group_folder_id').options].map(option => option.cloneNode(true));
    let typeOptions = [...field('target_folder_id').options].map(option => option.cloneNode(true));
    const filterOptions = (select, options, predicate) => {
        const selected = select.value;
        select.replaceChildren(...options.filter(option => !option.value || predicate(option)).map(option => option.cloneNode(true)));
        select.value = [...select.options].some(option => option.value === selected) ? selected : '';
    };
    const updateFolders = () => {
        const year = field('year').value;
        filterOptions(field('group_folder_id'), groupOptions, option => !option.dataset.year || option.dataset.year === year);
        const group = field('group_folder_id').value;
        filterOptions(field('target_folder_id'), typeOptions, option => option.dataset.year === year && option.dataset.group === group);
        if (! field('target_folder_id').value && field('target_folder_id').options.length === 2) {
            field('target_folder_id').selectedIndex = 1;
        }
    };
    const refreshFoldersButton = document.getElementById('refresh-upload-folders');
    refreshFoldersButton?.addEventListener('click', async () => {
        const status = document.getElementById('folder-refresh-status');
        refreshFoldersButton.disabled = true;
        status.textContent = 'Memuat pilihan folder…';
        try {
            const response = await fetch(refreshFoldersButton.dataset.url, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
            if (! response.ok || response.redirected) throw new Error('Folder tidak tersedia');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const groups = page.querySelector('#group_folder_id');
            const targets = page.querySelector('#target_folder_id');
            const years = page.querySelector('#year');
            if (! groups || ! targets || ! years) throw new Error('Pilihan folder tidak tersedia');
            groupOptions = [...groups.options];
            typeOptions = [...targets.options];
            filterOptions(field('year'), [...years.options], () => true);
            updateFolders();
            refreshPreview();
            status.textContent = 'Pilihan folder diperbarui. Pilih folder baru Anda.';
        } catch {
            status.textContent = 'Folder belum dapat diperbarui. Pastikan sesi masih aktif dan coba kembali.';
        } finally {
            refreshFoldersButton.disabled = false;
        }
    });
    const updateTiming = () => {
        uploadForm.querySelectorAll('[data-timing-field]').forEach(container => {
            const enabled = container.dataset.timingField === field('timing').value;
            container.hidden = !enabled;
            container.querySelectorAll('input, select').forEach(input => { input.disabled = !enabled; input.required = enabled; });
        });
    };
    let timer;
    let pending;
    const preview = document.getElementById('filename-preview');
    const refreshPreview = () => {
        clearTimeout(timer);
        pending?.abort();
        preview.textContent = 'Lengkapi informasi dan pilih file untuk melihat nama resmi.';
        const file = field('file').files[0];
        if (!file || !field('title').value || !field('target_folder_id').value) return;
        timer = setTimeout(async () => {
            pending = new AbortController();
            const data = Object.fromEntries(new FormData(uploadForm));
            delete data.file;
            data.extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
            preview.textContent = 'Memeriksa nama file…';
            try {
                const response = await fetch(uploadForm.dataset.previewUrl, {
                    method: 'POST', credentials: 'same-origin', signal: pending.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': field('_token').value },
                    body: JSON.stringify(data),
                });
                const result = await response.json();
                preview.textContent = response.ok ? result.filename : Object.values(result.errors ?? {}).flat()[0] ?? 'Preview belum tersedia. Periksa informasi dokumen.';
            } catch (error) {
                if (error.name !== 'AbortError') preview.textContent = 'Preview tidak dapat dimuat. Nama akhir tetap diperiksa saat disimpan.';
            }
        }, 300);
    };
    uploadForm.addEventListener('input', event => {
        if (['year', 'group_folder_id'].includes(event.target.name)) updateFolders();
        if (event.target.name === 'timing') updateTiming();
        refreshPreview();
    });
    uploadForm.addEventListener('change', event => {
        if (['year', 'group_folder_id'].includes(event.target.name)) updateFolders();
        if (event.target.name === 'timing') updateTiming();
        refreshPreview();
    });
    uploadForm.addEventListener('submit', event => {
        if (uploadForm.dataset.submitting) { event.preventDefault(); return; }
        uploadForm.dataset.submitting = 'true';
        const button = uploadForm.querySelector('[data-submit-upload]');
        button.disabled = true;
        button.querySelector('span').textContent = 'Menyimpan dokumen…';
        uploadForm.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        delete uploadForm.dataset.submitting;
        uploadForm.removeAttribute('aria-busy');
        const button = uploadForm.querySelector('[data-submit-upload]');
        button.disabled = false;
        button.querySelector('span').textContent = 'Simpan Dokumen';
    });
    updateFolders();
    updateTiming();
}
