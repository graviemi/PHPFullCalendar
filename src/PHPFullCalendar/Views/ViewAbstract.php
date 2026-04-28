<?php

namespace PHPFullCalendar\Views;

abstract class ViewAbstract implements ViewInterface
{
	public function code() : int
	{
		return 200;
	}

	public function message() : string
	{
		return 'Ok';
	}

	public function mimeType() : string
	{
		return 'text/html';
	}

	public function charSet() : string
	{
		return 'utf-8';
	}

	public function extraHeaders() : array
	{
		return [];
	}

	public function body() : void
	{}
}