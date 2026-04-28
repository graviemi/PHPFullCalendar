<?php

namespace PHPFullCalendar\Views;

class Main extends ViewAbstract implements ViewInterface
{
	protected string $path;

	public function __construct(string $path)
	{
		$this->path = $path;
	}

	public function body() : void
	{
		require $this->path;
	}
}