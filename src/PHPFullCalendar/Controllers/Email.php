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

class Email extends ControllerAbstract
{
	protected static array $validation = [
		'label' => ['/^.{1,255}$/', 'label_required'],
		'summary' => ['/^.{1,255}$/', 'wrong_summary'],
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
		$summary = trim($_POST['summary'] ?? '');
		$description_id = trim($_POST['description_id'] ?? '');
		if ($label === '' || $summary === '' || $description_id === '')
			return null;
		return ['label' => $label, 'summary' => $summary, 'description_id' => (int) $description_id];
	}

	private static function _recipientIds() : array
	{
		$recipients = $_POST['recipients'] ?? [];
		return array_map('intval', is_array($recipients) ? $recipients : [$recipients]);
	}

	private function _checkReferences(AlarmDB $db, array $data) : ViewInterface|null
	{
		if ($db->getDescription($data['description_id']) === false)
			return new BadRequest(_::_('wrong_identifier'));
		foreach (self::_recipientIds() as $recipient_id)
			if ($db->getRecipient($recipient_id) === false)
				return new BadRequest(_::_('wrong_identifier'));
		return null;
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		return new Json((new AlarmDB(_::getPDO()))->getEmails());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		$email = $db->getEmail((int) $matches[1]);
		if ($email === false)
			return new NotFound();
		$email['recipients'] = array_map(fn($r) => (int) $r['recipient_id'], $db->getEmailRecipients((int) $matches[1]));
		return new Json($email);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('email_fields_required'));
		$pdo = _::getPDO();
		$db = new AlarmDB($pdo);
		if (($invalid = $this->_checkReferences($db, $data)) !== null)
			return $invalid;
		$pdo->beginTransaction();
		try
		{
			$email_id = $db->createEmail($data);
			foreach (self::_recipientIds() as $recipient_id)
				$db->addEmailRecipient($email_id, $recipient_id);
			$pdo->commit();
		}
		catch (\Exception $e)
		{
			$pdo->rollBack();
			throw $e;
		}
		return new Json($email_id);
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$email_id = (int) $matches[1];
		$pdo = _::getPDO();
		$db = new AlarmDB($pdo);
		if ($db->getEmail($email_id) === false)
			return new NotFound();
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($data = self::_data()) === null)
			return new BadRequest(_::_('email_fields_required'));
		if (($invalid = $this->_checkReferences($db, $data)) !== null)
			return $invalid;
		$pdo->beginTransaction();
		try
		{
			$db->updateEmail($email_id, $data);
			foreach ($db->getEmailRecipients($email_id) as $recipient)
				$db->removeEmailRecipient($email_id, (int) $recipient['recipient_id']);
			foreach (self::_recipientIds() as $recipient_id)
				$db->addEmailRecipient($email_id, $recipient_id);
			$pdo->commit();
		}
		catch (\Exception $e)
		{
			$pdo->rollBack();
			throw $e;
		}
		return new Ok();
	}

	protected function _get_delete()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		(new AlarmDB(_::getPDO()))->deleteEmail((int) $matches[1]);
		return new Ok();
	}
}
