<div class="nino-grid-row">
	<div class="nino-grid-100 nino-grid-m-50 nino-mb-3">
		<h2 class="nino-section-title nino-text-left">Form</h2>
		<form class="nino-form" action="#" method="post">
			<label for="forms-name">Name *</label>
			<input type="text" id="forms-name" name="name" class="nino-form-input" value="Ada Lovelace" required>
			<label for="forms-mail">E-mail *</label>
			<input type="email" id="forms-mail" name="email" class="nino-form-input" placeholder="ada@example.org" required>
			<label for="forms-topic">Topic</label>
			<select id="forms-topic" name="topic" class="nino-form-select"><option>Enquiry</option><option>Offer</option></select>
			<label for="forms-message">Message *</label>
			<textarea id="forms-message" name="message" class="nino-form-textarea" required>Two lines, so the height and the padding are visible.</textarea>
			<label class="nino-forms-check"><input type="checkbox" id="forms-privacy" name="privacy" value="1"> I agree *</label>
			<fieldset class="nino-forms-group">
				<legend>How did you find us?</legend>
				<label class="nino-forms-check"><input type="radio" name="source" value="search" checked> A search</label>
				<label class="nino-forms-check"><input type="radio" name="source" value="friend"> A friend</label>
			</fieldset>
			<p class="nino-form-message" aria-live="polite"></p>
			<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Send</button>
		</form>
	</div>
	<div class="nino-grid-100 nino-grid-m-50 nino-mb-3">
		<h3 class="nino-section-subtitle nino-text-left">Messages</h3>
		<form class="nino-form nino-is-error" action="#"><p class="nino-form-message">Something is still missing.</p></form>
		<form class="nino-form nino-is-success" action="#"><p class="nino-form-message">Thank you, that arrived.</p></form>
		<h3 class="nino-section-subtitle nino-text-left">In one line</h3>
		<form class="nino-form nino-form--inline" action="#">
			<input type="email" class="nino-form-input" placeholder="E-mail" aria-label="E-mail">
			<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Sign up</button>
		</form>
	</div>
</div>
