
// Entry module : calendars, events, ACL, recurrence, ICS and app bootstrap.
// Core helpers (i18n/dom/http) and the alarms feature live in dedicated modules.

import { t as _, setTranslations as _setTranslations } from './core/i18n.js';
import * as dom from './core/dom.js';
import { request as _request, checkConnection as _checkConnection, loadingShow as _loadingShow, loadingHide as _loadingHide, promptLogin as _promptLogin, resetAskForLogin as _resetAskForLogin } from './core/http.js';
import { initAlarms as _initAlarms, alarmWidgetLoad as _alarmWidgetLoad, hideAlarmWidget as _hideAlarmWidget, eventCalendarAlarmsLoad as _eventCalendarAlarmsLoad } from './alarms.js';
import * as GlobalACL from './GlobalACL.js';
import * as CalendarACL from './CalendarACL.js';

let _calendar; // FullCalendar.js
let _calendar_id = null; // selected calendar unique database id
let _globalsAuth = 0; // connected user global authorizations
let _calendarAuth = 0; // connected user selected calendar authorizations
let _event_id = null; // clicked calendar event unique database id
let _user_infos;
let _sources = null;
let _globalPermissions;
let _icsFile = null;


function _toggleButtons()
{
	dom.enable('addCalendarBtn',(_globalsAuth & 1) === 1);
	dom.enable('manageGlobalAclBtn',((_globalsAuth & 16) === 16));
	dom.enable('manageAlarmsBtn',((_globalsAuth & 32) === 32));
	dom.enable('disconnectBtn',_user_infos !== null);
}

function _getUserInformations()
{
	_request('/User/Informations', {}).then(response => {
		if (response === null)
			return;
		response.json().then(infos => {
			_user_infos = infos;
			document.getElementById('userNameLabel').innerText = _user_infos['informations']['name'] ?? _('anonymous');
		});
	});
	_request('/Authorization/global', {}).then(response => {
		if (response === null)
			return;
		response.json().then(auth => {
			_globalsAuth = auth;
			_toggleButtons();
		});
	});
}

async function _refreshCalendarsList()
{
	const response = await _request('/Calendar/catalog');
	if (response === null)
		return;
	const calendars = await response.json();
	const list = document.getElementById('calendarsList');
	list.innerHTML = '';
	for (const cal of calendars)
	{
		const input = document.createElement('input');
		input.type = 'radio';
		input.name = 'calendar';
		input.id = `cal_${cal.calendar_id}`;
		input.value = cal.calendar_id;
		input.checked = (_calendar_id === cal.calendar_id);
		const label = document.createElement('label');
		label.className = 'calendar-item';
		label.htmlFor = `cal_${cal.calendar_id}`;
		label.textContent = cal.name;
		label.title = cal.description;
		label.addEventListener('click', function ()
		{
			_calendar_id = cal.calendar_id;
			_showCalendar();
		});
		list.appendChild(input);
		list.appendChild(label);
	}
}

async function _showCalendar()
{
	const calendar = document.getElementById('calendar');
	const no_calendar = document.getElementById('no_calendar');
	if (_calendar_id === null)
	{
		_calendarAuth = 0;
		dom.enable('addEventBtn', false);
		dom.enable('importICSBtn', false);
		dom.enable('calendarInfoBtn', false);
		dom.enable('editCalendarBtn', false);
		dom.enable('manageCalendarAclBtn', false);
		calendar.setAttribute('data-hidden', 'true');
		no_calendar.removeAttribute('data-hidden');
		dom.enable('removeCalendarBtn',false);
		return;
	}
	const response = await _request(`/Authorization/calendar/${_calendar_id}`);
	_calendarAuth = response !== null ? await response.json() : 0;
	dom.enable('addEventBtn', _calendarAuth >= 3);
	dom.enable('importICSBtn', _calendarAuth >= 3);
	dom.enable('calendarInfoBtn', _calendarAuth >= 1);
	dom.enable('editCalendarBtn', _calendarAuth >= 3);
	dom.enable('manageCalendarAclBtn', _calendarAuth >= 4);
	dom.enable('removeCalendarBtn', (_calendarAuth >= 4) && (_globalsAuth & 2) === 2);
	calendar.removeAttribute('data-hidden');
	no_calendar.setAttribute('data-hidden', 'true');
//	_calendar.removeAllEvents();
	_calendar.removeAllEventSources();
	_calendar.addEventSource({ url: `/Event/list/${_calendar_id}` });
//	_calendar.refetchEvents();
	_calendar.render();
}

function _moveTooltip(e)
{
	const tooltip = document.getElementById('pfc-tooltip');
	tooltip.style.left = (e.clientX + 12) + 'px';
	tooltip.style.top = (e.clientY + 12) + 'px';
}

function _toDateTimeLocal(str)
{
	if (! str)
		return '';
	if (/^\d{4}-\d{2}-\d{2}$/.test(str))
		return str + 'T00:00';
	return str.substring(0, 16);
}

function _utcToDateTimeLocal(str, offset = 0)
{
	if (! str) return '';
	const d = new Date(str);
	d.setSeconds(d.getSeconds() + offset);
	const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
	return local.toISOString().substring(0, 16);
}


function _icsSetFile(file)
{
	_icsFile = file;
	icsDropZone.classList.add('has-file');
	icsDropZone.classList.remove('drag-over');
	icsFileName.textContent = file.name;
	icsFileName.removeAttribute('data-hidden');
	dom.enable('doImportIcsBtn',true);
}
	
function _icsReset()
{
	_icsFile = null;
	icsFileInput.value = '';
	icsDropZone.classList.remove('has-file', 'drag-over');
	icsFileName.setAttribute('data-hidden', 'true');
	icsFileName.textContent = '';
	dom.enable('doImportIcsBtn',false);
}

function _rruleBuild()
{
	const rruleUI = document.getElementById('rruleCollapse');
	const freq = rruleUI.querySelector('[name=rrule_freq]:checked')?.value ?? 'daily';
	const interval = Math.max(1, parseInt(rruleUI.querySelector('[name=rrule_interval]').value) || 1);
	const endMode = rruleUI.querySelector('[name=rrule_end]:checked')?.value ?? 'endless';
	const freqMap = { daily: 'DAILY', weekly: 'WEEKLY', monthly: 'MONTHLY', yearly: 'YEARLY' };
	const parts = [`FREQ=${freqMap[freq] ?? 'DAILY'}`];
	if (interval > 1)
		parts.push(`INTERVAL=${interval}`);
	if (endMode === 'count') {
		const count = parseInt(rruleUI.querySelector('[name=rrule_count]').value);
		if (count >= 2) parts.push(`COUNT=${count}`);
	} else if (endMode === 'until') {
		const until = rruleUI.querySelector('[name=rrule_until]').value;
		if (until) parts.push(`UNTIL=${until.replace(/-/g, '')}T000000Z`);
	}
	return parts.join(';');
}

function _rruleSync(e)
{
	if (e.target.name !== 'rrule')
		document.querySelector('[name=rrule]').value = _rruleBuild();
}

	// constructeur
function construct(lang,sources)
{
	_sources = sources;
	const self = this;
	fetch('/index/translations',{}).then(async function (response)
	{
		if (response.ok)
			_setTranslations(await response.json());
		else
			_setTranslations({});
		_globalPermissions = [
			[1, _('gr_cal_create')],
			[2, _('gr_cal_destroy')],
			[4, _('gr_res_add')],
			[8, _('gr_res_del')],
			[16, _('gr_acl')],
			[32, _('gr_alarm')]
		];
		_calendar = new FullCalendar.Calendar(document.getElementById('calendar'),{
			'locale': lang,
			'headerToolbar': {
				'left': 'prev,next today',
				'center': 'title',
				'right': 'timeGridDay,timeGridWeek,dayGridMonth,dayGridYear'
			},
			'views': {
				'timeGridDay': {
					'allDaySlot': false
				},
				'timeGridWeek': {
					'allDaySlot': false
				}
			},
			'initialView': 'timeGridWeek',
			'height': '100%',
		});
		// Connection managment
		document.getElementById('loginBtn').addEventListener('click', self.connect);
		document.getElementById('disconnectBtn').addEventListener('click', self.disconnect);

		document.addEventListener('click', function(e) {
			const btn = e.target.closest('[data-copy-link]');
			if (!btn) return;
			const link = document.getElementById(btn.dataset.copyLink);
			if (!link) return;
			navigator.clipboard.writeText(link.href).then(() => {
				const icon = btn.querySelector('i');
				icon.className = 'bi bi-clipboard2-check-fill';
				setTimeout(() => { icon.className = 'bi bi-clipboard2-fill'; }, 1500);
			});
		});

		// Calendars managment

		document.getElementById('addCalendarBtn').addEventListener('click', function () {
			_calendar_id = null;
			document.getElementById('calendarForm').reset();
			_hideAlarmWidget('calendar');
			dom.getModal('calendar').show();
		});
		document.getElementById('calendarInfoBtn').addEventListener('click', async function () {
			if (_calendar_id === null)
				return;
			const response = await _request(`/Calendar/read/${_calendar_id}`);
			if (response === null)
				return;
			const cal = await response.json();
			document.getElementById('infoCalendarName').textContent = cal.name;
			document.getElementById('infoCalendarDescription').textContent = cal.description ?? '';
			const icsUrl = `${window.location.origin}/$/Event/ics/${_calendar_id}`;
			const link = document.getElementById('infoIcsLink');
			link.href = icsUrl;
			link.textContent = icsUrl;
			const fcUrl = `${window.location.origin}/$/Event/list/${_calendar_id}`;
			const fcLink = document.getElementById('infoFcLink');
			fcLink.href = fcUrl;
			fcLink.textContent = fcUrl;
			dom.getModal('info').show();
		});
		document.getElementById('editCalendarBtn').addEventListener('click', async function () {
			if (_calendar_id === null)
				return;
			const response = await _request(`/Calendar/read/${_calendar_id}`);
			if (response === null)
				return;
			const data = await response.json();
			const form = document.getElementById('calendarForm');
			form.reset();
			form.querySelectorAll('[name]').forEach(field => field.value = data[field.name]);
			await _alarmWidgetLoad('calendar', _calendar_id, _calendarAuth >= 4);
			dom.getModal('calendar').show();
		});
		document.getElementById('removeCalendarBtn').addEventListener('click', async function () {
			const confirmed = await swal({
				title: _('confirm_delete_calendar'),
				icon: 'warning',
				buttons: true,
				dangerMode: true
			});
			if (! confirmed)
				return;
			const response = await _request(`/Calendar/delete/${_calendar_id}`);
			if (response === null)
				return;
			_calendar_id = null;
			await _refreshCalendarsList();
			_showCalendar();
		});
		document.getElementById('saveCalendarBtn').addEventListener('click', async function (event) {
			const fields = event.target.closest('div .modal-content').querySelectorAll('[name]');
			const data = {};
			for (const field of fields)
				data[field.name] = field.value;
			const url = _calendar_id ? `/Calendar/update/${_calendar_id}` : '/Calendar/create';
			const response = await _request(url,
			{
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams(data)
			});
			if (response === null)
				return;
			if (_calendar_id === null)
			{
				_calendar_id = await response.json();
				_showCalendar();
			}
			await _refreshCalendarsList();
			dom.getModal('calendar').hide();
		});

			// Events managment
			const rruleCollapse = document.getElementById('rruleCollapse');
			const rruleCollapseBtn = document.querySelector('[data-bs-target="#rruleCollapse"]');
			const rruleActive = document.getElementById('rruleActive');
			function _rruleHide() {
				rruleCollapse.classList.remove('show');
				rruleCollapseBtn.classList.add('collapsed');
				rruleActive.value = '0';
			}
			_calendar.on('eventMouseEnter', function(info) {
				const desc = info.event.extendedProps.description;
				if (! desc) return;
				const tooltip = document.getElementById('pfc-tooltip');
				tooltip.textContent = desc;
				tooltip.style.display = 'block';
				info.el.addEventListener('mousemove', _moveTooltip);
			});
			_calendar.on('eventMouseLeave', function(info) {
				document.getElementById('pfc-tooltip').style.display = 'none';
				info.el.removeEventListener('mousemove', _moveTooltip);
			});
			_calendar.on('dateClick', function(info) {
				_event_id = null;
				const form = document.getElementById('eventForm');
				form.reset();
				_rruleHide();
				document.getElementById('rruleExpertSection').style.display = 'none';
				form.querySelector('input[name=start]').value = _toDateTimeLocal(info.dateStr);
				form.querySelector('input[name=end]').value = _toDateTimeLocal(info.dateStr);
				document.getElementById('eventModalTitle').textContent = _('new_event');
				document.getElementById('deleteEventBtn').disabled = true;
				_hideAlarmWidget('event');
				_eventCalendarAlarmsLoad(_calendar_id);
				dom.getModal('event').show();
			});
			_calendar.on('eventClick', async function(info) {
				info.jsEvent.preventDefault();
				_event_id = info.event.id;
				const response = await _request(`/Event/read/${_event_id}`);
				if (response === null)
					return;
				const data = await response.json();
//				console.log(data);
				const form = document.getElementById('eventForm');
				form.reset();
				document.getElementById('rruleExpertSection').style.display = 'none';
				form.querySelectorAll('[name]').forEach((field) => {
					if (data[field.name] === undefined) return;
					field.value = (field.name === 'start' || field.name === 'end') ? _utcToDateTimeLocal(data[field.name]) : data[field.name];
				});
				if (data.rrule) {
					const freqNumMap = {0: 'yearly', 1: 'monthly', 2: 'weekly', 3: 'daily'};
					let freq = 'daily', interval = 1, until = '', count = 0;
					if (_RRule) {
						try {
							const opts = _RRule.parseString(data.rrule);
							freq = freqNumMap[opts.freq] ?? 'daily';
							interval = opts.interval ?? 1;
							if (opts.until) until = opts.until.toISOString().substring(0, 10);
							if (opts.count) count = opts.count;
						} catch (e) {}
					} else {
						const mFreq = data.rrule.match(/FREQ=(\w+)/);
						const mInt = data.rrule.match(/INTERVAL=(\d+)/);
						const mUntil = data.rrule.match(/UNTIL=(\d{8})/);
						const mCount = data.rrule.match(/COUNT=(\d+)/);
						if (mFreq) freq = mFreq[1].toLowerCase();
						if (mInt) interval = parseInt(mInt[1]);
						if (mUntil) { const s = mUntil[1]; until = `${s.slice(0,4)}-${s.slice(4,6)}-${s.slice(6,8)}`; }
						if (mCount) count = parseInt(mCount[1]);
					}
					const freqRadio = form.querySelector(`[name=rrule_freq][value=${freq}]`);
					if (freqRadio) freqRadio.checked = true;
					_rruleShowFreqSection(freq);
					form.querySelector('[name=rrule_interval]').value = interval;
					if (count >= 2) {
						form.querySelector('[name=rrule_end][value=count]').checked = true;
						form.querySelector('[name=rrule_count]').value = count;
					} else if (until) {
						form.querySelector('[name=rrule_end][value=until]').checked = true;
						form.querySelector('[name=rrule_until]').value = until;
					}
					bootstrap.Collapse.getOrCreateInstance(rruleCollapse).show();
				}
				else
					_rruleHide();
				document.getElementById('eventModalTitle').textContent = _('edit_event');
				document.getElementById('deleteEventBtn').disabled = (_calendarAuth < 3);
				await _alarmWidgetLoad('event', _event_id, _calendarAuth >= 3);
				_eventCalendarAlarmsLoad(_calendar_id);
				dom.getModal('event').show();
			});
			document.getElementById('addEventBtn').addEventListener('click', function () {
				_event_id = null;
				document.getElementById('eventForm').reset();
				_rruleHide();
				document.getElementById('rruleExpertSection').style.display = 'none';
				dom.enable('deleteEventBtn',false);
				_hideAlarmWidget('event');
				_eventCalendarAlarmsLoad(_calendar_id);
				dom.getModal('event').show();
			});
			document.getElementById('copyStartToEndBtn').addEventListener('click', function () {
				const form = document.getElementById('eventForm');
//				console.log(form.querySelector('input[name=start]').value);
//				form.querySelector('input[name=end]').value = _toDateTimeLocal(form.querySelector('input[name=start]').value);
				form.querySelector('input[name=end]').value = form.querySelector('input[name=start]').value;
			});
			document.getElementById('addOneHourBtn').addEventListener('click', function () {
				const input = document.getElementById('eventForm').querySelector('input[name=end]');
				if (! input.value)
					return;
				input.value = _utcToDateTimeLocal(input.value,3600);
			});
			document.getElementById('addThirtyMinBtn').addEventListener('click', function () {
				const input = document.getElementById('eventForm').querySelector('input[name=end]');
				if (! input.value)
					return;
				input.value = _utcToDateTimeLocal(input.value,1800);
			});
			document.getElementById('saveEventBtn').addEventListener('click', async function (event) {
				const data = {};
				document.getElementById('eventForm').querySelectorAll('[name]').forEach(field => {
					if (field.value && (field.name === 'start' || field.name === 'end'))
						data[field.name] = new Date(field.value).toISOString();
					else
						data[field.name] = field.value;
				});
//				console.log(data);
				const url = _event_id ? `/Event/update/${_event_id}` : `/Event/create/${_calendar_id}`;
				const response = await _request(url,
				{
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams(data)
				});
				if (response === null)
					return;
				_showCalendar();
				dom.getModal('event').hide();
			});
			document.getElementById('deleteEventBtn').addEventListener('click', async function (event) {
				if (! _event_id) return;
				const confirmed = await swal({
					title: _('confirm_delete_event'),
					icon: 'warning',
					buttons: [_('cancel'), _('delete')],
					dangerMode: true
				});
				if (! confirmed) return;
				const response = await _request(`/Event/delete/${_event_id}`);
				if (response === null)
					return;
				const ev = _calendar.getEventById(_event_id);
				if (ev) ev.remove();
				dom.getModal('event').hide();
			});

			// Recurrence form

			rruleCollapse.addEventListener('show.bs.collapse', () => rruleActive.value = '1');
			rruleCollapse.addEventListener('hide.bs.collapse', () => rruleActive.value = '0');
			rruleCollapse.addEventListener('change', _rruleSync);
			rruleCollapse.addEventListener('input', _rruleSync);

			const _RRule = window.RRule
				?? window.rrule?.RRule
				?? window.FullCalendarRRule?.RRule;

			const rrulePreviewBtn = document.getElementById('rrulePreviewBtn');
			const rrulePreviewDiv = document.createElement('div');
			rrulePreviewDiv.id = 'rrulePreview';
			document.body.appendChild(rrulePreviewDiv);

			rrulePreviewBtn.addEventListener('mouseenter', function () {
				if (!_RRule) return;
				const rruleStr = _rruleBuild();
				if (!rruleStr) return;
				const startVal = document.querySelector('[name=start]').value;
				const dtstart = startVal ? new Date(startVal) : new Date();
				let dates;
				try {
					const opts = _RRule.parseString(rruleStr);
					opts.dtstart = dtstart;
					dates = new _RRule(opts).all((d, i) => i < 11);
				} catch (err) { return; }
				if (!dates || !dates.length) return;
				rrulePreviewDiv.textContent = dates
					.map(d => d.toLocaleDateString(lang, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }))
					.join('\n');
				const r = rrulePreviewBtn.getBoundingClientRect();
				rrulePreviewDiv.style.left = (r.right + 10) + 'px';
				rrulePreviewDiv.style.top = r.top + 'px';
				rrulePreviewDiv.style.display = 'block';
			});
			rrulePreviewBtn.addEventListener('mouseleave', () => rrulePreviewDiv.style.display = 'none');

			const _rruleFreqSections = {
				daily: document.getElementById('eventsDaily'),
				weekly: document.getElementById('eventsWeekly'),
				monthly: document.getElementById('eventsMonthly'),
				yearly: document.getElementById('eventsYearly'),
			};

			function _rruleShowFreqSection(freq)
			{
				for (const [key, el] of Object.entries(_rruleFreqSections))
					el.style.display = (key === freq) ? 'block' : 'none';
			}

			document.querySelectorAll('[name=rrule_freq]').forEach(radio => {
				radio.addEventListener('change', () => { if (radio.checked) _rruleShowFreqSection(radio.value); });
			});

			const rruleExpertCheck = document.getElementById('rruleExpertCheck');
			const rruleExpertSection = document.getElementById('rruleExpertSection');
			rruleExpertCheck.addEventListener('change', () => {
				rruleExpertSection.style.display = rruleExpertCheck.checked ? 'block' : 'none';
			});

			// ICS import

			const icsDropZone = document.getElementById('icsDropZone');
			const icsFileInput = document.getElementById('icsFileInput');
			const icsFileName = document.getElementById('icsFileName');

			icsDropZone.addEventListener('click', () => icsFileInput.click());
			icsDropZone.addEventListener('dragover', e => { e.preventDefault(); icsDropZone.classList.add('drag-over'); });
			icsDropZone.addEventListener('dragleave', () => icsDropZone.classList.remove('drag-over'));
			icsDropZone.addEventListener('drop', e => {
				e.preventDefault();
				const file = e.dataTransfer.files[0];
				if (file) _icsSetFile(file);
			});
			icsFileInput.addEventListener('change', () => {
				if (icsFileInput.files[0]) _icsSetFile(icsFileInput.files[0]);
			});
			doImportIcsBtn.addEventListener('click', async function () {
				if (!_icsFile || _calendar_id === null) return;
				const text = await _icsFile.text();
				const response = await _request(`/Event/ics/${_calendar_id}`, {
					method: 'PUT',
					headers: { 'Content-Type': 'text/calendar' },
					body: text
				});
				if (response === null) return;
				const result = await response.json();
				_icsReset();
				dom.getModal('importIcs').hide();
				_showCalendar();
				swal({ title: _('import_ics'), text: _('ics_import_success').replace('%d', result.imported), icon: 'success' });
			});

			GlobalACL.Init();
			CalendarACL.Init(() => _calendar_id);

			// Alarms
			_initAlarms();

			_showCalendar();
			_checkConnection().then(connected => {
				if (! connected)
					_promptLogin();
				else
				{
					_getUserInformations();
					_refreshCalendarsList();
				}
			});
		});
	}

	construct.prototype.connect = async function()
	{
		_resetAskForLogin();
		const source = document.getElementById('loginSource').value;
		const user_id = document.getElementById('loginUserId').value;
		const password = document.getElementById('loginPassword').value;
		_loadingShow();
		const response = await fetch('/Authenticate/connect',
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ source, user_id, password })
		});
		_loadingHide();
		if (response.ok)
		{
			dom.getModal('login').hide();
			_getUserInformations();
			_refreshCalendarsList();
			return true;
		}
		const message = await response.json();
		swal({
			title: _('auth_failed'),
			text: message,
			icon: "error"
		})
		return false;
	}

	construct.prototype.disconnect = async function()
	{
		const response = await fetch('/User/disconnect');
		_globalsAuth = 0;
		_calendarAuth = 0;
		_user_infos = null;
		_toggleButtons();
		if (response.ok)
			location.reload();
		else
			swal({
				title: _('internal_error'),
				icon: "error"
			});
	}

// Bootstrap : config (lang + auth sources) is provided by index.php as JSON.
const _config = JSON.parse(document.getElementById('pfc-config').textContent);
new construct(_config.lang, _config.sources);
