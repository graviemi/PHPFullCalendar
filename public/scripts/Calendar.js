// Calendars management : sidebar list, selection and calendar CRUD (calendar + info modals).
// Owns the selected calendar state, exposed through Id() and Auth().

import { request as _request } from './core/http.js';
import { enable, getModal } from './core/dom.js';
import { t as _ } from './core/i18n.js';
import { alarmWidgetLoad, hideAlarmWidget } from './alarms.js';

let _id = null; // selected calendar unique database id
let _auth = 0; // connected user authorizations on the selected calendar
let FullCal = () => null; // getter for the FullCalendar.js instance
let GlobalsAuth = () => 0; // getter for the connected user global authorizations

// currently selected calendar id (null when none)
export function Id()
{
	return _id;
}

// connected user authorization level on the selected calendar
export function Auth()
{
	return _auth;
}

// (re)build the sidebar calendars list
export async function Refresh()
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
		input.checked = (_id === cal.calendar_id);
		const label = document.createElement('label');
		label.className = 'calendar-item';
		label.htmlFor = `cal_${cal.calendar_id}`;
		label.textContent = cal.name;
		label.title = cal.description;
		label.addEventListener('click', function ()
		{
			_id = cal.calendar_id;
			Show();
		});
		list.appendChild(input);
		list.appendChild(label);
	}
}

// display the selected calendar : fetch its authorization, toggle the toolbar, load the event source
export async function Show()
{
	const calendar = document.getElementById('calendar');
	const no_calendar = document.getElementById('no_calendar');
	if (_id === null)
	{
		_auth = 0;
		enable('addEventBtn', false);
		enable('importICSBtn', false);
		enable('calendarInfoBtn', false);
		enable('editCalendarBtn', false);
		enable('manageCalendarAclBtn', false);
		calendar.setAttribute('data-hidden', 'true');
		no_calendar.removeAttribute('data-hidden');
		enable('removeCalendarBtn',false);
		return;
	}
	const response = await _request(`/Authorization/calendar/${_id}`);
	_auth = response !== null ? await response.json() : 0;
	enable('addEventBtn', _auth >= 3);
	enable('importICSBtn', _auth >= 3);
	enable('calendarInfoBtn', _auth >= 1);
	enable('editCalendarBtn', _auth >= 3);
	enable('manageCalendarAclBtn', _auth >= 4);
	enable('removeCalendarBtn', (_auth >= 4) && (GlobalsAuth() & 2) === 2);
	calendar.removeAttribute('data-hidden');
	no_calendar.setAttribute('data-hidden', 'true');
	FullCal().removeAllEventSources();
	FullCal().addEventSource({ url: `/Event/list/${_id}` });
	FullCal().render();
}

// Wire up the calendar CRUD buttons and their modals.
export function Init(getFullCalendar, getGlobalsAuth)
{
	FullCal = getFullCalendar;
	GlobalsAuth = getGlobalsAuth;
	document.getElementById('addCalendarBtn').addEventListener('click', function () {
		_id = null;
		document.getElementById('calendarForm').reset();
		hideAlarmWidget('calendar');
		getModal('calendar').show();
	});
	document.getElementById('calendarInfoBtn').addEventListener('click', async function () {
		if (_id === null)
			return;
		const response = await _request(`/Calendar/read/${_id}`);
		if (response === null)
			return;
		const cal = await response.json();
		document.getElementById('infoCalendarName').textContent = cal.name;
		document.getElementById('infoCalendarDescription').textContent = cal.description ?? '';
		const icsUrl = `${window.location.origin}/$/Event/ics/${_id}`;
		const link = document.getElementById('infoIcsLink');
		link.href = icsUrl;
		link.textContent = icsUrl;
		const fcUrl = `${window.location.origin}/$/Event/list/${_id}`;
		const fcLink = document.getElementById('infoFcLink');
		fcLink.href = fcUrl;
		fcLink.textContent = fcUrl;
		getModal('info').show();
	});
	document.getElementById('editCalendarBtn').addEventListener('click', async function () {
		if (_id === null)
			return;
		const response = await _request(`/Calendar/read/${_id}`);
		if (response === null)
			return;
		const data = await response.json();
		const form = document.getElementById('calendarForm');
		form.reset();
		form.querySelectorAll('[name]').forEach(field => field.value = data[field.name]);
		await alarmWidgetLoad('calendar', _id, _auth >= 4);
		getModal('calendar').show();
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
		const response = await _request(`/Calendar/delete/${_id}`);
		if (response === null)
			return;
		_id = null;
		await Refresh();
		Show();
	});
	document.getElementById('saveCalendarBtn').addEventListener('click', async function (event) {
		const fields = event.target.closest('div .modal-content').querySelectorAll('[name]');
		const data = {};
		for (const field of fields)
			data[field.name] = field.value;
		const url = _id ? `/Calendar/update/${_id}` : '/Calendar/create';
		const response = await _request(url,
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		if (response === null)
			return;
		if (_id === null)
		{
			_id = await response.json();
			Show();
		}
		await Refresh();
		getModal('calendar').hide();
	});
}
