<?php

namespace PHPFullCalendar\Views;

use PHPFullCalendar\_;

class FreeBusy extends ViewAbstract
{
	protected string $calendarName;
	protected array $events;

	public function __construct(string $calendarName, array $events)
	{
		$this->calendarName = $calendarName;
		$this->events = $events;
	}

	public function mimeType() : string
	{
		return 'text/calendar';
	}

	public function extraHeaders() : array
	{
		return [
			sprintf('Content-Disposition: attachment; filename="%s.ics"',
				preg_replace('/[^a-z0-9_-]/i', '_', $this->calendarName))
		];
	}

	protected function fold(string $line) : string
	{
		$result = '';
		while (strlen($line) > 75)
		{
			$pos = 75;
			while ($pos > 0 && (ord($line[$pos]) & 0xC0) === 0x80)
				$pos--;
			$result .= substr($line, 0, $pos)."\r\n ";
			$line = substr($line, $pos);
		}
		return $result.$line;
	}

	public function body() : void
	{
		$realm = _::$conf['realm'] ?? 'phpfullcalendar';
		$dtstamp = gmdate('Ymd\THis\Z');

		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//PHPFullCalendar//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VFREEBUSY',
			'DTSTAMP:'.$dtstamp,
			'UID:freebusy-'.md5($this->calendarName).'@'.$realm,
		];

		foreach ($this->events as $event)
		{
			if ($event['duration'] <= 0)
				continue;
			$startSec = $event['start'] * 60;
			$lines[] = 'FREEBUSY:'.gmdate('Ymd\THis\Z', $startSec).'/'.gmdate('Ymd\THis\Z', $startSec + $event['duration'] * 60);
		}

		$lines[] = 'END:VFREEBUSY';
		$lines[] = 'END:VCALENDAR';

		foreach ($lines as $line)
			echo $this->fold($line)."\r\n";
	}
}
