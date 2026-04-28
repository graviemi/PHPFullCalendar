<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\Views\ViewInterface;

interface ControllerInterface
{
	public function view() : ViewInterface; // return object which output response
}