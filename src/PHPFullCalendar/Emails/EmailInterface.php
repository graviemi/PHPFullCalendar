<?php

namespace PHPFullCalendar\Emails;

interface EmailInterface
{
	public function AddDestination(string $address, string|null $name = null);
	public function Send(string $subject, string $body);
}