[template /templates/html-header]
<section class="nino-section nino-section--narrow nino-text-center" id="protected">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			<h2 class="nino-section-title">[[/template/page-protected/intro/title]]</h2>
			<p class="nino-section-subtitle">[[/template/page-protected/intro/text]]</p>
			[protected-error]
			<form action="[[/nino/dir]]/.protected" method="post">
				<input type="hidden" name="return" value="[[/feature/protected/form/return]]">
				[csrf]
				<label for="protected-password">[[/template/page-protected/form/password]]</label>
				<input type="password" id="protected-password" name="password" class="nino-form-input" required>
				<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">[[/template/page-protected/form/submit]]</button>
			</form>
		</div>
	</div>
</section>
[template /templates/html-footer]
