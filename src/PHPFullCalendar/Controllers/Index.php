<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Views\Main,
	PHPFullCalendar\Views\Json;

class Index extends ControllerAbstract
{
	protected function _get_translations()
	{
		return new Json(_::$translations);
	}

	protected function _get_default()
	{
		return new Main(_::$conf['template'] ?? (_::$root.'/templates/index.php'));
	}
}