<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Exception,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Views\Forbidden,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\Json;

class Authenticate extends ControllerAbstract
{
	protected function _get_check()
	{
		return new Json(isSet(_::getUserData()['user_id']));
	}

	protected function _get_sources()
	{
		$sources = [];
		foreach (_::$conf['authentication'] as $source => $data)
			$sources[] = [$source,$data['method']];
		return new Json($sources);
	}

	protected function _post_connect()
	{
		$auth = _::getAuthentication($_POST['source']);
		_::debug('auth');
		if (($data = $auth->verify($_POST['user_id'] ?? '',$_POST['password'] ?? '')) === null)
			return new Forbidden('wrong identifier or password');
		_::debug(print_r($data,true));
		foreach ($data as $key => $value)
			_::getSession()[$key] = $value;
		return new Ok();
	}
}
