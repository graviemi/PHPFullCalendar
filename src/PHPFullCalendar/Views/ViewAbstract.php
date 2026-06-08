<?php

namespace PHPFullCalendar\Views;

abstract class ViewAbstract implements ViewInterface
{
	protected array $extraHeaders = [];
	protected int|null $lastModified = null;

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
		if ($this->lastModified !== null)
		{
			$this->extraHeaders[] = sprintf('Last-Modified: %s', gmdate('D, d M Y H:i:s \G\M\T', $this->lastModified));
			$this->extraHeaders[] = 'Cache-Control: no-cache';
		}
		return $this->extraHeaders;
	}

	public function body() : void
	{}
}