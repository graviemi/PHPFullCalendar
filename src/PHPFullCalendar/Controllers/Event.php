<?php

namespace PHPFullCalendar\Controllers;

use DateTimeImmutable,
	DateInterval,
	PHPFullCalendar\_,
	PHPFullCalendar\Database\ACL,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Views\Json,
	PHPFullCalendar\Views\Ics,
	PHPFullCalendar\Views\FreeBusy,
	PHPFullCalendar\Views\Ok,
	PHPFullCalendar\Views\BadRequest,
	PHPFullCalendar\Views\NotFound;

class Event extends ControllerAbstract
{
	protected static array $validation = [
		"title" => ['/^.{3,255}$/', "title_required"],
		"start" => [
			'/^\d{4,4}-\d{2,2}-\d{2,2}T\d{2,2}:\d{2,2}:00\.000Z$/',
			"start_date_required",
		],
		"end" => [
			'/^(|\d{4,4}-\d{2,2}-\d{2,2}T\d{2,2}:\d{2,2}:00\.000Z)$/',
			"wrong_end_date_format",
		],
		"url" => ['/^.{0,2000}$/', "url_too_long"],
		"description" => ['/^.{0,65535}$/s', "description_too_long"],
	];

	protected function _control(array $raw_data): string|null
	{
		foreach (self::$validation as $key => $rules) {
			if (!preg_match($rules[0], $raw_data[$key] ?? "")) {
				return $rules[1];
			}
		}
		return null;
	}

	protected static function _get_start_and_duration(
		string $start,
		string $end,
	): array {
		$start_ts = floor((new DateTimeImmutable($start))->getTimestamp() / 60);
		if ($end !== "") {
			$end_ts = floor((new DateTimeImmutable($end))->getTimestamp() / 60);
			return [$start_ts, $end_ts - $start_ts];
		}
		return [$start_ts, null];
	}

	protected function _post_create()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches)) {
			return new NotFound();
		}
		$calendar_id = (int) $matches[1];
		$acl = new ACL(_::getPDO());
		if (
			$acl->getCalendarAuthorization($calendar_id, _::getUserData()) <
			ACL::CAL_WRITE
		) {
			return _::denyAccess();
		}

		_::debug("post: %s", print_r($_POST, true));
		if (($message = self::_control($_POST)) !== null) {
			return new BadRequest(_::_($message));
		}
		[$start, $duration] = self::_get_start_and_duration(
			$_POST["start"],
			$_POST["end"],
		);
		if ($duration !== null && $duration < 1) {
			return new BadRequest(_::_("end_after_start_required"));
		}

		$db = new CalendarDB(_::getPDO());
		$event_id = $db->createEvent(
			$calendar_id,
			$start,
			$_POST["title"],
			$duration,
			null,
			null,
			$_POST["description"] ?? null,
			$_POST["url"] ?? null,
		);

		return new Json($event_id);
	}

	protected function _get_read()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches)) {
			return new NotFound();
		}
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false) {
			return new NotFound();
		}

		$acl = new ACL(_::getPDO());
		if (
			$acl->getCalendarAuthorization(
				$event["calendar_id"],
				_::getUserData(),
			) < ACL::CAL_READ
		) {
			return _::denyAccess();
		}

		$startSec = $event["start"] * 60;
		return new Json([
			"event_id" => $event["event_id"],
			"calendar_id" => $event["calendar_id"],
			"title" => $event["title"],
			"start" => gmdate("Y-m-d\TH:i:s\Z", $startSec),
			"end" =>
				$event["duration"] !== null
					? gmdate(
						"Y-m-d\TH:i:s\Z",
						$startSec + $event["duration"] * 60,
					)
					: null,
			"description" => $event["description"],
			"url" => $event["url"],
		]);
	}

	protected function _post_update()
	{
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches)) {
			return new NotFound();
		}
		$event_id = (int) $matches[1];

		$db = new CalendarDB(_::getPDO());
		$event = $db->getEvent($event_id);
		if ($event === false) {
			return new NotFound();
		}

		$acl = new ACL(_::getPDO());
		if (
			$acl->getCalendarAuthorization(
				$event["calendar_id"],
				_::getUserData(),
			) < ACL::CAL_WRITE
		) {
			return _::denyAccess();
		}

		if (($message = self::_control($_POST)) !== null) {
			return new BadRequest(_::_($message));
		}

		[$start, $duration] = self::_get_start_and_duration(
			$_POST["start"],
			$_POST["end"],
		);
		if ($duration !== null && $duration < 1) {
			return new BadRequest(_::_("end_after_start_required"));
		}

		$db->updateEvent(
			$event_id,
			$start,
			$_POST["title"],
			$duration,
			null,
			null,
			$_POST["description"] ?? null,
			$_POST["url"] ?? null,
		);

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
				$event["calendar_id"],
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
		if (!preg_match('|^(\d+)$|', $this->parameters, $matches)) {
			return new NotFound();
		}
		$calendar_id = (int) $matches[1];

		$acl = new ACL(_::getPDO());
		if (
			$acl->getCalendarAuthorization($calendar_id, _::getUserData()) <
			ACL::CAL_FREE_BUSY
		)
			return _::denyAccess();

		$start = isset($_GET["start"])
			? (int) (strtotime($_GET["start"]) / 60)
			: null;
		$end = isset($_GET["end"])
			? (int) (strtotime($_GET["end"]) / 60)
			: null;
		if ($start === null || $end === null) {
			return new BadRequest(_::_('start_and_end_required'));
		}

		$db = new CalendarDB(_::getPDO());
		$events = $db->getEvents($calendar_id, $start, $end);

		$result = [];
		foreach ($events as $event) {
			$startSec = $event["start"] * 60;
			$entry = [
				"id" => $event["event_id"],
				"title" => $event["title"],
				"start" => gmdate("Y-m-d\TH:i:s\Z", $startSec),
			];
			if ($event["duration"] !== null) {
				$entry["end"] = gmdate(
					"Y-m-d\TH:i:s\Z",
					$startSec + $event["duration"] * 60,
				);
			}
			if ($event["url"] !== null) {
				$entry["url"] = $event["url"];
			}
			if ($event["description"] !== null) {
				$entry["extendedProps"] = [
					"description" => $event["description"],
				];
			}
			$result[] = $entry;
		}

		return new Json($result);
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

		_::debug("%s", print_r($calendar, true));

		$six_month = new DateInterval("P6M");
		$start = floor((new DateTimeImmutable())->sub($six_month)->getTimestamp() / 60);
		$end = floor((new DateTimeImmutable())->add($six_month)->getTimestamp() / 60);

		/*		$startParam = $_GET['start'] ?? null;
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
		}*/

		_::debug("start: %s, end: %s", $start, $end);
		$events = $db->getEvents($calendar_id, $start, $end);
		_::debug("%s", print_r($events, true));
		if ($auth === ACL::CAL_FREE_BUSY)
			return new FreeBusy($calendar["name"], $events);
		return new Ics($calendar["name"], $events);
	}
}
