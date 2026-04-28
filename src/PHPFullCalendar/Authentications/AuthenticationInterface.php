<?php

namespace PHPFullCalendar\Authentications;

use ArrayObject;

interface AuthenticationInterface
{
	public function verify(string $login, string $password) : ArrayObject;
}