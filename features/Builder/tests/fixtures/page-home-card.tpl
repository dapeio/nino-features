<!-- nino:template-name Cards -->
[template /templates/html-header]
<div class="nino-wrap">

<section id="story" class="nino-section">
	<div class="nino-grid-row nino-grid-middle">
		<div class="nino-grid-s-100 nino-grid-m-33 nino-grid-l-33">
			[image /template/page-home-card/story/photo ratio="4-3"]
		</div>
		<div class="nino-grid-s-100 nino-grid-m-66 nino-grid-l-66 nino-p-2 nino-mt-1 nino-mb-1">
			[card ground="counter" padding="3"]
				[card-title /template/page-home-card/story/title level="3"]
				[text /template/page-home-card/story/text]
				[button /template/page-home-card/story/button href="/contact"]
			[/card]
		</div>
	</div>
</section>

<section id="services" class="nino-section nino-section--alt">
	<div class="nino-grid-row">
		<div class="nino-grid-100">
			[stack /services sort="title" limit="3" cols="100 50 50" autoheight="1"]
				[card fill="1" background="image" focus="5" dim="1"]
					[card-title title level="3"]
				[/card]
			[/stack]
		</div>
	</div>
</section>

</div>
[template /templates/html-footer]
