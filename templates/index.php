<?php

namespace PHPFullCalendar;

const tabs = "\t\t";
$sources_options = '';
foreach (_::$conf['authentication'] as $source => $data)
	$sources_options .= sprintf('<option value="%s">%s</option>'.PHP_EOL,$source,$data['parameters']['source']);

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
				$pfc_sources[] = [$source, sprintf('%s (%s)', $data['parameters']['source'], $data['method'])];
			echo json_encode(['lang' => _::$language, 'sources' => $pfc_sources]);
		?></script>
		<script type="module" src="/public/scripts/PHPFullCalendar.js"></script>
	</head>
	<body>
		<nav class="navbar bg-body-tertiary px-3">
			<div class="d-flex gap-2">
				<button class="btn btn-sm btn-outline-primary" id="addEventBtn" title="<?= _::uf('add_event') ?>" disabled>
					<i class="bi bi-plus-circle-fill"  style="font-size: 1.1rem;"></i> <?= _::uf('event') ?>
				</button>
				<div class="vr"></div>
				<label class="col-form-label"><?= _::uf('calendar') ?></label>
				<button class="btn btn-sm btn-outline-primary" id="addCalendarBtn" title="<?= _::uf('add_calendar') ?>" disabled>
					<i class="bi bi-plus-circle-fill"  style="font-size: 1.1rem;"></i>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="calendarInfoBtn" title="<?= _::uf('calendar_info') ?>" disabled>
					<i class="bi bi-info-circle-fill"  style="font-size: 1.1rem;"></i>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="editCalendarBtn" title="<?= _::uf('edit_calendar') ?>" disabled>
					<i class="bi bi-pen-fill" style="font-size: 1.1rem;"></i>
				</button>
				<button class="btn btn-sm btn-outline-danger" id="removeCalendarBtn" title="<?= _::uf('remove_calendar') ?>" disabled>
					<i class="bi bi-trash-fill"  style="font-size: 1.1rem;"></i>
				</button>
				<button class="btn btn-sm btn-outline-primary" style="font-size: 1.1rem;" id="importICSBtn" title="<?= _::uf('import_ics') ?>" data-bs-toggle="modal" data-bs-target="#importIcsModal" disabled>
					<i class="bi bi-upload"></i> ICS
				</button>
				<button class="btn btn-sm btn-outline-primary" id="manageCalendarAclBtn" title="<?= _::uf('manage_calendar_acl') ?>" disabled>
					<i class="bi bi-person-fill-check"  style="font-size: 1.1rem;"></i>
				</button>
				<div class="vr"></div>
				<button class="btn btn-sm btn-outline-primary" id="manageGlobalAclBtn" title="<?= _::uf('manage_global_acl') ?>" disabled>
					<i class="bi bi-gear-fill"  style="font-size: 1.1rem;"></i> <?= _::uf('global') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" id="manageAlarmsBtn" title="<?= _::uf('manage_alarms') ?>" data-bs-toggle="modal" data-bs-target="#alarmsModal" disabled>
					<i class="bi bi-bell-fill"  style="font-size: 1.1rem;"></i> <?= _::uf('manage_alarms') ?>
				</button>
			</div>
			<div class="d-flex align-items-center gap-3">
				<span class="text-body-secondary" id="userNameLabel"><?= _::uf('anonymous') ?></span>
				<button class="btn btn-sm btn-outline-primary" id="disconnectBtn" title="<?= _::uf('disconnect') ?>" disabled>
					<i class="bi bi-box-arrow-right"  style="font-size: 1.1rem;"></i> <?= _::uf('disconnect') ?>
				</button>
				<button class="btn btn-sm btn-outline-primary" title="<?= _::uf('help') ?>" data-bs-toggle="modal" data-bs-target="#helpModal">
					<i class="bi bi-question-circle"  style="font-size: 1.1rem;"></i>
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
