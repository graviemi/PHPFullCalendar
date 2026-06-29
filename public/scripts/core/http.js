// HTTP layer : loading overlay, request() with 400/401/403 handling, connection check.

import { t } from './i18n.js';
import { getModal } from './dom.js';

let _pendingRequests = 0;
let _loadingTimer = null;
let _askForLogin = false;

export function loadingShow()
{
	_pendingRequests++;
	if (_pendingRequests === 1)
		_loadingTimer = setTimeout(() => document.getElementById('loadingOverlay').classList.add('active'), 300);
}

export function loadingHide()
{
	_pendingRequests--;
	if (_pendingRequests === 0)
	{
		clearTimeout(_loadingTimer);
		document.getElementById('loadingOverlay').classList.remove('active');
	}
}

// Open the login modal once (idempotent until resetAskForLogin()).
export function promptLogin()
{
	if (! _askForLogin)
	{
		_askForLogin = true;
		getModal('login').show();
	}
}

export function resetAskForLogin()
{
	_askForLogin = false;
}

export async function request(url, options = {})
{
	loadingShow();
	const response = await fetch(url, options);
	if (response.status === 400)
	{
		const data = await response.json();
		loadingHide();
		swal({
			title: t('error'),
			text: t(data['message'] ?? t('unknown_error')),
			icon: 'error'
		});
		return null;
	}
	if (response.status === 403)
	{
		const data = await response.json();
		loadingHide();
		swal({
			title: t('unauthorized'),
			text: t(data['message'] ?? t('unknown_error')),
			icon: 'error'
		});
		return null;
	}
	if (response.status === 401)
	{
		loadingHide();
		promptLogin();
		return null;
	}
	loadingHide();
	if (response.ok)
		return response;
	return null;
}

export async function checkConnection()
{
	const response = await fetch('/Authenticate/check');
	if (response.ok)
		return await response.json();
	return false;
}
