<?php

namespace PHPFullCalendar\Database;

use ArrayObject,
	oTools\Sessions\Session;

class Calendar
{

	private $pdo;

	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}

	// -------------------------------------------------------------------------
	// Calendar CRUD
	// -------------------------------------------------------------------------

	public function getCalendar(int $calendar_id) : array|false
	{
		$stmt = $this->pdo->prepare(
			'SELECT * FROM calendar WHERE calendar_id = :calendar_id'
		);
		$stmt->execute([':calendar_id' => $calendar_id]);
		return $stmt->fetch(\PDO::FETCH_ASSOC);
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
		$stmt = $this->pdo->prepare(
			'INSERT INTO calendar (name, description) VALUES (:name, :description)'
		);
		$stmt->execute([
			':name' => $name,
			':description' => $description
		]);
		return (int) $this->pdo->lastInsertId();
	}

	public function updateCalendar(int $calendar_id, string $name, string|null $description = null) : bool
	{
		$stmt = $this->pdo->prepare(
			'UPDATE calendar SET name = :name, description = :description
			WHERE calendar_id = :calendar_id'
		);
		return $stmt->execute([
			':calendar_id' => $calendar_id,
			':name' => $name,
			':description' => $description
		]);
	}

	public function deleteCalendar(int $calendar_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM calendar WHERE calendar_id = :calendar_id'
		);
		return $stmt->execute([':calendar_id' => $calendar_id]);
	}

	// -------------------------------------------------------------------------
	// Event CRUD
	// -------------------------------------------------------------------------

	public function getEvent(int $event_id) : array|false
	{
		$stmt = $this->pdo->prepare(
			'SELECT * FROM event WHERE event_id = :event_id'
		);
		$stmt->execute([':event_id' => $event_id]);
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	public function getEvents(int $calendar_id, int $start, int $end) : array
	{
		$stmt = $this->pdo->prepare(
			'SELECT * FROM event
			WHERE calendar_id = :calendar_id AND ((start >= :start AND start < :end)
				OR (start + duration >= :start AND start + duration < :end))'
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start' => $start,
			':end' => $end
		]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function createEvent(
		int $calendar_id,
		int $start,
		string $title,
		int|null $duration = null,
		int|null $until = null,
		string|null $frequency = null,
		string|null $rrule = null,
		string|null $description = null,
		string|null $location = null,
		string|null $position = null,
		string|null $url = null,
		string|null $color = null
	) : int
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO event
				(calendar_id, start, duration, until, frequency, rrule, title, description, location, position, url, color)
			VALUES
				(:calendar_id, :start, :duration, :until, :frequency, :rrule, :title, :description, :location, :position, :url, :color)'
		);
		$stmt->execute([
			':calendar_id' => $calendar_id,
			':start'       => $start,
			':duration'    => $duration,
			':until'       => $until,
			':frequency'   => $frequency,
			':rrule'       => $rrule,
			':title'       => $title,
			':description' => $description,
			':location'    => $location,
			':position'    => $position,
			':url'         => $url,
			':color'       => $color,
		]);
		return (int) $this->pdo->lastInsertId();
	}

	public function updateEvent(
		int $event_id,
		int $start,
		string $title,
		int|null $duration = null,
		int|null $until = null,
		string|null $frequency = null,
		string|null $rrule = null,
		string|null $description = null,
		string|null $location = null,
		string|null $position = null,
		string|null $url = null,
		string|null $color = null
	) : bool
	{
		$stmt = $this->pdo->prepare(
			'UPDATE event
			SET start = :start, duration = :duration, until = :until,
				frequency = :frequency, rrule = :rrule, title = :title,
				description = :description, location = :location, position = :position,
				url = :url, color = :color
			WHERE event_id = :event_id'
		);
		return $stmt->execute([
			':event_id'    => $event_id,
			':start'       => $start,
			':duration'    => $duration,
			':until'       => $until,
			':frequency'   => $frequency,
			':rrule'       => $rrule,
			':title'       => $title,
			':description' => $description,
			':location'    => $location,
			':position'    => $position,
			':url'         => $url,
			':color'       => $color,
		]);
	}

	public function deleteEvent(int $event_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM event WHERE event_id = :event_id'
		);
		return $stmt->execute([':event_id' => $event_id]);
	}
}
