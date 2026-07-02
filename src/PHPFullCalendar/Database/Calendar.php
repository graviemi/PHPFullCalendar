<?php

namespace PHPFullCalendar\Database;

use ArrayObject,
	oTools\Sessions\Session;

class Calendar extends DatabaseAbstract
{
	// -------------------------------------------------------------------------
	// Calendar CRUD
	// -------------------------------------------------------------------------

	public function getCalendar(int $calendar_id) : array|false
	{
		return $this->_read('calendar', 'calendar_id', $calendar_id);
	}

	public function getManagedCalendars(ArrayObject|Session $data, int $level = 3) : array
	{
		$sql = 'SELECT c.calendar_id, c.name, c.description, MAX(a.authorization) AS authorization
			FROM calendar c
			INNER JOIN calendarACL a ON a.calendar_id = c.calendar_id
			WHERE (a.authorization >= :level) AND ((a.identifier = "anonymous" AND a.type = "special")';
		$source = $data['source'];
		$params = [':level' => $level];
		if (isset($data['user_id']))
		{
			$sql .= ' OR (a.source = "" AND a.identifier = "authenticated" AND a.type = "special")';
			$sql .= ' OR (a.source = :source AND ((a.identifier = "authenticated" AND a.type = "special")';
			$params[':source'] = $source;
			foreach ($data['groups'] ?? [] as $n => $name)
			{
				$sql .= sprintf(' OR (a.identifier = :group%d AND a.type = "group")', $n);
				$params[':group'.$n] = $name;
			}
			$sql .= ' OR (a.identifier = :identifier AND a.type = "user")))';
			$params[':identifier'] = $data['user_id'];
		}
		$sql .= ') GROUP BY c.calendar_id, c.name, c.description ORDER BY c.name';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function createCalendar(string $name, string|null $description = null) : int
	{
		return $this->_create('calendar', ['name' => $name, 'description' => $description]);
	}

	public function updateCalendar(int $calendar_id, string $name, string|null $description = null) : bool
	{
		return $this->_update('calendar', 'calendar_id', $calendar_id, ['name' => $name, 'description' => $description]);
	}

	public function deleteCalendar(int $calendar_id) : bool
	{
		return $this->_delete('calendar', 'calendar_id', $calendar_id);
	}

	// -------------------------------------------------------------------------
	// Event CRUD
	// -------------------------------------------------------------------------

	public function getEvent(int $event_id) : array|false
	{
		return $this->_read('event', 'event_id', $event_id);
	}

	public function getLastModified(int $calendar_id, int $start, int $end) : int
	{
		$stmt = $this->pdo->prepare(
			'SELECT MAX(modified) as last_modified FROM event
			WHERE calendar_id = :calendar_id AND start < :end
				AND (start + duration >= :start OR (rrule IS NOT NULL AND (until = 0 OR until >= :start)))'
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start' => $start,
			':end' => $end
		]);
		return ($stmt->fetchAll(\PDO::FETCH_ASSOC))[0]['last_modified'] ?? 0;
	}

	public function getEvents(int $calendar_id, int $start, int $end) : array
	{
		$stmt = $this->pdo->prepare(
			'SELECT * FROM event
			WHERE calendar_id = :calendar_id AND start < :end
				AND (start + duration >= :start OR (rrule IS NOT NULL AND (until = 0 OR until >= :start)))'
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start' => $start,
			':end' => $end
		]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	// Events in the window with a display alarm, from either the event itself (eventAlarm)
	// or the calendar defaults (calendarAlarm). One row per (event, display alarm).
	public function getEventsDisplays(int $calendar_id, int $start, int $end) : array
	{
		$columns = 'e.*, a.alarm_id, a.label AS alarm_label, a.`trigger`, a.`repeat`, a.duration AS alarm_duration, a.related, t.contents';
		$window = 'e.calendar_id = :calendar_id AND e.start < :end
				AND (e.start + e.duration >= :start OR (e.rrule IS NOT NULL AND (e.until = 0 OR e.until >= :start)))';
		$stmt = $this->pdo->prepare(
			'SELECT '.$columns.'
			FROM event e
			JOIN eventAlarm ea ON ea.event_id = e.event_id
			JOIN alarm a       ON a.alarm_id = ea.alarm_id
			JOIN display d     ON d.display_id = a.display_id
			JOIN description t ON t.description_id = d.description_id
			WHERE '.$window.'
			UNION
			SELECT '.$columns.'
			FROM event e
			JOIN calendarAlarm ca ON ca.calendar_id = e.calendar_id
			JOIN alarm a          ON a.alarm_id = ca.alarm_id
			JOIN display d        ON d.display_id = a.display_id
			JOIN description t    ON t.description_id = d.description_id
			WHERE '.$window
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start' => $start,
			':end' => $end
		]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	// All alarms applying to the window's events, from the event itself (eventAlarm) or the
	// calendar defaults (calendarAlarm). One row per (event, alarm) ; each row carries the
	// display/email/audio parts present (LEFT JOIN), any of which may be NULL. For ICS VALARM.
	public function getEventsAlarms(int $calendar_id, int $start, int $end) : array
	{
		$window = 'e.calendar_id = :calendar_id AND e.start < :end
				AND (e.start + e.duration >= :start OR (e.rrule IS NOT NULL AND (e.until = 0 OR e.until >= :start)))';
		$stmt = $this->pdo->prepare(
			'SELECT link.event_id,
				a.alarm_id, a.`trigger`, a.`repeat`, a.duration AS alarm_duration, a.related,
				dd.contents AS display_description,
				a.email_id, em.summary AS email_summary, ed.contents AS email_description,
				a.audio_id
			FROM (
				SELECT ea.event_id, ea.alarm_id
				FROM eventAlarm ea JOIN event e ON e.event_id = ea.event_id
				WHERE '.$window.'
				UNION
				SELECT e.event_id, ca.alarm_id
				FROM calendarAlarm ca JOIN event e ON e.calendar_id = ca.calendar_id
				WHERE '.$window.'
			) AS link
			JOIN alarm a            ON a.alarm_id = link.alarm_id
			LEFT JOIN display d      ON d.display_id = a.display_id
			LEFT JOIN description dd ON dd.description_id = d.description_id
			LEFT JOIN email em       ON em.email_id = a.email_id
			LEFT JOIN description ed ON ed.description_id = em.description_id
			ORDER BY link.event_id'
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start' => $start,
			':end' => $end
		]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function createEvent(int $calendar_id, array $data) : int
	{
		return $this->_create('event', array_merge(['calendar_id' => $calendar_id], $data));
	}

	public function updateEvent(int $event_id, array $data) : bool
	{
		return $this->_update('event', 'event_id', $event_id, $data);
	}

	public function deleteEvent(int $event_id) : bool
	{
		return $this->_delete('event', 'event_id', $event_id);
	}
}
