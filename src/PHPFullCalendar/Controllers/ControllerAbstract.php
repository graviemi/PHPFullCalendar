<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\NotFound,
	PHPFullCalendar\Exception;

abstract class ControllerAbstract implements ControllerInterface
{
	protected string $method;
	protected string|null $parameters;

	public function __construct(string $verb, string|null $method, string|null $parameters)
	{
		$this->method = sprintf('_%s_%s',$verb,$method ?? 'default');
		$this->parameters = $parameters;
	}

	public function view() : ViewInterface
	{
		\PHPFullCalendar\_::debug('method : %s',$this->method);
		if (! method_exists($this,$this->method))
			return new NotFound();
		\PHPFullCalendar\_::debug('method found');
		return $this->{$this->method}();
	}
}