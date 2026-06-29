<?php

namespace PHPFullCalendar\Database;

use PDO;

class Alarm
{

	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	// -------------------------------------------------------------------------
	// Generic helpers
	// -------------------------------------------------------------------------

	private function _create(string $table, array $data) : int
	{
		$columns = array_keys($data);
		$stmt = $this->pdo->prepare(sprintf(
			'INSERT INTO `%s` (`%s`) VALUES (:%s)',
			$table, implode('`, `', $columns), implode(', :', $columns)
		));
		$symbols = [];
		foreach ($data as $key => $value)
			$symbols[':'.$key] = $value;
		$stmt->execute($symbols);
		return (int) $this->pdo->lastInsertId();
	}

	private function _read(string $table, string $id_column, int $id) : array|false
	{
		$stmt = $this->pdo->prepare(sprintf(
			'SELECT * FROM `%s` WHERE `%s` = :id', $table, $id_column
		));
		$stmt->execute([':id' => $id]);
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	private function _update(string $table, string $id_column, int $id, array $data) : bool
	{
		$assignments = [];
		foreach (array_keys($data) as $column)
			$assignments[] = sprintf('`%s` = :%s', $column, $column);
		$stmt = $this->pdo->prepare(sprintf(
			'UPDATE `%s` SET %s WHERE `%s` = :id',
			$table, implode(', ', $assignments), $id_column
		));
		$symbols = [':id' => $id];
		foreach ($data as $key => $value)
			$symbols[':'.$key] = $value;
		return $stmt->execute($symbols);
	}

	private function _delete(string $table, string $id_column, int $id) : bool
	{
		$stmt = $this->pdo->prepare(sprintf(
			'DELETE FROM `%s` WHERE `%s` = :id', $table, $id_column
		));
		return $stmt->execute([':id' => $id]);
	}

	// -------------------------------------------------------------------------
	// Alarm CRUD
	// -------------------------------------------------------------------------

	public function createAlarm(array $data) : int
	{
		return $this->_create('alarm', $data);
	}

	public function getAlarm(int $alarm_id) : array|false
	{
		return $this->_read('alarm', 'alarm_id', $alarm_id);
	}

	public function getAlarms() : array
	{
		return $this->pdo->query('SELECT * FROM alarm ORDER BY label')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateAlarm(int $alarm_id, array $data) : bool
	{
		return $this->_update('alarm', 'alarm_id', $alarm_id, $data);
	}

	public function deleteAlarm(int $alarm_id) : bool
	{
		return $this->_delete('alarm', 'alarm_id', $alarm_id);
	}

	// -------------------------------------------------------------------------
	// Audio CRUD
	// -------------------------------------------------------------------------

	public function createAudio(array $data) : int
	{
		return $this->_create('audio', $data);
	}

	public function getAudio(int $audio_id) : array|false
	{
		return $this->_read('audio', 'audio_id', $audio_id);
	}

	public function getAudios() : array
	{
		return $this->pdo->query('SELECT audio_id, label, mimetype FROM audio ORDER BY label')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateAudio(int $audio_id, array $data) : bool
	{
		return $this->_update('audio', 'audio_id', $audio_id, $data);
	}

	public function deleteAudio(int $audio_id) : bool
	{
		return $this->_delete('audio', 'audio_id', $audio_id);
	}

	// -------------------------------------------------------------------------
	// Display CRUD
	// -------------------------------------------------------------------------

	public function createDisplay(array $data) : int
	{
		return $this->_create('display', $data);
	}

	public function getDisplay(int $display_id) : array|false
	{
		return $this->_read('display', 'display_id', $display_id);
	}

	public function getDisplays() : array
	{
		return $this->pdo->query('SELECT display_id, label FROM display ORDER BY label')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateDisplay(int $display_id, array $data) : bool
	{
		return $this->_update('display', 'display_id', $display_id, $data);
	}

	public function deleteDisplay(int $display_id) : bool
	{
		return $this->_delete('display', 'display_id', $display_id);
	}

	// -------------------------------------------------------------------------
	// Email CRUD
	// -------------------------------------------------------------------------

	public function createEmail(array $data) : int
	{
		return $this->_create('email', $data);
	}

	public function getEmail(int $email_id) : array|false
	{
		return $this->_read('email', 'email_id', $email_id);
	}

	public function getEmails() : array
	{
		return $this->pdo->query('SELECT email_id, label, summary FROM email ORDER BY label')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateEmail(int $email_id, array $data) : bool
	{
		return $this->_update('email', 'email_id', $email_id, $data);
	}

	public function deleteEmail(int $email_id) : bool
	{
		return $this->_delete('email', 'email_id', $email_id);
	}

	// -------------------------------------------------------------------------
	// Description CRUD
	// -------------------------------------------------------------------------

	public function createDescription(array $data) : int
	{
		return $this->_create('description', $data);
	}

	public function getDescription(int $description_id) : array|false
	{
		return $this->_read('description', 'description_id', $description_id);
	}

	public function getDescriptions() : array
	{
		return $this->pdo->query('SELECT description_id, label FROM description ORDER BY label')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateDescription(int $description_id, array $data) : bool
	{
		return $this->_update('description', 'description_id', $description_id, $data);
	}

	public function deleteDescription(int $description_id) : bool
	{
		return $this->_delete('description', 'description_id', $description_id);
	}

	// -------------------------------------------------------------------------
	// Recipient CRUD
	// -------------------------------------------------------------------------

	public function createRecipient(array $data) : int
	{
		return $this->_create('recipient', $data);
	}

	public function getRecipient(int $recipient_id) : array|false
	{
		return $this->_read('recipient', 'recipient_id', $recipient_id);
	}

	public function getRecipients() : array
	{
		return $this->pdo->query('SELECT * FROM recipient ORDER BY address')->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function updateRecipient(int $recipient_id, array $data) : bool
	{
		return $this->_update('recipient', 'recipient_id', $recipient_id, $data);
	}

	public function deleteRecipient(int $recipient_id) : bool
	{
		return $this->_delete('recipient', 'recipient_id', $recipient_id);
	}

	// -------------------------------------------------------------------------
	// Associations
	// -------------------------------------------------------------------------

	public function setAlarmAudio(int $alarm_id, int|null $audio_id) : bool
	{
		return $this->_update('alarm', 'alarm_id', $alarm_id, ['audio_id' => $audio_id]);
	}

	public function setAlarmDisplay(int $alarm_id, int|null $display_id) : bool
	{
		return $this->_update('alarm', 'alarm_id', $alarm_id, ['display_id' => $display_id]);
	}

	public function setAlarmEmail(int $alarm_id, int|null $email_id) : bool
	{
		return $this->_update('alarm', 'alarm_id', $alarm_id, ['email_id' => $email_id]);
	}

	public function setDisplayDescription(int $display_id, int $description_id) : bool
	{
		return $this->_update('display', 'display_id', $display_id, ['description_id' => $description_id]);
	}

	public function setEmailDescription(int $email_id, int $description_id) : bool
	{
		return $this->_update('email', 'email_id', $email_id, ['description_id' => $description_id]);
	}

	public function addEmailRecipient(int $email_id, int $recipient_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT IGNORE INTO emailRecipient (email_id, recipient_id) VALUES (:email_id, :recipient_id)'
		);
		return $stmt->execute([':email_id' => $email_id, ':recipient_id' => $recipient_id]);
	}

	public function removeEmailRecipient(int $email_id, int $recipient_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM emailRecipient WHERE email_id = :email_id AND recipient_id = :recipient_id'
		);
		return $stmt->execute([':email_id' => $email_id, ':recipient_id' => $recipient_id]);
	}

	public function getEmailRecipients(int $email_id) : array
	{
		$stmt = $this->pdo->prepare(
			'SELECT r.* FROM recipient r
			INNER JOIN emailRecipient er ON er.recipient_id = r.recipient_id
			WHERE er.email_id = :email_id
			ORDER BY r.address'
		);
		$stmt->execute([':email_id' => $email_id]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	// -------------------------------------------------------------------------
	// Calendar / event attachments
	// -------------------------------------------------------------------------

	public function getCalendarAlarms(int $calendar_id) : array
	{
		$stmt = $this->pdo->prepare(
			'SELECT a.* FROM alarm a
			INNER JOIN calendarAlarm ca ON ca.alarm_id = a.alarm_id
			WHERE ca.calendar_id = :calendar_id
			ORDER BY a.label'
		);
		$stmt->execute([':calendar_id' => $calendar_id]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function attachToCalendar(int $calendar_id, int $alarm_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT IGNORE INTO calendarAlarm (calendar_id, alarm_id) VALUES (:calendar_id, :alarm_id)'
		);
		return $stmt->execute([':calendar_id' => $calendar_id, ':alarm_id' => $alarm_id]);
	}

	public function detachFromCalendar(int $calendar_id, int $alarm_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM calendarAlarm WHERE calendar_id = :calendar_id AND alarm_id = :alarm_id'
		);
		return $stmt->execute([':calendar_id' => $calendar_id, ':alarm_id' => $alarm_id]);
	}

	public function getEventAlarms(int $event_id) : array
	{
		$stmt = $this->pdo->prepare(
			'SELECT a.* FROM alarm a
			INNER JOIN eventAlarm ea ON ea.alarm_id = a.alarm_id
			WHERE ea.event_id = :event_id
			ORDER BY a.label'
		);
		$stmt->execute([':event_id' => $event_id]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function attachToEvent(int $event_id, int $alarm_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT IGNORE INTO eventAlarm (event_id, alarm_id) VALUES (:event_id, :alarm_id)'
		);
		return $stmt->execute([':event_id' => $event_id, ':alarm_id' => $alarm_id]);
	}

	public function detachFromEvent(int $event_id, int $alarm_id) : bool
	{
		$stmt = $this->pdo->prepare(
			'DELETE FROM eventAlarm WHERE event_id = :event_id AND alarm_id = :alarm_id'
		);
		return $stmt->execute([':event_id' => $event_id, ':alarm_id' => $alarm_id]);
	}
}
