<!-- nino:template-name Hello World -->
<!-- nino:template-slot header -->
[template /templates/html-header]

<section class="nino-section">
	<div class="nino-grid-row">
		<div class="nino-grid-100">

			<h1 class="nino-section-title">[[/hello/page/title]]</h1>
			<p class="nino-section-text">[[/hello/page/text]]</p>

			[hello]

		</div>
	</div>
</section>

<!-- nino:template-slot footer -->
[template /templates/html-footer]
