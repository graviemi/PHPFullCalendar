<?php namespace PHPFullCalendar; ?>
<!-- calendar modal -->
<div class="modal fade" id="calendarModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="calendarForm" onsubmit="event.preventDefault()">
				<div class="modal-header">
					<h5 class="modal-title"><?= _::uf('add_calendar') ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label"><?= _::uf('calendar_name') ?></label>
						<input type="text" class="form-control" name="name" placeholder="<?= _::_('min_3_chars') ?>" required>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('calendar_description') ?></label>
						<textarea class="form-control" name="description"></textarea>
					</div>
					<div class="mb-3" id="calendarAlarmsWidget" style="display:none">
						<label class="form-label"><?= _::uf('calendar_alarms') ?></label>
						<div class="d-flex gap-2 mb-2">
							<select class="form-select form-select-sm" id="calendarAlarmPicker"></select>
							<button type="button" class="btn btn-sm btn-outline-primary" id="calendarAlarmAdd"><i class="bi bi-plus"></i></button>
						</div>
						<ul class="list-group" id="calendarAlarmList"></ul>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('cancel') ?></button>
					<button type="submit" class="btn btn-sm btn-primary" id="saveCalendarBtn"><?= _::uf('save') ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
