<?php

namespace PHPFullCalendar\Views;

class Json extends ViewAbstract
{
	protected $data;

	public function __construct($data = null)
	{
		$this->data = $data;
	}

	public function mimeType() : string
	{
		return 'application/json';
	}

	public function body() : void
	{
		if ($this->data !== null)
			echo json_encode($this->data,JSON_THROW_ON_ERROR);
	}
}