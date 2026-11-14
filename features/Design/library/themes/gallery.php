<?php
// features/Design/library/themes/gallery.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - an overlay-menu header and a one-legal-row footer over open sections, poster tiles and open cards, with lines where other looks have boxes - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Gallery',
	'description'		=> 'A fine, quiet look for photography, film and portfolios: only the mark and a menu button over the picture, a menu that opens over the whole page in large, light capitals, square-cornered poster tiles with a narrow gap, light type, capitals for the menu, labels and buttons, a dark main button with its label in the brand colour and lines instead of boxes, set small on a wide page.',
	'description-de'	=> 'Ein feiner, ruhiger Auftritt für Fotografie, Film und Portfolios: nur das Zeichen und ein Menü-Knopf über dem Bild, ein Menü, das sich über die ganze Seite öffnet, kantige Poster-Kacheln mit schmaler Lücke, leichte Schrift, Versalien für Menü, Labels und Buttons, ein dunkler Hauptbutton mit Beschriftung in der Markenfarbe und Linien statt Kästen, klein gesetzt auf einer weiten Seite.',
	'setup'				=> [
		'size'		=> -1,
		'width'	=> 1,
		'whitespace'=> 0,
		'radius'	=> -2,
		'radius-alt'=> -2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => 'blur', 'strength' => 'soft', 'speed' => 'medium', 'hover' => 'zoom-bright', 'hover-strength' => 'soft' ],
		'parts'		=> [ 'header' => 'overlay-menu', 'footer' => 'one-legal-row', 'section' => 'open', 'tiles' => 'poster', 'card' => 'open', 'buttons' => 'quiet', 'lists' => 'ruled', 'tables' => 'line', 'forms' => 'line', 'tabs' => 'regular', 'accordion' => 'regular' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 			=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => -2, 'border-color' => 0, 'shadow' => 0, 'position' => 0, 'cta' => 0, 'behaviour' => 0, 'nav-case' => 1, 'menu-size' => 2, 'menu-weight' => -1, 'menu-tracking' => 1 ],
			'footer' 			=> [ 'scale' => -1, 'height' => 1, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => -1, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 1 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => -1, 'subtitle' => -1, 'title-tracking' => 1, 'space' => 1, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 1, 'title-ground' => 0, 'rule-color' => 0, 'align' => -1, 'measure' => -1, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'tiles' 			=> [ 'title' => -1, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 1, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => -1, 'subtitle' => -1, 'title-tracking' => 1, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => 0, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => -1, 'spread' => 0, 'tracking' => 2, 'space' => 0, 'corners' => -2, 'outline' => -2, 'hover' => 0, 'case' => 1 ],
			'lists' 			=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 			=> [ 'scale' => 0, 'space' => 0, 'border' => -1, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 			=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => -1, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 1 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'marker' => 0, 'marker-ink' => '', 'weight' => 0 ],
		],
		'styles'	=> [
			'header' => [],
			'footer' => [],
			'section' => [],
			'tiles' => [],
			'card' => [],
			'buttons' => [ 'primary' => 'dark' ],
			'lists' => [],
			'tables' => [],
			'forms' => [],
			'tabs' => [],
			'accordion' => [],
		],
		'features'	=> [],
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'light', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'open-sans', 'weight' => 'light', 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
