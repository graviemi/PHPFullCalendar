<?php namespace PHPFullCalendar; ?>
<!-- calendar default alarms modal -->
<div class="modal fade" id="calendarAlarmsModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?= _::uf('calendar_alarms') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div id="calendarAlarmsList"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>
