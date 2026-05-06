<?php

namespace PHPFullCalendar;

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
	<link href="https://ressources.osug.fr/styles/bootstrap.min.css" rel="stylesheet">
	<link href="https://ressources.osug.fr/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
	<link href="https://ressources.osug.fr/fonts/open-sans.css" rel="stylesheet">
	<link href="/public/styles/main.css" rel="stylesheet">
	<script src="https://ressources.osug.fr/bootstrap-5.3.3-dist/js/bootstrap.min.js"></script>
	<script src="https://ressources.osug.fr/scripts/ical.min.js"></script>
	<script src="https://ressources.osug.fr/scripts/fullcalendar-6.1.15/dist/index.global.min.js"></script>
	<script src="https://ressources.osug.fr/scripts/fullcalendar-6.1.15/packages/core/locales-all.global.min.js"></script>
<!--	<script src="https://ressources.osug.fr/scripts/fullcalendar-6.1.15/packages/core/index.global.min.js"></script>
	<script src="https://ressources.osug.fr/scripts/fullcalendar-6.1.15/packages/timegrid/index.global.min.js"></script>-->
	<script src="https://ressources.osug.fr/scripts/sweetalert.min.js"></script>
	<script src="/public/scripts/PHPFullCalendar.js"></script>
	<script type="application/javascript">
		document.addEventListener('DOMContentLoaded', () => {
			const pfc = new PHPFullCalendar('<?= _::$language ?>',[<?php
				foreach (_::$conf['authentication'] as $source => $data)
					printf('[\'%s\',\'%s (%s)\']',$source,$source,$data['method']);
			?>]);
		});
	</script>
</head>
<body>
	<nav class="navbar bg-body-tertiary px-3">
		<div class="d-flex gap-2">
			<button class="btn btn-sm btn-outline-primary" id="addEventBtn" disabled>
				<i class="bi bi-plus-circle-fill"></i> <?= _::uf('event') ?>
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
			<div class="vr"></div>
			<button class="btn btn-sm btn-outline-primary" id="addCalendarBtn" disabled>
				<i class="bi bi-plus-circle-fill"></i> <?= _::uf('calendar') ?>
			</button>
			<button class="btn btn-sm btn-outline-primary" id="manageGlobalAclBtn" disabled>
				<i class="bi bi-gear-fill"></i> <?= _::uf('global') ?>
			</button>
		</div>
		<div class="d-flex align-items-center gap-3">
			<span class="text-body-secondary" id="userNameLabel"><?= _::uf('anonymous') ?></span>
			<button class="btn btn-sm btn-outline-primary" id="disconnectBtn" disabled>
				<i class="bi bi-box-arrow-right"></i> <?= _::uf('disconnect') ?>
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

<!-- loading overlay -->
<div id="loadingOverlay">
	<div class="spinner-border text-light" style="width:3rem;height:3rem;" role="status"></div>
</div>

<!-- event modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="eventForm">
				<div class="modal-header">
					<h5 class="modal-title" id="eventModalTitle"><?= _::uf('new_event') ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Titre</label>
						<input type="text" class="form-control" name="title" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Date de début</label>
						<div class="input-group">
							<input type="datetime-local" class="form-control" name="start" required>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="copyStartToEndBtn" tabindex="-1">
								<i class="bi bi-arrow-down-left-square"></i>
							</button>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">Date de fin</label>
						<input type="datetime-local" class="form-control" name="end">
					</div>

<!--					<div class="form-check mb-3">
						<input class="form-check-input" type="checkbox" name="AllDay">
						<label class="form-check-label">Toute la journée</label>
					</div>-->

					<div class="mb-3">
						<label class="form-label">URL</label>
						<input type="url" class="form-control" name="url">
					</div>

<!--					<div class="mb-3">
						<label class="form-label">Couleur</label>
						<input type="color" class="form-control form-control-color" name="Color">
					</div>-->

					<div class="mb-3">
						<label class="form-label">Description</label>
						<textarea class="form-control" name="description"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-danger me-auto" id="deleteEventBtn" disabled><?= _::uf('delete') ?></button>
					<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('cancel') ?></button>
					<button type="reset" class="btn btn-sm btn-secondary"><?= _::uf('reset') ?></button>
					<button type="button" class="btn btn-sm btn-primary" id="saveEventBtn"><?= _::uf('save') ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- calendar modal -->
<div class="modal fade" id="calendarModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('add_calendar') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="calendarForm">
					<div class="mb-3">
						<label class="form-label"><?= _::uf('calendar_name') ?></label>
						<input type="text" class="form-control" name="name" placeholder="<?= _::_('min_3_chars') ?>" required>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('calendar_description') ?></label>
						<textarea class="form-control" name="description"></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('cancel') ?></button>
				<button type="button" class="btn btn-sm btn-primary" id="saveCalendarBtn"><?= _::uf('save') ?></button>
			</div>
		</div>
	</div>
</div>

<!-- calendar info modal -->
<div class="modal fade" id="infoModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('calendar_info') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<dl>
					<dt><?= _::uf('calendar_name') ?></dt>
					<dd id="infoCalendarName"></dd>
					<dt><?= _::uf('calendar_description') ?></dt>
					<dd id="infoCalendarDescription"></dd>
					<dt><?= _::uf('ics_link') ?></dt>
					<dd><a id="infoIcsLink" href="#" target="_blank"></a></dd>
				</dl>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>

<!-- calendar ACL modal -->
<div class="modal fade" id="calendarAclModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('manage_calendar_acl') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<table class="table table-sm table-hover mb-3">
					<thead>
						<tr>
							<th><?= _::uf('type_of_user') ?></th>
							<th><?= _::uf('acl_level') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="calendarSpecialAclList"></tbody>
					<tfoot>
						<tr>
							<td>
								<select class="form-select" name="user_type">
									<option value=":anonymous"><?= _::_('anonymous') ?></option>
									<option value=":connected"><?= _::_('connected') ?></option>
									<?php

									foreach (_::$conf['authentication'] as $source => $data)
										printf('<option value="%s:connected">%s (%s)</option>'.PHP_EOL,$source,_::_('connected'),$source);

									?>
								</select>
							</td>
							<td>
								<select name="authorization" class="form-select form-select-sm">
									<option value="0"><?= _::_('level_forbidden') ?></option>
									<option value="1"><?= _::_('level_free_busy') ?></option>
									<option value="2"><?= _::_('level_read') ?></option>
									<option value="3"><?= _::_('level_write') ?></option>
									<option value="4"><?= _::_('level_acl') ?></option>
								</select>
							</td>
							<td>
								<button type="button" id="calendarAclAddSpecial" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i></button>
							</td>
						</tr>
					</tfoot>
				</table>
				<hr>
				<table class="table table-sm table-hover mb-3">
					<thead>
						<tr>
							<th><?= _::uf('acl_source') ?></th>
							<th><?= _::uf('acl_identifier') ?></th>
							<th><?= _::uf('acl_type') ?></th>
							<th><?= _::uf('acl_level') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="calendarAclList"></tbody>
					<tfoot>
						<tr>
							<td>
								<select class="form-select form-select-sm" name="source">
									<?= $sources_options ?>
								</select>
							</td>
							<td>
								<input type="text" class="form-control form-control-sm" name="identifier">
							</td>
							<td>
								<select class="form-select form-select-sm" name="type">
									<option value="user"><?= _::_('type_user') ?></option>
									<option value="group"><?= _::_('type_group') ?></option>
								</select>
							</td>
							<td>
								<select class="form-select form-select-sm" name="authorization">
									<option value="1"><?= _::_('level_free_busy') ?></option>
									<option value="2"><?= _::_('level_read') ?></option>
									<option value="3"><?= _::_('level_write') ?></option>
									<option value="4"><?= _::_('level_acl') ?></option>
								</select>
							</td>
							<td>
								<button type="button" id="calendarAclAddRegular" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i></button>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>

<!-- global ACL modal -->
<div class="modal fade" id="globalAclModal" tabindex="-1">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('manage_global_acl') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th><?= _::_('type_of_users') ?></th>
							<th><?= _::_('acl_authorizations') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="globalACLSpecial"></tbody>
					<tfoot>
						<tr>
							<td>
								<select class="form-select" name="user_type">
									<option value=":anonymous"><?= _::_('anonymous') ?></option>
									<option value=":connected"><?= _::_('connected') ?></option>
									<?php

									foreach (_::$conf['authentication'] as $source => $data)
										printf('<option value="%s:connected">%s (%s)</option>'.PHP_EOL,$source,_::_('connected'),$source);

									?>
								</select>
							</td>
							<td>
								<div class="acl_checkboxes">
									<label>
										<i class="bi bi-calendar-plus" title="<?= _::_('gr_cal_create') ?>"></i>
										<input type="checkbox" value="1">
									</label><label>
										<i class="bi bi-calendar-x" title="<?= _::_('gr_cal_destroy') ?>"></i>
										<input type="checkbox" value="2">
									</label><label>
										<i class="bi bi-box-fill" title="<?= _::_('gr_res_add') ?>"></i>
										<input type="checkbox" value="4">
									</label><label>
										<i class="bi bi-box" title="<?= _::_('gr_res_del') ?>"></i>
										<input type="checkbox" value="8">
									</label><label>
										<i class="bi bi-person" title="<?= _::_('gr_acl') ?>"></i>
										<input type="checkbox" value="16">
									</label>
								</div>
							</td>
							<td>
								<button type="button" id="globalAclAddSpecial" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i></button>
							</td>
						</tr>
					</tfoot>
				</table>
				<hr>
				<table class="table table-sm table-hover mb-3">
					<thead>
						<tr>
							<th><?= _::uf('acl_source') ?></th>
							<th><?= _::uf('acl_identifier') ?></th>
							<th><?= _::uf('acl_type') ?></th>
							<th><?= _::uf('acl_authorizations') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="globalAclList"></tbody>
					<tfoot>
						<tr>
							<td>
								<select class="form-select" name="source">
									<?= $sources_options ?>
								</select>
							</td>
							<td>
								<input type="text" class="form-control" name="identifier">
							</td>
							<td>
								<select class="form-select" name="type">
									<option value="user"><?= _::_('type_user') ?></option>
									<option value="group"><?= _::_('type_group') ?></option>
								</select>
							</td>
							<td>
								<div class="acl_checkboxes">
									<label>
										<i class="bi bi-calendar-plus" title="<?= _::_('gr_cal_create') ?>"></i>
										<input type="checkbox" value="1">
									</label><label>
										<i class="bi bi-calendar-x" title="<?= _::_('gr_cal_destroy') ?>"></i>
										<input type="checkbox" value="2">
									</label><label>
										<i class="bi bi-box-fill" title="<?= _::_('gr_res_add') ?>"></i>
										<input type="checkbox" value="4">
									</label><label>
										<i class="bi bi-box" title="<?= _::_('gr_res_del') ?>"></i>
										<input type="checkbox" value="8">
									</label><label>
										<i class="bi bi-person" title="<?= _::_('gr_acl') ?>"></i>
										<input type="checkbox" value="16">
									</label>
								</div>
							</td>
							<td>
								<button type="button" id="globalAclAddRegular" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i></button>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>

<!-- login modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('auth') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="loginForm">
					<div class="mb-3">
						<label class="form-label"><?= _::uf('acl_source') ?></label>
						<select class="form-select" id="loginSource">
							<?= $sources_options ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('login') ?></label>
						<input type="text" class="form-control" id="loginUserId" required>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('password') ?></label>
						<input type="password" class="form-control" id="loginPassword">
					</div>
				</form>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" id="loginBtn"><?= _::uf('connect') ?></button>
			</div>
		</div>
	</div>
</div>
</body>
</html>
