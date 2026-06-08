<?php namespace PHPFullCalendar; ?>
<!-- import ICS modal -->
<div class="modal fade" id="importIcsModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i><?= _::uf('import_ics') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div id="icsDropZone" class="ics-drop-zone">
					<i class="bi bi-cloud-upload ics-drop-icon"></i>
					<div class="ics-drop-label"><?= _::_('ics_drop_label') ?></div>
					<div class="ics-drop-hint"><?= _::_('ics_drop_hint') ?></div>
					<input type="file" id="icsFileInput" accept=".ics,text/calendar">
				</div>
				<div id="icsFileName" class="mt-2 text-center text-body-secondary small" data-hidden="true"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('cancel') ?></button>
				<button type="button" class="btn btn-sm btn-primary" id="doImportIcsBtn" disabled><?= _::uf('import') ?></button>
			</div>
		</div>
	</div>
</div>
