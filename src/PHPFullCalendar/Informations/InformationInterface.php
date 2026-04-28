<?php

namespace PHPFullCalendar\Informations;

interface InformationInterface
{
	public function get(string $user_id) : array;
}