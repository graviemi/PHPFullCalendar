<?php

namespace PHPFullCalendar\Views;

interface ViewInterface
{
	public function code() : int;
	public function message() : string;
	public function mimeType() : string;
	public function charSet() : string;
	public function extraHeaders() : array;
	public function body() : void;
}