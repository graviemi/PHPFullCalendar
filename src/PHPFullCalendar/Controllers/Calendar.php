<?php

namespace PHPFullCalendar\Controllers;

use ArrayObject,
	oTools\Sessions\Session,
	PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Calendar extends ControllerAbstract
{

	public function _post_create()
	{
		$acl = new ACL(_::getPDO());
		if (! ($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_CAL_CREATE))
			return _::denyAccess();
		$name = $_POST['calendarName'] ?? null;
		if ($name === null)
			return new BadRequest('name is required');
		$db = new CalendarDB(_::getPDO());
		$calendar_id = $db->createCalendar($name, $_POST['calendarDescription'] ?? null);
		$data = _::getUserData();
		$acl->setCalendarAuthorization($calendar_id, $data['source'] ?? '', $data['user_id'] ?? '', 'user', ACL::CAL_ACL);
		return new Json(['calendar_id' => $calendar_id]);
	}

	public function _get_catalog()
	{
		$db = new CalendarDB(_::getPDO());
		return new Json($db->getAuthorizedCalendars(_::getUserData()));
	}

	public function _get_read()
	{
		if (! preg_match('|^/(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$db = new CalendarDB(_::getPDO());
		$calendar = $db->getCalendar((int) $matches[1]);
		if ($calendar === false)
			return new NotFound();
		return new Json($calendar);
	}

	public function _post_update()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$name = $_POST['name'] ?? null;
		if ($name === null)
			return new BadRequest('name is required');
		$db = new CalendarDB(_::getPDO());
		if ($db->getCalendar((int) $matches[1]) === false)
			return new NotFound();
		$db->updateCalendar((int) $matches[1], $name, $_POST['description'] ?? null);
		return new Ok();
	}

	public function _get_delete()
	{
		$acl = new ACL(_::getPDO());
		if (! ($acl->getGlobalAuthorization(_::getUserData()) & ACL::GR_CAL_DESTROY))
			return _::denyAccess();
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$db = new CalendarDB(_::getPDO());
		if ($db->getCalendar((int) $matches[1]) === false)
			return new NotFound();
		$db->deleteCalendar((int) $matches[1]);
		return new Ok();
	}

}
