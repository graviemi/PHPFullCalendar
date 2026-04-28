<?php

namespace PHPFullCalendar\Views;

use oTools\template as oToolsTemplate;

class Template extends ViewAbstract
{
	protected oToolsTemplate $template;

	public function __construct(string $path, array &$data)
	{
		$this->template = new oToolsTemplate($path,$data);
	}

	public function body() : void
	{
		echo $this->template;
	}
}