// Alarms module : alarm definitions CRUD, reusable components management
// (display/email/sound/description/recipient) and calendar/event attachments.

import { request as _request } from './core/http.js';
import { el as _el } from './core/dom.js';
import { t as _ } from './core/i18n.js';

	// Parse an ICS duration (PnW / PnD / PTnH / PTnM) into {value, unit}
	function _parseDuration(str)
	{
		const matches = String(str ?? '').match(/^P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?)?$/);
		if (matches === null)
			return { value: 0, unit: 'M' };
		if (matches[1] !== undefined) return { value: Number(matches[1]), unit: 'W' };
		if (matches[2] !== undefined) return { value: Number(matches[2]), unit: 'D' };
		if (matches[3] !== undefined) return { value: Number(matches[3]), unit: 'H' };
		if (matches[4] !== undefined) return { value: Number(matches[4]), unit: 'M' };
		return { value: 0, unit: 'M' };
	}

	function _alarmTypeIcons(alarm)
	{
		const map = [['display_id','window','alarm_display'],['email_id','envelope','alarm_email'],['audio_id','volume-up','alarm_audio']];
		const icons = [];
		for (const [field, icon, key] of map)
			if (alarm[field] !== null && alarm[field] !== undefined)
				icons.push({ tag: 'i', attrs: { class: `bi bi-${icon} me-2`, title: _(key) } });
		if (icons.length === 0)
			return [{ tag: 'span', attrs: { class: 'text-muted' }, content: '—' }];
		return icons;
	}

	function _alarmTriggerText(alarm)
	{
		const units = { M: 'minutes', H: 'hours', D: 'days', W: 'weeks' };
		const trigger = String(alarm.trigger ?? '');
		const sign = trigger.startsWith('-') ? _('before') : _('after');
		const duration = _parseDuration(trigger.replace(/^[-+]/, ''));
		const related = alarm.related === 'end' ? _('event_end') : _('event_start');
		return `${duration.value} ${_(units[duration.unit])} ${sign} ${related}`;
	}

	function _alarmsShowList()
	{
		document.getElementById('alarmListArea').style.display = '';
		document.getElementById('alarmFormArea').style.display = 'none';
	}

	function _alarmsShowForm()
	{
		document.getElementById('alarmListArea').style.display = 'none';
		document.getElementById('alarmFormArea').style.display = '';
	}

	async function _alarmFetchJson(url)
	{
		const response = await _request(url);
		return response === null ? [] : await response.json();
	}

	// Fill a <select> with an optional empty option then the items (value = item[idKey]).
	function _alarmFillSelect(select, items, idKey, labelFn, emptyLabel = null)
	{
		select.innerHTML = '';
		if (emptyLabel !== null)
			select.appendChild(_el({ tag: 'option', attrs: { value: '' }, content: emptyLabel }));
		for (const item of items)
			select.appendChild(_el({ tag: 'option', attrs: { value: item[idKey] }, content: labelFn(item) }));
	}

	// Fill the alarm form selects (choice only) from the dedicated component controllers.
	async function _alarmLoadLists()
	{
		const [displays, emails, audios] = await Promise.all([
			_alarmFetchJson('/Display/list'),
			_alarmFetchJson('/Email/list'),
			_alarmFetchJson('/Sound/list')
		]);
		_alarmFillSelect(document.querySelector('#alarmForm [name=display_id]'), displays, 'display_id', d => d.label, _('alarm_none'));
		_alarmFillSelect(document.querySelector('#alarmForm [name=email_id]'), emails, 'email_id', e => `${e.label} (${e.summary})`, _('alarm_none'));
		_alarmFillSelect(document.querySelector('#alarmForm [name=audio_id]'), audios, 'audio_id', a => a.label, _('alarm_none'));
	}

	// -------------------------------------------------------------------------
	// Reusable components management (display / email / sound / description / recipient)
	// -------------------------------------------------------------------------

	const _COMPONENTS = {
		display:     { ctrl: 'Display', idKey: 'display_id', columns: ['label'],
		               selects: [{ name: 'description_id', list: '/Description/list', idKey: 'description_id', text: r => r.label }] },
		email:       { ctrl: 'Email', idKey: 'email_id', columns: ['label', 'summary'],
		               selects: [{ name: 'description_id', list: '/Description/list', idKey: 'description_id', text: r => r.label }] },
		sound:       { ctrl: 'Sound', idKey: 'audio_id', columns: ['label', 'mimetype'] },
		description: { ctrl: 'Description', idKey: 'description_id', columns: ['label'] },
		recipient:   { ctrl: 'Recipient', idKey: 'recipient_id', columns: ['address', 'name'] }
	};

	function _entForm(key)
	{
		return document.querySelector(`[data-ent-form="${key}"]`);
	}

	function _entShowList(key)
	{
		document.querySelector(`[data-ent-list="${key}"]`).style.display = '';
		_entForm(key).style.display = 'none';
	}

	function _entShowForm(key)
	{
		document.querySelector(`[data-ent-list="${key}"]`).style.display = 'none';
		_entForm(key).style.display = '';
	}

	async function _entLoad(key)
	{
		const cfg = _COMPONENTS[key];
		const items = await _alarmFetchJson(`/${cfg.ctrl}/list`);
		const tbody = document.getElementById(`${key}List`);
		tbody.innerHTML = '';
		if (items.length === 0)
		{
			tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}" class="text-center text-muted">${_('nothing_yet')}</td></tr>`;
			return;
		}
		for (const item of items)
		{
			const cells = cfg.columns.map(column => ({ tag: 'td', content: String(item[column] ?? '') }));
			cells.push({
				tag: 'td',
				attrs: { class: 'text-end' },
				content: [
					{ tag: 'button', attrs: { type: 'button', class: 'btn btn-sm btn-outline-primary me-1' }, content: { tag: 'i', attrs: { class: 'bi bi-pencil' } }, events: { click: function () {_entEdit(key, item[cfg.idKey])} } },
					{ tag: 'button', attrs: { type: 'button', class: 'btn btn-sm btn-outline-danger' }, content: { tag: 'i', attrs: { class: 'bi bi-trash' } }, events: { click: function () {_entDelete(key, item[cfg.idKey])} } }
				]
			});
			tbody.appendChild(_el({ tag: 'tr', content: cells }));
		}
	}

	// Fill the form's reference selects (description, recipients) from their controllers.
	async function _entLoadSelects(key)
	{
		const cfg = _COMPONENTS[key];
		if (! cfg.selects)
			return;
		const form = _entForm(key);
		for (const select of cfg.selects)
		{
			const items = await _alarmFetchJson(select.list);
			_alarmFillSelect(form.querySelector(`[name="${select.name}"]`), items, select.idKey, select.text, select.multiple ? null : _('choose'));
		}
	}

	// Email recipients widget : add/remove from existing recipients (hidden recipients[] inputs).
	let _emailRecipients = [];

	function _emailRecipientLabel(recipient)
	{
		return recipient.name ? `${recipient.name} <${recipient.address}>` : recipient.address;
	}

	function _emailRecipientChosenIds()
	{
		return Array.from(document.querySelectorAll('#emailRecipientList input[name="recipients[]"]')).map(input => input.value);
	}

	// Rebuild the picker with the recipients not already chosen.
	function _emailRecipientRefreshPicker()
	{
		const picker = document.getElementById('emailRecipientPicker');
		const chosen = new Set(_emailRecipientChosenIds());
		picker.innerHTML = '';
		picker.appendChild(_el({ tag: 'option', attrs: { value: '' }, content: _('choose') }));
		for (const recipient of _emailRecipients)
			if (! chosen.has(String(recipient.recipient_id)))
				picker.appendChild(_el({ tag: 'option', attrs: { value: recipient.recipient_id }, content: _emailRecipientLabel(recipient) }));
	}

	function _emailRecipientAddItem(recipient)
	{
		const item = _el({
			tag: 'li',
			attrs: { class: 'list-group-item d-flex justify-content-between align-items-center py-1' },
			content: [
				{ tag: 'span', content: _emailRecipientLabel(recipient) },
				{ tag: 'input', attrs: { type: 'hidden', name: 'recipients[]', value: recipient.recipient_id } }
			]
		});
		item.appendChild(_el({
			tag: 'button',
			attrs: { type: 'button', class: 'btn btn-sm btn-outline-danger py-0' },
			content: { tag: 'i', attrs: { class: 'bi bi-x' } },
			events: { click: function () { item.remove(); _emailRecipientRefreshPicker(); } }
		}));
		document.getElementById('emailRecipientList').appendChild(item);
	}

	function _emailRecipientPick()
	{
		const picker = document.getElementById('emailRecipientPicker');
		if (picker.value === '')
			return;
		const recipient = _emailRecipients.find(r => String(r.recipient_id) === picker.value);
		if (recipient)
		{
			_emailRecipientAddItem(recipient);
			_emailRecipientRefreshPicker();
		}
	}

	async function _emailRecipientsSetup(selectedIds)
	{
		_emailRecipients = await _alarmFetchJson('/Recipient/list');
		document.getElementById('emailRecipientList').innerHTML = '';
		for (const id of (selectedIds ?? []).map(String))
		{
			const recipient = _emailRecipients.find(r => String(r.recipient_id) === id);
			if (recipient)
				_emailRecipientAddItem(recipient);
		}
		_emailRecipientRefreshPicker();
	}

	async function _entNew(key)
	{
		const form = _entForm(key);
		form.reset();
		delete form.dataset.entId;
		await _entLoadSelects(key);
		if (key === 'email')
			await _emailRecipientsSetup([]);
		_entShowForm(key);
	}

	async function _entEdit(key, id)
	{
		const cfg = _COMPONENTS[key];
		const form = _entForm(key);
		form.reset();
		form.dataset.entId = id;
		await _entLoadSelects(key);
		const data = await _alarmFetchJson(`/${cfg.ctrl}/read/${id}`);
		form.querySelectorAll('[name]').forEach(function (field) {
			if (field.type === 'file' || field.name === 'recipients[]')
				return;
			if (data[field.name] !== undefined && data[field.name] !== null)
				field.value = data[field.name];
		});
		if (key === 'email')
			await _emailRecipientsSetup(data.recipients);
		_entShowForm(key);
	}

	async function _entSave(key)
	{
		const cfg = _COMPONENTS[key];
		const form = _entForm(key);
		const id = form.dataset.entId;
		const url = id ? `/${cfg.ctrl}/update/${id}` : `/${cfg.ctrl}/create`;
		const response = await _request(url, { method: 'POST', body: new FormData(form) });
		if (response === null)
			return;
		_entShowList(key);
		_entLoad(key);
	}

	async function _entDelete(key, id)
	{
		const confirmed = await swal({
			title: _('confirm_delete_component'),
			icon: 'warning',
			buttons: [_('cancel'), _('delete')],
			dangerMode: true
		});
		if (! confirmed)
			return;
		const response = await _request(`/${_COMPONENTS[key].ctrl}/delete/${id}`);
		if (response === null)
			return;
		_entLoad(key);
	}

	async function _alarmsLoad()
	{
		const response = await _request('/Alarm/list');
		if (response === null)
			return;
		const alarms = await response.json();
		const tbody = document.getElementById('alarmsList');
		tbody.innerHTML = '';
		if (alarms.length === 0)
		{
			tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">${_('no_alarm')}</td></tr>`;
			return;
		}
		for (const alarm of alarms)
			tbody.appendChild(_el({
				tag: 'tr',
				content: [
					{ tag: 'td', content: alarm.label ?? '' },
					{ tag: 'td', content: _alarmTypeIcons(alarm) },
					{ tag: 'td', content: _alarmTriggerText(alarm) },
					{
						tag: 'td',
						attrs: { class: 'text-end' },
						content: [
							{
								tag: 'button',
								attrs: { class: 'btn btn-sm btn-outline-primary me-1' },
								content: { tag: 'i', attrs: { class: 'bi bi-pencil' } },
								events: { click: function () {_alarmEdit(alarm.alarm_id)} }
							},
							{
								tag: 'button',
								attrs: { class: 'btn btn-sm btn-outline-danger' },
								content: { tag: 'i', attrs: { class: 'bi bi-trash' } },
								events: { click: function () {_alarmDelete(alarm.alarm_id)} }
							}
						]
					}
				]
			}));
	}

	async function _alarmNew()
	{
		const form = document.getElementById('alarmForm');
		form.reset();
		delete form.dataset.editId;
		await _alarmLoadLists();
		_alarmsShowForm();
	}

	async function _alarmEdit(alarm_id)
	{
		const response = await _request(`/Alarm/read/${alarm_id}`);
		if (response === null)
			return;
		const alarm = await response.json();
		const form = document.getElementById('alarmForm');
		form.reset();
		form.dataset.editId = alarm_id;
		form.querySelector('[name=label]').value = alarm.label ?? '';
		const trigger = String(alarm.trigger ?? '');
		const parsed = _parseDuration(trigger.replace(/^[-+]/, ''));
		form.querySelector('[name=trigger_value]').value = parsed.value;
		form.querySelector('[name=trigger_unit]').value = parsed.unit;
		form.querySelector('[name=trigger_sign]').value = trigger.startsWith('-') ? '-' : '+';
		form.querySelector('[name=related]').value = alarm.related === 'end' ? 'end' : 'start';
		form.querySelector('[name=repeat]').value = alarm.repeat ?? 0;
		if (alarm.duration)
		{
			const duration = _parseDuration(alarm.duration);
			form.querySelector('[name=duration_value]').value = duration.value;
			form.querySelector('[name=duration_unit]').value = duration.unit;
		}
		await _alarmLoadLists();
		// pré-sélection des composants réutilisables existants
		if (alarm.display_id != null)
			form.querySelector('[name=display_id]').value = alarm.display_id;
		if (alarm.email_id != null)
			form.querySelector('[name=email_id]').value = alarm.email_id;
		if (alarm.audio_id != null)
			form.querySelector('[name=audio_id]').value = alarm.audio_id;
		_alarmsShowForm();
	}

	async function _alarmDelete(alarm_id)
	{
		const confirmed = await swal({
			title: _('confirm_delete_alarm'),
			icon: 'warning',
			buttons: [_('cancel'), _('delete')],
			dangerMode: true
		});
		if (! confirmed)
			return;
		const response = await _request(`/Alarm/delete/${alarm_id}`);
		if (response === null)
			return;
		_alarmsLoad();
	}

	async function _alarmSave()
	{
		const form = document.getElementById('alarmForm');
		const data = new FormData(form);
		const alarm_id = form.dataset.editId;
		const url = alarm_id ? `/Alarm/update/${alarm_id}` : '/Alarm/create';
		const response = await _request(url, { method: 'POST', body: data });
		if (response === null)
			return;
		_alarmsShowList();
		_alarmsLoad();
	}

	// Attachment widget (same UI as the email recipients picker) for calendar/event
	// default alarms. Add/remove persist immediately via /Alarm/{attach,detach}<kind>.
	const _ALARM_WIDGETS = {
		calendar: { kind: 'Calendar', widget: 'calendarAlarmsWidget', picker: 'calendarAlarmPicker', add: 'calendarAlarmAdd', list: 'calendarAlarmList' },
		event:    { kind: 'Event', widget: 'eventAlarmsWidget', hint: 'eventAlarmsHint', picker: 'eventAlarmPicker', add: 'eventAlarmAdd', list: 'eventAlarmList' }
	};
	// per-scope state : { ownerId, canEdit, all: [...alarms] }
	const _alarmWidgetState = {};

	function _alarmWidgetChosenIds(scope)
	{
		return Array.from(document.querySelectorAll(`#${_ALARM_WIDGETS[scope].list} li[data-alarm-id]`)).map(li => li.dataset.alarmId);
	}

	function _alarmWidgetRefreshPicker(scope)
	{
		const cfg = _ALARM_WIDGETS[scope];
		const picker = document.getElementById(cfg.picker);
		const chosen = new Set(_alarmWidgetChosenIds(scope));
		picker.innerHTML = '';
		picker.appendChild(_el({ tag: 'option', attrs: { value: '' }, content: _('choose') }));
		for (const alarm of _alarmWidgetState[scope].all)
			if (! chosen.has(String(alarm.alarm_id)))
				picker.appendChild(_el({ tag: 'option', attrs: { value: alarm.alarm_id }, content: alarm.label ?? '' }));
	}

	function _alarmWidgetAddItem(scope, alarm)
	{
		const cfg = _ALARM_WIDGETS[scope];
		const item = _el({
			tag: 'li',
			attrs: { class: 'list-group-item d-flex justify-content-between align-items-center py-1', 'data-alarm-id': alarm.alarm_id },
			content: [{ tag: 'span', content: alarm.label ?? '' }]
		});
		if (_alarmWidgetState[scope].canEdit)
			item.appendChild(_el({
				tag: 'button',
				attrs: { type: 'button', class: 'btn btn-outline-danger py-0' },
				content: { tag: 'i', attrs: { class: 'bi bi-trash-fill' } },
				events: { click: async function () {
					const state = _alarmWidgetState[scope];
					const response = await _request(`/Alarm/detach${cfg.kind}/${state.ownerId}/${alarm.alarm_id}`, { method: 'POST' });
					if (response !== null)
					{
						item.remove();
						_alarmWidgetRefreshPicker(scope);
					}
				} }
			}));
		document.getElementById(cfg.list).appendChild(item);
	}

	async function _alarmWidgetPick(scope)
	{
		const cfg = _ALARM_WIDGETS[scope];
		const state = _alarmWidgetState[scope];
		const picker = document.getElementById(cfg.picker);
		if (picker.value === '' || state === undefined)
			return;
		const alarm = state.all.find(a => String(a.alarm_id) === picker.value);
		if (alarm === undefined)
			return;
		const response = await _request(`/Alarm/attach${cfg.kind}/${state.ownerId}/${alarm.alarm_id}`, { method: 'POST' });
		if (response !== null)
		{
			_alarmWidgetAddItem(scope, alarm);
			_alarmWidgetRefreshPicker(scope);
		}
	}

	// Show the widget for an owner and load its attached alarms. canEdit toggles the picker/remove.
	async function _alarmWidgetLoad(scope, ownerId, canEdit)
	{
		const cfg = _ALARM_WIDGETS[scope];
		document.getElementById(cfg.widget).style.display = '';
		if (cfg.hint)
			document.getElementById(cfg.hint).style.display = 'none';
		const [all, attached] = await Promise.all([
			_alarmFetchJson('/Alarm/list'),
			_alarmFetchJson(`/Alarm/for${cfg.kind}/${ownerId}`)
		]);
		_alarmWidgetState[scope] = { ownerId, canEdit, all };
		document.getElementById(cfg.list).innerHTML = '';
		const attachedIds = new Set(attached.map(a => String(a.alarm_id)));
		for (const alarm of all)
			if (attachedIds.has(String(alarm.alarm_id)))
				_alarmWidgetAddItem(scope, alarm);
		document.getElementById(cfg.picker).style.display = canEdit ? '' : 'none';
		document.getElementById(cfg.add).style.display = canEdit ? '' : 'none';
		_alarmWidgetRefreshPicker(scope);
	}

	// Hide the widget (e.g. new calendar/event without id) ; show its hint if any.
	function _hideAlarmWidget(scope)
	{
		const cfg = _ALARM_WIDGETS[scope];
		document.getElementById(cfg.widget).style.display = 'none';
		if (cfg.hint)
			document.getElementById(cfg.hint).style.display = '';
	}

	// Read-only list of the calendar's default alarms shown in the event modal (info only).
	async function _eventCalendarAlarmsLoad(calendarId)
	{
		const section = document.getElementById('eventCalendarAlarms');
		const list = document.getElementById('eventCalendarAlarmsList');
		list.innerHTML = '';
		const alarms = await _alarmFetchJson(`/Alarm/forCalendar/${calendarId}`);
		if (alarms.length === 0)
		{
			section.style.display = 'none';
			return;
		}
		for (const alarm of alarms)
			list.appendChild(_el({ tag: 'li', attrs: { class: 'list-group-item py-1 text-body-secondary' }, content: alarm.label ?? '' }));
		section.style.display = '';
	}

	// Wire up the alarms modal (definitions list/form + the 5 component managers).
	export function initAlarms()
	{
		document.getElementById('alarmsModal').addEventListener('show.bs.modal', function () {
			_alarmsShowList();
			_alarmsLoad();
			for (const key in _COMPONENTS)
			{
				_entShowList(key);
				_entLoad(key);
			}
		});
		document.getElementById('newAlarmBtn').addEventListener('click', _alarmNew);
		document.getElementById('alarmBackBtn').addEventListener('click', _alarmsShowList);
		document.getElementById('alarmForm').addEventListener('submit', function (event) {
			event.preventDefault();
			_alarmSave();
		});
		document.getElementById('emailRecipientAdd').addEventListener('click', _emailRecipientPick);
		for (const key in _COMPONENTS)
		{
			document.querySelector(`[data-ent-new="${key}"]`).addEventListener('click', function () { _entNew(key); });
			document.querySelector(`[data-ent-back="${key}"]`).addEventListener('click', function () { _entShowList(key); });
			_entForm(key).addEventListener('submit', function (event) { event.preventDefault(); _entSave(key); });
		}
		for (const scope in _ALARM_WIDGETS)
			document.getElementById(_ALARM_WIDGETS[scope].add).addEventListener('click', function () { _alarmWidgetPick(scope); });
	}

	export { _alarmWidgetLoad as alarmWidgetLoad, _hideAlarmWidget as hideAlarmWidget, _eventCalendarAlarmsLoad as eventCalendarAlarmsLoad };
