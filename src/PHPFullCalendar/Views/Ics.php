<?php

namespace PHPFullCalendar\Views;

use PHPFullCalendar\_;

class Ics extends ViewAbstract
{
	protected string $calendarName;
	protected array $events;

	public function __construct(string $calendarName, array $events, int|null $lastModified = null)
	{
		$this->calendarName = $calendarName;
		$this->events = $events;
		$this->lastModified = $lastModified;
		$this->extraHeaders[] = sprintf('Content-Disposition: attachment; filename="%s.ics"',preg_replace('/[^a-z0-9_-]/i', '_', $this->calendarName));
	}

	public function mimeType() : string
	{
		return 'text/calendar';
	}

	protected function escape(string $text) : string
	{
		$text = str_replace('\\', '\\\\', $text);
		$text = str_replace(';', '\;', $text);
		$text = str_replace(',', '\,', $text);
		$text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
		return $text;
	}

	protected function fold(string $line) : string
	{
		$result = '';
		while (strlen($line) > 75)
		{
			// don't cut in the middle of a UTF-8 multi-byte sequence
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
		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//PHPFullCalendar//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:'.$this->escape($this->calendarName),
		];

		$realm = _::$conf['realm'] ?? 'phpfullcalendar';
		$dtstamp = gmdate('Ymd\THis\Z');

		_::debug('%s',print_r($this->events,true));

		foreach ($this->events as $event)
		{
			$startSec = $event['start'] * 60;
			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:event-'.$event['event_id'].'@'.$realm;
			$lines[] = 'DTSTAMP:'.$dtstamp;
			$lines[] = 'DTSTART:'.gmdate('Ymd\THis\Z', $startSec);
			if ($event['duration'] > 0)
				$lines[] = 'DTEND:'.gmdate('Ymd\THis\Z', $startSec + $event['duration'] * 60);
			if (!empty($event['rrule']))
				$lines[] = 'RRULE:'.$event['rrule'];
			$lines[] = 'SUMMARY:'.$this->escape($event['title']);
			if (!empty($event['description']))
				$lines[] = 'DESCRIPTION:'.$this->escape($event['description']);
			if (!empty($event['location']))
				$lines[] = 'LOCATION:'.$this->escape($event['location']);
			if (!empty($event['position']))
				$lines[] = 'GEO:'.str_replace(',', ';', $event['position']);
			if (!empty($event['color']))
				$lines[] = 'COLOR:'.$event['color'];
			if (!empty($event['url']))
				$lines[] = 'URL:'.$event['url'];
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		foreach ($lines as $line)
			echo $this->fold($line)."\r\n";
	}
}
