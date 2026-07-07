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

class Sound extends ControllerAbstract
{
	protected static array $validation = [
		'label' => ['/^.{1,255}$/', 'label_required'],
	];

	private function _checkAlarmRight() : ViewInterface|null
	{
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_ALARM) === 0)
			return _::denyAccess();
		return null;
	}

	public static function checkSound() : string|null
	{
		if (! isset($_FILES['sound']) || $_FILES['sound']['error'] === UPLOAD_ERR_NO_FILE)
			return null;
		if ($_FILES['sound']['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($_FILES['sound']['tmp_name']))
			return 'sound_upload_failed';
		if ($_FILES['sound']['size'] > 65535)
			return 'sound_too_large';
		$mimetype = (new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['sound']['tmp_name']);
		if (! str_starts_with($mimetype, 'audio/') && $mimetype !== 'application/ogg')
			return 'wrong_sound_type';
		return null;
	}

	private static function _uploadedSound() : array|null
	{
		if (! isset($_FILES['sound']) || ! is_uploaded_file($_FILES['sound']['tmp_name']))
			return null;
		$tmp = $_FILES['sound']['tmp_name'];
		return [
			'mimetype' => (new \finfo(FILEINFO_MIME_TYPE))->file($tmp),
			'sound' => file_get_contents($tmp),
		];
	}

	protected function _get_list()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		return new Json((new AlarmDB(_::getPDO()))->getAudios());
	}

	protected function _get_read()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$audio = (new AlarmDB(_::getPDO()))->getAudio((int) $matches[1]);
		if ($audio === false)
			return new NotFound();
		// never expose the blob
		unset($audio['sound']);
		return new Json($audio);
	}

	protected function _post_create()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($message = self::checkSound()) !== null)
			return new BadRequest(_::_($message));
		$label = trim($_POST['label'] ?? '');
		if ($label === '')
			return new BadRequest(_::_('label_required'));
		if (($audio = self::_uploadedSound()) === null)
			return new BadRequest(_::_('sound_required'));
		$audio['label'] = $label;
		return new Json((new AlarmDB(_::getPDO()))->createAudio($audio));
	}

	protected function _post_update()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$db = new AlarmDB(_::getPDO());
		if ($db->getAudio((int) $matches[1]) === false)
			return new NotFound();
		if (($message = $this->_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		if (($message = self::checkSound()) !== null)
			return new BadRequest(_::_($message));
		$label = trim($_POST['label'] ?? '');
		if ($label === '')
			return new BadRequest(_::_('label_required'));
		$data = ['label' => $label];
		// the sound itself is only replaced when a new file is uploaded
		if (($audio = self::_uploadedSound()) !== null)
			$data += $audio;
		$db->updateAudio((int) $matches[1], $data);
		return new Ok();
	}

	protected function _get_delete()
	{
		if (($denied = $this->_checkAlarmRight()) !== null)
			return $denied;
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		(new AlarmDB(_::getPDO()))->deleteAudio((int) $matches[1]);
		return new Ok();
	}
}
