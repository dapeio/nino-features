<div class="nino-grid-100 nino-grid-m-50 nino-p-2">
	[[area:intro]]
	<ul class="nino-list">
		<li><strong>[[/project/company/general/name]]</strong></li>
		<li>[[/project/company/contact/address]]</li>
		<li><a href="mailto:[[/project/company/contact/email]]">[[/project/company/contact/email]]</a></li>
		<li><a href="tel:[[/project/company/contact/phone]]">[[/project/company/contact/phone]]</a></li>
	</ul>
	[[area:outro]]
</div>
<div class="nino-grid-100 nino-grid-m-50 nino-p-2">
	<form class="nino-form">
		[csrf]
		<label for="[[section:id]]-name">[[/template/common/form/name]]</label>
		<input type="text" id="[[section:id]]-name" name="name" class="nino-form-input" required>

		<label for="[[section:id]]-email">[[/template/common/form/email]]</label>
		<input type="email" id="[[section:id]]-email" name="email" class="nino-form-input" required>

		<label for="[[section:id]]-message">[[/template/common/form/message]]</label>
		<textarea id="[[section:id]]-message" name="message" class="nino-form-textarea" required></textarea>

		<input type="text" name="location" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="nino-form-trap">

		<p class="nino-form-message"></p>
		<p><small>[[/template/common/form/required]]</small></p>
		<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">[[/template/common/form/submit]]</button>
	</form>
</div>
