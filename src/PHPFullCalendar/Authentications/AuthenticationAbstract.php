<?php

namespace PHPFullCalendar\Authentications;

use PHPFullCalendar\_,
	PHPFullCalendar\Exception,
	PHPFullCalendar\Informations\InformationInterface,
	PHPFullCalendar\Groups\GroupInterface;

abstract class AuthenticationAbstract implements AuthenticationInterface
{
	protected string $source;
	protected array $parameters = [];
	protected InformationInterface|null $information = null;
	protected GroupInterface|null $groups = null;
	protected string $error;

	public function __construct(string $source, array $parameters, array $information, array $groups)
	{
		$this->source = $source;
		$this->parameters = $parameters;
		if (isset($information['method']))
		{
			$class = sprintf('PHPFullCalendar\\Informations\\%s',$information['method']);
			if (! class_exists($class))
				throw new Exception(_::NOT_FOUND,'unknown information method "%s"',$information['method']);
			$this->information = new $class($information['parameters']);
		}
		if (isset($groups['method']))
		{
			$class = sprintf('PHPFullCalendar\\Groups\\%s',$groups['method']);
			if (! class_exists($class))
				throw new Exception(_::NOT_FOUND,'unknown groups method "%s"',$groups['method']);
			$this->groups = new $class($groups['parameters']);
		}
	}

	protected function failure() : void
	{
		$this->error = _::_('wrong_login_or_password');
	}

	public function getError() : string
	{
		return $this->error;
	}
}
