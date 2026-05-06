<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Views\ViewInterface,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Authorization extends ControllerAbstract
{
	protected function _get_global() : ViewInterface
	{
		$acl = new ACL(_::getPDO());
		return new Json($acl->getGlobalAuthorization(_::getUserData()));
	}

	protected function _get_calendar() : ViewInterface
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new \PHPFullCalendar\Views\NotFound('unknown calendar ID "%s"',$this->parameters);
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		return new Json($acl->getCalendarAuthorization($calendar_id,_::getUserData()));
	}

	protected function _get_globalacl() : ViewInterface
	{
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_ACL) === 0)
			return _::denyAccess();
		return new Json($acl->getGlobalACLList());
	}

	protected function _post_globalacl() : ViewInterface
	{
		$userData = _::getUserData();
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization($userData) & ACL::GR_ACL) === 0)
			return _::denyAccess();
		$source = $_POST['source'] ?? '';
		$identifier = trim($_POST['identifier'] ?? '');
		$type = $_POST['type'] ?? '';
		$authorization = (int)($_POST['authorization'] ?? 0);
		if (($identifier === $userData['user_id']) && ($type === 'user'))
			return new BadRequest(_::_('should_not_modify_own_acl'));
		if (! in_array($source,_::$sources))
			return new BadRequest(_::_('unknown_source'));
		if (strlen($identifier) === 0 || strlen($identifier) > 255)
			return new BadRequest(_::_('identifier_must_not_be_empty'));
		if (! in_array($type, ['user', 'group', 'special']))
			return new BadRequest(_::_('unknown_type'));
		if ($authorization < 0 || $authorization > 31)
			return new BadRequest(_::_('wrong_authorization'));

		$acl->setGlobalAuthorization($source, $identifier, $type, $authorization);
		return new Ok();
	}

	protected function _delete_globalacl() : ViewInterface
	{
		$userData = _::getUserData();
		$acl = new ACL(_::getPDO());
		if (($acl->getGlobalAuthorization($userData) & ACL::GR_ACL) === 0)
			return _::denyAccess();
		$body = [];
		parse_str(file_get_contents('php://input'), $body);
		$source = $body['source'] ?? '';
		$identifier = $body['identifier'] ?? '';
		$type = $body['type'] ?? '';
		if (($identifier === $userData['user_id']) && ($type === 'user'))
			return new BadRequest(_::_('should_not_modify_own_acl'));
		if (! in_array($source,_::$sources))
			return new BadRequest(_::_('unknown_source'));
		if (strlen($identifier) === 0 || strlen($identifier) > 255)
			return new BadRequest(_::_('identifier_must_not_be_empty'));
		if (! in_array($type, ['user', 'group', 'special']))
			return new BadRequest(_::_('unknown_type'));
		$acl->deleteGlobalACL($source, $identifier, $type);
		return new Ok();
	}

	protected function _get_resource() : void
	{
	}

	protected function _get_calendaracl() : ViewInterface
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_ACL)
			return _::denyAccess();
		return new Json($acl->getCalendarACLList($calendar_id));
	}

	protected function _post_calendaracl() : ViewInterface
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$userData = _::getUserData();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, $userData) < ACL::CAL_ACL)
			return _::denyAccess();
		$source = $_POST['source'] ?? '';
		$identifier = trim($_POST['identifier'] ?? '');
		$type = $_POST['type'] ?? '';
		$level = (int)($_POST['authorization'] ?? 0);
		if (($identifier === $userData['user_id']) && ($type === 'user'))
			return new BadRequest(_::_('should_not_modify_own_acl'));
		if ($identifier === '')
			return new BadRequest(_::_('identifier_must_not_be_empty'));
		if (! in_array($type, ['user', 'group', 'special']))
			return new BadRequest(_::_('unknown_type'));
		if ($level < 1 || $level > 4)
			return new BadRequest(_::_('wrong_authorization'));

		$acl->setCalendarAuthorization($calendar_id, $source, $identifier, $type, $level);
		return new Ok();
	}

	protected function _delete_calendaracl() : ViewInterface
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$userData = _::getUserData();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, $userData) < ACL::CAL_ACL)
			return _::denyAccess();
		$body = [];
		parse_str(file_get_contents('php://input'), $body);
		$source = $body['source'] ?? '';
		$identifier = $body['identifier'] ?? '';
		$type = $body['type'] ?? '';
		if (($identifier === $userData['user_id']) && ($type === 'user'))
			return new BadRequest(_::_('should_not_modify_own_acl'));
		if ($identifier === '')
			return new BadRequest(_::_('identifier_must_not_be_empty'));
		if (! in_array($type, ['user', 'group', 'special']))
			return new BadRequest(_::_('unknown_type'));
		$acl->deleteCalendarACL($calendar_id, $source, $identifier, $type);
		return new Ok();
	}
}
