<?php

namespace PHPFullCalendar\Database;

use PDO;

class DatabaseAbstract
{
	protected PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	// -------------------------------------------------------------------------
	// Generic helpers
	// -------------------------------------------------------------------------

	protected function _create(string $table, array $data) : int
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

	protected function _read(string $table, string $id_column, int $id) : array|false
	{
		$stmt = $this->pdo->prepare(sprintf(
			'SELECT * FROM `%s` WHERE `%s` = :id', $table, $id_column
		));
		$stmt->execute([':id' => $id]);
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	protected function _update(string $table, string $id_column, int $id, array $data) : bool
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

	protected function _delete(string $table, string $id_column, int $id) : bool
	{
		$stmt = $this->pdo->prepare(sprintf(
			'DELETE FROM `%s` WHERE `%s` = :id', $table, $id_column
		));
		return $stmt->execute([':id' => $id]);
	}
}