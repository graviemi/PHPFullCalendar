<?php

namespace PHPFullCalendar\Informations;

use PHPFullCalendar\_;

abstract class InformationAbstract implements InformationInterface
{
	protected array $parameters = [];

	public function __construct(array $parameters)
	{
		$this->parameters = $parameters;
	}

	public function get(string $user_id) : array
	{
		return ['name' => $user_id];
	}
}