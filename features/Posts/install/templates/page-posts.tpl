[template /templates/html-header]
<section class="nino-section">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			<h2 class="nino-section-title">[[/posts/index/title]]</h2>
			<p class="nino-section-subtitle">[[/posts/index/intro]]</p>
		</div>
	</div>
	<div class="nino-grid-row">
		[posts]
		<div class="nino-grid-100 nino-grid-m-50 nino-grid-l-33">
			<article class="nino-article">
				<a href="[[.url]]" class="nino-article-link">[[.image]]</a>
				<div class="nino-article-content">
					<p class="nino-article-subtitle">[[date]]</p>
					<h3 class="nino-article-title"><a href="[[.url]]">[[title]]</a></h3>
					<p class="nino-article-descr">[[summary]]</p>
				</div>
			</article>
		</div>
		[/posts]
	</div>
	<div class="nino-grid-row">
		<div class="nino-grid-100">[posts-pager]</div>
	</div>
</section>
[template /templates/html-footer]
