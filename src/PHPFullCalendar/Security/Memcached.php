<?php

namespace PHPFullCalendar\Security;

class Memcached implements SecurityInterface
{
	protected int $ttl;
	protected \Memcached $mc;
	protected string $prefix;
	protected int $max;

	public function __construct(array $parameters)
	{
		$this->mc = new \Memcached();
		$this->mc->addServer($parameters['host'] ?? '127.0.0.1', $parameters['port'] ?? 11211);
		$this->ttl = $parameters['quarantine'] ?? 3600;
		$this->prefix = $parameters['prefix'] ?? 'pfc_';
		$this->max = $parameters['max_try'] ?? 3;
	}

	public function Check(string $IP) : bool
	{
		if (($count = $this->mc->get($this->prefix.$IP)) === false)
			return true;
		return ($count < $this->max);
	}

	public function Store(string $IP) : int
	{
		// add() creates the counter at 1 (with TTL) if absent ; otherwise increment().
		// This works under the default ASCII protocol, unlike increment()'s initial value.
		$key = $this->prefix.$IP;
		if ($this->mc->add($key, 1, $this->ttl))
			return 1;
		return (int) $this->mc->increment($key);
	}
}