<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Views\Forbidden,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\Json;

class Authenticate extends ControllerAbstract
{
	protected function _get_check()
	{
		$userData = _::getUserData();
		return new Json(isSet($userData['user_id']));
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
		usleep(random_int(1100,11000));
		if ((($secu = _::getSecurity()) !== null) && ! $secu->check(_::clientIp()))
			return new Forbidden('your have been blocked (Too many failed attempt).');		
		$auth = _::getAuthentication($_POST['source']);
		if (($data = $auth->verify($_POST['user_id'] ?? '',$_POST['password'] ?? '')) === null)
		{
			if ($secu !== null)
				$count = $secu->Store(_::clientIp());
			return new Forbidden($auth->getError());
		}
		foreach ($data as $key => $value)
			_::getSession()[$key] = $value;
		return new Ok();
	}
}
