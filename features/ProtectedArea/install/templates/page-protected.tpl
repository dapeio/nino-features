[template /templates/html-header]
<section class="nino-section nino-section--narrow nino-text-center" id="protected">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			<h2 class="nino-section-title">[[/protected/title]]</h2>
			<p class="nino-section-subtitle">[[/protected/text]]</p>
			[protected-error]
			<form class="nino-form" action="/.protected" method="post">
				<input type="hidden" name="return" value="[[/protected/return]]">
				[csrf]
				<label for="protected-password">[[/protected/label/password]]</label>
				<input type="password" id="protected-password" name="password" class="nino-form-input" required>
				<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">[[/protected/label/submit]]</button>
			</form>
		</div>
	</div>
</section>
[template /templates/html-footer]
