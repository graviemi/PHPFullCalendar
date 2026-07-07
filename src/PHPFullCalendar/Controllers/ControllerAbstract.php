<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\NotFound,
	PHPFullCalendar\Exception;

abstract class ControllerAbstract implements ControllerInterface
{
	protected string $method;
	protected string|null $parameters;

	protected static array $validation = [];

	public function __construct(string $verb, string|null $method, string|null $parameters)
	{
		$this->method = sprintf('_%s_%s',$verb,$method ?? 'default');
		$this->parameters = $parameters;
	}

	protected function _control(array $raw_data) : string|null
	{
		foreach (static::$validation as $key => $rules)
		{
			$value = $raw_data[$key] ?? '';
			if (! is_string($value) || ! preg_match($rules[0], $value))
				return $rules[1];
		}
		return null;
	}

	public function view() : ViewInterface
	{
		if (! method_exists($this,$this->method))
			return new NotFound();
		return $this->{$this->method}();
	}
}