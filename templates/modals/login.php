<?php namespace PHPFullCalendar; ?>
<!-- login modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="loginForm" onsubmit="event.preventDefault()">
				<div class="modal-header">
					<h5 class="modal-title"><?= _::uf('auth') ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
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
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-sm btn-primary" id="loginBtn"><?= _::uf('connect') ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
