// Calendar ACL management : the calendarAcl modal (per-calendar access levels).

import { request as _request } from './core/http.js';
import { el, getModal } from './core/dom.js';
import { t as _ } from './core/i18n.js';

let Id = () => null;

// Access levels [value, label] ; rebuilt from translations on demand.
function _levels()
{
	return [
		[0,_('no_access')],
		[1,_('level_free_busy')],
		[2,_('level_read')],
		[3,_('level_write')],
		[4,_('level_acl')]
	];
}

function _selectElement({options, value, onchange})
{
	return el({
		tag: 'select',
		attrs: {
			name: 'level',
			class: 'form-select form-select-sm'
		},
		content: options.map((option) => {
			return {
				tag: 'option',
				attrs: {
					value: option[0],
					selected: option[0] === value
				},
				content: option[1]
			}
		}),
		events: {change: onchange}
	});
}

async function Save(data)
{
	await _request(`/Authorization/calendaracl/${Id()}`,
	{
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams(data)
	});
	Load();
}

async function Update(event)
{
	const tr = event.target.closest('tr');
	const data = tr.dataset;
	data.authorization = tr.querySelector('[name=level]').value;
	Save(data);
}

async function Delete(key)
{
	const confirmed = await swal({
		title: _('confirm_delete_acl'),
		icon: 'warning',
		buttons: [_('cancel'), _('delete')],
		dangerMode: true
	});
	if (! confirmed)
		return;
	await _request(`/Authorization/calendaracl/${Id()}`,
	{
		method: 'DELETE',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams(key)
	});
	Load();
}

async function Load()
{
	const resp = await _request(`/Authorization/calendaracl/${Id()}`);
	if (resp === null)
		return false;
	const acls = await resp.json();
	const special = acls.filter((acl) => {return acl.type === 'special'});
	const tbodySpecial = document.getElementById('calendarSpecialAclList');
	tbodySpecial.innerHTML = '';
	if (special.length === 0)
		tbodySpecial.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${_('no_acl')}</td></tr>`;
	else
	{
		for (const acl of special)
		{
			const tr = el({
				tag: 'tr',
				attrs: {
					'data-source': acl.source,
					'data-identifier': acl.identifier,
					'data-type': acl.type
				},
				content: [
					{ tag: 'td', content: (acl.identifier === 'anonymous')?_('anonymous'):`${_('connected')} (${(acl.source === '')?_('all'):acl.source})`},
					{
						tag: 'td',
						content: _selectElement({
							options: _levels(),
							value: acl.authorization,
							onchange: function (event) { Update(event) }
						})
					},
					{
						tag: 'td',
						content: {
							tag: 'button',
							attrs: {class: 'btn btn-sm btn-outline-danger'},
							content: [
								{tag: 'i', attrs: { class: 'bi bi-trash' } }
							],
							events: {
								click: async function(event) { Delete(event.target.closest('tr').dataset) }
							}
						}
					}
				]
			});
			tbodySpecial.appendChild(tr);
		}
	}
	const regular = acls.filter((acl) => {return acl.type !== 'special'});
	const tbody = document.getElementById('calendarAclList');
	tbody.innerHTML = '';
	if (regular.length === 0)
	{
		tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${_('no_acl')}</td></tr>`;
		return true;
	}
	for (const acl of regular)
	{
		const tr = el({
			tag: 'tr',
			attrs: {
				'data-source': acl.source,
				'data-identifier': acl.identifier,
				'data-type': acl.type
			},
			content: [
				{ tag: 'td', content: acl.source },
				{ tag: 'td', content: acl.identifier},
				{ tag: 'td', content: _('type_' + acl.type)},
				{
					tag: 'td',
					content: _selectElement({
						options: _levels().slice(1),
						value: acl.authorization,
						onchange: function (event) { Update(event) }
					})
				},
				{
					tag: 'td',
					content: {
						tag: 'button',
						attrs: {class: 'btn btn-sm btn-outline-danger'},
						content: {tag: 'i', attrs: { class: 'bi bi-trash' } },
						events: {
							click: async function(event) { Delete(event.target.closest('tr').dataset) }
						}
					}
				}
			]
		});
		tbody.appendChild(tr);
	}
	return true;
}

// Wire up the calendar ACL modal. getCalendarId returns the current calendar id.
export function Init(getCalendarId)
{
	Id = getCalendarId;
	document.getElementById('manageCalendarAclBtn').addEventListener('click', async function () {
		const success = await Load();
		if (success)
			getModal('calendarAcl').show();
	});
	document.getElementById('calendarAclAddSpecial').addEventListener('click', function (event) {
		const tr = event.target.closest('tr');
		const matches = tr.querySelector('[name=user_type]').value.match(/^([^:]*):(.*)$/);
		const data = {
			source: matches[1],
			identifier: matches[2],
			type: 'special',
			authorization: tr.querySelector('[name=authorization]').value
		};
		Save(data);
	});
	document.getElementById('calendarAclAddRegular').addEventListener('click', function (event) {
		const tr = event.target.closest('tr');
		const fields = tr.querySelectorAll('[name]');
		const data = {};
		fields.forEach((field) => {data[field.name] = field.value});
		Save(data);
	});
}
