#!/usr/bin/env php
<?php

namespace PHPFullCalendar;

use DateTimeImmutable,
	PHPFullCalendar\Database\Calendar as CalendarDB,
	PHPFullCalendar\Database\Alarm as AlarmDB;
use PDOStatement;

const REMINDER_AHEAD = 11;

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

$directory = $conf['emails']['reminder-dir'] ?? 'reminders';
if (substr($directory, 0, 1) !== '/')
	$directory = $root.'/'.$directory;
if (! is_dir($directory) && ! @mkdir($directory, 0750, true))
{
	fwrite(STDERR, sprintf('unable to create reminder directory "%s"%s', $directory, PHP_EOL));
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

function context(array|false $event, string $calendar, int $start) : array
{
	$context = [
		'title' => '',
		'start' => '',
		'end' => '',
		'location' => '',
		'description' => '',
		'position' => '',
		'url' => '',
		'calendar' => $calendar,
	];
	if ($event === false)
		return $context;
	$duration = (int) $event['duration'];
	$context['title'] = $event['title'] ?? '';
	$context['start'] = date('Y-m-d H:i', $start);
	$context['end'] = $duration > 0 ? date('Y-m-d H:i', $start + $duration * 60) : '';
	$context['location'] = $event['location'] ?? '';
	$context['description'] = $event['description'] ?? '';
	$context['position'] = $event['position'] ?? '';
	$context['url'] = $event['url'] ?? '';
	return $context;
}

function occurrences(array $row, int $from, int $limit) : array
{
	$start = (int) $row['start'];
	if ($row['rrule'] === null || $row['rrule'] === '')
		return $start >= $from ? [$start] : [];
	try
	{
		$rrule = new \RRule\RRule($row['rrule'], new DateTimeImmutable('@'.($start * 60)));
		$dates = $rrule->getOccurrencesAfter(new DateTimeImmutable('@'.($from * 60)), true, $limit);
	}
	catch (\Exception $e)
	{
		return [];
	}
	$until = (int) $row['until'];
	$list = [];
	foreach ($dates as $date)
	{
		$minute = intdiv($date->getTimestamp(), 60);
		if ($until !== 0 && $minute > $until)
			break;
		$list[] = $minute;
	}
	return $list;
}

function reminders(PDOStatement $rows, int $now) : \ArrayObject
{
	$lines = new \ArrayObject();
	foreach ($rows as $row)
	{
		if (($offset = icsMinutes($row['trigger'])) === null)
			continue;
		$step = $row['alarm_duration'] !== null ? icsMinutes($row['alarm_duration']) : null;
		if ($step === null || $step <= 0)
			$step = 0;
		$repeat = $step > 0 ? (int) $row['repeat'] : 0;
		$delta = $offset + ($row['related'] === 'end' ? (int) $row['duration'] : 0);
		$recurring = $row['rrule'] !== null && $row['rrule'] !== '';
		$email_id = (int) $row['email_id'];
		$event_id = (int) $row['event_id'];
		$count = 0;
		foreach (occurrences($row, $now - $delta - $repeat * $step, REMINDER_AHEAD) as $start)
		{
			for ($k = 0; $k <= $repeat; $k++)
			{
				$when = $start + $delta + $k * $step;
				if ($when < $now)
					continue;
				$lines[] = [$when, $email_id, $event_id, $start];
				if ($recurring && ++$count >= REMINDER_AHEAD)
					break 2;
			}
		}
	}
	$lines->uasort(fn(array $a, array $b) => $a[0] <=> $b[0]);
	return $lines;
}

$database = $conf['database'];
$pdo = new \PDO(
	sprintf('%s:dbname=%s;host=%s', $database['driver'], $database['name'], $database['host']),
	$database['user'], $database['pass']
);
$calendarDb = new CalendarDB($pdo);
$alarmDb = new AlarmDB($pdo);

$emails = [];
$recipients = [];
$events = [];
$sent = 0;

foreach ($calendarDb->getCalendars() as $calendar)
{
	$calendar_id = (int) $calendar['calendar_id'];
	$file = sprintf('%s/reminder-%d.txt', $directory, $calendar_id);
	clearstatcache(true, $file);
	if (! is_file($file) || filesize($file) === 0 || filemtime($file) < (int) $calendar['modified'])
	{
		$stamp = time();
		$lines = [];
		foreach (reminders($calendarDb->getEmailAlarms($calendar_id), intdiv($stamp, 60)) as $line)
			$lines[] = sprintf('%s %d %d %s', gmdate('Y-m-d\TH:i:s\Z', $line[0] * 60), $line[1], $line[2], gmdate('Y-m-d\TH:i:s\Z', $line[3] * 60));
		if ($lines === [])
			$lines[] = 'empty';
		print_r($lines);
		if (file_put_contents($file, implode(PHP_EOL, $lines).PHP_EOL) === false)
		{
			fwrite(STDERR, sprintf('unable to write reminder file "%s"%s', $file, PHP_EOL));
			continue;
		}
		touch($file, $stamp);
	}
	if (($contents = @file_get_contents($file)) === false || trim($contents) === '')
		continue;
	$lines = explode(PHP_EOL, trim($contents, "\r\n"));
	$now = time();
	$consumed = 0;
	while ($lines !== [] && $lines[0] !== 'empty')
	{
		if (! preg_match('/^(\S+) (\d+) (\d+) (\S+)$/', $lines[0], $matches) || ($when = strtotime($matches[1])) === false)
		{
			array_shift($lines);
			$consumed++;
			continue;
		}
		if ($when > $now)
			break;
		$email_id = (int)$matches[2];
		$event_id = (int)$matches[3];
		$occurrence = strtotime($matches[4]);
		if (! array_key_exists($email_id, $emails))
		{
			$emails[$email_id] = null;
			$recipients[$email_id] = [];
			$email = $alarmDb->getEmail($email_id);
			if ($email !== false && ($description = $alarmDb->getDescription((int) $email['description_id'])) !== false)
			{
				$emails[$email_id] = ['summary' => $email['summary'], 'description' => $description['contents']];
				$recipients[$email_id] = $alarmDb->getEmailRecipients($email_id);
			}
		}
		if ($emails[$email_id] !== null && $recipients[$email_id] !== [])
		{
			$events[$event_id] ??= $calendarDb->getEvent($event_id);
			$context = context($events[$event_id], $calendar['name'], $occurrence === false ? 0 : $occurrence);
			$mailer = new $backend($conf['emails']['parameters'] ?? []);
			foreach ($recipients[$email_id] as $recipient)
				$mailer->AddDestination($recipient['address'], $recipient['name']);
			if ($mailer->Send(template($emails[$email_id]['summary'], $context), template($emails[$email_id]['description'], $context)))
				$sent++;
		}
		array_shift($lines);
		$consumed++;
	}
	if ($consumed > 0)
		file_put_contents($file, implode(PHP_EOL, $lines).($lines === [] ? '' : PHP_EOL), LOCK_EX);
}

if ($sent > 0)
	printf('%s : %d email(s) sent%s', date('Y-m-d H:i'), $sent, PHP_EOL);
