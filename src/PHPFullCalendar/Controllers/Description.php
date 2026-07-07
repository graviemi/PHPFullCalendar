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

class Description extends ControllerAbstract
{
	protected static array $validation = [
		'label' => ['/^.{1,255}$/', 'label_required'],
		'contents' => ['/^.{1,65535}$/s', 'label_and_contents_required'],
	];

	private function _checkAlarmRight() : ViewInterface|null
	{
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_ALARM) === 0)
			return _::denyAccess();
		return null;
	}

	private static function _data() : array
	{
		return [
			'label' => trim($_POST['label'] ?? ''),
			'contents' => trim($_POST['contents'] ?? ''),
		];
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		return new Json((new AlarmDB(_::getPDO()))->getDescriptions());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$description = (new AlarmDB(_::getPDO()))->getDescription((int) $matches[1]);
		if ($description === false)
			return new NotFound();
		return new Json($description);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_data();
		if ($data['label'] === '' || $data['contents'] === '')
			return new BadRequest(_::_('label_and_contents_required'));
		return new Json((new AlarmDB(_::getPDO()))->createDescription($data));
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		if ($db->getDescription((int) $matches[1]) === false)
			return new NotFound();
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_data();
		if ($data['label'] === '' || $data['contents'] === '')
			return new BadRequest(_::_('label_and_contents_required'));
		$db->updateDescription((int) $matches[1], $data);
		return new Ok();
	}

	protected function _get_delete()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		(new AlarmDB(_::getPDO()))->deleteDescription((int) $matches[1]);
		return new Ok();
	}
}
