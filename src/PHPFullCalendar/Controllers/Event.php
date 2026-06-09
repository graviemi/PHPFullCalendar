<?php

namespace PHPFullCalendar\Controllers;

use DateTimeImmutable,
	DateTimeZone,
	DateInterval,
	oTools\Ics\Parser as IcsParser,
	oTools\Ics\Exception as IcsException,
	oTools\Ics\Component as IcsComponent,
	PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ics,
	PHPFullCalendar\Views\FreeBusy,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound,
	PHPFullCalendar\Views\NotModified;

class Event extends ControllerAbstract
{
	protected static array $eventFields = [
		'title', 'description', 'rrule', 'location', 'position', 'color', 'url'
	];
	protected static array $validation = [
		'title' => ['/^.{3,255}$/', 'title_required'],
		'start' => [
			'/^\d{4,4}-\d{2,2}-\d{2,2}T\d{2,2}:\d{2,2}:00\.000Z$/',
			'start_date_required',
		],
		'end' => [
			'/^(|\d{4,4}-\d{2,2}-\d{2,2}T\d{2,2}:\d{2,2}:00\.000Z)$/',
			'wrong_end_date_format',
		],
		'url' => ['/^.{0,2000}$/', 'url_too_long'],
		'color' => ['/^#[0-9a-f]{6}$/','unknown_color'],
		'description' => ['/^.{0,65535}$/s', 'description_too_long'],
	];

	protected function _control(array $raw_data): string|null
	{
		foreach (self::$validation as $key => $rules)
		{
			if (!preg_match($rules[0], $raw_data[$key] ?? ""))
				return $rules[1];
		}
		return null;
	}

	protected static function _get_start_and_duration(string $start, string $end) : array
	{
		$start_ts = floor((new DateTimeImmutable($start))->getTimestamp() / 60);
		if ($end !== "")
		{
			$end_ts = floor((new DateTimeImmutable($end))->getTimestamp() / 60);
			return [$start_ts, $end_ts - $start_ts];
		}
		return [$start_ts, 0];
	}

	protected function _post_create()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();
		if (($message = self::_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = [];
		foreach (self::$eventFields as $name)
			$data[$name] = ($_POST[$name] === '')?null:$_POST[$name];
		[$data['start'],$data['duration']] = self::_get_start_and_duration($_POST['start'],$_POST['end']);
		if ($data['duration'] < 0)
			return new BadRequest(_::_('end_after_start_required'));
		if (($_POST['rrule_active'] ?? '0') !== '1')
			$data['rrule'] = null;
		$data['until'] = self::_computeUntil($data['rrule'], $data['start']);
		$db = new CalendarDB(_::getPDO());
		return new Json($db->createEvent($calendar_id, $data));
	}

	protected function _get_read()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false)
			return new NotFound();

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($event['calendar_id'],_::getUserData(),) < ACL::CAL_READ)
			return _::denyAccess();

		$startSec = $event['start'] * 60;
		return new Json([
			'event_id' => $event['event_id'],
			'calendar_id' => $event['calendar_id'],
			'title' => $event['title'],
			'start' => gmdate('Y-m-d\TH:i:s\Z', $startSec),
			'end' => $event['duration'] !== null ? gmdate('Y-m-d\TH:i:s\Z',$startSec + $event['duration'] * 60) : null,
			'location' => $event['location'],
			'position' => $event['position'],
			'description' => $event['description'],
			'color' => $event['color'],
			'url' => $event['url'],
			'rrule' => $event['rrule']
		]);
	}

	protected function _post_update()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$event_id = (int) $matches[1];
		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false)
			return new NotFound();
		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($event['calendar_id'],_::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();
		if (($message = self::_control($_POST)) !== null)
			return new BadRequest(_::_($message));
		$data = [];
		foreach (self::$eventFields as $name)
			$data[$name] = ($_POST[$name] === '')?null:$_POST[$name];
		[$data['start'],$data['duration']] = self::_get_start_and_duration($_POST['start'],$_POST['end']);
		if ($data['duration'] < 0)
			return new BadRequest(_::_('end_after_start_required'));
		if (($_POST['rrule_active'] ?? '0') !== '1')
			$data['rrule'] = null;
		$data['until'] = self::_computeUntil($data['rrule'], $data['start']);
		$db->updateEvent($event_id, $data);
		return new Ok();
	}

	protected function _get_delete()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches)) {
			return new NotFound();
		}
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false)
			return new NotFound();

		$acl = new ACL(_::getPDO());
		if (
			$acl->getCalendarAuthorization(
				$event['calendar_id'],
				_::getUserData(),
			) < ACL::CAL_WRITE
		) {
			return _::denyAccess();
		}

		$db->deleteEvent($event_id);
		return new Ok();
	}

	protected function _get_list()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_FREE_BUSY)
			return _::denyAccess();

		try
		{
			$start = floor((new DateTimeImmutable($_GET['start']))->getTimestamp() / 60);
			$end = floor((new DateTimeImmutable($_GET['end']))->getTimestamp() / 60);
		}
		catch (Exception $e)
		{
			return new BadRequest(_::_('start_and_end_required'));
		}

		$db = new CalendarDB(_::getPDO());
		$calendar = $db->getCalendar($calendar_id);
		$lastModified = max((int) $calendar['modified'], $db->getLastModified($calendar_id, $start, $end));
		$ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? (int) strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;

		if ($ifModifiedSince > 0 && $lastModified <= $ifModifiedSince)
			return new NotModified();

		$events = $db->getEvents($calendar_id, $start, $end);
		$endDt = new DateTimeImmutable('@'.($end * 60));
		$result = [];
		foreach ($events as $event) {
			$startSec = $event['start'] * 60;
			$entry = [
				'id' => $event['event_id'],
				'title' => $event['title'],
			];
			if ($event['rrule'] !== null && $event['rrule'] !== '') {
				try {
					$rruleObj = new \RRule\RRule($event['rrule'], new DateTimeImmutable('@'.$startSec));
					$beginDt = new DateTimeImmutable('@'.(($start - ($event['duration'] ?? 0)) * 60));
					if (empty($rruleObj->getOccurrencesBetween($beginDt, $endDt, 1)))
						continue;
				} catch (\Exception $e) {
					continue;
				}
				$entry['rrule'] = 'DTSTART:'.gmdate('Ymd\THis\Z', $startSec).chr(10).'RRULE:'.$event['rrule'];
				if ($event['duration'] !== null)
					$entry['duration'] = sprintf('%02d:%02d', intdiv($event['duration'], 60), $event['duration'] % 60);
			} else {
				$entry['start'] = gmdate('Y-m-d\TH:i:s\Z', $startSec);
				if ($event['duration'] !== null)
					$entry['end'] = gmdate('Y-m-d\TH:i:s\Z', $startSec + $event['duration'] * 60);
			}
			if ($event['color'] !== null)
				$entry['color'] = $event['color'];
			if ($event['url'] !== null)
				$entry['url'] = $event['url'];
			$extendedProps = [];
			if ($event['description'] !== null)
				$extendedProps['description'] = $event['description'];
			if ($event['location'] !== null)
				$extendedProps['location'] = $event['location'];
			if ($event['position'] !== null)
				$extendedProps['position'] = $event['position'];
			if ($extendedProps)
				$entry['extendedProps'] = $extendedProps;
			$result[] = $entry;
		}

		return new Json($result, $lastModified ?: null);
	}

	protected function _get_ics()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters ?? "", $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		$auth = $acl->getCalendarAuthorization($calendar_id, _::getUserData());
		if ($auth < ACL::CAL_FREE_BUSY)
			return _::denyAccess();

		$db = new CalendarDB(_::getPDO());
		$calendar = $db->getCalendar($calendar_id);
		if ($calendar === false)
			return new NotFound();

		$six_month = new DateInterval('P6M');
		try
		{
			$start = isset($_GET['start']) ? floor((new DateTimeImmutable($_GET['start']))->getTimestamp() / 60) : floor((new DateTimeImmutable())->sub($six_month)->getTimestamp() / 60);
			$end   = isset($_GET['end'])   ? floor((new DateTimeImmutable($_GET['end']))->getTimestamp()   / 60) : floor((new DateTimeImmutable())->add($six_month)->getTimestamp() / 60);
		}
		catch (\Exception $e)
		{
			return new BadRequest(_::_('start_and_end_required'));
		}

		$lastModified = max((int) $calendar['modified'], $db->getLastModified($calendar_id, $start, $end));
		$ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? (int) strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
		if ($ifModifiedSince > 0 && $lastModified <= $ifModifiedSince)
			return new NotModified();

		$events = $db->getEvents($calendar_id, $start, $end);
		if ($auth === ACL::CAL_FREE_BUSY)
			return new FreeBusy($calendar['name'], $events, $lastModified ?: null);
		return new Ics($calendar['name'], $events, $lastModified ?: null);
	}

	protected function _put_ics()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters ?? '', $matches))
			return new NotFound();
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if ($acl->getCalendarAuthorization($calendar_id, _::getUserData()) < ACL::CAL_WRITE)
			return _::denyAccess();

		$body = file_get_contents('php://input');
		if ($body === false || trim($body) === '')
			return new BadRequest(_::_('empty_request_body'));

		try {
			$vcalendar = (new IcsParser())->parse($body);
		} catch (IcsException $e) {
			return new BadRequest($e->getMessage());
		}

		$db = new CalendarDB(_::getPDO());
		$ids = [];

		foreach ($vcalendar->getComponents('VEVENT') as $vevent)
		{
			$id = $this->_importVEvent($db, $calendar_id, $vevent);
			if ($id !== null)
				$ids[] = $id;
		}

		return new Json(['imported' => count($ids), 'ids' => $ids]);
	}

	private function _importVEvent(CalendarDB $db, int $calendar_id, IcsComponent $vevent) : int|null
	{
		$title = $vevent->getProperty('SUMMARY')?->getRawValue();
		if ($title === null || $title === '')
			return null;

		$dtstart = $vevent->getProperty('DTSTART');
		if ($dtstart === null)
			return null;
		$startUtc = $dtstart->getUTC();
		if ($startUtc === null)
			return null;
		$start = (int) floor($startUtc->getTimestamp() / 60);

		$duration = null;
		$dtend = $vevent->getProperty('DTEND');
		if ($dtend !== null && ($endUtc = $dtend->getUTC()) !== null)
			$duration = (int) (($endUtc->getTimestamp() - $startUtc->getTimestamp()) / 60);
		elseif (($durationProp = $vevent->getProperty('DURATION')) !== null)
			$duration = self::_parseDuration($durationProp->getRawValue());

		$rrule = $vevent->getProperty('RRULE')?->getRawValue();
		$until = self::_computeUntil($rrule, $start);

		return $db->createEvent($calendar_id, [
			'start'    => $start,
			'duration' => $duration,
			'until'    => $until,
			'rrule'    => $rrule,
			'title'       => $title,
			'description' => $vevent->getProperty('DESCRIPTION')?->getRawValue(),
			'location'    => $vevent->getProperty('LOCATION')?->getRawValue(),
			'position'    => null,
			'url'         => $vevent->getProperty('URL')?->getRawValue(),
			'color'       => $vevent->getProperty('COLOR')?->getRawValue(),
		]);
	}

	private static function _parseDuration(string $value) : int|null
	{
		// P[n]W
		if (preg_match('/^P(\d+)W$/', $value, $m))
			return (int) $m[1] * 7 * 1440;
		// P[n]DT[n]H[n]M
		if (preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:\d+S)?)?$/', $value, $m))
			return ((int) ($m[1] ?? 0)) * 1440 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
		return null;
	}

	private static function _computeUntil(string|null $rrule, int $startMinutes) : int|null
	{
		if ($rrule === null)
			return null;
		try {
			$rruleObj = new \RRule\RRule($rrule, new DateTimeImmutable('@'.($startMinutes * 60)));
			if ($rruleObj->isInfinite())
				return 0;
			$last = $rruleObj[count($rruleObj) - 1];
			return (int) floor($last->getTimestamp() / 60);
		} catch (\Exception $e) {
			return 0;
		}
	}

	private static function _parseUntil(string $value) : int|null
	{
		if (strlen($value) === 8)
			$dt = DateTimeImmutable::createFromFormat('Ymd', $value, new DateTimeZone('UTC'));
		elseif (str_ends_with($value, 'Z'))
			$dt = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
		else
			$dt = DateTimeImmutable::createFromFormat('Ymd\THis', $value, new DateTimeZone('UTC'));
		return $dt === false ? null : (int) floor($dt->getTimestamp() / 60);
	}
}
