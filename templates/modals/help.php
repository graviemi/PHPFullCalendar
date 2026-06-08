<?php namespace PHPFullCalendar; ?>
<!-- help modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="bi bi-question-circle me-2"></i><?= _::uf('help_title') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div class="accordion" id="helpAccordion">
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button" type="button" data-bs-target="#help">
								<?= _::uf('help_intro_title') ?>
							</button>
						</h2>
						<div id="help" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
							<div class="accordion-body"><?= _::_('help_intro_body') ?></div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpCalendars">
								<?= _::uf('help_calendars_title') ?>
							</button>
						</h2>
						<div id="helpCalendars" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
							<div class="accordion-body"><?= _::_('help_calendars_body') ?></div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpEvents">
								<?= _::uf('help_events_title') ?>
							</button>
						</h2>
						<div id="helpEvents" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
							<div class="accordion-body"><?= _::_('help_events_body') ?></div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpAcl">
								<?= _::uf('help_acl_title') ?>
							</button>
						</h2>
						<div id="helpAcl" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
							<div class="accordion-body"><?= _::_('help_acl_body') ?></div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpIcs">
								<?= _::uf('help_ics_title') ?>
							</button>
						</h2>
						<div id="helpIcs" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
							<div class="accordion-body"><?= _::_('help_ics_body') ?></div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('close') ?></button>
			</div>
		</div>
	</div>
</div>
