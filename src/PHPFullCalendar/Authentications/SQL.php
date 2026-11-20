<?php

namespace PHPFullCalendar\Authentications;

use ArrayObject,
	PDO,
	PDOException,
	PHPFullCalendar\_,
	PHPFullCalendar\Exception as PFCException;

class SQL extends AuthenticationAbstract
{
	protected PDO $connection;

	public function verify(string $login, string $password) : ArrayObject|null
	{
		$algo = $this->parameters['hash'] ?? 'sha256';
		if (! in_array($algo, hash_algos(), true))
			throw new PFCException(503, 'unknown hash algorithm "%s"', $algo);
		$hash = hash($algo, $password);
		$dsn = sprintf('%s:dbname=%s;host=%s;port=%d', $this->parameters['driver'] ?? 'mysql', $this->parameters['name'] ?? '', $this->parameters['host'] ?? '', $this->parameters['port'] ?? 3306);
		try
		{
			$this->connection = new PDO($dsn, $this->parameters['user'] ?? '', $this->parameters['pass'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		}
		catch (PDOException $exception)
		{
			throw new PFCException(503, 'connection to "%s" failed', $this->parameters['host'] ?? 'null');
		}
		$request = $this->parameters['request'] ?? '';
		$parameters = [
			':id' => $login,
			':hash' => $hash
		];
		try
		{
			$statement = $this->connection->prepare($request);
			$statement->execute($parameters);
		}
		catch (PDOException $exception)
		{
			_::debug('sql_auth_query_failed');
			$this->error = $exception->getMessage();
			return null;
		}
		if (! $statement->fetchColumn())
		{
			$this->failure();
			return null;
		}
		return new ArrayObject([
			'user_id' => $login,
			'source' => $this->parameters['source'] ?? $this->source,
			'informations' => ($this->information === null)?[]:$this->information->get($login),
			'groups' => ($this->groups === null)?[]:$this->groups->getGroups($login)
		]);
	}
}
