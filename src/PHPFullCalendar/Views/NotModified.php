<?php

namespace PHPFullCalendar\Views;

class NotModified extends ViewAbstract
{
	public function code() : int
	{
		return 304;
	}

	public function message() : string
	{
		return 'Not Modified';
	}
}
