<?php

namespace PHPFullCalendar\Views;

use PHPFullCalendar\_;

class BadRequest extends Json
{
	public function code() : int
	{
		return 400;
	}

	public function message() : string
	{
		return 'Bad Request';
	}

	public function body() : void
	{
		if ($this->data !== null)
			echo json_encode(['message' => $this->data],JSON_THROW_ON_ERROR);
	}
}