<?php namespace PHPFullCalendar; ?>
<!-- event modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="eventForm" onsubmit="event.preventDefault()">
				<div class="modal-header">
					<h5 class="modal-title" id="eventModalTitle"><?= _::uf('new_event') ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label"><?= _::uf('title') ?></label>
						<input type="text" class="form-control" name="title" required>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('start_date') ?></label>
						<div class="input-group">
							<input type="datetime-local" class="form-control" name="start" required>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="copyStartToEndBtn" tabindex="-1" title="<?= _::_('_date_copy_title') ?>">
								<i class="bi bi-arrow-down-left-square"></i>
							</button>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label"><?= _::uf('end_date') ?></label>
						<div class="input-group">
							<input type="datetime-local" class="form-control" name="end">
							<button type="button" class="btn btn-sm btn-outline-secondary" id="addOneHourBtn" tabindex="-1">+1h</button>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="addThirtyMinBtn" tabindex="-1">+30m</button>
						</div>
					</div>

					<div class="mb-3">
						<label class="form-label"><?= _::uf('color') ?></label>
						<input type="color" class="form-control form-control-color" name="color">
					</div>

					<div class="mb-3">
						<label class="form-label"><?= _::uf('description') ?></label>
						<textarea class="form-control" name="description"></textarea>
					</div>

					<div class="accordion accordion-flush mb-3 border rounded" id="eventExtentAccordion">
						<div class="accordion-item">
							<h2 class="accordion-header">
								<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#eventExtentCollapse">
									<i class="bi bi-list me-2"></i><?= _::uf('event_extent') ?>
								</button>
							</h2>
							<div id="eventExtentCollapse" class="accordion-collapse collapse" data-bs-parent="#eventExtentAccordion">
								<div class="accordion-body py-2">
									<div class="mb-3">
										<label class="form-label"><?= _::uf('location') ?></label>
										<input type="text" class="form-control" name="location" placeholder="<?= _::_('location_ph') ?>">
									</div>
									<div class="mb-3">
										<label class="form-label"><?= _::uf('position') ?></label>
										<input type="text" class="form-control" name="position" placeholder="<?= _::_('position_ph') ?>">
									</div>
									<div class="mb-3">
										<label class="form-label">URL</label>
										<input type="url" class="form-control" name="url">
									</div>
								</div>
							</div>
						</div>
					</div>

<!--					<div class="form-check mb-3">
						<input class="form-check-input" type="checkbox" name="AllDay">
						<label class="form-check-label">Toute la journée</label>
					</div>-->

					<div class="accordion accordion-flush mb-3 border rounded" id="rruleAccordion">
						<div class="accordion-item">
							<h2 class="accordion-header">
								<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#rruleCollapse">
									<i class="bi bi-repeat me-2"></i><?= _::uf('recurring_event') ?>
								</button>
							</h2>
							<input type="hidden" name="rrule_active" id="rruleActive" value="0">
							<div id="rruleCollapse" class="accordion-collapse collapse" data-bs-parent="#rruleAccordion">
								<div class="accordion-body py-2">
									<div class="d-flex align-items-center gap-2 flex-wrap mb-2">
										<span><?= _::uf('every') ?></span>
										<input type="number" class="form-control form-control-sm" name="rrule_interval" value="1" min="1" style="width:4rem">
										<?php foreach ([['daily','day_s'],['weekly','week_s'],['monthly','month_s'],['yearly','year_s']] as [$val,$key]): ?>
										<div class="form-check form-check-inline m-0">
											<input class="form-check-input" type="radio" name="rrule_freq" id="rrule_freq_<?= $val ?>" value="<?= $val ?>"<?= $val === 'daily' ? ' checked' : '' ?>>
											<label class="form-check-label" for="rrule_freq_<?= $val ?>"><?= _::_($key) ?></label>
										</div>
										<?php endforeach ?>
									</div>
									<div class="d-flex align-items-center gap-3 flex-wrap mb-2">
										<div class="form-check form-check-inline m-0 d-flex align-items-center gap-2">
											<input class="form-check-input" type="radio" name="rrule_end" id="rrule_end_until" value="endless" checked>
											<label class="form-check-label" for="rrule_end_until"><?= _::uf('endless') ?></label>
										</div>
										<div class="form-check form-check-inline m-0 d-flex align-items-center gap-2">
											<input class="form-check-input" type="radio" name="rrule_end" id="rrule_end_until" value="until">
											<label class="form-check-label" for="rrule_end_until"><?= _::uf('until') ?></label>
											<input type="date" class="form-control form-control-sm" name="rrule_until" style="width:9.5rem">
										</div>
										<div class="form-check form-check-inline m-0 d-flex align-items-center gap-2">
											<input class="form-check-input" type="radio" name="rrule_end" id="rrule_end_count" value="count">
											<label class="form-check-label" for="rrule_end_count"><?= _::uf('repeated') ?></label>
											<input type="number" class="form-control form-control-sm" name="rrule_count" min="2" style="width:4rem" value="2">
											<span><?= _::_('time') ?></span>
										</div>
									</div>

									<div id="eventsDaily" style="display:none"></div>
									<div id="eventsWeekly" style="display:none"></div>
									<div id="eventsMonthly" style="display:none"></div>
									<div id="eventsYearly" style="display:none"></div>

									<div class="d-flex align-items-center gap-2 mt-2">
										<div class="form-check m-0">
											<input class="form-check-input" type="checkbox" id="rruleExpertCheck">
											<label class="form-check-label" for="rruleExpertCheck">
												<?= _::_('rrule_expert') ?>
												<a href="https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.10" target="_blank">RFC5545</a>
											</label>
										</div>
										<span id="rrulePreviewBtn" class="ms-auto d-flex align-items-center gap-1 text-body-secondary" style="cursor:pointer; font-size:.85rem; user-select:none">
											<?= _::_('show_some_dates') ?> <i class="bi bi-eye"></i>
										</span>
									</div>
									<div id="rruleExpertSection" style="display:none" class="mt-2">
										<input type="text" class="form-control form-control-sm font-monospace" name="rrule" placeholder="FREQ=WEEKLY;INTERVAL=1;BYDAY=MO">
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="accordion accordion-flush mb-3 border rounded" id="eventAlarmsAccordion">
						<div class="accordion-item">
							<h2 class="accordion-header">
								<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#eventAlarmsCollapse">
									<i class="bi bi-bell me-2"></i><?= _::uf('event_alarms') ?>
								</button>
							</h2>
							<div id="eventAlarmsCollapse" class="accordion-collapse collapse" data-bs-parent="#eventAlarmsAccordion">
								<div class="accordion-body py-2">
									<div id="eventAlarmsWidget">
										<div class="d-flex gap-2 mb-2">
											<select class="form-select form-select-sm" id="eventAlarmPicker"></select>
											<button type="button" class="btn btn-sm btn-outline-primary" id="eventAlarmAdd"><i class="bi bi-plus"></i></button>
										</div>
										<ul class="list-group" id="eventAlarmList"></ul>
									</div>
									<div id="eventAlarmsHint" class="text-muted small" style="display:none"><?= _::_('alarm_save_event_first') ?></div>
									<div class="mt-3" id="eventCalendarAlarms" style="display:none">
										<label class="form-label text-body-secondary"><?= _::uf('calendar_alarms') ?></label>
										<ul class="list-group" id="eventCalendarAlarmsList"></ul>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-danger me-auto" id="deleteEventBtn" disabled><?= _::uf('delete') ?></button>
					<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= _::uf('cancel') ?></button>
					<button type="reset" class="btn btn-sm btn-secondary"><?= _::uf('reset') ?></button>
					<button type="submit" class="btn btn-sm btn-primary" id="saveEventBtn"><?= _::uf('save') ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
