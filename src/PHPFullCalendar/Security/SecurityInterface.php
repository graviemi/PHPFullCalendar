<?php

namespace PHPFullCalendar\Security;

interface SecurityInterface
{
	public function Check(string $IP) : bool;
	public function Store(string $IP) : int;
}