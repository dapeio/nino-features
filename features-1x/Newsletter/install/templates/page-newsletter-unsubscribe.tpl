[template /templates/html-header]
<section class="nino-section nino-text-center" id="newsletter-unsubscribe">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			<h2 class="nino-section-title">[[/template/page-newsletter-unsubscribe/intro/title]]</h2>
			<p class="nino-section-subtitle">[[/template/page-newsletter-unsubscribe/intro/text]]</p>
			<form class="nino-form--inline" action="[[/nino/dir]]/.newsletter/unsubscribe" method="post">
				[csrf]
				<input type="text" name="location" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="nino-form-trap">
				<label for="newsletter-unsubscribe-email" class="nino-sr-only">[[/template/common/form/email]]</label>
				<input type="email" id="newsletter-unsubscribe-email" name="email" class="nino-form-input" placeholder="[[/template/common/form/email]]" required>
				<button type="submit" class="nino-btn nino-btn--primary">[[/template/page-newsletter-unsubscribe/form/submit]]</button>
			</form>
			<p class="nino-form-message" aria-live="polite">[[/feature/newsletter/unsubscribe/error]]</p>
		</div>
	</div>
</section>
[template /templates/html-footer]
