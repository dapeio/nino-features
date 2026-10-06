[template /templates/html-header]
<section class="nino-section nino-text-center" id="newsletter-unsubscribe">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			<h2 class="nino-section-title">[[/newsletter/unsubscribe/title]]</h2>
			<p class="nino-section-subtitle">[[/newsletter/unsubscribe/text]]</p>
			<form class="nino-form--inline" action="[[/nino/dir]]/.newsletter/unsubscribe" method="post">
				[csrf]
				<input type="text" name="location" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="nino-form-trap">
				<label for="newsletter-unsubscribe-email" class="nino-sr-only">[[/newsletter/label/email]]</label>
				<input type="email" id="newsletter-unsubscribe-email" name="email" class="nino-form-input" placeholder="[[/newsletter/label/email]]" required>
				<button type="submit" class="nino-btn nino-btn--primary">[[/newsletter/unsubscribe/submit]]</button>
			</form>
			<p class="nino-form-message" aria-live="polite">[[/newsletter/unsubscribe/error]]</p>
		</div>
	</div>
</section>
[template /templates/html-footer]
