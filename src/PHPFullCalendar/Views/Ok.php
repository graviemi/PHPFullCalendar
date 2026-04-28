<?php

namespace PHPFullCalendar\Views;

class Ok extends Json
{
	public function code() : int
	{
		return 200;
	}

	public function message() : string
	{
		return 'Ok';
	}
}