<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ok;

class User extends ControllerAbstract
{
	protected function _get_disconnect()
	{
		_::disconnect();
		return new Ok();
	}

	protected function _get_informations()
	{
		$data = _::getUserData();
		if (is_a($data,'ArrayObject'))
			return new Json($data->getArrayCopy());
		return new Json($data->toArray());
	}
}
