[template /templates/html-header]
[post]
<article class="nino-section">
	<div class="nino-grid-row">
		<div class="nino-grid-100 nino-grid-l-66">
			<h1 class="nino-section-title">[[title]]</h1>
			<p class="nino-section-subtitle">[[date]] [[author]]</p>
			[[.image]]
			[[.body]]
		</div>
	</div>
</article>
[/post]
<nav class="nino-section nino-section--alt" aria-label="[[/posts/nav/label]]">
	<div class="nino-grid-row">
		[post-nav]
		<div class="nino-grid-100 nino-grid-m-50">
			<p class="nino-section-subtitle">[[/posts/nav/[[.rel]]]]</p>
			<p><a class="nino-btn nino-btn--outline" href="[[.url]]">[[title]]</a></p>
		</div>
		[/post-nav]
	</div>
</nav>
[template /templates/html-footer]
