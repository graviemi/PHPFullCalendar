<?php namespace PHPFullCalendar; ?>
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
					<dd class="d-flex align-items-center gap-2">
						<a id="infoIcsLink" href="#" target="_blank" class="text-truncate"></a>
						<button type="button" class="btn btn-sm btn-outline-primary" data-copy-link="infoIcsLink" title="<?= _::_('copy') ?>"><i class="bi bi-clipboard2-fill"></i> <?= _::_('copy') ?></button>
					</dd>
					<dt><?= _::uf('fc_link') ?></dt>
					<dd class="d-flex align-items-center gap-2">
						<a id="infoFcLink" href="#" target="_blank" class="text-truncate"></a>
						<button type="button" class="btn btn-sm btn-outline-primary" data-copy-link="infoFcLink" title="<?= _::_('copy') ?>"><i class="bi bi-clipboard2-fill"></i> <?= _::_('copy') ?></button>
					</dd>
				</dl>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>
