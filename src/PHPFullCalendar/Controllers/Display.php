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
	protected static array $validation = [
		'label' => ['/^.{1,255}$/', 'label_required'],
		'description_id' => ['/^\d+$/', 'wrong_identifier'],
	];

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
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('label_and_description_required'));
		$db = new AlarmDB(_::getPDO());
		if ($db->getDescription($data['description_id']) === false)
			return new BadRequest(_::_('wrong_identifier'));
		return new Json($db->createDisplay($data));
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
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('label_and_description_required'));
		if ($db->getDescription($data['description_id']) === false)
			return new BadRequest(_::_('wrong_identifier'));
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
