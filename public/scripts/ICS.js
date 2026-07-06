import { request as _request } from './core/http.js';
import * as dom from './core/dom.js';
import { t as _ } from './core/i18n.js';
import * as Calendar from './Calendar.js';

const icsDropZone = document.getElementById('icsDropZone');
const icsFileInput = document.getElementById('icsFileInput');
const icsFileName = document.getElementById('icsFileName');
const doImportIcsBtn = document.getElementById('doImportIcsBtn');

let ICSFile = null;

function SetFile(file)
{
	ICSFile = file;
	icsDropZone.classList.add('has-file');
	icsDropZone.classList.remove('drag-over');
	icsFileName.textContent = file.name;
	icsFileName.removeAttribute('data-hidden');
	dom.enable('doImportIcsBtn',true);
}

function Reset()
{
	ICSFile = null;
	icsFileInput.value = '';
	icsDropZone.classList.remove('has-file', 'drag-over');
	icsFileName.setAttribute('data-hidden', 'true');
	icsFileName.textContent = '';
	dom.enable('doImportIcsBtn',false);
}

export function Init()
{
	icsDropZone.addEventListener('click', () => icsFileInput.click());
	icsDropZone.addEventListener('dragover', e => { e.preventDefault(); icsDropZone.classList.add('drag-over'); });
	icsDropZone.addEventListener('dragleave', () => icsDropZone.classList.remove('drag-over'));
	icsDropZone.addEventListener('drop', e => {
		e.preventDefault();
		const file = e.dataTransfer.files[0];
		if (file)
			SetFile(file);
	});
	icsFileInput.addEventListener('change', () => {
		if (icsFileInput.files[0])
			SetFile(icsFileInput.files[0]);
	});
	doImportIcsBtn.addEventListener('click', async function () {
		if (! ICSFile || Calendar.Id() === null) return;
		const text = await ICSFile.text();
		const response = await _request(`/Event/ics/${Calendar.Id()}`, {
			method: 'PUT',
			headers: { 'Content-Type': 'text/calendar' },
			body: text
		});
		if (response === null) return;
		const result = await response.json();
		Reset();
		dom.getModal('importIcs').hide();
		Calendar.Show();
		swal({ title: _('import_ics'), text: _('ics_import_success').replace('%d', result.imported), icon: 'success' });
	});
}
