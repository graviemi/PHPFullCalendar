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

class Recipient extends ControllerAbstract
{
	protected static array $validation = [
		'address' => ['/^.{1,255}$/', 'wrong_email_address'],
		'name' => ['/^.{0,255}$/', 'wrong_value'],
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
			'address' => trim($_POST['address'] ?? ''),
			'name' => ($_POST['name'] ?? '') === '' ? null : $_POST['name'],
		];
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		return new Json((new AlarmDB(_::getPDO()))->getRecipients());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$recipient = (new AlarmDB(_::getPDO()))->getRecipient((int) $matches[1]);
		if ($recipient === false)
			return new NotFound();
		return new Json($recipient);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_data();
		if (! filter_var($data['address'], FILTER_VALIDATE_EMAIL))
			return new BadRequest(_::_('wrong_email_address'));
		return new Json((new AlarmDB(_::getPDO()))->createRecipient($data));
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		if ($db->getRecipient((int) $matches[1]) === false)
			return new NotFound();
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_data();
		if (! filter_var($data['address'], FILTER_VALIDATE_EMAIL))
			return new BadRequest(_::_('wrong_email_address'));
		$db->updateRecipient((int) $matches[1], $data);
		return new Ok();
	}

	protected function _get_delete()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		(new AlarmDB(_::getPDO()))->deleteRecipient((int) $matches[1]);
		return new Ok();
	}
}
