<!-- nino:template-name Tiles -->
[template /templates/html-header]
<div class="nino-wrap">

<section id="work" class="nino-section nino-tiles nino-tiles--16-9 nino-section--alt">
	<div class="nino-grid-row nino-grid-gap-1">
		<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-50 nino-grid--dim nino-grid--linked nino-stack-end nino-text-left">
			<div class="nino-grid-bg nino-img-focus--5">[image /template/page-home-tiles/work/picture alt=""]</div>
			[title /template/page-home-tiles/work/title href="/_nino/webpage/work/uri"]
		</div>
		<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-50 nino-grid--primary nino-text-center">
			[title /template/page-home-tiles/work/claim]
			[text /template/page-home-tiles/work/text format="inline"]
			[button /_nino/webpage/contact/name href="/_nino/webpage/contact/uri" style="primary"]
		</div>
	</div>
</section>

<section id="team" class="nino-section nino-tiles nino-mt-0 nino-mb-0">
	<div class="nino-grid-row nino-grid-gap-1">
		<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-33 nino-grid-l-33 nino-grid--alt">
			[title /template/page-home-tiles/team/one]
		</div>
		<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-33 nino-grid-l-33 nino-grid--tint">
			[title /template/page-home-tiles/team/two]
		</div>
		<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-33 nino-grid-l-33 nino-grid--counter">
			[title /template/page-home-tiles/team/three]
		</div>
	</div>
</section>

[sections /services id="news" alternate="1" class="nino-tiles nino-tiles--4-3"]
	<div class="nino-grid-row">
		<div class="nino-grid-100 nino-grid--linked">
			[title title href=".uri"]
		</div>
	</div>
[/sections]

</div>
[template /templates/html-footer]
