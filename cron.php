#!/usr/bin/env php
<?php

namespace PHPFullCalendar;

use DateTimeImmutable,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Database\Alarm as AlarmDB;

$root = __DIR__;
$conf = require($_SERVER['PFC_CONFIG_PATH'] ?? $root.'/config.php');

spl_autoload_register(function (string $class) use ($root) {
	$path = sprintf('%s/src/%s.php', $root, strtr($class, '\\', DIRECTORY_SEPARATOR));
	if (is_file($path))
		require $path;
});

date_default_timezone_set($conf['timezone'] ?? 'Europe/Paris');

if (! isset($conf['emails']))
	exit(0);
$backend = sprintf('PHPFullCalendar\\Emails\\%s', $conf['emails']['backend']);
if (! class_exists($backend))
{
	fwrite(STDERR, sprintf('unknown emails backend "%s"%s', $conf['emails']['backend'], PHP_EOL));
	exit(1);
}

function icsMinutes(string $duration) : int|null
{
	if (! preg_match('/^(-)?P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $duration, $matches))
		return null;
	$minutes = (int)($matches[2] ?? 0) * 10080 + (int)($matches[3] ?? 0) * 1440
		+ (int)($matches[4] ?? 0) * 60 + (int)($matches[5] ?? 0);
	return ($matches[1] ?? '') === '-' ? -$minutes : $minutes;
}

function template(string $tpl, array $vars) : string
{
	$map = [];
	foreach ($vars as $key => $value)
		$map['{{'.$key.'}}'] = (string) $value;
	return strtr($tpl, $map);
}

$database = $conf['database'];
$pdo = new \PDO(
	sprintf('%s:dbname=%s;host=%s', $database['driver'], $database['name'], $database['host']),
	$database['user'], $database['pass']
);
$calendarDb = new CalendarDB($pdo);
$alarmDb = new AlarmDB($pdo);

$now = intdiv(time(), 60);
$recipients = [];
$sent = 0;

foreach ($calendarDb->getEmailAlarms() as $row)
{
	if (($offset = icsMinutes($row['trigger'])) === null)
		continue;
	$repeat = (int) $row['repeat'];
	$step = $row['alarm_duration'] !== null ? icsMinutes($row['alarm_duration']) : null;
	$delta = $offset + ($row['related'] === 'end' ? (int) $row['duration'] : 0);
	for ($k = 0; $k <= $repeat; $k++)
	{
		if ($k > 0 && ($step === null || $step <= 0))
			break;
		$candidate = $now - $delta - ($k > 0 ? $k * $step : 0);
		if ($row['rrule'] === null || $row['rrule'] === '')
			$fires = ((int) $row['start'] === $candidate);
		elseif ($candidate < (int) $row['start'] || ((int) $row['until'] !== 0 && $candidate > (int) $row['until']))
			$fires = false;
		else
		{
			try
			{
				$rrule = new \RRule\RRule($row['rrule'], new DateTimeImmutable('@'.($row['start'] * 60)));
				$fires = $rrule->occursAt(new DateTimeImmutable('@'.($candidate * 60)));
			}
			catch (\Exception $e)
			{
				$fires = false;
			}
		}
		if (! $fires)
			continue;
		$email_id = (int) $row['email_id'];
		$recipients[$email_id] ??= $alarmDb->getEmailRecipients($email_id);
		if ($recipients[$email_id] === [])
			continue;
		$startSec = $candidate * 60;
		$context = [
			'title' => $row['title'] ?? '',
			'start' => date('Y-m-d H:i', $startSec),
			'end' => $row['duration'] > 0 ? date('Y-m-d H:i', $startSec + $row['duration'] * 60) : '',
			'location' => $row['location'] ?? '',
			'description' => $row['event_description'] ?? '',
			'position' => $row['position'] ?? '',
			'url' => $row['url'] ?? '',
			'calendar' => $row['calendar_name'],
		];
		$mailer = new $backend($conf['emails']['parameters'] ?? []);
		foreach ($recipients[$email_id] as $recipient)
			$mailer->AddDestination($recipient['address'], $recipient['name']);
		if ($mailer->Send(template($row['email_summary'], $context), template($row['email_description'], $context)))
			$sent++;
	}
}

if ($sent > 0)
	printf('%s : %d email(s) sent%s', date('Y-m-d H:i'), $sent, PHP_EOL);
