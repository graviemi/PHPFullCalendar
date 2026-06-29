<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Alarm as AlarmDB,
	PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Display extends ControllerAbstract
{
	private function _checkAlarmRight() : ViewInterface|null
	{
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_ALARM) === 0)
			return _::denyAccess();
		return null;
	}

	private static function _data() : array|null
	{
		$label = trim($_POST['label'] ?? '');
		$description_id = trim($_POST['description_id'] ?? '');
		if ($label === '' || $description_id === '')
			return null;
		return ['label' => $label, 'description_id' => (int) $description_id];
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		return new Json((new AlarmDB(_::getPDO()))->getDisplays());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$display = (new AlarmDB(_::getPDO()))->getDisplay((int) $matches[1]);
		if ($display === false)
			return new NotFound();
		return new Json($display);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('label_and_description_required'));
		return new Json((new AlarmDB(_::getPDO()))->createDisplay($data));
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		if ($db->getDisplay((int) $matches[1]) === false)
			return new NotFound();
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('label_and_description_required'));
		$db->updateDisplay((int) $matches[1], $data);
		return new Ok();
	}

	protected function _get_delete()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		(new AlarmDB(_::getPDO()))->deleteDisplay((int) $matches[1]);
		return new Ok();
	}
}
