
PHPFullCalendar = (function ()
{
	var _translations;
	var _modals = {};
	var _calendar; // FullCalendar.js
	var _calendar_id = null; // selected calendar unique database id
	var _globalsAuth = 0; // connected user global authorizations
	var _calendarAuth = 0; // connected user selected calendar authorizations
	var _event_id = null; // clicked calendar event unique database id
	var _user_infos;
	var _ask_for_login = false;
	var _sources = null;
	var _levels;
	var _globalPermissions;
	var _pendingRequests = 0;
	var _loadingTimer = null;
	var _icsFile = null;

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

	function _loadingShow()
	{
		_pendingRequests++;
		if (_pendingRequests === 1)
			_loadingTimer = setTimeout(() => document.getElementById('loadingOverlay').classList.add('active'), 300);
	}

	function _loadingHide()
	{
		_pendingRequests--;
		if (_pendingRequests === 0)
		{
			clearTimeout(_loadingTimer);
			document.getElementById('loadingOverlay').classList.remove('active');
		}
	}

	async function _request(url, options = {})
	{
		_loadingShow();
		const response = await fetch(url, options);
//		console.log(response.status);
		if (response.status === 400)
		{
			const data = await response.json();
			_loadingHide();
			swal({
				title: _('error'),
				text: _(data['message'] ?? _('unknown_error')),
				icon: 'error'
			});
			return null;
		}
		if (response.status === 403)
		{
			const data = await response.json();
			_loadingHide();
			swal({
				title: _('unauthorized'),
				text: _(data['message'] ?? _('unknown_error')),
				icon: 'error'
			});
			return null;
		}
		if (response.status === 401)
		{
			_loadingHide();
			if (! _ask_for_login)
			{
				_ask_for_login = true;
				_getModal('login').show();
			}
			return null;
		}
		_loadingHide();
		if (response.ok)
			return response;
		return null;
	}

	async function _checkConnection()
	{
		const response = await fetch('/Authenticate/check');
		if (response.ok)
			return await response.json();
		return false;
	}

	function _enable(button_id, enabled)
	{
		document.getElementById(button_id).disabled = (! enabled);
	}

	function _toggleButtons()
	{
		_enable('addCalendarBtn',(_globalsAuth & 1) === 1);
		_enable('manageGlobalAclBtn',((_globalsAuth & 16) === 16));
		_enable('disconnectBtn',_user_infos !== null);
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

	function _getModal(name)
	{
		const element = document.getElementById(`${name}Modal`);
		if (element === null)
		{
			console.log(`${name}Modal element not found`)
			return null;
		}
		if (! _modals.hasOwnProperty(name))
			_modals[name] = new bootstrap.Modal(element);
		return _modals[name];
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
			_enable('addEventBtn', false);
			_enable('importICSBtn', false);
			_enable('calendarInfoBtn', false);
			_enable('editCalendarBtn', false);
			_enable('manageCalendarAclBtn', false);
			calendar.setAttribute('data-hidden', 'true');
			no_calendar.removeAttribute('data-hidden');
			_enable('removeCalendarBtn',false);
			return;
		}
		const response = await _request(`/Authorization/calendar/${_calendar_id}`);
		_calendarAuth = response !== null ? await response.json() : 0;
		_enable('addEventBtn', _calendarAuth >= 3);
		_enable('importICSBtn', _calendarAuth >= 3);
		_enable('calendarInfoBtn', _calendarAuth >= 1);
		_enable('editCalendarBtn', _calendarAuth >= 3);
		_enable('manageCalendarAclBtn', _calendarAuth >= 4);
		_enable('removeCalendarBtn', (_calendarAuth >= 4) && (_globalsAuth & 2) === 2);
		calendar.removeAttribute('data-hidden');
		no_calendar.setAttribute('data-hidden', 'true');
//		_calendar.removeAllEvents();
		_calendar.removeAllEventSources();
		_calendar.addEventSource({ url: `/Event/list/${_calendar_id}` });
//		_calendar.refetchEvents();
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

	function _utcToDateTimeLocal(str)
	{
		if (! str) return '';
		const d = new Date(str);
		const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
		return local.toISOString().substring(0, 16);
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

	function _selectElement({options, value, onchange})
	{
		return _el({
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

	async function _calendarAclSave(data)
	{
		const resp = await _request(`/Authorization/calendaracl/${_calendar_id}`,
		{
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		_calendarAclLoad();
	}

	async function _calendarAclUpdate(event)
	{
		const tr = event.target.closest('tr');
		const data = tr.dataset;
		data.authorization = tr.querySelector('[name=level]').value;
		_calendarAclSave(data);
	}

	async function _calendarAclDelete(key)
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
			body: new URLSearchParams(key)
		});
		_calendarAclLoad();
	}

	async function _calendarAclLoad()
	{
		const resp = await _request(`/Authorization/calendaracl/${_calendar_id}`);
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
				const tr = _el({
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
								options: _levels,
								value: acl.authorization,
								onchange: function (event) { _calendarAclUpdate(event) }
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
									click: async function(event) { _calendarAclDelete(event.target.closest('tr').dataset) }
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
			const tr = _el({
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
							options: _levels.slice(1),
							value: acl.authorization,
							onchange: function (event) { _calendarAclUpdate(event) }
						})
					},
					{
						tag: 'td',
						content: {
							tag: 'button',
							attrs: {class: 'btn btn-sm btn-outline-danger'},
							content: {tag: 'i', attrs: { class: 'bi bi-trash' } },
							events: {
								click: async function(event) { _calendarAclDelete(event.target.closest('tr').dataset) }
							}
						}
					}
				]
			});
			tbody.appendChild(tr);
		}
		return true;
	}

	function _globalACLCheck(acl,changeHandler)
	{
		acl = Number(acl);
		const children = [];
		for (let b = 1; b <= 16; b *= 2)
		{
			let key = `val${b}`;
			children.push({
				tag: 'label',
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
		const r = await _request('/Authorization/globalacl', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data)
		});
		_globalAclLoad();
	}

	async function _globalAclDelete(key)
	{
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
		_globalAclSave(data);
	}

	async function _globalAclLoad()
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
								click: function (event) {_globalAclDelete(event.target.closest('tr').dataset)}
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
									class: 'btn btn-sm btn-outline-danger'
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
		return true;
	}

	function _icsSetFile(file)
	{
		_icsFile = file;
		icsDropZone.classList.add('has-file');
		icsDropZone.classList.remove('drag-over');
		icsFileName.textContent = file.name;
		icsFileName.removeAttribute('data-hidden');
		_enable('doImportIcsBtn',true);
	}
	
	function _icsReset()
	{
		_icsFile = null;
		icsFileInput.value = '';
		icsDropZone.classList.remove('has-file', 'drag-over');
		icsFileName.setAttribute('data-hidden', 'true');
		icsFileName.textContent = '';
		_enable('doImportIcsBtn',false);
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
				[1, _('gr_cal_create')],
				[2, _('gr_cal_destroy')],
				[4, _('gr_res_add')],
				[8, _('gr_res_del')],
				[16, _('gr_acl')]
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
				_getModal('calendar').show();
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
				_getModal('info').show();
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
				_getModal('calendar').show();
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
				_getModal('calendar').hide();
			});

			// Events managment

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
				bootstrap.Collapse.getOrCreateInstance(rruleCollapse).hide();
				document.getElementById('rruleExpertSection').style.display = 'none';
				form.querySelector('input[name=start]').value = _toDateTimeLocal(info.dateStr);
				form.querySelector('input[name=end]').value = _toDateTimeLocal(info.dateStr);
				document.getElementById('eventModalTitle').textContent = _('new_event');
				document.getElementById('deleteEventBtn').disabled = true;
				_getModal('event').show();
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
					bootstrap.Collapse.getOrCreateInstance(rruleCollapse).hide();
				document.getElementById('eventModalTitle').textContent = _('edit_event');
				document.getElementById('deleteEventBtn').disabled = (_calendarAuth < 3);
				_getModal('event').show();
			});
			document.getElementById('addEventBtn').addEventListener('click', function () {
				_event_id = null;
				document.getElementById('eventForm').reset();
				bootstrap.Collapse.getOrCreateInstance(rruleCollapse).hide();
				document.getElementById('rruleExpertSection').style.display = 'none';
				_enable('deleteEventBtn',false);
				_getModal('event').show();
			});
			document.getElementById('copyStartToEndBtn').addEventListener('click', function () {
				const form = document.getElementById('eventForm');
//				console.log(form.querySelector('input[name=start]').value);
//				form.querySelector('input[name=end]').value = _toDateTimeLocal(form.querySelector('input[name=start]').value);
				form.querySelector('input[name=end]').value = form.querySelector('input[name=start]').value;
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
				_getModal('event').hide();
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
				_getModal('event').hide();
			});

			// Recurrence form

			const rruleCollapse = document.getElementById('rruleCollapse');
			const rruleActive = document.getElementById('rruleActive');
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
				_getModal('importIcs').hide();
				_showCalendar();
				swal({ title: _('import_ics'), text: _('ics_import_success').replace('%d', result.imported), icon: 'success' });
			});

			// Calendar ACL managment

			document.getElementById('manageCalendarAclBtn').addEventListener('click', async function () {
				const success = await _calendarAclLoad();
				if (success)
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
				_globalAclSave(data);
			});
			document.getElementById('globalAclAddRegular').addEventListener('click', function (event) {
				const tr = event.target.closest('tr')
				const fields = tr.querySelectorAll('[name]');
				const data = {};
				fields.forEach((field) => {data[field.name] = field.value});
				data['authorization'] = _getAuthorization(tr.querySelector('.acl_checkboxes'));
				_globalAclSave(data);
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
				_calendarAclSave(data);
			});
			document.getElementById('calendarAclAddRegular').addEventListener('click', function (event) {
				const tr = event.target.closest('tr');
				const fields = tr.querySelectorAll('[name]');
				const data = {};
				fields.forEach((field) => {data[field.name] = field.value});
				_calendarAclSave(data);
			});

			// Global ACL

			document.getElementById('manageGlobalAclBtn').addEventListener('click', async function () {
				const success = await _globalAclLoad();
				if (success)
					_getModal('globalAcl').show();
			});

			_showCalendar();
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
			_getModal('login').hide();
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

	return construct;
}());
