<?php

namespace PHPFullCalendar\Database;

use ArrayObject,
	oTools\Sessions\Session;

class ACL
{

	// Global authorizations codes can combine
	const GR_CAL_CREATE = 1; // can create new calendar
	const GR_CAL_DESTROY = 2; // can destroy calendar
	const GR_RES_ADD = 4; // can add a new resource
	const GR_RES_DEL = 8; // can delete a resource
	const GR_ACL = 16; // can manage access control list

	// Calendar authorization levels
	const CAL_FREE_BUSY = 1; // can read calendar free/busy informations
	const CAL_READ = 2; // can read calendar events
	const CAL_WRITE = 3; // can modify calendar : change/delete events
	const CAL_ACL = 4; // can assign rights on calendar

	// Resources authorization levels
	const RES_REQ = 1; // can request booking
	const RES_BOOK = 2; // can book
	const RES_REV = 3; // can accept / reject booking requests
	const RES_MANAGE = 4; // can change / modify / delete booking
	const RES_EDIT = 5; // can modify resource attributes

	private $pdo;

	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}

	public function getGlobalAuthorization(ArrayObject|Session $data): int
	{
		$sql = 'SELECT BIT_OR(authorization) AS authorization FROM globalACL
			WHERE (source = "" AND identifier = "anonymous" AND type = "special")';
		$params = [];
		if (isset($data['user_id']))
		{
			$sql .= ' OR (source = "" AND identifier = "authenticated" AND type = "special")';
			$source = $data['source'] ?? '';
			foreach ($data['groups'] ?? [] as $n => $name)
			{
				$sql .= sprintf(' OR (source = :gsrc%d AND identifier = :grp%d AND type = "group")', $n, $n);
				$params[':gsrc'.$n] = $source;
				$params[':grp'.$n] = $name;
			}
			$sql .= ' OR (source = :source AND identifier = :identifier AND type = "user")';
			$params[':source'] = $source;
			$params[':identifier'] = $data['user_id'];
		}
		\PHPFullCalendar\_::debug('getGlobalAuthorization sql:%s',$sql);
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $row ? (int) $row['authorization'] : 0;
	}

	public function setGlobalAuthorization(string $source, string $identifier, string $type, int $authorization): bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO globalACL (source, identifier, type, authorization)
			VALUES (:source, :identifier, :type, :authorization)
			ON DUPLICATE KEY UPDATE authorization = :authorization'
		);
		return $stmt->execute([
			':source' => $source,
			':identifier' => $identifier,
			':type' => $type,
			':authorization' => $authorization
		]);
	}

	public function getCalendarAuthorization(int $calendar_id, ArrayObject|Session $data): int
	{
		$sql = 'SELECT MAX(authorization) AS authorization FROM calendarACL
			WHERE calendar_id = :calendar_id AND (
				(source = "" AND identifier = "anonymous" AND type = "special")';
		$params = [':calendar_id' => $calendar_id];
		if (isset($data['user_id']))
		{
			$sql .= ' OR (source = "" AND identifier = "authenticated" AND type = "special")';
			$source = $data['source'] ?? '';
			foreach ($data['groups'] ?? [] as $n => $name)
			{
				$sql .= sprintf(' OR (source = :gsrc%d AND identifier = :grp%d AND type = "group")', $n, $n);
				$params[':gsrc'.$n] = $source;
				$params[':grp'.$n] = $name;
			}
			$sql .= ' OR (source = :source AND identifier = :identifier AND type = "user")';
			$params[':source'] = $source;
			$params[':identifier'] = $data['user_id'];
		}
		$sql .= ')';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $row ? (int) $row['authorization'] : 0;
	}

	public function setCalendarAuthorization(int $calendar_id, string $source, string $identifier, string $type, int $authorization): bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO calendarACL (calendar_id, source, identifier, type, authorization)
			VALUES (:calendar_id, :source, :identifier, :type, :authorization)
			ON DUPLICATE KEY UPDATE authorization = :authorization'
		);
		return $stmt->execute([
			':calendar_id' => $calendar_id,
			':source' => $source,
			':identifier' => $identifier,
			':type' => $type,
			':authorization' => $authorization
		]);
	}

	public function getCalendarACLList(int $calendar_id): array
	{
		$stmt = $this->pdo->prepare(
			'SELECT source, identifier, type, authorization FROM calendarACL
			WHERE calendar_id = :calendar_id ORDER BY type, source, identifier'
		);
		$stmt->execute([':calendar_id' => $calendar_id]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function deleteCalendarACL(int $calendar_id, string $source, string $identifier, string $type): bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM calendarACL
			WHERE calendar_id = :calendar_id AND source = :source AND identifier = :identifier AND type = :type'
		);
		return $stmt->execute([
			':calendar_id' => $calendar_id,
			':source' => $source,
			':identifier' => $identifier,
			':type' => $type
		]);
	}

	public function getGlobalACLList(): array
	{
		$stmt = $this->pdo->query(
			'SELECT source, identifier, type, authorization FROM globalACL
			ORDER BY type, source, identifier'
		);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function deleteGlobalACL(string $source, string $identifier, string $type): bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM globalACL
			WHERE source = :source AND identifier = :identifier AND type = :type'
		);
		return $stmt->execute([
			':source' => $source,
			':identifier' => $identifier,
			':type' => $type
		]);
	}
}
