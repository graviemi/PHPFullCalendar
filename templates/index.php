<?php

namespace PHPFullCalendar;

const tabs = "\t\t";
$sources_options = '';
foreach (_::$conf['authentication'] as $source => $data)
	$sources_options .= sprintf('<option value="%s">%s (%s)</option>'.PHP_EOL,$source,$source,$data['method']);

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= _::$conf['title'] ?></title>
		<link rel="icon" href="/public/icons/favicon.ico">
		<link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/public/icons/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/public/icons/favicon-16x16.png">
<?php foreach (_::$conf['styles'] as $url) printf(tabs.'<link href="%s" rel="stylesheet">'.PHP_EOL,$url); ?>
		<link href="/public/styles/main.css" rel="stylesheet">
<?php foreach (_::$conf['scripts'] as $url) printf(tabs.'<script src="%s"></script>'.PHP_EOL,$url); ?>
		<script type="application/json" id="pfc-config"><?php
			$pfc_sources = [];
			foreach (_::$conf['authentication'] as $source => $data)
				$pfc_sources[] = [$source, sprintf('%s (%s)', $source, $data['method'])];
			echo json_encode(['lang' => _::$language, 'sources' => $pfc_sources]);
		?></script>
		<script type="module" src="/public/scripts/PHPFullCalendar.js"></script>
	</head>
	<body>
		<nav class="navbar bg-body-tertiary px-3">
			<div class="d-flex gap-2">
				<button class="btn btn-sm btn-outline-primary" id="addEventBtn" disabled>
					<i class="bi bi-plus-circle-fill"></i> <?= _::uf('event') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="importICSBtn" data-bs-toggle="modal" data-bs-target="#importIcsModal" disabled>
					<i class="bi bi-cloud-upload-fill"></i> <?= _::uf('import_ics') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="calendarInfoBtn" disabled>
					<i class="bi bi-info-circle-fill"></i> <?= _::uf('calendar') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="editCalendarBtn" disabled>
					<i class="bi bi-pen-fill"></i> <?= _::uf('calendar') ?>
				</button>
				<button class="btn btn-sm btn-outline-danger" id="removeCalendarBtn" disabled>
					<i class="bi bi-trash-fill"></i> <?= _::uf('calendar') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="manageCalendarAclBtn" disabled>
					<i class="bi bi-person-fill-check"></i> <?= _::uf('calendar') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="calendarAlarmsBtn" disabled>
					<i class="bi bi-bell-fill"></i> <?= _::uf('calendar') ?>
				</button>
				<div class="vr"></div>
				<button class="btn btn-sm btn-outline-primary" id="addCalendarBtn" disabled>
					<i class="bi bi-plus-circle-fill"></i> <?= _::uf('calendar') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="manageGlobalAclBtn" disabled>
					<i class="bi bi-gear-fill"></i> <?= _::uf('global') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="manageAlarmsBtn" data-bs-toggle="modal" data-bs-target="#alarmsModal" disabled>
					<i class="bi bi-bell"></i> <?= _::uf('manage_alarms') ?>
				</button>
			</div>
			<div class="d-flex align-items-center gap-3">
				<span class="text-body-secondary" id="userNameLabel"><?= _::uf('anonymous') ?></span>
				<button class="btn btn-sm btn-outline-primary" id="disconnectBtn" disabled>
					<i class="bi bi-box-arrow-right"></i> <?= _::uf('disconnect') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#helpModal">
					<i class="bi bi-question-circle"></i>
				</button>
			</div>
		</nav>
		<div class="d-flex" id="main-container">	
			<div class="sidebar p-3">
				<h5><?= _::uf('calendars') ?></h5>
				<ul class="nav flex-column" id="calendarsList">
				</ul>
			</div>
			<div class="flex-grow-1 p-3 overflow-auto">
				<div id="calendar" data-hidden="true"></div>
				<div id="no_calendar" class="no_calendar"><?= _::_('no_calendar_selected') ?></div>
			</div>
		</div>
		<div id="pfc-tooltip"></div>
<?php

$list = scandir($root = _::$root.'/templates/modals');
foreach ($list as $name)
{
	$path = $root.'/'.$name;
	if (is_file($path) && (substr($name,-4) === '.php'))
		require $path;
}

?>
		<!-- loading overlay -->
		<div id="loadingOverlay">
			<div class="spinner-border text-light" style="width:3rem;height:3rem;" role="status"></div>
		</div>
	</body>
</html>
