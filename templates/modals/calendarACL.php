<?php namespace PHPFullCalendar; ?>
<!-- calendar ACL modal -->
<div class="modal fade" id="calendarAclModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<form id="calendarAclForm" onsubmit="event.preventDefault()">
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
			</form>
		</div>
	</div>
</div>
