
PHPFullCalendar = (function () 
{
	var _translations;
	var _modals = {};
	var _calendar;
	var _globalsAuth = 0;
	var _calendarAuth = 0;
	var _calendar_id = null;
	var _user_infos;
	var _ask_for_login = false;
	var _sources = null;
	var _levels;
	var _globalPermissions;
	const _globalACLIcons = {
		val1: ['calendar-plus','gr_cal_create'],
		val2: ['calendar-x','gr_cal_destroy'],
		val4: ['box-fill','gr_res_add'],
		val8: ['box','gr_res_del'],
		val16: ['person','gr_acl']
	};

	function _(text_key)
	{
		return _translations[text_key] ?? text_key;
	}

	async function _request(url, options = {})
	{
		const response = await fetch(url, options);
		if (response.status === 400)
		{
			const data = await response.json();
			swal({
				title: _('error'),
				text: _(data['message'] ?? _('unknown_error')),
				icon: 'error'
			});
			return null;
		}
		else if (response.status === 401)
		{
			if (! _ask_for_login)
			{
				_ask_for_login = true;
				_getModal('login').show();
			}
			return null;
		}
		if (response.ok)
			return response;
		return null;
	}

	function _checkConnection()
	{
		return fetch('/Authenticate/check').then(response =>
			response.ok ? response.json() : false
		);
	}

	function _enable(button_id, enabled)
	{
		document.getElementById(button_id).disabled = (! enabled);
	}

	function _toggleButtons()
	{
		_enable('addCalendarBtn',(_globalsAuth & 1) === 1);
		_enable('manageGlobalAclBtn',((_globalsAuth & 16) === 16));
	}

	function _getUserInformations()
	{
		_request('/User/Informations', {}).then(response => {
			if (response === null || ! response.ok)
				return;
			response.json().then(infos => {
				_user_infos = infos;
				document.getElementById('userNameLabel').innerText = _user_infos['informations']['name'] ?? _('anonymous');
			});
		});
		_request('/Authorization/global', {}).then(response => {
			if (response === null || ! response.ok)
				return;
			response.json().then(auth => {
				_globalsAuth = auth;
				_toggleButtons();
			});
		});
	}

	function _getModal(name)
	{
		const element = document.getElementById(`${name}Modal`);
		if (element === null)
			return null;
		if (! _modals.hasOwnProperty(name))
			_modals[name] = new bootstrap.Modal(element);
		return _modals[name];
	}

	async function _refreshCalendarsList()
	{
		const response = await _request('/Calendar/catalog');
		if (response === null || ! response.ok)
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
			_enable('addEventBtn', false);
			_enable('editCalendarBtn', false);
			_enable('manageCalendarAclBtn', false);
			calendar.setAttribute('data-hidden', 'true');
			no_calendar.removeAttribute('data-hidden');
			_enable('removeCalendarBtn',false);
			return;
		}
		const response = await _request(`/Authorization/calendar/${_calendar_id}`);
		_calendarAuth = (response !== null && response.ok) ? await response.json() : 0;
		_enable('addEventBtn', _calendarAuth >= 3);
		_enable('editCalendarBtn', _calendarAuth >= 3);
		_enable('manageCalendarAclBtn', _calendarAuth >= 4);
		_enable('removeCalendarBtn',true);
		calendar.removeAttribute('data-hidden');
		no_calendar.setAttribute('data-hidden', 'true');
		_calendar.removeAllEventSources();
		_calendar.addEventSource({ url: `/Event/events/${_calendar_id}` });
		_calendar.render();
	}

	function _moveTooltip(e)
	{
		const tooltip = document.getElementById('pfc-tooltip');
		tooltip.style.left = (e.clientX + 12) + 'px';
		tooltip.style.top  = (e.clientY + 12) + 'px';
	}

	function _toDateTimeLocal(str)
	{
		if (! str)
			return '';
		if (/^\d{4}-\d{2}-\d{2}$/.test(str))
			return str + 'T00:00';
		return str.substring(0, 16);
	}

	// Escape HTML to safely insert user-controlled content via innerHTML
	function _h(str)
	{
		return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function _el({tag, content = '', attrs = {}, events = {}})
	{
		const el = document.createElement(tag);
		for (const [k, v] of Object.entries(attrs))
		{
			if (typeof v === 'boolean')
			{
				if (v) el.toggleAttribute(k);
			}
			else
				el.setAttribute(k, v);
		}
		if (typeof content === 'string')
			el.innerText = content;
		else if (content instanceof Element)
			el.appendChild(content);
		else if (Array.isArray(content))
			for (const child of content)
				el.appendChild(child instanceof Element ? child : _el(child));
		else
			el.appendChild(_el(content));
		for (const [event, handler] of Object.entries(events))
			el.addEventListener(event, handler);
		return el;
	}

	// Given a full DB identifier and the known sources, return {source, identifier}
	function _splitIdentifier(full, type)
	{
		if (type === 'special' || _sources === null)
			return { source: '', identifier: full };
		for (const [source] of _sources)
		{
			if (source !== '' && full.startsWith(source))
				return { source, identifier: full.slice(source.length) };
		}
		return { source: '', identifier: full };
	}

	function _buildSpecialLevelSelect(current)
	{
		const select = document.createElement('select');
		select.className = 'form-select form-select-sm';
		for (const [val, key] of [[0,'level_forbidden'],[1,'level_free_busy'],[2,'level_read'],[3,'level_write'],[4,'level_acl']])
		{
			const opt = document.createElement('option');
			opt.value = val;
			opt.textContent = _(key);
			if (val === current)
				opt.selected = true;
			select.appendChild(opt);
		}
		return select;
	}

	function _selectElement({options, attrs = {}})
	{
		return _el({
			tag: 'select',
			attrs: attrs,
			content: options.map((option) => {
				return {
					tag: 'option',
					attrs: { value: option[0] },
					content: option[1]
				}
			})
		});
	}

	async function _addSpecialCalendarAcl(element)
	{
	}

	async function _addCalendarAcl(element)
	{
		const body = {};
		const fields = element.querySelectorAll('[name]');
		fields.forEach((field) => {body[field.name] = field.value});
		if (body['identifier'] === '')
		{
			return;
		}
		const resp = await _request(`/Authorization/calendaracl/${_calendar_id}`,
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(body)
		});
		if (resp && resp.ok)
			_loadCalendarAcl();
		else
			swal({ title: _('error'), icon: 'error' });
	}

	async function _removeCalendarACL(source, identifier, type)
	{
		const confirmed = await swal({
			title: _('confirm_delete_acl'),
			icon: 'warning',
			buttons: [_('cancel'), _('delete')],
			dangerMode: true
		});
		if (! confirmed)
			return;
		const response = await _request(`/Authorization/calendaracl/${_calendar_id}`,
		{
			method: 'DELETE',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ source: source, identifier: identifier, type: type })
		});
		if (response && response.ok)
			_loadCalendarAcl();
		else
			swal({ title: _('error'), icon: 'error' });
	}

	function _appendCalendarAclFields(container, list, handler)
	{
		for (element of list)
		{
			container.appendChild(_el({
				tag: 'div',
				attrs: { class: 'col-auto' },
				content: [
					{
						tag: 'label',
						attrs: { class: 'form-label' },
						content: element[0]
					},
					element[1]
				]
			}));
		}
		container.appendChild(_el({
			tag: 'div',
			attrs: { class: 'col-auto'},
			content: {
				tag: 'button',
				attrs: {
					type: 'button',
					class: 'btn btn-primary',					
				},
				content: _('acl_add'),
				events: {
					click: handler
				}
			}
		}));
	}

	async function _loadCalendarAcl()
	{
		const resp = await _request(`/Authorization/calendaracl/${_calendar_id}`);
		if (resp === null || ! resp.ok)
			return;
		const acls = await resp.json();
		const special = acls.filter((acl) => {return acl.type === 'special'});
		const tbodySpecial = document.getElementById('calendarSpecialAclList');
		tbodySpecial.innerHTML = '';
		if (special.length === 0)
		{
			tbodySpecial.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${_('no_acl')}</td></tr>`;
			return;
		}
		for (const acl of special)
		{
			const tr = _el({
				tag: 'tr',
				content: [
					{ tag: 'td', content: (acl.identifier === 'anonymous')?_('anonymous'):`${_(connected)} (${(acl.source === '')?_('all'):acl.source})`},
					{ tag: 'td', content: _levels[acl.authorization][1]},
					{
						tag: 'td',
						content: {
							tag: 'button',
							attrs: {class: 'btn btn-sm btn-outline-danger'},
							content: [
								{tag: 'i', attrs: { class: 'bi bi-trash' } }	
							],
							events: {
								click: async function() { _removeCalendarACL(acl.source,acl.identifier,acl.type) }
							}
						}
					}
				]
			});
			tbodySpecial.appendChild(tr);
		}
		let form = document.getElementById('calendarSpecialAclForm');
		const specialOptions = [
			['anonymous',_('anonymous')],
			['connected',`${_('connected')} (${_('all')})`]
		];
		for (source of _sources)
			specialOptions.push([`connected_${source[0]}`,`${_('connected')} (${source[0]})`]);
		_appendCalendarAclFields(
			form,
			[
				[
					_('type_of_user'),
					_selectElement({
						options : specialOptions,
						attrs: { class: 'form-select', name: 'user_type' }
					})
				],
				[
					_('acl_authorization'),
					_selectElement({
						options: _levels,
						attrs: { class: 'form-select', name: 'authorization'}
					})
				]
			],
			function () { _addSpecialCalendarAcl(form) }
		);
		const regular = acls.filter((acl) => {return acl.type !== 'special'});
		const tbody = document.getElementById('calendarAclList');
		tbody.innerHTML = '';
		if (regular.length === 0)
		{
			tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${_('no_acl')}</td></tr>`;
			return;
		}
		for (const acl of regular)
		{
			const tr = _el({
				tag: 'tr',
				content: [
					{ tag: 'td', content: acl.source },
					{ tag: 'td', content: acl.identifier},
					{ tag: 'td', content: _('type_' + acl.type)},
					{ tag: 'td', content: _levels[acl.authorization][1]},
					{
						tag: 'td',
						content: [
							{
								tag: 'button',
								attrs: {class: 'btn btn-sm btn-outline-danger'},
								content: [
									{tag: 'i', attrs: { class: 'bi bi-trash' } }	
								],
								events: {
									click: async function() { _removeCalendarACL(acl.source,acl.identifier,acl.type) }
								}
							}
						]
					}
				]
			});
			tbody.appendChild(tr);
		}
		form = document.getElementById('calendarAclForm');
		elements = [
			[
				_('acl_source'),
				_selectElement({
					options: _sources,
					attrs: { class: 'form-select', name: 'source' }
				})
			],
			[
				_('acl_identifier'),
				_el({
					tag: 'input',
					attrs: {type: 'text', class: 'form-control', name: 'identifier', placeholder: _('user_id_or_group_id')},
				})
			],
			[
				_('acl_type'),
				_selectElement({
					options : [ ['user',_('user')], ['group',_('group')] ],
					attrs: { class: 'form-select', name: 'type' }
				})
			],
			[
				_('acl_authorization'),
				_selectElement({
					options: _levels.slice(1),
					attrs: { class: 'form-select', name: 'authorization'}
				})
			]
		];

		for (element of elements)
		{
			form.appendChild(_el({
				tag: 'div',
				attrs: { class: 'col-auto' },
				content: [
					{
						tag: 'label',
						attrs: { class: 'form-label' },
						content: element[0]
					},
					element[1]
				]
			}));
		}
		form.appendChild(_el({
			tag: 'div',
			attrs: { class: 'col-auto'},
			content: {
				tag: 'button',
				attrs: {
					type: 'button',
					class: 'btn btn-primary',					
				},
				content: _('acl_add'),
				events: {
					click: function () { _addCalendarAcl(form) }
				}
			}
		}));
	}

	function _globalACLCheck(acl,changeHandler)
	{
		acl = Number(acl);
		const children = [];
		for (b = 1; b <= 16; b *= 2)
		{
			let key = `val${b}`;
			children.push({
				tag: 'label',
				attrs: {
				},
				content: [
					{
						tag: 'i',
						attrs: {
							class: `bi bi-${_globalACLIcons[key][0]}`,
							title: _(_globalACLIcons[key][1])
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
		return _el({
			tag: 'div',
			attrs: {
				class: 'acl_checkboxes'
			},
			content: children
		});
	}

	async function _globalAclSave(data)
	{
		r = await _request('/Authorization/globalacl', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		if (r !== null)
			_globalAclLoad();
	}

	async function _globalAclDelete(key)
	{
		console.log(key);
		const confirmed = await swal({
			title: _('confirm_delete_acl'),
			icon: 'warning',
			buttons: [_('cancel'), _('delete')],
			dangerMode: true
		});
		if (! confirmed) return;
		const r = await _request('/Authorization/globalacl', {
			method: 'DELETE',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(key)
		});
		if (r !== null)
			_globalAclLoad();
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

	async function _globalACLUpdate(event)
	{
		const tr = event.target.closest('tr');
		const data = tr.dataset;
		data.authorization = _getAuthorization(tr);
		console.log(data);
		_globalAclSave(data);
	}

	async function _globalAclLoad()
	{
		const resp = await _request('/Authorization/globalacl');
		if (resp === null || ! resp.ok)
			return;
		const acls = await resp.json();

		const special = acls.filter((acl) => {return acl.type === 'special'});
		const specialTbody = document.getElementById('globalACLSpecial');
		specialTbody.innerHTML = '';
		for (const acl of special)
		{
			specialTbody.appendChild(_el({
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
						content: _globalACLCheck(acl.authorization,function (event) {_globalACLUpdate(event)})
					},
					{
						tag: 'td',
						content: {
							tag: 'td', 
							content: {
								tag: 'button',
								attrs: {
									class: 'btn btn-outline-danger'
								},
								content: {
									tag: 'i', 
									attrs: {
										class: 'bi bi-trash'
									}
								},
								events: {
									click: function (event) {_globalAclDelete(event.target.closest('tr').dataset)}
								}
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
				const auth = parseInt(acl.authorization);
				tbody.appendChild(_el({
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
							content: _globalACLCheck(acl.authorization,function (event) {_globalACLUpdate(event)})
						},
						{
							tag: 'td', 
							content: {
								tag: 'button',
								attrs: {
									class: 'btn btn-outline-danger'
								},
								content: {
									tag: 'i', 
									attrs: {
										class: 'bi bi-trash'
									}
								},
								events: {
									click: function (event) {_globalAclDelete(event.target.closest('tr').dataset)}
								}
							}
						}
					]
				}));
			}
		}
	}

	async function _createCalendar(data)
	{
		const response = await _request('/Calendar/create',
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		return response.ok;
	}

	// constructeur
	function construct(lang,sources)
	{
		_sources = sources;
		const self = this;	
		fetch('/index/translations',{}).then(async function (response) 
		{
			if (response.ok)
				_translations = await response.json();
			else
				_translations = {};
			_levels = [
				[0,_('no_access')],
				[1,_('level_free_busy')],
				[2,_('level_read')],
				[3,_('level_write')],
				[4,_('level_acl')]
			];
			_globalPermissions = [
				[1,  _('gr_cal_create')],
				[2,  _('gr_cal_destroy')],
				[4,  _('gr_res_add')],
				[8,  _('gr_res_del')],
				[16, _('gr_acl')]
			];
			_calendar = new FullCalendar.Calendar(document.getElementById('calendar'),{
				'locale': lang,
		//		'height': 'auto',
				'headerToolbar': {
					'left': 'prev,next today',
					'center': 'title',
					'right': 'dayGridDay,dayGridWeek,dayGridMonth,dayGridYear'
				},
				'views': {
					'dayGridWeek': {
						'buttonText': 'semaine'
					},
					'dayGridMonth': {
						'buttonText': 'mois'
					},
					'dayGridYear': {
						'buttonText': 'année'
					}
				},
				'initialView': 'dayGridWeek',
				'height': '100%',
			});
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
				document.getElementById('eventId').value = '';
				document.getElementById('eventModalTitle').textContent = _('new_event');
				document.getElementById('eventForm').reset();
				document.getElementById('eventStart').value = _toDateTimeLocal(info.dateStr);
				document.getElementById('deleteEventBtn').disabled = true;
				_getModal('event').show();
			});
			_calendar.on('eventClick', function(info) {
				info.jsEvent.preventDefault();
				const ev = info.event;
				document.getElementById('eventId').value = ev.id;
				document.getElementById('eventModalTitle').textContent = _('edit_event');
				document.getElementById('eventTitle').value = ev.title;
				document.getElementById('eventStart').value = _toDateTimeLocal(ev.startStr);
				document.getElementById('eventEnd').value = _toDateTimeLocal(ev.endStr);
				document.getElementById('eventAllDay').checked = ev.allDay;
				document.getElementById('eventUrl').value = ev.url ?? '';
				document.getElementById('eventDescription').value = ev.extendedProps.description ?? '';
				document.getElementById('deleteEventBtn').disabled = (_calendarAuth < 3);
				_getModal('event').show();
			});
			_showCalendar();
			document.getElementById('addEventBtn').addEventListener('click', function () {
				_getModal('event').show();
			});
			document.getElementById('addCalendarBtn').addEventListener('click', function () {
				_getModal('calendar').show();
			});
			document.getElementById('copyStartToEndBtn').addEventListener('click', function () {
				document.getElementById('eventEnd').value = _toDateTimeLocal(document.getElementById('eventStart').value);
			});
			document.getElementById('saveEventBtn').addEventListener('click', self.addEvent);
			document.getElementById('deleteEventBtn').addEventListener('click', self.deleteEvent);
			document.getElementById('loginBtn').addEventListener('click', self.connect);
			document.getElementById('disconnectBtn').addEventListener('click', self.disconnect);
			document.getElementById('saveCalendarBtn').addEventListener('click', self.saveCalendar);
			document.getElementById('manageCalendarAclBtn').addEventListener('click', function ()
			{
				_loadCalendarAcl();
				_getModal('calendarAcl').show();
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
				console.log(data);
				_globalAclSave(data);
			});
			document.getElementById('globalAclAddRegular').addEventListener('click', function (event) {
				const tr = event.target.closest('tr')
				const fields = tr.querySelectorAll('[name]');
				const data = {};
				fields.forEach((field) => {data[field.name] = field.value});
				data['authorization'] = _getAuthorization(tr.querySelector('.acl_checkboxes'));
				console.log(data);
				_globalAclSave(data);
			});
			document.getElementById('manageGlobalAclBtn').addEventListener('click', function ()
			{
				_globalAclLoad();
				_getModal('globalAcl').show();
			});
			_checkConnection().then(connected => {
				if ((! connected) && (! _ask_for_login))
				{
					_ask_for_login = true;
					_getModal('login').show();
				}
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
		_ask_for_login = false;
		const source = document.getElementById('loginSource').value;
		const user_id = document.getElementById('loginUserId').value;
		const password = document.getElementById('loginPassword').value;
		const response = await fetch('/Authenticate/connect',
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ source, user_id, password })
		});
		if (response.ok)
		{
			_getModal('login').hide();
			_getUserInformations();
			_refreshCalendarsList();
		}
		else if (response.status === 403)
			swal({
				title: _('auth_failed'),
				text: _('wrong_login_or_pwd'),
				icon: "error"
			})
		else
		{
			const message = await response.json();
			swal({
				title: message,
				text: _('internal_error'),
				icon: "error"
			})
		}
		return response.ok;
	}

	construct.prototype.disconnect = async function()
	{
		const response = await fetch('/User/disconnect');
		if (response.ok)
			location.reload();
		else
			swal({
				title: message,
				text: _('internal_error'),
				icon: "error"
			});
	}

	construct.prototype.saveCalendar = async function()
	{
		const fields = document.getElementById('calendarForm').querySelectorAll('[id]');
		const data = {};
		for (const field of fields)
			data[field.id] = field.value;
		console.log(data);
		const calendarId = data["calendarId"];
		delete data["calendarId"];
		_getModal('calendar').hide();
		if (((calendarId === "") && _createCalendar(data)) || _updateCalendar(Number(calendarId),data))
		{
			swal({
				title: _('success'),
				text: _('calendar_created'),
				icon: 'success'
			});
			_refreshCalendarsList();
		}
		else
			swal({
				title: _('error'),
				text: _('calendar_creation_failed'),
				icon: "error"
			});

	}

	construct.prototype.addEvent = async function()
	{
		const fields = document.getElementById('eventForm').querySelectorAll('[id]');
		const data = {};
		for (const field of fields)
			data[field.id] = field.value;
		if (! data['eventTitle'] || ! data['eventStart'])
		{
			swal({
				title: _('error'),
				text: _('event_missing_title_or_start'),
				icon: 'error'
			});
			return;
		}
		const response = await _request(`/Event/create/${_calendar_id}`,
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		const result = await response.json();
		if (response.ok)
		{
			_calendar.addEvent({
				id: result['event_id'],
				title: eventTitle,
				start: eventStart,
				end: eventEnd || null,
				url: eventUrl || null,
				allDay: eventAllDay,
				extendedProps: {
					description: eventDescription
				}
			});
			_showCalendar();
			_getModal('event').hide();
		}
		else if (response.status === 403)
			swal({title: _('Forbidden'), icon: 'error'});
		else if (response.status === 404)
			swal({title: _('Not Found'), icon: 'error'});
		else
			swal({title: _('Error'), icon: 'error'});
	}	

	construct.prototype.deleteEvent = async function()
	{
		const event_id = document.getElementById('eventId').value;
		if (! event_id) return;
		const confirmed = await swal({
			title: _('confirm_delete_event'),
			icon: 'warning',
			buttons: [_('cancel'), _('delete')],
			dangerMode: true
		});
		if (! confirmed) return;
		const response = await _request(`/Event/delete/${event_id}`);
		if (response && response.ok)
		{
			const ev = _calendar.getEventById(event_id);
			if (ev) ev.remove();
			_getModal('event').hide();
		}
		else
			swal({ title: _('error'), icon: 'error' });
	}

	return construct;
}());