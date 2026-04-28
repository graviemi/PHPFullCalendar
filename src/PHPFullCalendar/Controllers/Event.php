<?php

namespace PHPFullCalendar\Controllers;

use PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ics,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Event extends ControllerAbstract
{

	public function _post_create()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();

		$title = $_POST['eventTitle'] ?? null;
		$startStr = $_POST['eventStart'] ?? null;
		if ($title === null || $startStr === null)
			return new BadRequest(_::_('title_and_start_required'));

		$start = (int)(strtotime($startStr) / 60);

		$duration = null;
		if (! empty($_POST['eventEnd']))
		{
			$end = (int)(strtotime($_POST['eventEnd']) / 60);
			$duration = $end - $start;
		}

		$db = new CalendarDB(_::getPDO());
		$event_id = $db->createEvent(
			$calendar_id,
			$start,
			$title,
			$duration,
			null,
			null,
			$_POST['eventDescription'] ?? null,
			$_POST['eventUrl'] ?? null
		);

		return new Json(['event_id' => $event_id]);
	}

	public function _post_update()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false)
			return new NotFound();

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($event['calendar_id'], _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();

		$title = $_POST['eventTitle'] ?? null;
		$startStr = $_POST['eventStart'] ?? null;
		if ($title === null || $startStr === null)
			return new BadRequest(_::_('title_and_start_required'));

		$start = (int)(strtotime($startStr) / 60);

		$duration = null;
		if (! empty($_POST['eventEnd']))
		{
			$end = (int)(strtotime($_POST['eventEnd']) / 60);
			$duration = $end - $start;
		}

		$db->updateEvent(
			$event_id,
			$start,
			$title,
			$duration,
			null,
			null,
			$_POST['eventDescription'] ?? null,
			$_POST['eventUrl'] ?? null
		);

		return new Ok();
	}

	public function _get_delete()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false)
			return new NotFound();

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($event['calendar_id'], _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();

		$db->deleteEvent($event_id);
		return new Ok();
	}

	public function _get_list()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_FREE_BUSY)
			return _::denyAccess();

		$start = isset($_GET['start']) ? (int)(strtotime($_GET['start']) / 60) : null;
		$end   = isset($_GET['end'])   ? (int)(strtotime($_GET['end'])   / 60) : null;
		if ($start === null || $end === null)
			return new BadRequest('start and end parameters are required');

		$db = new CalendarDB(_::getPDO());
		$events = $db->getEvents($calendar_id, $start, $end);

		$result = [];
		foreach ($events as $event)
		{
			$startSec = $event['start'] * 60;
			$entry = [
				'id'    => $event['event_id'],
				'title' => $event['title'],
				'start' => gmdate('Y-m-d\TH:i:s\Z', $startSec),
			];
			if ($event['duration'] !== null)
				$entry['end'] = gmdate('Y-m-d\TH:i:s\Z', $startSec + $event['duration'] * 60);
			if ($event['url'] !== null)
				$entry['url'] = $event['url'];
			if ($event['description'] !== null)
				$entry['extendedProps'] = ['description' => $event['description']];
			$result[] = $entry;
		}

		return new Json($result);
	}

	public function _get_ics()
	{
		if (! preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_FREE_BUSY)
			return _::denyAccess();

		_::debug('acl ok');
		$db = new CalendarDB(_::getPDO());
		$calendar = $db->getCalendar($calendar_id);
		if ($calendar === false)
			return new NotFound();
		_::debug('%s',print_r($calendar,true));

		$startParam = $_GET['start'] ?? null;
		$endParam   = $_GET['end']   ?? null;

		if ($startParam !== null && $endParam !== null)
		{
			$start = (int)(strtotime($startParam) / 60);
			$end   = (int)(strtotime($endParam)   / 60);
		}
		elseif ($startParam === null && $endParam === null)
		{
			// mois en cours
			$start = (int)(mktime(0, 0, 0, (int)date('n'),     1) / 60);
			$end   = (int)(mktime(0, 0, 0, (int)date('n') + 1, 1) / 60);
		}
		elseif ($startParam !== null)
		{
			// start fourni : end = fin du mois suivant celui de start
			$startTs = strtotime($startParam);
			$start = (int)($startTs / 60);
			$end   = (int)(mktime(0, 0, 0, (int)date('n', $startTs) + 2, 1, (int)date('Y', $startTs)) / 60);
		}
		else
		{
			// end fourni : start = début du mois précédant celui de end
			$endTs = strtotime($endParam);
			$end   = (int)($endTs / 60);
			$start = (int)(mktime(0, 0, 0, (int)date('n', $endTs) - 1, 1, (int)date('Y', $endTs)) / 60);
		}

		$events = $db->getEvents($calendar_id, $start, $end);
		return new Ics($calendar['name'], $events);
	}

}
