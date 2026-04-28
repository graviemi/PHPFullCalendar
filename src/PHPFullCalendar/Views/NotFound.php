<?php

namespace PHPFullCalendar\Views;

class NotFound extends ViewAbstract
{
	public function code() : int
	{
		return 404;
	}

	public function message() : string
	{
		return 'NotFound';
	}
}