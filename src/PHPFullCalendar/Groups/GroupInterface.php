<?php

namespace PHPFullCalendar\Groups;

interface GroupInterface
{
	public function getGroups(string $user_id) : array;
	public function getMembers(string $group_id) : array;
}