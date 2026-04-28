<?php

namespace backup;

class lang
{
	protected static $data;

	public static function load(array $data)
	{
		self::$data = $data;
	}

	public static function _(string $key) : string
	{
		return self::$data[$key] ?? $key;
	}

	public static function uf(string $key) : string
	{
		return ucfirst(self::$data[$key] ?? $key);
	}

	public static function get(string $key)
	{
		return self::$data[$key] ?? $key;
	}
}