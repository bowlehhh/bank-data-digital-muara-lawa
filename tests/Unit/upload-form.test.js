import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');
const option = (value, dataset = {}) => ({ value, dataset, cloneNode() { return option(value, { ...dataset }); } });
class Select {
    constructor(options, value = '') { this.options = options; this.value = value; }
    set value(value) { this.current = this.options.some(option => option.value === value) ? value : ''; }
    get value() { return this.current; }
    set selectedIndex(index) { this.value = this.options[index].value; }
    replaceChildren(...options) { this.options = options; }
}
function setup({ year = '2026', group = '10', target = '11', fetch } = {}) {
    const listeners = {};
    const windowListeners = {};
    const refreshListeners = {};
    const fields = {
        year: new Select([option('2026'), option('2027')], year),
        group_folder_id: new Select([
            option(''), option('10', { year: '2026' }), option('20', { year: '2027' }),
        ], group),
        target_folder_id: new Select([
            option(''), option('11', { year: '2026', group: '10' }), option('12', { year: '2026', group: '10' }),
            option('21', { year: '2027', group: '20' }),
        ], target),
        file: { files: [{ name: 'rencana.pdf' }] }, title: { value: '' }, description: { value: 'Rencana tahunan' },
        timing: { value: 'none' },
    };
    const label = { textContent: '' };
    const button = { disabled: false, querySelector: () => label };
    const status = { textContent: '' };
    const refreshButton = { dataset: { url: '/documents/create' }, addEventListener: (event, callback) => { refreshListeners[event] = callback; } };
    const form = {
        dataset: {}, elements: { namedItem: name => fields[name] },
        addEventListener: (event, callback) => { listeners[event] = callback; },
        querySelectorAll: () => [], querySelector: () => button, setAttribute() {}, removeAttribute() {},
    };
    const elements = { 'document-upload': form, 'filename-preview': {}, 'refresh-upload-folders': refreshButton, 'folder-refresh-status': status };
    runInNewContext(source, {
        document: { getElementById: id => elements[id], querySelector: () => null },
        window: { matchMedia: () => ({ addEventListener() {} }), addEventListener: (event, callback) => { windowListeners[event] = callback; } },
        clearTimeout, setTimeout, fetch,
        DOMParser: class { parseFromString(page) { return page; } },
    });
    return { fields, form, listeners, windowListeners, button, refreshListeners, status };
}

test('changing year clears the previous group and destination', () => {
    const { fields, listeners } = setup();
    fields.year.value = '2027';
    listeners.change({ target: { name: 'year' } });
    assert.equal(fields.group_folder_id.value, '');
    assert.equal(fields.target_folder_id.value, '');
    assert.deepEqual(fields.target_folder_id.options.map(option => option.value), ['']);
    fields.group_folder_id.value = '20';
    listeners.change({ target: { name: 'group_folder_id' } });
    assert.equal(fields.target_folder_id.value, '21');
});

test('year change only exposes its matching direct-folder group', () => {
    const { fields, listeners } = setup();
    fields.year.value = '2027';
    listeners.input({ target: { name: 'year' } });
    assert.deepEqual(fields.group_folder_id.options.map(option => option.value), ['', '20']);
    fields.group_folder_id.value = '20';
    listeners.change({ target: { name: 'group_folder_id' } });
    assert.equal(fields.target_folder_id.value, '21');
});

test('valid selection is restored after server validation', () => {
    const { fields } = setup({ target: '12' });
    assert.equal(fields.target_folder_id.value, '12');
});

test('double submit is blocked and back navigation restores submit', () => {
    const { form, listeners, windowListeners, button } = setup();
    let prevented = 0;
    const event = { preventDefault() { prevented++; } };
    listeners.submit(event);
    listeners.submit(event);
    assert.equal(prevented, 1);
    assert.equal(button.disabled, true);
    windowListeners.pageshow();
    assert.equal(button.disabled, false);
    assert.equal(form.dataset.submitting, undefined);
});

test('refresh includes newly created folders while retaining selected file and metadata', async () => {
    const page = {
        '#year': new Select([option('2026'), option('2027')]),
        '#group_folder_id': new Select([option(''), option('10', { year: '2026' })]),
        '#target_folder_id': new Select([option(''), option('11', { year: '2026', group: '10' }), option('13', { year: '2026', group: '10' })]),
    };
    const { fields, refreshListeners, status } = setup({ fetch: async () => ({ ok: true, redirected: false, text: async () => ({ querySelector: key => page[key] }) }) });
    const file = fields.file.files[0];
    await refreshListeners.click();
    assert.deepEqual(fields.target_folder_id.options.map(option => option.value), ['', '11', '13']);
    assert.equal(fields.file.files[0], file);
    assert.equal(fields.description.value, 'Rencana tahunan');
    assert.equal(fields.target_folder_id.value, '11');
    assert.match(status.textContent, /diperbarui/);
});

test('expired session during refresh preserves the form and shows an actionable error', async () => {
    const { fields, refreshListeners, status } = setup({ fetch: async () => ({ ok: true, redirected: true }) });
    await refreshListeners.click();
    assert.equal(fields.target_folder_id.value, '11');
    assert.equal(fields.file.files[0].name, 'rencana.pdf');
    assert.match(status.textContent, /sesi masih aktif/);
});
