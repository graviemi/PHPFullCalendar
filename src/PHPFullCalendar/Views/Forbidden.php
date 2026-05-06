<?php

namespace PHPFullCalendar\Views;

class Forbidden extends Json
{
	public function code() : int
	{
		return 403;
	}

	public function message() : string
	{
		return 'Forbidden';
	}
}
