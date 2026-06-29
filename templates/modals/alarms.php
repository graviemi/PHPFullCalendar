<?php namespace PHPFullCalendar; ?>
<!-- alarms management modal -->
<div class="modal fade" id="alarmsModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="alarmsModalTitle"><?= _::uf('manage_alarms') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">

				<!-- alarms list (toggles with the alarm form) -->
				<div id="alarmListArea">
					<table class="table table-sm table-hover mb-2">
						<thead>
							<tr>
								<th><?= _::uf('title') ?></th>
								<th><?= _::uf('alarm_type') ?></th>
								<th><?= _::uf('alarm_trigger') ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody id="alarmsList"></tbody>
					</table>
					<button type="button" class="btn btn-sm btn-primary" id="newAlarmBtn">
						<i class="bi bi-plus"></i> <?= _::uf('new_alarm') ?>
					</button>
				</div>

				<!-- alarm form (replaces the list, keeps the components accordion visible) -->
				<div id="alarmFormArea" style="display:none">
					<form id="alarmForm" onsubmit="event.preventDefault()">
						<div class="mb-3">
							<label class="form-label"><?= _::uf('title') ?></label>
							<input type="text" class="form-control" name="label" required>
						</div>

						<div class="mb-3">
							<label class="form-label"><?= _::uf('alarm_trigger') ?></label>
							<div class="d-flex align-items-center gap-2 flex-wrap">
								<input type="number" class="form-control form-control-sm" name="trigger_value" value="15" min="0" style="width:5rem">
								<select class="form-select form-select-sm" name="trigger_unit" style="width:auto">
									<option value="M"><?= _::_('minutes') ?></option>
									<option value="H"><?= _::_('hours') ?></option>
									<option value="D"><?= _::_('days') ?></option>
									<option value="W"><?= _::_('weeks') ?></option>
								</select>
								<select class="form-select form-select-sm" name="trigger_sign" style="width:auto">
									<option value="-"><?= _::_('before') ?></option>
									<option value="+"><?= _::_('after') ?></option>
								</select>
								<select class="form-select form-select-sm" name="related" style="width:auto">
									<option value="start"><?= _::_('event_start') ?></option>
									<option value="end"><?= _::_('event_end') ?></option>
								</select>
							</div>
						</div>

						<div class="mb-3">
							<label class="form-label"><?= _::uf('alarm_repeat') ?></label>
							<div class="d-flex align-items-center gap-2 flex-wrap">
								<input type="number" class="form-control form-control-sm" name="repeat" value="0" min="0" style="width:5rem">
								<span><?= _::_('alarm_repeat_every') ?></span>
								<input type="number" class="form-control form-control-sm" name="duration_value" value="5" min="1" style="width:5rem">
								<select class="form-select form-select-sm" name="duration_unit" style="width:auto">
									<option value="M"><?= _::_('minutes') ?></option>
									<option value="H"><?= _::_('hours') ?></option>
									<option value="D"><?= _::_('days') ?></option>
								</select>
							</div>
						</div>

						<div class="mb-3">
							<label class="form-label"><i class="bi bi-window me-2"></i><?= _::uf('alarm_display') ?></label>
							<select class="form-select" name="display_id"></select>
						</div>
						<div class="mb-3">
							<label class="form-label"><i class="bi bi-envelope me-2"></i><?= _::uf('alarm_email') ?></label>
							<select class="form-select" name="email_id"></select>
						</div>
						<div class="mb-3">
							<label class="form-label"><i class="bi bi-volume-up me-2"></i><?= _::uf('alarm_audio') ?></label>
							<select class="form-select" name="audio_id"></select>
						</div>
						<div class="d-flex gap-2">
							<button type="button" class="btn btn-sm btn-secondary me-auto" id="alarmBackBtn">
								<i class="bi bi-arrow-left"></i> <?= _::uf('back') ?>
							</button>
							<button type="submit" class="btn btn-sm btn-primary" id="saveAlarmBtn"><?= _::uf('save') ?></button>
						</div>
					</form>
				</div>

				<hr>
				<h6 class="text-body-secondary"><?= _::uf('manage_components') ?></h6>
				<div class="accordion" id="manageComponentsAccordion">

					<!-- displays -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manageDisplaysCollapse">
								<i class="bi bi-window me-2"></i> <?= _::uf('displays') ?>
							</button>
						</h2>
						<div id="manageDisplaysCollapse" class="accordion-collapse collapse" data-bs-parent="#manageComponentsAccordion">
							<div class="accordion-body">
								<div data-ent-list="display">
									<table class="table table-sm table-hover mb-2"><tbody id="displayList"></tbody></table>
									<button type="button" class="btn btn-sm btn-primary" data-ent-new="display"><i class="bi bi-plus"></i> <?= _::uf('add') ?></button>
								</div>
								<form data-ent-form="display" style="display:none" onsubmit="event.preventDefault()">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_item_label') ?></label>
										<input type="text" class="form-control" name="label" placeholder="<?= _::_('something_to_refer_to_this') ?>" required>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('description') ?></label>
										<select class="form-select" name="description_id"></select>
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-secondary me-auto" data-ent-back="display"><i class="bi bi-arrow-left"></i> <?= _::uf('back') ?></button>
										<button type="submit" class="btn btn-sm btn-primary"><?= _::uf('save') ?></button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<!-- emails -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manageEmailsCollapse">
								<i class="bi bi-envelope me-2"></i> <?= _::uf('emails') ?>
							</button>
						</h2>
						<div id="manageEmailsCollapse" class="accordion-collapse collapse" data-bs-parent="#manageComponentsAccordion">
							<div class="accordion-body">
								<div data-ent-list="email">
									<table class="table table-sm table-hover mb-2"><tbody id="emailList"></tbody></table>
									<button type="button" class="btn btn-sm btn-primary" data-ent-new="email"><i class="bi bi-plus"></i> <?= _::uf('add') ?></button>
								</div>
								<form data-ent-form="email" style="display:none" onsubmit="event.preventDefault()">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_item_label') ?></label>
										<input type="text" class="form-control" name="label" placeholder="<?= _::_('something_to_refer_to_this') ?>" required>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_email_summary') ?> (Twig)</label>
										<input type="text" class="form-control font-monospace" name="summary" placeholder="<?= _::_('example') ?> : [{{ calendar }}] {{ title }}">
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('description') ?></label>
										<select class="form-select" name="description_id"></select>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_recipients') ?></label>
										<div class="d-flex gap-2 mb-2">
											<select class="form-select form-select-sm" id="emailRecipientPicker"></select>
											<button type="button" class="btn btn-sm btn-outline-primary" id="emailRecipientAdd"><i class="bi bi-plus"></i></button>
										</div>
										<ul class="list-group" id="emailRecipientList"></ul>
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-secondary me-auto" data-ent-back="email"><i class="bi bi-arrow-left"></i> <?= _::uf('back') ?></button>
										<button type="submit" class="btn btn-sm btn-primary"><?= _::uf('save') ?></button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<!-- sounds -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manageSoundsCollapse">
								<i class="bi bi-volume-up me-2"></i> <?= _::uf('sounds') ?>
							</button>
						</h2>
						<div id="manageSoundsCollapse" class="accordion-collapse collapse" data-bs-parent="#manageComponentsAccordion">
							<div class="accordion-body">
								<div data-ent-list="sound">
									<table class="table table-sm table-hover mb-2"><tbody id="soundList"></tbody></table>
									<button type="button" class="btn btn-sm btn-primary" data-ent-new="sound"><i class="bi bi-plus"></i> <?= _::uf('add') ?></button>
								</div>
								<form data-ent-form="sound" style="display:none" onsubmit="event.preventDefault()">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_item_label') ?></label>
										<input type="text" class="form-control" name="label" placeholder="<?= _::_('something_to_refer_to_this') ?>" required>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_sound') ?></label>
										<input type="file" class="form-control" name="sound" accept="audio/*">
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-secondary me-auto" data-ent-back="sound"><i class="bi bi-arrow-left"></i> <?= _::uf('back') ?></button>
										<button type="submit" class="btn btn-sm btn-primary"><?= _::uf('save') ?></button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<!-- descriptions -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manageDescriptionsCollapse">
								<i class="bi bi-card-text me-2"></i> <?= _::uf('descriptions') ?>
							</button>
						</h2>
						<div id="manageDescriptionsCollapse" class="accordion-collapse collapse" data-bs-parent="#manageComponentsAccordion">
							<div class="accordion-body">
								<div data-ent-list="description">
									<table class="table table-sm table-hover mb-2"><tbody id="descriptionList"></tbody></table>
									<button type="button" class="btn btn-sm btn-primary" data-ent-new="description"><i class="bi bi-plus"></i> <?= _::uf('add') ?></button>
								</div>
								<form data-ent-form="description" style="display:none" onsubmit="event.preventDefault()">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('alarm_item_label') ?></label>
										<input type="text" class="form-control" name="label" placeholder="<?= _::_('something_to_refer_to_this') ?>" required>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('description') ?> (Twig)</label>
										<textarea class="form-control font-monospace" name="contents" rows="3" placeholder="<?= _::_('example') ?> : {{ title }} — {{ start }}"></textarea>
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-secondary me-auto" data-ent-back="description"><i class="bi bi-arrow-left"></i> <?= _::uf('back') ?></button>
										<button type="submit" class="btn btn-sm btn-primary"><?= _::uf('save') ?></button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<!-- recipients -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manageRecipientsCollapse">
								<i class="bi bi-person me-2"></i> <?= _::uf('alarm_recipients') ?>
							</button>
						</h2>
						<div id="manageRecipientsCollapse" class="accordion-collapse collapse" data-bs-parent="#manageComponentsAccordion">
							<div class="accordion-body">
								<div data-ent-list="recipient">
									<table class="table table-sm table-hover mb-2"><tbody id="recipientList"></tbody></table>
									<button type="button" class="btn btn-sm btn-primary" data-ent-new="recipient"><i class="bi bi-plus"></i> <?= _::uf('add') ?></button>
								</div>
								<form data-ent-form="recipient" style="display:none" onsubmit="event.preventDefault()">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('email') ?></label>
										<input type="email" class="form-control" name="address" required>
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('acl_identifier') ?></label>
										<input type="text" class="form-control" name="name">
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('login') ?></label>
										<input type="text" class="form-control" name="firstname">
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-secondary me-auto" data-ent-back="recipient"><i class="bi bi-arrow-left"></i> <?= _::uf('back') ?></button>
										<button type="submit" class="btn btn-sm btn-primary"><?= _::uf('save') ?></button>
									</div>
								</form>
							</div>
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
