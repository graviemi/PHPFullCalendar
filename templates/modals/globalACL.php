<?php namespace PHPFullCalendar; ?>
<!-- global ACL modal -->
<div class="modal fade" id="globalAclModal" tabindex="-1">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<form id="globalAclForm" onsubmit="event.preventDefault()">
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
									</label><label>
										<i class="bi bi-bell" title="<?= _::_('gr_alarm') ?>"></i>
										<input type="checkbox" value="32">
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
									</label><label>
										<i class="bi bi-bell" title="<?= _::_('gr_alarm') ?>"></i>
										<input type="checkbox" value="32">
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
			</form>
		</div>
	</div>
</div>
