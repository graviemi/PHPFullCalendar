// Global ACL management : the globalAcl modal (special + regular rights as bitmask checkboxes).

import { request as _request } from './core/http.js';
import { el, getModal } from './core/dom.js';
import { t as _ } from './core/i18n.js';

const Icons = {
	val1: ['calendar-plus','gr_cal_create'],
	val2: ['calendar-x','gr_cal_destroy'],
	val4: ['box-fill','gr_res_add'],
	val8: ['box','gr_res_del'],
	val16: ['person','gr_acl'],
	val32: ['bell','gr_alarm']
};

function Check(acl,changeHandler)
{
	acl = Number(acl);
	const children = [];
	for (let b = 1; b <= 32; b *= 2)
	{
		let key = `val${b}`;
		children.push({
			tag: 'label',
			content: [
				{
					tag: 'i',
					attrs: {
						class: `bi bi-${Icons[key][0]}`,
						title: _(Icons[key][1])
					}
				},
				{
					tag: 'input',
					attrs: {
						type: 'checkbox',
						value: b,
						checked: (acl & b) === b
					},
					events: {
						change: changeHandler
					}
				}
			]
		});
	}
	return el({
		tag: 'div',
		attrs: {
			class: 'acl_checkboxes'
		},
		content: children
	});
}

async function Save(data)
{
	await _request('/Authorization/globalacl', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams(data)
	});
	Load();
}

async function Delete(key)
{
	const confirmed = await swal({
		title: _('confirm_delete_acl'),
		icon: 'warning',
		buttons: [_('cancel'), _('delete')],
		dangerMode: true
	});
	if (! confirmed) return;
	await _request('/Authorization/globalacl', {
		method: 'DELETE',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams(key)
	});
	Load();
}

function _getAuthorization(element)
{
	const checkboxes = element.querySelectorAll('input');
	let level = 0;
	checkboxes.forEach((checkbox) => {
		if (checkbox.checked)
			level += Number(checkbox.value);
	});
	return level;
}

async function Update(event)
{
	const tr = event.target.closest('tr');
	const data = tr.dataset;
	data.authorization = _getAuthorization(tr);
	Save(data);
}

async function Load()
{
	const resp = await _request('/Authorization/globalacl');
	if (resp === null)
		return false;
	const acls = await resp.json();
	const special = acls.filter((acl) => {return acl.type === 'special'});
	const specialTbody = document.getElementById('globalACLSpecial');
	specialTbody.innerHTML = '';
	for (const acl of special)
	{
		specialTbody.appendChild(el({
			tag: 'tr',
			attrs: {
				'data-source': acl.source,
				'data-identifier': acl.identifier,
				'data-type': acl.type
			},
			content: [
				{
					tag: 'td',
					content: `${acl.identifier} (${(acl.source === '')?_('all_sources'):acl.source})`
				},
				{
					tag: 'td',
					content: Check(acl.authorization,function (event) {Update(event)})
				},
				{
					tag: 'td',
					content: {
						tag: 'button',
						attrs: {
							class: 'btn btn-sm btn-outline-danger'
						},
						content: {
							tag: 'i',
							attrs: {
								class: 'bi bi-trash'
							}
						},
						events: {
							click: function (event) {Delete(event.target.closest('tr').dataset)}
						}
					}
				}
			]
		}));
	}
	const regular = acls.filter(a => a.type !== 'special');
	const tbody = document.getElementById('globalAclList');
	tbody.innerHTML = '';
	if (regular.length === 0)
		tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${_('no_acl')}</td></tr>`;
	else
	{
		for (const acl of regular)
		{
			tbody.appendChild(el({
				tag: 'tr',
				attrs: {
					'data-source': acl.source,
					'data-identifier': acl.identifier,
					'data-type': acl.type
				},
				content: [
					{
						tag: 'td',
						content: acl.source
					},
					{
						tag: 'td',
						content: acl.identifier
					},
					{
						tag: 'td',
						content: _('type_' + acl.type)
					},
					{
						tag: 'td',
						content: Check(acl.authorization,function (event) {Update(event)})
					},
					{
						tag: 'td',
						content: {
							tag: 'button',
							attrs: {
								class: 'btn btn-sm btn-outline-danger'
							},
							content: {
								tag: 'i',
								attrs: {
									class: 'bi bi-trash'
								}
							},
							events: {
								click: function (event) {Delete(event.target.closest('tr').dataset)}
							}
						}
					}
				]
			}));
		}
	}
	return true;
}

// Wire up the global ACL modal (open button + the two "add" rows).
export function Init()
{
	document.getElementById('manageGlobalAclBtn').addEventListener('click', async function () {
		const success = await Load();
		if (success)
			getModal('globalAcl').show();
	});
	document.getElementById('globalAclAddSpecial').addEventListener('click', function (event) {
		const tr = event.target.closest('tr')
		const matches = tr.querySelector('[name=user_type]').value.match(/^([^:]*):(.*)$/);
		const data = {
			source: matches[1],
			identifier: matches[2],
			type: 'special',
			authorization: _getAuthorization(tr.querySelector('.acl_checkboxes'))
		};
		Save(data);
	});
	document.getElementById('globalAclAddRegular').addEventListener('click', function (event) {
		const tr = event.target.closest('tr')
		const fields = tr.querySelectorAll('[name]');
		const data = {};
		fields.forEach((field) => {data[field.name] = field.value});
		data['authorization'] = _getAuthorization(tr.querySelector('.acl_checkboxes'));
		Save(data);
	});
}
