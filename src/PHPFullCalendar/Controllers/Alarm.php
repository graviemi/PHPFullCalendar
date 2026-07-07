<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Alarm as AlarmDB,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Alarm extends ControllerAbstract
{
	protected static array $validation = [
		'label' => ['/^.{1,255}$/', 'title_required'],
		'trigger_value' => ['/^\d*$/', 'wrong_value'],
		'trigger_unit' => ['/^[MHDW]?$/', 'wrong_value'],
		'trigger_sign' => ['/^[-+]?$/', 'wrong_value'],
		'related' => ['/^(start|end)?$/', 'wrong_value'],
		'repeat' => ['/^\d*$/', 'wrong_value'],
		'duration_value' => ['/^([1-9]\d*)?$/', 'wrong_value'],
		'duration_unit' => ['/^[MHDW]?$/', 'wrong_value'],
		'display_id' => ['/^\d*$/', 'wrong_identifier'],
		'email_id' => ['/^\d*$/', 'wrong_identifier'],
		'audio_id' => ['/^\d*$/', 'wrong_identifier'],
		'display_description_id' => ['/^\d*$/', 'wrong_identifier'],
		'email_description_id' => ['/^\d*$/', 'wrong_identifier'],
		'display_label' => ['/^.{0,255}$/', 'wrong_label'],
		'email_label' => ['/^.{0,255}$/', 'wrong_label'],
		'audio_label' => ['/^.{0,255}$/', 'wrong_label'],
		'display_description_label' => ['/^.{0,255}$/', 'wrong_label'],
		'email_description_label' => ['/^.{0,255}$/', 'wrong_label'],
		'email_summary' => ['/^.{0,255}$/', 'wrong_summary'],
		'display_description' => ['/^.{0,65535}$/s', 'description_too_long'],
		'email_description' => ['/^.{0,65535}$/s', 'description_too_long'],
	];

	private function _checkAlarmRight() : ViewInterface|null
	{
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_ALARM) === 0)
			return _::denyAccess();
		return null;
	}

	private static function _duration(int $value, string $unit) : string
	{
		return match ($unit) {
			'W' => sprintf('P%dW', $value),
			'D' => sprintf('P%dD', $value),
			'H' => sprintf('PT%dH', $value),
			default => sprintf('PT%dM', $value),
		};
	}

	private static function _alarmData() : array
	{
		$trigger = self::_duration((int) ($_POST['trigger_value'] ?? 15), $_POST['trigger_unit'] ?? 'M');
		$repeat = (int) ($_POST['repeat'] ?? 0);
		return [
			'label' => trim($_POST['label'] ?? ''),
			'trigger' => (($_POST['trigger_sign'] ?? '-') === '+' ? '' : '-').$trigger,
			'repeat' => $repeat,
			'duration' => $repeat > 0 ? self::_duration((int) ($_POST['duration_value'] ?? 5), $_POST['duration_unit'] ?? 'M') : null,
			'related' => ($_POST['related'] ?? 'start') === 'end' ? 'end' : 'start',
		];
	}

	private static function _recipientIds() : array
	{
		$recipients = $_POST['recipients'] ?? [];
		return array_map('intval', is_array($recipients) ? $recipients : [$recipients]);
	}

	private static function _hasSound() : bool
	{
		return isset($_FILES['sound']) && is_uploaded_file($_FILES['sound']['tmp_name']);
	}

	private static function _uploadedSound() : array|null
	{
		if (! self::_hasSound())
			return null;
		$tmp = $_FILES['sound']['tmp_name'];
		return [
			'label' => $_FILES['sound']['name'],
			'mimetype' => (new \finfo(FILEINFO_MIME_TYPE))->file($tmp),
			'sound' => file_get_contents($tmp),
		];
	}

	private function _resolveDescription(AlarmDB $db, string $prefix) : int|null
	{
		if (($id = trim($_POST[$prefix.'_description_id'] ?? '')) !== '')
			return (int) $id;
		$label = trim($_POST[$prefix.'_description_label'] ?? '');
		$contents = trim($_POST[$prefix.'_description'] ?? '');
		if ($label === '' || $contents === '')
			return null;
		return $db->createDescription(['label' => $label, 'contents' => $contents]);
	}

	private function _resolveDisplay(AlarmDB $db) : int|null
	{
		if (($id = trim($_POST['display_id'] ?? '')) !== '')
			return (int) $id;
		$label = trim($_POST['display_label'] ?? '');
		if ($label === '')
			return null;
		if (($description_id = $this->_resolveDescription($db, 'display')) === null)
			return null;
		return $db->createDisplay(['label' => $label, 'description_id' => $description_id]);
	}

	private function _resolveEmail(AlarmDB $db) : int|null
	{
		if (($id = trim($_POST['email_id'] ?? '')) !== '')
			return (int) $id;
		$label = trim($_POST['email_label'] ?? '');
		$summary = trim($_POST['email_summary'] ?? '');
		if ($label === '' || $summary === '')
			return null;
		if (($description_id = $this->_resolveDescription($db, 'email')) === null)
			return null;
		$email_id = $db->createEmail(['label' => $label, 'summary' => $summary, 'description_id' => $description_id]);
		foreach (self::_recipientIds() as $recipient_id)
			$db->addEmailRecipient($email_id, $recipient_id);
		return $email_id;
	}

	private function _resolveAudio(AlarmDB $db) : int|null
	{
		if (($id = trim($_POST['audio_id'] ?? '')) !== '')
			return (int) $id;
		if (($audio = self::_uploadedSound()) === null)
			return null;
		if (($label = trim($_POST['audio_label'] ?? '')) !== '')
			$audio['label'] = $label;
		return $db->createAudio($audio);
	}

	private function _checkReferences(AlarmDB $db) : ViewInterface|null
	{
		$references = [
			'display_id' => 'getDisplay',
			'email_id' => 'getEmail',
			'audio_id' => 'getAudio',
			'display_description_id' => 'getDescription',
			'email_description_id' => 'getDescription',
		];
		foreach ($references as $field => $getter)
		{
			$id = trim($_POST[$field] ?? '');
			if ($id !== '' && $db->$getter((int) $id) === false)
				return new BadRequest(_::_('wrong_identifier'));
		}
		foreach (self::_recipientIds() as $recipient_id)
			if ($db->getRecipient($recipient_id) === false)
				return new BadRequest(_::_('wrong_identifier'));
		return null;
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		$db = new AlarmDB(_::getPDO());
		return new Json($db->getAlarms());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		$alarm = $db->getAlarm((int) $matches[1]);
		if ($alarm === false)
			return new NotFound();
		if ($alarm['display_id'] !== null)
		{
			$display = $db->getDisplay((int) $alarm['display_id']);
			$alarm['display_label'] = $display['label'];
			$description = $db->getDescription((int) $display['description_id']);
			$alarm['display_description_id'] = (int) $display['description_id'];
			$alarm['display_description_label'] = $description['label'];
			$alarm['display_description'] = $description['contents'];
		}
		if ($alarm['email_id'] !== null)
		{
			$email = $db->getEmail((int) $alarm['email_id']);
			$alarm['email_label'] = $email['label'];
			$alarm['email_summary'] = $email['summary'];
			$description = $db->getDescription((int) $email['description_id']);
			$alarm['email_description_id'] = (int) $email['description_id'];
			$alarm['email_description_label'] = $description['label'];
			$alarm['email_description'] = $description['contents'];
			$alarm['recipients'] = array_map(fn($r) => (int) $r['recipient_id'], $db->getEmailRecipients((int) $alarm['email_id']));
		}
		if ($alarm['audio_id'] !== null)
			$alarm['audio_label'] = $db->getAudio((int) $alarm['audio_id'])['label'];
		$alarm['has_audio'] = $alarm['audio_id'] !== null;
		return new Json($alarm);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_alarmData();
		if ($data['label'] === '')
			return new BadRequest(_::_('title_required'));
		if (($message = Sound::checkSound()) !== null)
			return new BadRequest(_::_($message));
		$pdo = _::getPDO();
		$db = new AlarmDB($pdo);
		if (($invalid = $this->_checkReferences($db)) !== null)
			return $invalid;
		$pdo->beginTransaction();
		try
		{
			$data['display_id'] = $this->_resolveDisplay($db);
			$data['email_id'] = $this->_resolveEmail($db);
			$data['audio_id'] = $this->_resolveAudio($db);
			if ($data['display_id'] === null && $data['email_id'] === null && $data['audio_id'] === null)
			{
				$pdo->rollBack();
				return new BadRequest(_::_('alarm_action_required'));
			}
			$alarm_id = $db->createAlarm($data);
			$pdo->commit();
		}
		catch (\Exception $e)
		{
			$pdo->rollBack();
			throw $e;
		}
		return new Json($alarm_id);
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$alarm_id = (int) $matches[1];
		$pdo = _::getPDO();
		$db = new AlarmDB($pdo);
		if ($db->getAlarm($alarm_id) === false)
			return new NotFound();
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = self::_alarmData();
		if ($data['label'] === '')
			return new BadRequest(_::_('title_required'));
		if (($message = Sound::checkSound()) !== null)
			return new BadRequest(_::_($message));
		if (($invalid = $this->_checkReferences($db)) !== null)
			return $invalid;
		$pdo->beginTransaction();
		try
		{
			// only the alarm's references change ; the components are independent and kept
			$data['display_id'] = $this->_resolveDisplay($db);
			$data['email_id'] = $this->_resolveEmail($db);
			$data['audio_id'] = $this->_resolveAudio($db);
			if ($data['display_id'] === null && $data['email_id'] === null && $data['audio_id'] === null)
			{
				$pdo->rollBack();
				return new BadRequest(_::_('alarm_action_required'));
			}
			$db->updateAlarm($alarm_id, $data);
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
		$alarm_id = (int) $matches[1];
		$db = new AlarmDB(_::getPDO());
		if ($db->getAlarm($alarm_id) === false)
			return new NotFound();
		$db->deleteAlarm($alarm_id);
		return new Ok();
	}

	protected function _get_forCalendar()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_READ)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		return new Json($db->getCalendarAlarms($calendar_id));
	}

	protected function _post_attachCalendar()
	{
		if (!preg_match('|^(\d+)/(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_ACL)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		if ($db->getAlarm((int) $matches[2]) === false)
			return new NotFound();
		$db->attachToCalendar($calendar_id, (int) $matches[2]);
		return new Ok();
	}

	protected function _post_detachCalendar()
	{
		if (!preg_match('|^(\d+)/(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_ACL)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		$db->detachFromCalendar($calendar_id, (int) $matches[2]);
		return new Ok();
	}

	protected function _get_forEvent()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$event_id = (int) $matches[1];
		$event = (new CalendarDB(_::getPDO()))->getEvent($event_id);
		if ($event === false)
			return new NotFound();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization((int) $event['calendar_id'], _::getUserData()) < ACL::CAL_READ)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		return new Json($db->getEventAlarms($event_id));
	}

	protected function _post_attachEvent()
	{
		if (!preg_match('|^(\d+)/(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$event_id = (int) $matches[1];
		$event = (new CalendarDB(_::getPDO()))->getEvent($event_id);
		if ($event === false)
			return new NotFound();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization((int) $event['calendar_id'], _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		if ($db->getAlarm((int) $matches[2]) === false)
			return new NotFound();
		$db->attachToEvent($event_id, (int) $matches[2]);
		return new Ok();
	}

	protected function _post_detachEvent()
	{
		if (!preg_match('|^(\d+)/(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$event_id = (int) $matches[1];
		$event = (new CalendarDB(_::getPDO()))->getEvent($event_id);
		if ($event === false)
			return new NotFound();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization((int) $event['calendar_id'], _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();
		$db = new AlarmDB(_::getPDO());
		$db->detachFromEvent($event_id, (int) $matches[2]);
		return new Ok();
	}
}
