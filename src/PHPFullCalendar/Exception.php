<?php

namespace PHPFullCalendar;

class Exception extends \Exception
{
	public function __construct(int $code, string $string,...$args)
	{
		parent::__construct(vsprintf($string,$args),$code);
	}
}
