<?php

namespace PHPFullCalendar\Views;

use PHPFullCalendar\_;

class Unauthorized extends Json
{
	public function code() : int
	{
		return 401;
	}

	public function message() : string
	{
		return 'Unauthorized';
	}

	public function extraHeaders() : array
	{
		if (_::$noSession)
			return [
				sprintf('WWW-Authenticate: Basic realm="%s"', _::$conf['realm'] ?? 'PHPFullCalendar')
			];
		return [];
	}
}