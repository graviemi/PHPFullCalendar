<?php

namespace PHPFullCalendar\Data;

use ArrayObject;

class User extends ArrayObject
{

	public function isAnonymous() : bool
	{
		return ! $this->isAuthenticated();
	}

	public function isAuthenticated() : bool
	{
		return $this->offsetExists('user_id');
	}

	public function Id() : string
	{
		return $this['source'] ?? ''.$this['user_id'] ?? '';
	}
}