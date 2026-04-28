<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\NotFound;

class Favicon implements ControllerInterface
{
	public function __construct(string $verb, string|null $method, string|null $parameters)
	{}

	public function view() : ViewInterface
	{
		return new NotFound();
	}
}