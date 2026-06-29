#!/usr/bin/env php
<?php

// Crée (ou met à jour) un compte administrateur dans la table globalACL :
// type = user, authorization = 63 (tous les droits globaux).
// Réutilise config.php et PHPFullCalendar\Database\ACL.

use PHPFullCalendar\Database\ACL;

$root = __DIR__;
$conf = require($_SERVER['PFC_CONFIG_PATH'] ?? $root.'/config.php');

spl_autoload_register(function (string $class) use ($root) {
	$path = sprintf('%s/src/%s.php', $root, strtr($class, '\\', DIRECTORY_SEPARATOR));
	if (is_file($path))
		require $path;
});

function prompt(string $label) : string
{
	echo $label;
	return trim(fgets(STDIN));
}

$source = prompt('Source d\'authentification : ');
$identifier = prompt('Identifiant : ');

if ($identifier === '')
{
	fwrite(STDERR, 'L\'identifiant ne peut pas être vide.'.PHP_EOL);
	exit(1);
}

$database = $conf['database'];
$pdo = new PDO(
	sprintf('%s:dbname=%s;host=%s', $database['driver'], $database['name'], $database['host']),
	$database['user'], $database['pass']
);

(new ACL($pdo))->setGlobalAuthorization($source, $identifier, 'user', 63);

printf('Compte administrateur « %s:%s » créé / mis à jour (type user, authorization 63).'.PHP_EOL, $source, $identifier);
