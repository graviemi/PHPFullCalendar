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
