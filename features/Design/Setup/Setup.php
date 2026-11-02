<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Setup		see features/Design/Design.php for the feature's
 *											own docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Setup							What was chosen, and nothing derived from it. Read and
	 *										written as /data/design.php, declared under `data` in
	 *										feature.php so a backup carries it - it is the one thing
	 *										here that cannot be worked out again from what is on disk.
	 *
	 *										One model for the panel (the loader of the typefaces installs
	 *										families and keeps nothing here): the eight
	 *										templates (header and footer bring markup and a stylesheet,
	 *										section, card, buttons, lists, tables and forms are one stylesheet
	 *										each), the root size, the width and the whitespace of the
	 *										page, the radius of its surfaces - two strengths and, for
	 *										each kind of surface, the one it takes -, its animation and
	 *										the hover of its pictures, a position for every named knob
	 *										of the eight templates, the five font roles and the
	 *										palette - and, for the list of the themes and the view CSS,
	 *										the slug of the theme these choices were taken from and
	 *										the custom css that goes with them (see Themes: a theme is
	 *										this whole setup kept under a name). Where each of them
	 *										stands in the panel is LAYOUT's to say, and nothing
	 *										else's.
	 *
	 *										A template brings styles of its own along (see styles()): the
	 *										css files beside it that a person chooses from, one select for
	 *										each style the head of the template declares. The choice is part
	 *										of the setup, kept per part and per style, and is held to the
	 *										template that is chosen for the part.
	 *
	 *										A feature that is active and carries variants of its own
	 *										(features/<Name>/design/<variant>.css) adds to it: the variant
	 *										chosen for it, and a position or a value for each knob the head
	 *										of that variant declares - a scale or a choice on a ladder, a
	 *										ground, an ink, a colour in light and dark. They are read through
	 *										Features::all() and head() (see features()), and what they add
	 *										to the tables of the panel is layout() and objects(). It may
	 *										bring a block for the specimen too, features/<Name>/design/
	 *										preview.tpl (see previewOf()). It declares styles of its own
	 *										for all its variants at once, in design/styles.css and the files
	 *										beside it (see featureStyles()): a pair of a style and an option,
	 *										which is part of its entry as a template's is part of the setup.
	 *										A feature switched off keeps its entry, which nothing compiles.
	 *
	 *										Every value is normalised against the library that is
	 *										actually present: a part naming a set that is not there
	 *										falls back to the set the fresh install has, and says so,
	 *										rather than compiling a stylesheet with a hole in it. A key
	 *										this version does not know is dropped, and says so as well.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Setup {

		public const string PATH = '/data/design.php';

		/*	The shape's own version. A file of another one is not read and not
			mapped: Design 2.0 was the first version to be used, so read() replaces
			it with the defaults and says so. A file written while the scales had
			three positions has this format and is read by the rules of normalize()
			as it is: a size that is a letter is the middle, a knob that is gone is
			dropped and named, and a position is a position of five	*/
		public const int FORMAT = 2;

		/*	The keys of the file, in the order they are written. `theme` and `css` came
			with the themes and need no new format: a file without them reads as no
			theme and no css, which is what every project had before. So does
			`whitespace`: a file without it stands at the middle, the air Nino.css has.
			And so do `radius`, `radius-alt` and `radii`: a file without them stands at
			the two strengths Nino.css has and every kind of surface on the standard one.
			And `features`: a file without it has chosen nothing for any feature, which
			is each feature as it is. And `styles`: a file without it has chosen no style
			of any template, which is each template as it is	*/
		public const array KEYS = [ 'format', 'theme', 'size', 'width', 'whitespace', 'radius', 'radius-alt', 'radii', 'motion', 'parts', 'knobs', 'styles', 'features', 'fonts', 'colours', 'css', 'compiled' ];

		/*	The notes that carry a code, for the screen to say them in the words of the person's language: the code with the English
			sentence for a log, whose %s are the params in turn (see note()). The words are the code's text under /_admin/error/<code>,
			in both text files, as a refused request's are. Every other note is one English sentence a hand-edited file earns	*/
		public const array NOTES = [
			'design_theme_missing' 		=> 'the theme "%s" is not there - none is active',
			'design_set_missing' 			=> '"%s" names the set "%s", which is not in the library - using "%s"',
			'design_feature_inactive' => 'the feature "%s" is not active, or brings no variants - its choice is kept, and nothing is compiled for it',
		];

		/*	The parts, in the order they are compiled - which is also the order they are
			shown in, and the order of the cascade: what a later part says, it says over an
			earlier one. Each styles one object of a page (see OBJECTS): the header and the
			footer the two frames; the section the hero, a section with its title, subtitle and
			text, the cover and the column on a ground; the card a card and the parts of it; the
			buttons .nino-btn and its variants; the lists .nino-list and its two markers; the
			tables .nino-table, its cells, its lines and its stripes; the forms the fields of a
			form, their labels, the status line, the checkbox and the radio group. The blocks -
			badge, alert, pricing table, accordion and timeline - are no part: the kernel draws
			them, until the features that bring blocks bring their own. The kernel draws a form
			as well - Nino.css holds the rules and the defaults of the properties --forms-* -, and
			the sets of the forms are the ladders of those properties and the looks they make.

			A `frame` part brings a template and is chosen from library/<part>/<set>/: a
			template.tpl, which is the markup, and a style.css, which declares ladders the way
			a set's stylesheet does, so that a frame has knobs like the rest. The others are one
			stylesheet each, library/<part>/<set>.css. The section and the card were one part, the
			content set, until its rules were cut in two: the sets of the two carry the same four
			names - Regular, Open, Panel and Box -, so that a theme names a sensible pair and a
			person can still mix them. The tables are what is left of the components set, whose
			fields and blocks the kernel draws now: its table rules, five sets of them. The forms
			are the eighth part, four sets of them, which declare no rule of their own: the rules
			are the kernel's, and a set says what the properties they read are	*/
		public const array PARTS = [
			'header' 			=> 'frame',
			'footer' 			=> 'frame',
			'section' 		=> 'set',
			'card' 				=> 'set',
			'buttons' 		=> 'set',
			'lists' 			=> 'set',
			'tables' 			=> 'set',
			'forms' 			=> 'set',
		];

		/*	What a knob can be set to, as the words a stylesheet declares its values
			under. The model keeps -2 to +2 and the library keeps these five words;
			word() is the one place that maps one to the other. A choice has three
			alternatives and declares them under the inner three - less, default, more -
			and a toggle two, default and more, so that there is one vocabulary and no
			word of its own	*/
		public const array STEPS = [ 'least', 'less', 'default', 'more', 'most' ];

		/*	The knobs, per part, and the kind of each - and nothing else. A knob belongs
			to the part whose templates answer to it and has a name: what it is called and
			what the terse note beside it says live in the panel's text files, one key each
			in every locale, and so do the three alternatives of a choice. The meaning of
			each is fixed here and not per set, so that the templates of a part stay
			exchangeable - what a set decides is the values behind a knob, and whether it
			answers to it at all. Which tab of the panel a knob stands on, and under which
			object, is LAYOUT's.

			A scale is a track of five positions, -2 to +2, the middle the template as it
			is: a size, a space, a corner, a line. A choice is a few alternatives that
			are no more or less of anything - the stripes of a table, the marker of a
			list - and has three, which the panel names by words. A toggle is a switch of
			two positions, 0 and 1, off and on - the case of a thing, the template as it is
			or in capitals - and the panel draws it as a switch and no strip. An ink is
			the colour of a thing as one of the seven tones of INKS - the line of a field, the
			words of a label - or none, which is the tone the template itself gives it: it
			has one rung, `default`, which the template declares as the tone it gives, and the
			panel draws it as a select. It is written on every element, since the tone is
			the page's on the ground the element stands on (see Compiler::compile()):

				header		nav					the size of the links of the navigation
							height				how tall the bar is, and how far main starts below a fixed one
							space				the space between the logo, the links and the edges
							ground				what the bar stands on (a choice)
							border-size			the weight of the line under the bar
							border-color		the colour of that line (a choice)
							position			where the navigation stands: left, centre or right (a choice)
							cta					the button of the header: hidden, small or large (a choice)
							nav-case			the links of the navigation in capitals (a toggle)
				footer		scale				the type size of the footer
							height				the space above and below its content
							space				the space between its columns, its lines and its links
							ground				what it stands on (a choice)
							border-size			the weight of the line over it
							border-color		the colour of that line (a choice)
							position			where the content stands: left, the place the template gives it, or centre (a choice)
							nav-case			the links of the navigation in capitals (a toggle)
				section		quiet, loud			the size of the --quiet and --loud variants - the title and the subtitle of a hero are the loud variant
							title, subtitle	the size of the title and of the subtitle of a section
							space				the vertical space of a section
							rule				the rule under the title of a section
							dim					how dark the scrim of a dimmed cover is
							title-case			the title of a section, the hero's among them, in capitals (a toggle)
							subtitle-case		the subtitle of a section, the hero's among them, in capitals (a toggle)
				card		title, subtitle	the size of the title and of the subtitle of a card
							space				the space inside a card and between its parts
							border, shadow		the line and the shadow of a card
							tone				the colour the shadow is drawn in (a choice)
							hover				what a card does under the pointer (a choice)
							title-case			the title of a card in capitals (a toggle)
							subtitle-case		the subtitle of a card in capitals (a toggle)
				buttons		size				the size of .nino-btn
							spread				how far .nino-btn--small and --big stand from it
							space, corners		the padding and the radius
							hover				what a button does under the pointer (a choice)
							case				the label of a button in capitals (a toggle)
				lists		scale				the type size of a list
							space				the space between its items
							marker				the marker of a check list and of a numbered list (a choice)
				tables		scale				the type size of a table
							space				the space in a cell
							border				the weight of its lines
							zebra				the stripes of a table that asks for them (a choice)
							stripes				how strong the stripes are
				forms		scale				the type size of a field
							space				the padding of a field and the space under it
							corners				the corners of a field
							line				the weight of the line of a field
							labels				where the label of a field stands: beside it, above it or floating on its edge (a choice)
							edge				the colour of the lines of a field (an ink)
							label				the colour of the labels (an ink)
							label-case			the labels in capitals (a toggle)	*/
		public const array KNOBS = [
			'header' 		=> [ 'nav' => 'scale', 'height' => 'scale', 'space' => 'scale', 'ground' => 'choice', 'border-size' => 'scale', 'border-color' => 'choice', 'position' => 'choice', 'cta' => 'choice', 'nav-case' => 'toggle' ],
			'footer' 		=> [ 'scale' => 'scale', 'height' => 'scale', 'space' => 'scale', 'ground' => 'choice', 'border-size' => 'scale', 'border-color' => 'choice', 'position' => 'choice', 'nav-case' => 'toggle' ],
			'section' 		=> [ 'quiet' => 'scale', 'loud' => 'scale', 'title' => 'scale', 'subtitle' => 'scale', 'space' => 'scale', 'rule' => 'scale', 'dim' => 'scale', 'title-case' => 'toggle', 'subtitle-case' => 'toggle' ],
			'card' 			=> [ 'title' => 'scale', 'subtitle' => 'scale', 'space' => 'scale', 'border' => 'scale', 'shadow' => 'scale', 'tone' => 'choice', 'hover' => 'choice', 'title-case' => 'toggle', 'subtitle-case' => 'toggle' ],
			'buttons' 		=> [ 'size' => 'scale', 'spread' => 'scale', 'space' => 'scale', 'corners' => 'scale', 'hover' => 'choice', 'case' => 'toggle' ],
			'lists' 		=> [ 'scale' => 'scale', 'space' => 'scale', 'marker' => 'choice' ],
			'tables' 		=> [ 'scale' => 'scale', 'space' => 'scale', 'border' => 'scale', 'zebra' => 'choice', 'stripes' => 'scale' ],
			'forms' 		=> [ 'scale' => 'scale', 'space' => 'scale', 'corners' => 'scale', 'line' => 'scale', 'labels' => 'choice', 'edge' => 'ink', 'label' => 'ink', 'label-case' => 'toggle' ],
		];

		/*	The setup in two axes. A tab is a property of the design - the templates, the type,
			the surfaces, the colours, the motion, and the css of the theme - and an object is what
			a property is set on: the page, the header, the footer, a section, a card, the buttons,
			the lists, the tables and the forms. TABS are the six views of the panel's setup screen, in the
			order they are shown in, and OBJECTS the nine objects in the order their rows follow one
			another on a view that draws rows, which is every view but the last: the templates, the
			type, the surfaces, the colours and the motion, each an accordion of one block for each
			object that has a control on it - the css is one field with no object. The themes and the
			typefaces are no views of the setup: the library of the themes is the pane's own screen,
			and the loader of the typefaces a tab of its own. The panel's words for the views and the
			objects are /_admin/design/tab/<tab>/label and /_admin/design/object/<object>/label.

			Every part but the page has an object of its own, named as the part is: the header and
			the footer, the section, the card, the buttons, the lists, the tables and the forms. The page is
			the one object that is no part - its controls are the root size, the width, the
			whitespace, the radius, the five font roles, the palette, the animation and the
			hover of the pictures	*/
		public const array TABS = [ 'templates', 'type', 'surfaces', 'colours', 'motion', 'css' ];

		public const array OBJECTS = [ 'page', 'header', 'footer', 'section', 'card', 'buttons', 'lists', 'tables', 'forms' ];

		/*	Where every control of the panel stands, and the only place that says: one entry for
			each thing the setup lets a person choose, keyed by where the choice is kept in it -
			`size`, `radii/card`, `motion/speed`, `fonts/title`, `parts/header`,
			`knobs/<part>/<knob>`, `colours/primary` - and valued [ tab, object, order ]. The
			screen draws a tab from the entries that name it and from nothing else: a row for
			each object that has a control on the tab, in the order of OBJECTS, and in the row
			its controls in the order of their numbers, which count from 1 in a row. Keys that
			stand at one place are one control: the cover, the row and the column of a section
			take the strength of their corners from one choice, and the second colour is the
			swatch at the end of the row of Harmony. An object with no control on a tab has no
			row there. Every entry names one of OBJECTS: the eight templates stand under the
			object each is the template of - the header, the footer, the section, the card, the
			buttons, the lists, the tables and the forms -, and the knobs under the object of their part,
			the frames' among them: a header and a footer have a ladder each for their type, their
			surfaces and their ground. The colours and the motion are the page's palette, its
			animation and the hover of its pictures, and each object that has a ladder of colour
			or of motion of its own has a control there: the dimming of a section, the tone of
			the shadow of a card and the stripes of a table on the colours, the hover of a card
			and of a button on the motion.
			The list of the themes, the view CSS and the loader of the typefaces have no control
			in the table: the first works on `theme`, and the view is the one field of `css`, which
			a theme file is kept with and which are no choice of a design, and the loader keeps
			nothing in the setup. Every row of the table names one of the five views that have objects.
			Nothing is stored under a place: a setup, and a theme file, keep every choice by
			its key, as before	*/
		public const array LAYOUT = [
			// The templates: the choice of a set for each of the eight parts, under the object it is the template of
			'parts/header' 							=> [ 'templates', 'header', 1 ],
			'knobs/header/position' 				=> [ 'templates', 'header', 2 ],
			'knobs/header/cta' 						=> [ 'templates', 'header', 3 ],
			'parts/footer' 							=> [ 'templates', 'footer', 1 ],
			'knobs/footer/position' 				=> [ 'templates', 'footer', 2 ],
			'parts/section' 						=> [ 'templates', 'section', 1 ],
			'parts/card' 							=> [ 'templates', 'card', 1 ],
			'parts/buttons' 						=> [ 'templates', 'buttons', 1 ],
			'parts/lists' 							=> [ 'templates', 'lists', 1 ],
			'parts/tables' 						=> [ 'templates', 'tables', 1 ],
			'parts/forms' 							=> [ 'templates', 'forms', 1 ],

			// The type: the root size and the five roles are the page's; the size of the links of the header, the type of the footer, the sizes of the titles and the type of a list and of a table are the object's own, and so is the case of a thing, a switch under the sizes of its object
			'size' 									=> [ 'type', 'page', 1 ],
			'fonts/title' 							=> [ 'type', 'page', 2 ],
			'fonts/subtitle' 						=> [ 'type', 'page', 3 ],
			'fonts/text' 							=> [ 'type', 'page', 4 ],
			'fonts/button' 							=> [ 'type', 'page', 5 ],
			'fonts/code' 							=> [ 'type', 'page', 6 ],
			'knobs/header/nav' 						=> [ 'type', 'header', 1 ],
			'knobs/header/nav-case' 				=> [ 'type', 'header', 2 ],
			'knobs/footer/scale' 					=> [ 'type', 'footer', 1 ],
			'knobs/footer/nav-case' 				=> [ 'type', 'footer', 2 ],
			'knobs/section/title' 					=> [ 'type', 'section', 1 ],
			'knobs/section/subtitle' 				=> [ 'type', 'section', 2 ],
			'knobs/section/quiet' 					=> [ 'type', 'section', 3 ],
			'knobs/section/loud' 					=> [ 'type', 'section', 4 ],
			'knobs/section/title-case' 				=> [ 'type', 'section', 5 ],
			'knobs/section/subtitle-case' 			=> [ 'type', 'section', 6 ],
			'knobs/card/title' 						=> [ 'type', 'card', 1 ],
			'knobs/card/subtitle' 					=> [ 'type', 'card', 2 ],
			'knobs/card/title-case' 				=> [ 'type', 'card', 3 ],
			'knobs/card/subtitle-case' 				=> [ 'type', 'card', 4 ],
			'knobs/buttons/size' 					=> [ 'type', 'buttons', 1 ],
			'knobs/buttons/spread' 					=> [ 'type', 'buttons', 2 ],
			'knobs/buttons/case' 					=> [ 'type', 'buttons', 3 ],
			'knobs/lists/scale' 					=> [ 'type', 'lists', 1 ],
			'knobs/tables/scale' 					=> [ 'type', 'tables', 1 ],
			'knobs/forms/scale' 					=> [ 'type', 'forms', 1 ],
			'knobs/forms/label-case' 				=> [ 'type', 'forms', 2 ],

			// The surfaces: the space, the corners, the lines and the shadows of each object, the height and the line of the frames and the marker of a list. The three kinds of a section share one place: they are one choice
			'width' 								=> [ 'surfaces', 'page', 1 ],
			'whitespace' 							=> [ 'surfaces', 'page', 2 ],
			'radius' 								=> [ 'surfaces', 'page', 3 ],
			'radius-alt' 							=> [ 'surfaces', 'page', 4 ],
			'radii/image' 							=> [ 'surfaces', 'page', 5 ],
			'knobs/header/height' 					=> [ 'surfaces', 'header', 1 ],
			'knobs/header/space' 					=> [ 'surfaces', 'header', 2 ],
			'knobs/header/border-size' 				=> [ 'surfaces', 'header', 3 ],
			'knobs/footer/height' 					=> [ 'surfaces', 'footer', 1 ],
			'knobs/footer/space' 					=> [ 'surfaces', 'footer', 2 ],
			'knobs/footer/border-size' 				=> [ 'surfaces', 'footer', 3 ],
			'knobs/section/space' 					=> [ 'surfaces', 'section', 1 ],
			'knobs/section/rule' 					=> [ 'surfaces', 'section', 2 ],
			'radii/cover' 							=> [ 'surfaces', 'section', 3 ],
			'radii/row' 							=> [ 'surfaces', 'section', 3 ],
			'radii/column' 							=> [ 'surfaces', 'section', 3 ],
			'knobs/card/space' 						=> [ 'surfaces', 'card', 1 ],
			'knobs/card/border' 					=> [ 'surfaces', 'card', 2 ],
			'knobs/card/shadow' 					=> [ 'surfaces', 'card', 3 ],
			'radii/card' 							=> [ 'surfaces', 'card', 4 ],
			'knobs/buttons/space' 					=> [ 'surfaces', 'buttons', 1 ],
			'knobs/buttons/corners' 				=> [ 'surfaces', 'buttons', 2 ],
			'knobs/lists/space' 					=> [ 'surfaces', 'lists', 1 ],
			'knobs/lists/marker' 					=> [ 'surfaces', 'lists', 2 ],
			'knobs/tables/space' 					=> [ 'surfaces', 'tables', 1 ],
			'knobs/tables/border' 					=> [ 'surfaces', 'tables', 2 ],
			'knobs/tables/zebra' 					=> [ 'surfaces', 'tables', 3 ],
			'knobs/forms/space' 					=> [ 'surfaces', 'forms', 1 ],
			'knobs/forms/corners' 					=> [ 'surfaces', 'forms', 2 ],
			'knobs/forms/line' 						=> [ 'surfaces', 'forms', 3 ],
			'knobs/forms/labels' 					=> [ 'surfaces', 'forms', 4 ],

			// The colours: the palette is the page's, the frames have the ground of their bar and the colour of its line, and three objects have a ladder of colour of their own - the dimming of a section, the tone of the shadow of a card, the stripes of a table; the forms have the colour of their lines and of their labels, which are inks. The second colour is the swatch at the end of the row of Harmony: one control
			'colours/primary' 						=> [ 'colours', 'page', 1 ],
			'colours/secondary' 					=> [ 'colours', 'page', 2 ],
			'colours/harmony' 						=> [ 'colours', 'page', 2 ],
			'colours/temperature' 					=> [ 'colours', 'page', 3 ],
			'colours/saturation' 					=> [ 'colours', 'page', 4 ],
			'colours/contrast' 						=> [ 'colours', 'page', 5 ],
			'colours/depth' 						=> [ 'colours', 'page', 6 ],
			'colours/mode' 							=> [ 'colours', 'page', 7 ],
			'knobs/header/ground' 					=> [ 'colours', 'header', 1 ],
			'knobs/header/border-color' 			=> [ 'colours', 'header', 2 ],
			'knobs/footer/ground' 					=> [ 'colours', 'footer', 1 ],
			'knobs/footer/border-color' 			=> [ 'colours', 'footer', 2 ],
			'knobs/section/dim' 					=> [ 'colours', 'section', 1 ],
			'knobs/card/tone' 						=> [ 'colours', 'card', 1 ],
			'knobs/tables/stripes' 					=> [ 'colours', 'tables', 1 ],
			'knobs/forms/edge' 						=> [ 'colours', 'forms', 1 ],
			'knobs/forms/label' 					=> [ 'colours', 'forms', 2 ],

			// The motion: the animation of the page and the hover of its pictures, and what a card and a button do under the pointer
			'motion/effect' 						=> [ 'motion', 'page', 1 ],
			'motion/strength' 						=> [ 'motion', 'page', 2 ],
			'motion/speed' 							=> [ 'motion', 'page', 3 ],
			'motion/hover' 							=> [ 'motion', 'page', 4 ],
			'motion/hover-strength' 				=> [ 'motion', 'page', 5 ],
			'knobs/card/hover' 						=> [ 'motion', 'card', 1 ],
			'knobs/buttons/hover' 					=> [ 'motion', 'buttons', 1 ],
		];

		/*	How a set says which knobs it answers to: one ladder per knob, named
			after the part and the knob and nothing else.

			  --section-space--least / --less / --default / --more / --most

			A ladder is the five values of a scale, the middle of them the template as it
			is, the three of a choice, which are declared under less, default and more, or
			the two of a toggle, under default and more: off, which is the template as it is,
			and on.
			Declaring the ladder *is* publishing the knob - the panel offers exactly what
			a set declares and greys out what it does not, so a handle the stylesheet
			does not answer to can never be offered. A scale with three of its five, and a
			choice with five of its three, have published nothing - nor has a toggle with
			three of its two. The @knob line in a
			set's own opening comment is documentation for whoever reads the file; the
			panel reads the declarations.

			A value that cannot say all a knob means - the stripes of a table are a
			background and a text colour - may be declared in more ladders, named after
			the knob and an aspect of it (--tables-zebra-ink--less ...). They are the
			knob's too: they are selected where it stands, and are as long as it is. A
			stylesheet that declares one without the ladder of the knob itself has
			published nothing, and one with another length than its knob's has not
			published the knob. A name that is a knob of its own is no aspect of another:
			--section-title-case is the toggle of that name and not an aspect of the
			size --section-title.

			The name of a rung is KNOB_TOKEN filled with the part, the name and the
			word: ladders() reads the declarations with it, and the compiler asks
			the rung of the position with it */
		public const string KNOB_TOKEN = '--%s-%s--%s';

		/*	The five kinds of knob a feature can declare in the head of a variant. A scale and a choice are ladders, as the knobs of the
			eight parts are, with the key of the feature in the place of the part's name (KNOB_TOKEN). A ground is one of the surfaces of
			the palette (Colours::SURFACES), an ink one of the seven tones of INKS, a colour a pair of hex colours - light, then dark	*/
		public const array FEATURE_KINDS = [ 'scale', 'choice', 'ground', 'ink', 'colour' ];

		/*	...of which these two are ladders, declared in the stylesheet of the variant and published by it (see variants()). A
			toggle is a ladder too, of two rungs, and is the parts' alone: a feature cannot declare one (see FEATURE_KINDS)	*/
		public const array LADDERS = [ 'scale', 'choice', 'toggle' ];

		/*	The seven tones the page writes with on whatever ground an element stands on, and the custom property of the kernel each is: the
			roles Nino.css re-points inside .nino-section--alt, --tint, --dark and --black (and in the grid, the row and the card of the same
			ground), so that an ink is the right one on every ground. The brand is one tone here - the grade the palette solves it to as a
			text colour, which is why its quiet and its loud grade are grounds and no inks. tests/design-smoke.php holds each token to the
			:root of the Nino.css it runs against	*/
		public const array INKS = [
			'ink' 			=> '--color-ink',
			'subtitle' 	=> '--color-subtitle',
			'title' 		=> '--color-title',
			'accent' 		=> '--color-accent',
			'primary' 	=> '--color-primary',
			'border' 		=> '--color-border',
			'focus' 		=> '--color-focus',
		];

		/*	The block a feature brings for the specimen of the preview: its file below the feature's directory, and the prefix of the id of the
			section Design makes around it, which is the prefix and the key of the feature - the place the panel scrolls the frame to when a
			control of the feature is used (see previewOf(), Preview::blocks()). The ids inside a block start with the key and a hyphen	*/
		public const string PREVIEW = 'design/preview.tpl';
		public const string ANCHOR_PREFIX = 'feature-';

		/*	The styles a feature declares once for all its variants: the file design/styles.css below the feature's directory, which is no variant (see
			variants()), and the directory design/styles/ beside it, where the files of the options stand (see featureStyles())	*/
		public const string FEATURE_STYLES = 'styles';

		// How much of a stylesheet is read for its opening comment: a variant of a feature declares its knobs there, and its head is longer than a set's
		private const int HEAD = 16384;

		// What a set is called, as a file name: letters, digits and the dot, the hyphen and the underscore after the first (file() holds the name against '..' as well)
		private const string SET_NAME = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/D';

		/*	What a style and an option are called, and so a style file (<style>-<option>.css): a slug each, the pattern a text key's words have. A style
			starts with a letter: a name of digits alone is an integer key in a php array, and an array that has only such keys is a list - which the wire
			answers as one, and which the screen takes for none. An option is the slug of the file's name, as it was, and a file name is held to it as a whole	*/
		private const string STYLE_NAME = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D';
		private const string OPTION_NAME = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		/*	The root size, as the percentage pair Nino.css wants: below the 768px
			breakpoint, and from it. Relative, never a length - see
			--base-size in Nino.css.

			Five positions, -2 to +2, each a choice somebody can see on the page rather
			than one they have to measure. Below the breakpoint they are 85, 92.5, 100,
			115 and 145%: the two steps under the middle 7.5% each, the two over it 15%
			and 30%, so that the small end is close and the large end reaches far. At a
			browser default of 16px that is 13.6, 14.8, 16, 18.4 and 23.2px. From it
			each is 1.125 times its own, the ratio of the kernel's pair of 100% and
			112.5%: 15.3, 16.65, 18, 20.7 and 26.1px. The middle position is the
			delivered size and does not move, so nothing compiled before this reads
			differently. The model keeps the position, a number, and not the pair	*/
		public const array SIZES = [
			-2 	=> [ '85%', '95.625%' ],
			-1 	=> [ '92.5%', '104.0625%' ],
			0 	=> [ '100%', '112.5%' ],
			1 	=> [ '115%', '129.375%' ],
			2 	=> [ '145%', '163.125%' ],
		];

		/*	The width of the page, as the maximum width of a row and the space
			between the viewport and it, below 768px and from it. 0 is Nino.css as
			it is and writes nothing: a row of at most 120rem, with the kernel's own
			space to the sides (--space-2, from 768px --space-3).

			The four others run from a reading column to the edge, and every one of the
			five is another row in a preview of 1280px, which is the width the panel
			shows. At the root size the kernel has from 768px, 18px, the frame is 1280px
			and a section keeps 36px of padding of its own at both sides, so a row has
			1208px at the most, and its text 1208px less the space to the sides, twice:

				-2	48rem, 864px	with 36px to each side: a text of 792px. A reading column
				-1	64rem, 1152px	with 36px to each side: 1080px. A page that is contained
				 0	120rem, 2160px	more than there is: the row is 1208px, 1136px of text
				 1	none			1208px, the kernel's small space to the sides at every
								width, 18px: 1172px of text
				 2	none			1208px and from 768px no space to the sides: 1208px of
								text, to the edge of the section's own padding. Below 768px
								the space stays - a phone is as wide as it gets, and the text
								stays off the edge of the screen

			Nino.css reads --grid-max-width as a max-width, of which none is a value,
			and --grid-gutter as the padding of a row, of which 0 is one.	*/
		public const array WIDTHS = [
			-2 	=> [ '48rem', 'var(--space-2)', 'var(--space-3)' ],
			-1 	=> [ '64rem', 'var(--space-2)', 'var(--space-3)' ],
			1 	=> [ 'none', 'var(--space-2)', 'var(--space-2)' ],
			2 	=> [ 'none', 'var(--space-2)', '0' ],
		];

		/*	The whitespace of the page, as the number Nino.css's --whitespace-scale is set
			to: five positions, -2 to +2, from three quarters of the air to twice it. 0 is
			Nino.css as it is - the layout of every page that never heard of the token, to
			the pixel - and writes nothing, like the width. The kernel takes the number
			whole for the areas (the padding of a section, the margin and the padding of a
			card and of what is in it, the gap between the columns of a row of cards) and
			derives two damped strengths from it for what stands between pieces (the gaps
			of a stack: .8 of the way from 1 to the number) and for the room under text (a
			title, a paragraph, the text of a card: .6 of the way), so that twice the air
			round a section is not twice the gap in a paragraph - at 2 the areas are
			doubled, the gaps 1.8 times and the text 1.6, at .75 they are .75, .8 and .85.
			What the number never touches - the width, the line heights, the buttons and
			the utility classes - Nino.css says as well	*/
		public const array WHITESPACE = [ -2 => '.75', -1 => '.9', 0 => '1', 1 => '1.5', 2 => '2' ];

		/*	The radius of a surface, the way Nino.css has it at its :root: two strengths for the whole page, the
			standard one --radius-surface and the alternative one --radius-surface-alt, and five kinds of surface
			- the picture of a padded cover, a row, a column, a card and the frame of a picture -, each with the
			token --radius-<kind> that points at one of the two and the marker --radius-<kind>-default, a plain
			word that says which, for a tool that wants the default of a kind without resolving a length. The
			setup holds a position for each strength (`radius` and `radius-alt`) and a word for each kind (`radii`).
			The positions are five, -2 to +2, and one table says the length of both: the middle is the kernel's
			small radius, .1rem, and +1 its large one, .4rem, so that a setup without the keys stands at 0 for
			the standard strength and at +1 for the alternative one, which is Nino.css as it is	*/
		public const array RADIUS = [ -2 => '0rem', -1 => '.05rem', 0 => '.1rem', 1 => '.4rem', 2 => '1rem' ];

		// The kinds of surface in the order Nino.css names them, and the two words a kind is set to: the strength it takes
		public const array SURFACES = [ 'cover', 'row', 'column', 'card', 'image' ];

		public const array RADIUS_WORDS = [ 'standard', 'alternative' ];

		/*	The animation of the page: what Nino.css sets for nino-vpa--<effect>-<strength>
			and nino-vpa--speed-<speed>, as the properties of a rule on main. Held in
			code, because the compiler writes them and does not read a stylesheet it
			has no business with - tests/design-smoke.php reads them against the
			Nino.css it runs against. An effect with no strength is the middle one.
			The slides are named for the motion the visitor sees, as Nino.css names
			them: slide-left starts to the right of its place and slides left,
			slide-up starts below it and slides up	*/
		public const array STRENGTHS = [ 'soft', 'medium', 'hard' ];

		public const array SPEEDS = [ 'fast' => '.45s', 'medium' => '1s', 'slow' => '2s' ];

		public const array EFFECTS = [
			'zoom' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'scale(.94)', '--vpa-t-visible' => 'scale(1)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'scale(.85)', '--vpa-t-visible' => 'scale(1)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'scale(.7)', '--vpa-t-visible' => 'scale(1)' ],
			],
			'zoom-out' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'scale(1.06)', '--vpa-t-visible' => 'scale(1)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'scale(1.15)', '--vpa-t-visible' => 'scale(1)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'scale(1.35)', '--vpa-t-visible' => 'scale(1)' ],
			],
			'slide-left' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateX(1.5rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateX(3rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateX(6rem)', '--vpa-t-visible' => 'translateX(0)' ],
			],
			'slide-right' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateX(-1.5rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateX(-3rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateX(-6rem)', '--vpa-t-visible' => 'translateX(0)' ],
			],
			'slide-up' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateY(1.5rem)', '--vpa-t-visible' => 'translateY(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateY(3rem)', '--vpa-t-visible' => 'translateY(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateY(6rem)', '--vpa-t-visible' => 'translateY(0)' ],
			],
			'slide-down' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateY(-1.5rem)', '--vpa-t-visible' => 'translateY(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateY(-3rem)', '--vpa-t-visible' => 'translateY(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateY(-6rem)', '--vpa-t-visible' => 'translateY(0)' ],
			],
			'blur' => [
				'soft' 		=> [ '--vpa-f-hidden' => 'blur(6px)', '--vpa-f-visible' => 'blur(0)' ],
				'medium' 	=> [ '--vpa-f-hidden' => 'blur(12px)', '--vpa-f-visible' => 'blur(0)' ],
				'hard' 		=> [ '--vpa-f-hidden' => 'blur(24px)', '--vpa-f-visible' => 'blur(0)' ],
			],
			'flip' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-20deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-40deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-65deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
			],
		];

		/*	The hover of the pictures of the page: the custom properties Nino.css reads for
			a picture that says nino-hover, as the properties of the rule on main that the
			animation writes, after its own. Held in code, as the effects are, and held to
			the stylesheet by tests/design-smoke.php: the names are the kernel's, and
			--image-hover-duration, the time the change takes, is the kernel's and never
			written. A kind with no strength is the middle one, and no kind is Nino.css's
			own gentle zoom, which writes nothing. Zoom grows the picture, bright brightens
			it and keeps its size - the factor Nino.css grows it by is set to 1, or the
			brightening would come with a zoom nobody chose -, and zoom-bright does both. None
			leaves the picture as it is under the pointer, which a template that says the
			class does not change: every handle at the value that does nothing, the same for
			all three strengths. There is no lift: a frame that rises is itself under the
			pointer, which is a hover of its own	*/
		public const array HOVERS = [
			'zoom' => [
				'soft' 		=> [ '--image-hover-scale' => '1.03' ],
				'medium' 	=> [ '--image-hover-scale' => '1.06' ],
				'hard' 		=> [ '--image-hover-scale' => '1.1' ],
			],
			'bright' => [
				'soft' 		=> [ '--image-hover-scale' => '1', '--image-hover-brightness' => '1.06' ],
				'medium' 	=> [ '--image-hover-scale' => '1', '--image-hover-brightness' => '1.12' ],
				'hard' 		=> [ '--image-hover-scale' => '1', '--image-hover-brightness' => '1.2' ],
			],
			'zoom-bright' => [
				'soft' 		=> [ '--image-hover-scale' => '1.03', '--image-hover-brightness' => '1.06' ],
				'medium' 	=> [ '--image-hover-scale' => '1.06', '--image-hover-brightness' => '1.12' ],
				'hard' 		=> [ '--image-hover-scale' => '1.1', '--image-hover-brightness' => '1.2' ],
			],
			'none' => [
				'soft' 		=> [ '--image-hover-scale' => '1', '--image-hover-lift' => '0px', '--image-hover-brightness' => '1', '--image-hover-shadow' => 'none' ],
				'medium' 	=> [ '--image-hover-scale' => '1', '--image-hover-lift' => '0px', '--image-hover-brightness' => '1', '--image-hover-shadow' => 'none' ],
				'hard' 		=> [ '--image-hover-scale' => '1', '--image-hover-lift' => '0px', '--image-hover-brightness' => '1', '--image-hover-shadow' => 'none' ],
			],
		];

		/**
		 *	Everything a fresh install starts from: the eight sets of the first
		 *	theme the feature ships (see Themes::SHIPPED - the theme that is the
		 *	framework as it is), every knob where that theme puts it, which is the
		 *	middle but for the navigation of the header on the right and its button
		 *	hidden, the delivered root
		 *	size, the width, the whitespace, the radius, the animation and the
		 *	hover of the pictures of Nino.css, the face the base unit delivers,
		 *	the framework's own colours, no theme and no css of its own
		 *
		 *	The eight sets and the positions of the knobs are read out of that
		 *	theme's file, which is a php array and is included the way
		 *	Themes::read() includes a shipped file - only the names of the sets
		 *	and the positions are taken from it, as they stand, and not a
		 *	call into Themes::setup() and the normalisation of a theme, which
		 *	itself starts from these defaults
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *
		 *	@return 	array
		 */
		public static function defaults( string $libraryDir ): array {

			$first = rtrim( $libraryDir, '/' ). '/themes/'. Themes::SHIPPED[0]. '.php';
			$theme = is_file( $first ) === true ? include $first : [];
			$named = is_array( $theme ) === true && is_array( $theme['setup']['parts'] ?? null ) === true ? $theme['setup']['parts'] : [];
			$parts = [];

			foreach( array_keys( self::PARTS ) as $part )
				$parts[$part] = is_string( $named[$part] ?? null ) === true ? $named[$part] : '';

			$knobs = [];

			foreach( self::KNOBS as $part => $kinds )
				foreach( $kinds as $knob => $kind )
					$knobs[$part][$knob] = $kind === 'ink' ? self::tone( $theme['setup']['knobs'][$part][$knob] ?? '' ) : self::position( $theme['setup']['knobs'][$part][$knob] ?? 0, $kind );

			return [
				'format' 	=> self::FORMAT,
				// The slug of the theme the choices were taken from, '' for none
				'theme' 	=> '',
				'size' 		=> 0,
				'width' 	=> 0,
				'whitespace' => 0,
				// The standard strength at the kernel's small radius, the alternative at its large one, and every kind of surface on the standard one
				'radius' 	=> 0,
				'radius-alt' => 1,
				'radii' 	=> array_fill_keys( self::SURFACES, self::RADIUS_WORDS[0] ),
				// '' is what Nino.css does by itself: a fade and a rise, as fast as it is, and a gentle zoom of the pictures that say nino-hover
				'motion' 	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ],
				'parts' 	=> $parts,
				'knobs' 	=> $knobs,
				// The styles of the templates that are chosen: for each part the style => option pairs, none is the template as it is
				'styles' 	=> array_fill_keys( array_keys( self::PARTS ), [] ),
				// What was chosen for the features that bring variants: an entry for each, key => set and knobs - none is the feature as it is
				'features' => [],
				'fonts' 	=> Fonts::DEFAULTS,
				// The palette half, in Colours' own vocabulary - two colours, five
				// knobs and the mode. Its defaults are the framework's own values, so
				// a setup that never opened the Colours tab compiles to the palette
				// library/base.css already declares
				'colours'	=> Colours::normalize( [] ),
				// The custom css, which the compiler writes as the last section of the sheet
				'css' 		=> '',
				'compiled'=> [],
			];
		}

		/**
		 *	The word a stylesheet declares a knob position under: -2 is least, -1
		 *	less, 0 default, +1 more and +2 most. The model keeps numbers, the
		 *	library keeps words, and this is the only place that knows both
		 *
		 *	@param		int				$position			-2 to 2; a number past the ends is the end
		 *
		 *	@return 	string
		 */
		public static function word( int $position ): string {
			return self::STEPS[ max( 0, min( count( self::STEPS ) - 1, $position + 2 ) ) ];
		}

		/**
		 *	The strength a kind of surface takes in a setup: the word it is set to, and the
		 *	standard one for a kind with none or with a word that is none of the two
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$kind					A value of SURFACES
		 *
		 *	@return 	string								A value of RADIUS_WORDS
		 */
		public static function radiusOf( array $setup, string $kind ): string {
			return ( $setup['radii'][$kind] ?? null ) === self::RADIUS_WORDS[1] ? self::RADIUS_WORDS[1] : self::RADIUS_WORDS[0];
		}

		/**
		 *	What a knob is: 'scale', 'choice', 'toggle' or 'ink' (see KNOBS)
		 *
		 *	@param		string		$part					A key of KNOBS
		 *	@param		string		$knob					A name in KNOBS[$part]
		 *
		 *	@return 	string								'' for a knob there is none of
		 */
		public static function kind( string $part, string $knob ): string {
			return self::KNOBS[$part][$knob] ?? '';
		}

		/**
		 *	The positions a kind of knob can stand at, from the least to the most: two to
		 *	each side of the middle for a scale, one for a choice, and for a toggle off,
		 *	which is the middle, and on. An ink has the middle alone: what it stands at is a tone
		 *	and no position (see tone()), and the one rung it has is the tone the template gives.
		 *	A kind there is none of has the middle alone as well
		 *
		 *	@param		string		$kind					'scale', 'choice', 'toggle' or 'ink'
		 *
		 *	@return 	array										Integers, eg. [ -1, 0, 1 ]
		 */
		public static function positions( string $kind ): array {

			return match( $kind ) {
				'scale' 	=> [ -2, -1, 0, 1, 2 ],
				'choice' 	=> [ -1, 0, 1 ],
				'toggle' 	=> [ 0, 1 ],
				'ink' 		=> [ 0 ],
				default 	=> [ 0 ],
			};
		}

		/**
		 *	What an ink stands at: one of the tones of INKS, or '' for the tone the template itself gives it - which is the
		 *	middle of an ink, as it is of a ladder. Anything else - a number, a word that is no tone, an array - is ''
		 *
		 *	@param		mixed			$value				Straight off the wire
		 *
		 *	@return 	string
		 */
		public static function tone( mixed $value ): string {
			return is_string( $value ) === true && isset( self::INKS[$value] ) === true ? $value : '';
		}

		/**
		 *	The word a knob stands at, as the selection of the sheet and its header say it: the step of a ladder, and for an ink
		 *	the tone chosen - or 'default', the tone the template gives
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					A key of KNOBS
		 *	@param		string		$knob					A name in KNOBS[$part]
		 *
		 *	@return 	string
		 */
		public static function said( array $setup, string $part, string $knob ): string {

			$tone = self::kind( $part, $knob ) === 'ink' ? self::tone( $setup['knobs'][$part][$knob] ?? '' ) : '';

			return $tone !== '' ? $tone : self::step( $setup, $part, $knob );
		}

		/**
		 *	The words a ladder of that kind is declared under, the words of its positions: all
		 *	five of STEPS for a scale, the inner three for a choice, default and more for a toggle,
		 *	default alone for an ink
		 *
		 *	@param		string		$kind					'scale', 'choice', 'toggle' or 'ink'
		 *
		 *	@return 	array
		 */
		public static function words( string $kind ): array {

			return array_map( self::word( ... ), self::positions( $kind ) );
		}

		/**
		 *	The sets a part can be given, sorted, as they are on disk
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *
		 *	@return 	array										Set names, eg. [ 'regular', 'pill' ]
		 */
		public static function available( string $libraryDir, string $part ): array {

			$kind = self::PARTS[$part] ?? '';
			$found = [];

			if( $kind === 'frame' )
				foreach( glob( rtrim( $libraryDir, '/' ). '/'. $part. '/*', GLOB_ONLYDIR ) ?: [] as $dir )
					if( is_file( $dir. '/template.tpl' ) === true )
						$found[] = basename( $dir );

			if( $kind === 'set' )
				foreach( glob( rtrim( $libraryDir, '/' ). '/'. $part. '/*.css' ) ?: [] as $file )
					$found[] = basename( $file, '.css' );

			sort( $found, SORT_NATURAL );

			return $found;
		}

		/**
		 *	The file a part's choice resolves to
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *	@param		string		$which				'style' or, for a frame, 'template'
		 *
		 *	@return 	string								A path, '' when the choice does not exist
		 */
		public static function file( string $libraryDir, string $part, string $set, string $which = 'style' ): string {

			$libraryDir = rtrim( $libraryDir, '/' );

			// A set name is a file name, and a file name from a stored setup is
			// input: anything that could climb out of the library is not a set
			if( self::named( $set ) === false )
				return '';

			if( isset( self::PARTS[$part] ) === false )
				return '';

			$path = self::PARTS[$part] === 'frame'
				? $libraryDir. '/'. $part. '/'. $set. '/'. ( $which === 'template' ? 'template.tpl' : 'style.css' )
				: $libraryDir. '/'. $part. '/'. $set. '.css';

			return is_file( $path ) === true ? $path : '';
		}

		/**
		 *	What a variant is called and what it is, for the panel to list it as.
		 *
		 *	Read from the stylesheet's own opening comment - `@name` and
		 *	`@description` on their own lines - rather than from a manifest
		 *	beside it (see head(), the one parser of it). A set is one file, so a
		 *	second file per set would be a second file to keep in sync, and the
		 *	person writing sets is going to write a lot of them. A file without a
		 *	@name is offered under its own name, which is what a set in progress
		 *	looks like.
		 *
		 *	The two lines of another language stand beside them - `@name-de` and
		 *	`@description-de` for de_DE, the two letters before the underscore -
		 *	and are read where the locale is not an English one. A file that has
		 *	not got the line of that language gives the English one: a template
		 *	somebody else wrote is listed, and in the words it was written in
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *
		 *	@return 	array										[ name, description ]
		 */
		public static function describe( string $libraryDir, string $part, string $set, string $locale = 'en_US' ): array {

			$file = self::file( $libraryDir, $part, $set );
			$read = [ 'name' => $set, 'description' => '' ];

			if( $file === '' )
				return $read;

			$head = self::head( $file, $locale );

			foreach( [ 'name', 'description' ] as $tag )
				if( $head[$tag] !== '' )
					$read[$tag] = $head[$tag];

			return $read;
		}

		/**
		 *	The opening comment of a stylesheet, read: the one parser of the head of a set of
		 *	the library and of a variant of a feature. The first comment block only - a @name
		 *	further down belongs to a rule somebody documented, not to the file.
		 *
		 *	The English lines are the plain tags, the others carry their language - `@name-de`,
		 *	`@description-de`, `@knob-de` and `@option-de` for de_DE, the two letters before the
		 *	underscore - and are read where the locale is not an English one; a line the file
		 *	has not got in that language gives the English one.
		 *
		 *	A variant of a feature declares its knobs in its head, one line each:
		 *
		 *	  @knob <name> <kind> <tab> <label> [= <default>]
		 *
		 *	The name is letters, digits and hyphens, the kind one of FEATURE_KINDS, the tab one of
		 *	the tabs that draw rows but the templates (type, surfaces, colours, motion), the label the
		 *	English words. A ground, an ink and a colour need the choice they stand at before a
		 *	person chooses - a surface of the palette, a tone of INKS, the pair of hex colours, light
		 *	then dark - and a scale and a choice take none, their middle is the default. A choice
		 *	has its three words, `@option <knob> <less|default|more> <label>`, all three or the
		 *	choice is not published. A line that is none of this is dropped and named in `notes`:
		 *	the file is somebody's work in progress, and the rest of its knobs stand.
		 *
		 *	The `@knob` lines of a set of the library are documentation there - the panel reads the
		 *	ladders (see ladders()) - and in a grammar of their own; read by this, they are none of
		 *	its lines and are named in notes that a caller for a set never looks at.
		 *
		 *	A template of the library declares the styles it brings, one line each, and the same line of another language beside it:
		 *
		 *	  @style <name> <label>
		 *	  @style-de <name> <label>
		 *
		 *	The name is letters, digits and hyphens and starts with a letter, the label the words the select of the style is headed with. The files of a style
		 *	stand beside the template and are read by styles(). A line that is none of this - no name, no label, a name twice, a line of
		 *	another language for a style that is not declared - is dropped and named in `notes`, and in `styleNotes` alone, which is
		 *	what styles() hands on: the other notes of a set are the ones its @knob lines earn, which are documentation there.
		 *
		 *	A feature declares its styles the same way, in the head of design/styles.css, once for all its variants (see featureStyles()).
		 *
		 *	A variant of a feature says nothing of the specimen: the block a feature shows there is the file design/preview.tpl
		 *	beside its stylesheets, the feature's and not the variant's (see previewOf()). A line of the head that is none of the
		 *	lines above is not read and not named: the file is somebody's, and a comment may say what it likes.
		 *
		 *	@param		string		$file					A path
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *
		 *	@return 	array										name, description ('' for none), knobs (name => kind, tab, label, options, default - in the order of the lines), styles (name => label, in the order of the lines), notes and styleNotes (the ones of the style lines alone)
		 */
		public static function head( string $file, string $locale = 'en_US' ): array {

			$read = [ 'name' => '', 'description' => '', 'knobs' => [], 'styles' => [], 'notes' => [], 'styleNotes' => [] ];

			// The first comment block only: a @name further down belongs to a
			// rule somebody documented, not to the file
			$head = (string) @file_get_contents( $file, false, null, 0, self::HEAD );

			if( preg_match( '~/\*(.*?)\*/~s', $head, $block ) !== 1 )
				return $read;

			// The English lines are the plain tags, the others carry their language: a locale
			// that is not two letters of the alphabet at its start names no line of its own
			$language = strtolower( substr( $locale, 0, 2 ) );
			$own = preg_match( '/^[a-z]{2}$/', $language ) === 1 && $language !== 'en';

			foreach( [ 'name', 'description' ] as $tag )
				foreach( $own === true ? [ $tag. '-'. $language, $tag ] : [ $tag ] as $line )
					if( preg_match( '/@'. $line. '[ \t]+(.+)$/m', $block[1], $found ) === 1 ) {
						$read[$tag] = trim( (string) preg_replace( '/\s+/', ' ', $found[1] ) );
						break;
					}

			$read['knobs'] = self::declared( $block[1], $own === true ? $language : '', $read['notes'] );
			$read['styles'] = self::styled( $block[1], $own === true ? $language : '', $read['styleNotes'] );
			$read['notes'] = array_merge( $read['notes'], $read['styleNotes'] );

			return $read;
		}

		/**
		 *	The styles a head declares (see head()): its `@style` lines held to their grammar, each with its label in the language asked
		 *	for, else in English. The English line is the plain tag and is the one that declares the style; a line of another language
		 *	only gives it its words
		 *
		 *	@param		string		$comment			The text of the opening comment
		 *	@param		string		$language			The two letters of the language the labels are wanted in, '' for English
		 *	@param		array 		&$notes				(reference) The lines that were dropped, each named
		 *
		 *	@return 	array										name => label
		 */
		private static function styled( string $comment, string $language, array &$notes ): array {

			$styles 	= [];
			$labels 	= [];
			$dropped 	= static function( string $line, string $why ) use ( &$notes ): void {
				$notes[] = 'the line "'. self::shown( $line, 60 ). '" is dropped: '. $why;
			};

			foreach( (array) preg_split( '/\R/', $comment ) as $row ) {

				// The line without the asterisk and the indent it stands behind
				$line = trim( (string) preg_replace( '/^\s*\*/', '', (string) $row ) );

				if( preg_match( '/^@style(?:-([a-z]{2}))?(?:[ \t]+(.*))?$/', $line, $tag ) !== 1 )
					continue;

				$of 	= $tag[1] ?? '';
				$rest = trim( $tag[2] ?? '' );

				if( preg_match( '/^(\S+)[ \t]+(.+)$/', $rest, $found ) !== 1 ) {
					$dropped( $line, 'it is no @style'. ( $of === '' ? '' : '-'. $of ). ' line - it takes a name and a label' );
					continue;
				}

				[ , $name, $label ] = $found;

				$why = match( true ) {
					preg_match( self::STYLE_NAME, $name ) !== 1 							=> 'a name is letters, digits and hyphens, and starts with a letter',
					$of === '' && isset( $styles[$name] ) === true 				=> 'the style "'. $name. '" is declared already',
					$of !== '' && isset( $labels[$of][$name] ) === true 	=> 'the style "'. $name. '" has its @style-'. $of. ' line already',
					default 																							=> '',
				};

				if( $why !== '' ) {
					$dropped( $line, $why );
					continue;
				}

				if( $of === '' )
					$styles[$name] = trim( $label );
				else
					$labels[$of][$name] = trim( $label );
			}

			// A line of another language for a style that is not there
			foreach( $labels as $of => $named )
				foreach( array_keys( $named ) as $name )
					if( isset( $styles[$name] ) === false )
						$notes[] = 'the label "@style-'. $of. ' '. self::shown( $name ). '" names no style - dropped';

			foreach( $styles as $name => $label )
				$styles[$name] = $labels[$language][$name] ?? $label;

			return $styles;
		}

		/**
		 *	The knobs a head declares (see head()): its `@knob` and `@option` lines held to their
		 *	grammar, each with its label and - for a choice - its three words in the language asked for
		 *
		 *	@param		string		$comment			The text of the opening comment
		 *	@param		string		$language			The two letters of the language the labels are wanted in, '' for English
		 *	@param		array 		&$notes				(reference) The lines that were dropped, each named
		 *
		 *	@return 	array										name => kind, tab, label, options, default
		 */
		private static function declared( string $comment, string $language, array &$notes ): array {

			$tabs 		= array_values( array_diff( self::TABS, [ 'templates', 'css' ] ) );
			$words 		= self::words( 'choice' );
			$expects 	= [
				'ground' 	=> 'a surface of the palette: '. implode( ', ', Colours::SURFACES ),
				'ink' 		=> 'a tone of the page: '. implode( ', ', array_keys( self::INKS ) ),
				'colour' 	=> 'two hex colours, light then dark',
			];
			$knobs 		= [];
			$lines 		= [];
			$labels 	= [];
			$options 	= [];
			$dropped 	= static function( string $line, string $why ) use ( &$notes ): void {
				$notes[] = 'the line "'. self::shown( $line, 60 ). '" is dropped: '. $why;
			};

			foreach( (array) preg_split( '/\R/', $comment ) as $row ) {

				// The line without the asterisk and the indent it stands behind
				$line = trim( (string) preg_replace( '/^\s*\*/', '', (string) $row ) );

				if( preg_match( '/^@(knob|option)(?:-([a-z]{2}))?(?:[ \t]+(.*))?$/', $line, $tag ) !== 1 )
					continue;

				$of 	= $tag[2] ?? '';
				$rest = trim( $tag[3] ?? '' );

				if( $tag[1] === 'option' ) {

					if( preg_match( '/^(\S+)[ \t]+(\S+)[ \t]+(.+)$/', $rest, $found ) !== 1 )
						$dropped( $line, 'it is no @option'. ( $of === '' ? '' : '-'. $of ). ' line - it takes a knob, one of '. implode( ', ', $words ). ' and a label' );
					else if( in_array( $found[2], $words, true ) === false )
						$dropped( $line, 'there is no word "'. self::shown( $found[2] ). '" of a choice - '. implode( ', ', $words ) );
					else
						$options[$of][ $found[1] ][ $found[2] ] = trim( $found[3] );

					continue;
				}

				if( $of !== '' ) {

					if( preg_match( '/^(\S+)[ \t]+(.+)$/', $rest, $found ) === 1 )
						$labels[$of][ $found[1] ] = trim( $found[2] );
					else
						$dropped( $line, 'it is no @knob-'. $of. ' line - it takes a knob and a label' );

					continue;
				}

				if( preg_match( '/^(\S+)[ \t]+(\S+)[ \t]+(\S+)[ \t]+(.+)$/', $rest, $found ) !== 1 ) {
					$dropped( $line, 'it is no @knob line - it takes a name, a kind, a tab and a label, and after an equals sign the default of a ground, an ink or a colour' );
					continue;
				}

				[ , $name, $kind, $tab, $tail ] = $found;

				// The label is what stands before a ' = ', the default what stands after
				$default = null;

				if( preg_match( '/^(.*?)[ \t]+=[ \t]+(.+)$/', $tail, $pair ) === 1 ) {
					$tail 		= $pair[1];
					$default 	= trim( $pair[2] );
				}

				$value = match( $kind ) {
					'ground' 	=> in_array( (string) $default, Colours::SURFACES, true ) === true ? $default : null,
					'ink' 		=> isset( self::INKS[ (string) $default ] ) === true ? $default : null,
					'colour' 	=> self::pair( preg_split( '/\s+/', (string) $default ) ?: [] ),
					'scale', 'choice' => $default === null ? 0 : null,
					default 	=> null,
				};

				$why = match( true ) {
					preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $name ) !== 1 		=> 'a name is letters, digits and hyphens',
					isset( $knobs[$name] ) === true 															=> 'the knob "'. $name. '" is declared already',
					in_array( $kind, self::FEATURE_KINDS, true ) === false 				=> 'there is no kind "'. self::shown( $kind ). '" - '. implode( ', ', self::FEATURE_KINDS ),
					in_array( $tab, $tabs, true ) === false 											=> 'there is no tab "'. self::shown( $tab ). '" to put a knob on - '. implode( ', ', $tabs ),
					$value === null 																							=> 'the kind "'. $kind. '" '. ( in_array( $kind, self::LADDERS, true ) === true ? 'takes no default - its middle is the one it stands at' : 'needs a default after an equals sign - '. $expects[$kind] ),
					trim( $tail ) === '' 																					=> 'it has no label',
					default 																											=> '',
				};

				if( $why !== '' ) {
					$dropped( $line, $why );
					continue;
				}

				$knobs[$name] = [ 'kind' => $kind, 'tab' => $tab, 'label' => trim( $tail ), 'options' => [], 'default' => $value ];
				$lines[$name] = $line;
			}

			// A ground writes one property for each part of its surface, named <knob>-<part> (see Compiler): a knob of that name in the
			// same head would write the same property, and the later line would win in silence - so the ground keeps the name
			foreach( $knobs as $name => $knob )
				if( $knob['kind'] === 'ground' )
					foreach( array_slice( Colours::parts(), 1 ) as $part )
						if( isset( $knobs[ $name. '-'. $part ] ) === true ) {
							$dropped( $lines[ $name. '-'. $part ], 'the ground "'. $name. '" writes '. $name. '-'. $part. ' for the '. $part. ' of its surface - a ground takes the names of its parts' );
							unset( $knobs[ $name. '-'. $part ], $lines[ $name. '-'. $part ] );
						}

			// A choice has its three words in English, or it is not published - and its words are explained by that, not named a second time
			$gone = [];

			foreach( $knobs as $name => $knob ) {

				if( $knob['kind'] !== 'choice' )
					continue;

				$missing = array_values( array_diff( $words, array_keys( $options[''][$name] ?? [] ) ) );

				if( $missing !== [] ) {
					$notes[] = 'the choice "'. $name. '" has no @option line for '. implode( ' and ', $missing ). ' - dropped';
					unset( $knobs[$name] );
					$gone[$name] = true;
				}
			}

			// A line of another language, or an option, for a knob that is not there or is no choice
			foreach( $labels as $of => $named )
				foreach( array_keys( $named ) as $name )
					if( isset( $knobs[$name] ) === false )
						$notes[] = 'the label "@knob-'. $of. ' '. self::shown( $name ). '" names no knob - dropped';

			foreach( $options as $of => $named )
				foreach( array_keys( $named ) as $name )
					if( ( $knobs[$name]['kind'] ?? '' ) !== 'choice' && isset( $gone[$name] ) === false )
						$notes[] = 'the words "@option'. ( $of === '' ? '' : '-'. $of ). ' '. self::shown( $name ). '" name no choice - dropped';

			foreach( $knobs as $name => $knob ) {

				$knobs[$name]['label'] = $labels[$language][$name] ?? $knob['label'];

				if( $knob['kind'] === 'choice' )
					foreach( $words as $word )
						$knobs[$name]['options'][$word] = $options[$language][$name][$word] ?? $options[''][$name][$word];
			}

			return $knobs;
		}

		/**
		 *	Every variant a part can be given, described - what the panel's
		 *	picker is built out of
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *
		 *	@return 	array										[ set => [ name, description ] ]
		 */
		public static function catalogue( string $libraryDir, string $part, string $locale = 'en_US' ): array {

			$catalogue = [];

			foreach( self::available( $libraryDir, $part ) as $set )
				$catalogue[$set] = self::describe( $libraryDir, $part, $set, $locale );

			return $catalogue;
		}

		/**
		 *	The styles a template brings: the css files beside it that a person chooses one of, for each style its head declares
		 *	(`@style <name> <label>`, see head()). A frame's files stand in its directory, next to template.tpl and style.css
		 *	(library/header/floating/nav-hover-glow.css); a set of one file has a directory named like the file, beside it
		 *	(library/section/regular/<style>-<option>.css, beside regular.css). A file is named <style>-<option>.css, the style
		 *	and the option a slug each: the style is the longest name the head declares that the file name starts with, so a style
		 *	may be called nav-hover and an option soft-glow. The options are listed by the name of their file, in natural order,
		 *	each under the name and the description their own head gives it (`@name`, `@description` and their twins of another
		 *	language) and under the slug where it gives none.
		 *
		 *	What is work in progress is not offered and is named in $notes, led by the part and the set: a file whose name is no
		 *	<style>-<option>.css, a file of a style the head does not declare, and a declared style that has no file - which is a
		 *	select with "None" alone - as well as the lines of the head that were dropped (see head())
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *	@param		array 		&$notes				(reference) What was not offered, and why
		 *
		 *	@return 	array										style => label, options (option => name, description, file), in the order of the head's lines
		 */
		public static function styles( string $libraryDir, string $part, string $set, string $locale = 'en_US', array &$notes = [] ): array {

			$file = self::file( $libraryDir, $part, $set );

			if( $file === '' )
				return [];

			$head = self::head( $file, $locale );
			$led 	= $part. '/'. $set. ': ';

			foreach( $head['styleNotes'] as $note )
				$notes[] = $led. $note;

			// The stylesheet of a frame is the frame's own, and no style
			return self::offered( $head['styles'], self::styleDir( $libraryDir, $part, $set ), self::PARTS[$part] === 'frame' ? 'style' : '', $led, $locale, $notes );
		}

		/**
		 *	The options of the styles a head declares, out of the files of a directory: the one reading of a style's files, which the
		 *	styles of a template (see styles()) and those of a feature (see featureStyles()) have in common. A file is named
		 *	<style>-<option>.css, the style the longest declared name its name starts with; the options are in the natural order of their
		 *	names, each under the name and the description its own head gives it and under its slug where it gives none. What is work in
		 *	progress is not offered and is named in $notes, led by $led
		 *
		 *	@param		array 		$declared			name => label, as head() gives the styles
		 *	@param		string		$dir					The directory the files stand in, '' where there is none
		 *	@param		string		$own					The name of a file of the directory that is no option's, without .css; '' for none
		 *	@param		string		$led					What every note starts with
		 *	@param		string		$locale				The language of the workbench
		 *	@param		array 		&$notes				(reference) What was not offered, and why
		 *
		 *	@return 	array										style => label, options (option => name, description, file)
		 */
		private static function offered( array $declared, string $dir, string $own, string $led, string $locale, array &$notes ): array {

			$styles = [];

			foreach( $declared as $name => $label )
				$styles[$name] = [ 'label' => $label, 'options' => [] ];

			foreach( $dir === '' ? [] : ( glob( $dir. '/*.css' ) ?: [] ) as $path ) {

				$base = basename( $path, '.css' );

				if( $own !== '' && $base === $own )
					continue;

				if( is_file( $path ) === false )
					continue;

				if( preg_match( self::OPTION_NAME, $base ) !== 1 ) {
					$notes[] = $led. 'the file "'. self::shown( basename( $path ), 60 ). '" is named for no style and option (style-option.css, letters, digits and hyphens each) - not offered';
					continue;
				}

				$style = '';

				foreach( array_keys( $styles ) as $name )
					if( str_starts_with( $base, $name. '-' ) === true && strlen( $name ) > strlen( $style ) )
						$style = (string) $name;

				if( $style === '' ) {

					// The file of a style by itself has no option, and says what it lacks rather than that its style is not declared
					$notes[] = $led. 'the file "'. self::shown( basename( $path ), 60 ). ( isset( $styles[$base] ) === true
						? '" names no option - the file of a style is style-option.css - not offered'
						: '" belongs to no style the head declares with @style - not offered' );
					continue;
				}

				// A name that is a slug and starts with the style and a hyphen has a slug after it
				$option = substr( $base, strlen( $style ) + 1 );
				$head 	= self::head( $path, $locale );

				$styles[$style]['options'][$option] = [ 'name' => $head['name'] !== '' ? $head['name'] : $option, 'description' => $head['description'], 'file' => $path ];
			}

			foreach( $styles as $name => $style ) {

				uksort( $styles[$name]['options'], static fn( int|string $a, int|string $b ): int => strnatcmp( (string) $a, (string) $b ) );

				if( $style['options'] === [] )
					$notes[] = $led. 'the style "'. $name. '" has no file - only "None" is offered';
			}

			return $styles;
		}

		/**
		 *	The directory the files of a template's styles stand in: the template's own for a frame, and for a set of one file the
		 *	directory named like the file, beside it (see styles())
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	string								A path, '' where the set or the directory is not there
		 */
		private static function styleDir( string $libraryDir, string $part, string $set ): string {

			if( self::named( $set ) === false || isset( self::PARTS[$part] ) === false )
				return '';

			$dir = rtrim( $libraryDir, '/' ). '/'. $part. '/'. $set;

			return is_dir( $dir ) === true ? $dir : '';
		}

		/**
		 *	The file an option of a style resolves to
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *	@param		string		$style				A style the head of the set declares
		 *	@param		string		$option				An option of it
		 *
		 *	@return 	string								A path, '' when the choice does not exist
		 */
		public static function styleFile( string $libraryDir, string $part, string $set, string $style, string $option ): string {

			// The names are file names, and a name from a stored setup is input: anything but a slug (a style one that starts with a letter) is no style and no option
			if( preg_match( self::STYLE_NAME, $style ) !== 1 || preg_match( self::OPTION_NAME, $option ) !== 1 )
				return '';

			if( self::file( $libraryDir, $part, $set ) === '' )
				return '';

			$dir = self::styleDir( $libraryDir, $part, $set );
			$path = $dir. '/'. $style. '-'. $option. '.css';

			return $dir !== '' && is_file( $path ) === true ? $path : '';
		}

		/**
		 *	The features that bring variants of their own: every feature that is active and carries a
		 *	design/ directory with a stylesheet in it, read through the kernel (\Nino\Features::all())
		 *	and the head of each file (see head()). A feature without design/ appears nowhere, and
		 *	so does a feature that is not active - its entry in the setup is kept, and nothing is
		 *	compiled for it (see normalize()). A feature is called as the panel's objects are
		 *	called - a part or the page - only by the name of a part or of the page: such a key is
		 *	skipped with a note, since the rows of its knobs would be those of the object.
		 *
		 *	It may declare styles too, for all its variants at once (see featureStyles()): they stand beside the variants in its entry.
		 *
		 *	A feature that brings variants may bring a block for the specimen as well, design/preview.tpl (see previewOf()). It names
		 *	the anchor of the block where the file is there and stands the check, and none where it is not: the page has what the
		 *	answer says, because Preview::blocks() asks the same question.
		 *
		 *	The answer is read once for a request and the language of the workbench, and kept in the
		 *	app data beside the list of modules it was made for.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What the heads said that was dropped, led by the feature and the variant
		 *
		 *	@return 	array										key => name (the manifest's, in the language of the workbench), dir, anchor (the id of the feature's block on the specimen, '' where it has none), default (the variant a feature has before anything is chosen: regular, else the first), catalogue (variant => name, description, knobs - each with its ladder where it has one), styles (style => label, options - see featureStyles(); [] for a feature that declares none), sorted by name
		 */
		public static function features( array &$appData, array &$notes = [] ): array {

			$locale 	= \Nino\Locales::getCurrentLocale( $appData );
			$modules 	= (array) ( $appData['/nino/modules'] ?? [] );
			$dir 			= \Nino\Features::dir();
			$kept 		= $appData['./design/features'] ?? null;

			if( is_array( $kept ) === false || $kept['locale'] !== $locale || $kept['modules'] !== $modules || $kept['dir'] !== $dir ) {

				$kept = [ 'locale' => $locale, 'modules' => $modules, 'dir' => $dir, 'catalogue' => [], 'notes' => [] ];

				foreach( \Nino\Features::all( $appData ) as $key => $feature ) {

					if( $feature['active'] !== true || is_dir( $feature['dir']. '/design' ) === false )
						continue;

					if( in_array( $key, self::OBJECTS, true ) === true ) {
						$kept['notes'][] = 'the feature "'. $key. '" is called as an object of the panel is - its variants are not offered';
						continue;
					}

					$variants = self::variants( $feature['dir'], $key, $locale, $kept['notes'] );

					if( $variants === [] )
						continue;

					$kept['catalogue'][$key] = [
						'name' 				=> \Nino\Features::localized( $feature['name'], $locale ) ?: $key,
						'dir' 				=> $feature['dir'],
						'anchor' 			=> self::previewOf( $feature['dir'], $key, $kept['notes'] ) === '' ? '' : self::ANCHOR_PREFIX. $key,
						'default' 		=> isset( $variants['regular'] ) === true ? 'regular' : (string) array_key_first( $variants ),
						'catalogue' 	=> $variants,
						'styles' 			=> self::featureStyles( $feature['dir'], $key, $locale, $kept['notes'] ),
					];
				}

				$names = $kept['catalogue'];

				uksort( $kept['catalogue'], static fn( string $a, string $b ): int => strnatcasecmp( $names[$a]['name'], $names[$b]['name'] ) ?: strcmp( $a, $b ) );

				$appData['./design/features'] = $kept;
			}

			foreach( $kept['notes'] as $note )
				$notes[] = $note;

			return $kept['catalogue'];
		}

		/**
		 *	The variants one feature brings: a stylesheet each in its design/ directory, in the order of their
		 *	names, with the name, the description and the knobs its head declares. A scale or a choice a
		 *	head declares is published by its ladder, as the knobs of the parts are (see declarations()):
		 *	one without a complete ladder is not, and says so in a note. A ladder no head declares is not
		 *	offered - nothing says where it stands
		 *
		 *	@param		string		$dir					The directory of the feature
		 *	@param		string		$key					The key of the feature, which is the part name of its ladders
		 *	@param		string		$locale				The language of the workbench
		 *	@param		array 		&$notes				(reference) What was dropped, led by the feature and the variant
		 *
		 *	@return 	array										variant => name, description, knobs
		 */
		private static function variants( string $dir, string $key, string $locale, array &$notes ): array {

			$files = [];

			foreach( glob( $dir. '/design/*.css' ) ?: [] as $file )
				if( self::named( basename( $file, '.css' ) ) === true && basename( $file, '.css' ) !== self::FEATURE_STYLES && is_file( $file ) === true )
					$files[ basename( $file, '.css' ) ] = $file;

			ksort( $files, SORT_NATURAL );

			$variants = [];

			foreach( $files as $variant => $file ) {

				$head 	= self::head( $file, $locale );
				$knobs 	= $head['knobs'];
				$ladder = [];

				foreach( $head['notes'] as $note )
					$notes[] = $key. '/'. $variant. ': '. $note;

				foreach( $knobs as $name => $knob )
					if( in_array( $knob['kind'], self::LADDERS, true ) === true )
						$ladder[$name] = $knob['kind'];

				$found = self::declarations( self::uncomment( (string) @file_get_contents( $file ) ), $key, $ladder );

				foreach( array_keys( $ladder ) as $name )
					if( isset( $found[$name] ) === true )
						$knobs[$name]['ladder'] = $found[$name];
					else {
						$notes[] = $key. '/'. $variant. ': the knob "'. $name. '" has no complete ladder - not published';
						unset( $knobs[$name] );
					}

				$variants[$variant] = [ 'name' => $head['name'] !== '' ? $head['name'] : $variant, 'description' => $head['description'], 'knobs' => $knobs ];
			}

			return $variants;
		}

		/**
		 *	The block a feature brings for the specimen: the file design/preview.tpl of its directory - the inside of a section, one
		 *	grid row (<div class="nino-grid-row">...</div>) in HTML+ as the specimen is written, with the tokens [[lorem]], [[short]] and
		 *	[[placeholder]], which Preview fills. A feature without the file has no block and is not named; the file is looked at for a
		 *	feature that is active and brings variants only (see features()).
		 *
		 *	The page has one element for each anchor, so a block is held to two things: every id in it starts with the key of the
		 *	feature and a hyphen (forms-name), and it holds no section of its own - Design makes the section, with the id of the feature
		 *	(see Preview::blocks()). A block that breaks either is no block: it is left out and named in `notes`, led by the feature, the
		 *	way a line of a head that is wrong is. So is a file with nothing in it. The check errs the strict way: it reads the text of
		 *	the file, not a document, so an id inside a comment or a `<section` inside an attribute value drops the block too.
		 *
		 *	@param		string		$dir					The directory of the feature
		 *	@param		string		$key					The key of the feature, the prefix of every id in its block
		 *	@param		array 		&$notes				(reference) Why the file is no block, led by the feature
		 *
		 *	@return 	string										The markup without the blank space at its ends; '' where there is no file, and where it is no block
		 */
		public static function previewOf( string $dir, string $key, array &$notes = [] ): string {

			$file = $dir. '/'. self::PREVIEW;

			if( is_file( $file ) === false )
				return '';

			$markup = trim( (string) @file_get_contents( $file ) );
			$stray 	= '';

			// The id of every tag that has one: in a tag only, so that a word of the text is none - a quoted value may hold a '>' and the tag goes on
			preg_match_all( '/<[a-z](?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $markup, $tags );

			foreach( $tags[0] as $tag )
				if( preg_match( '/\sid\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $tag, $one ) === 1 ) {

					$id = $one[1] !== '' ? $one[1] : ( ( $one[2] ?? '' ) !== '' ? $one[2] : ( $one[3] ?? '' ) );

					if( str_starts_with( $id, $key. '-' ) === false ) {
						$stray = $id === '' ? '(empty)' : $id;
						break;
					}
				}

			$why = match( true ) {
				$markup === '' 											=> 'the file is empty',
				preg_match( '/<section[\s>\/]/i', $markup ) === 1 		=> 'it holds a section - the block is the inside of the one the preview makes',
				$stray !== '' 											=> 'the id "'. self::shown( $stray ). '" does not start with "'. $key. '-"',
				default 												=> '',
			};

			if( $why === '' )
				return $markup;

			$notes[] = $key. ': the block '. self::PREVIEW. ' is left out: '. $why;

			return '';
		}

		/**
		 *	The file a variant of a feature resolves to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of an active feature that brings variants
		 *	@param		string		$variant			A variant of it
		 *
		 *	@return 	string										A path, '' when the feature or the variant is not there
		 */
		public static function featureFile( array &$appData, string $key, string $variant ): string {

			$feature = self::features( $appData )[$key] ?? null;

			if( $feature === null || isset( $feature['catalogue'][$variant] ) === false )
				return '';

			$path = $feature['dir']. '/design/'. $variant. '.css';

			return is_file( $path ) === true ? $path : '';
		}

		/**
		 *	The styles a feature declares, once for all its variants: the head of design/styles.css declares them (`@style <name> <label>` and the
		 *	`@style-de` line beside it, the grammar of the head of a template, see head()), and the files of their options stand in the directory
		 *	design/styles/ beside it, each named <style>-<option>.css, the style the longest declared name its name starts with. The file styles.css
		 *	is no variant (see variants()). The options are listed as those of a template's styles are (see offered()): by the natural order of
		 *	their file names, under the name and the description their own heads give them, under their slug where they give none.
		 *
		 *	A feature without design/styles.css declares no style, and its design/styles/ is not looked at. What is work in progress is not
		 *	offered and is named in $notes, led by the feature and "styles": a file whose name is no <style>-<option>.css, a file of a style the head
		 *	does not declare, a declared style that has no file, and the lines of the head that were dropped
		 *
		 *	@param		string		$dir					The directory of the feature
		 *	@param		string		$key					The key of the feature
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *	@param		array 		&$notes				(reference) What was not offered, and why
		 *
		 *	@return 	array										style => label, options (option => name, description, file), in the order of the head's lines; [] where the feature declares none
		 */
		public static function featureStyles( string $dir, string $key, string $locale, array &$notes = [] ): array {

			$file = $dir. '/design/'. self::FEATURE_STYLES. '.css';

			if( is_file( $file ) === false )
				return [];

			$head = self::head( $file, $locale );
			$led 	= $key. '/'. self::FEATURE_STYLES. ': ';

			foreach( $head['styleNotes'] as $note )
				$notes[] = $led. $note;

			// A file of that name that declares no style is neither a variant nor a source of styles: said, so that nobody looks for it
			if( $head['styles'] === [] && $head['styleNotes'] === [] )
				$notes[] = $led. 'design/'. self::FEATURE_STYLES. '.css declares no style - it is no variant and is not compiled';

			return self::offered( $head['styles'], $dir. '/design/'. self::FEATURE_STYLES, '', $led, $locale, $notes );
		}

		/**
		 *	The file an option of a style of a feature resolves to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of an active feature that brings variants
		 *	@param		string		$style				A style the feature declares
		 *	@param		string		$option				An option of it
		 *
		 *	@return 	string										A path, '' when the feature, the style or the option is not there
		 */
		public static function featureStyleFile( array &$appData, string $key, string $style, string $option ): string {

			$path = self::features( $appData )[$key]['styles'][$style]['options'][$option]['file'] ?? '';

			return is_string( $path ) === true && is_file( $path ) === true ? $path : '';
		}

		/**
		 *	The rules the styles of a feature share: the body of design/styles.css, which is the file without the comment it opens with (that
		 *	one is the head, see head()). The compiler writes it before the options that were chosen (see Compiler::compile())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of an active feature that brings variants
		 *
		 *	@return 	string										The css without the blank space at its ends; '' where the feature is not there, has no such file, or the file holds the head alone
		 */
		public static function featureShared( array &$appData, string $key ): string {

			$feature = self::features( $appData )[$key] ?? null;

			if( $feature === null )
				return '';

			$css = (string) @file_get_contents( $feature['dir']. '/design/'. self::FEATURE_STYLES. '.css' );

			return trim( (string) preg_replace( '~^\s*/\*.*?\*/~s', '', $css, 1 ) );
		}

		/**
		 *	Where every control of the panel stands, the features' among them: the static table (LAYOUT, which stays
		 *	complete for the keys of the setup that are no feature's) and the rows the features add. A feature has a row of
		 *	its own under Templates - `features/<key>/set`, the choice of its variant - and a place for each knob any of
		 *	its variants declares - `features/<key>/knobs/<knob>`, on the tab the head names, under the feature as the object,
		 *	counted from 1 on the tab in the order of the head's lines (the variants in the order of their names, a knob
		 *	where the first that declares it puts it). A variant that does not declare the knob has it greyed out
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										key => [ tab, object, order ]
		 */
		public static function layout( array &$appData ): array {

			$rows = self::LAYOUT;

			foreach( self::features( $appData ) as $key => $feature ) {

				$rows['features/'. $key. '/set'] = [ 'templates', $key, 1 ];
				$count = [];

				foreach( $feature['catalogue'] as $variant )
					foreach( $variant['knobs'] as $knob => $meta ) {

						if( isset( $rows['features/'. $key. '/knobs/'. $knob] ) === true )
							continue;

						$count[ $meta['tab'] ] = ( $count[ $meta['tab'] ] ?? 0 ) + 1;
						$rows['features/'. $key. '/knobs/'. $knob] = [ $meta['tab'], $key, $count[ $meta['tab'] ] ];
					}
			}

			return $rows;
		}

		/**
		 *	The objects the blocks of the panel are of: the nine of OBJECTS and, after the forms, one for each feature
		 *	that brings variants, in the order of the catalogue
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										The keys of the objects
		 */
		public static function objects( array &$appData ): array {
			return array_merge( self::OBJECTS, array_keys( self::features( $appData ) ) );
		}

		/**
		 *	Whether a name can be the name of a set or of a variant: a file name of letters, digits, the dot, the hyphen and the
		 *	underscore, with nothing in it that could climb out of the directory it is looked for in
		 *
		 *	@param		string		$set
		 *
		 *	@return 	bool
		 */
		private static function named( string $set ): bool {
			return preg_match( self::SET_NAME, $set ) === 1 && str_contains( $set, '..' ) === false;
		}

		/**
		 *	A stored setup, held against the library that is really there. What
		 *	cannot be honoured is replaced and named - a compile from a setup
		 *	nobody checked is how a project ends up with a stylesheet missing a
		 *	part and no idea why - and a key that is none of this version's is
		 *	dropped and named.
		 *
		 *	The format is not looked at here: a request names none, and read() is
		 *	the one place a file is held to it.
		 *
		 *	The theme is kept where it names a theme that is there, shipped or the
		 *	project's, and is none otherwise, with a note. The css is kept as text,
		 *	its line breaks all one kind. What the css may carry is not judged here:
		 *	a stored file is read as it is, and the refusals belong to the three
		 *	doors a request comes through (see Themes::refusal()).
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data - the families a project installed are read off its disk
		 *	@param		array 		$raw					Whatever the file held, or the screen posted
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped: a sentence, or - for the two that the screen says in words (see NOTES) - note()'s array
		 *
		 *	@return 	array										A setup safe to compile
		 */
		public static function normalize( array &$appData, array $raw, string $libraryDir, array &$notes = [] ): array {

			$setup = self::defaults( $libraryDir );

			foreach( array_keys( $raw ) as $key )
				if( in_array( $key, self::KEYS, true ) === false )
					$notes[] = 'the key "'. self::shown( $key ). '" is none of this version\'s - dropped';

			// A theme that is not there is no theme - the one that was deleted since, or a file that was edited by hand
			$theme = $raw['theme'] ?? '';

			if( is_string( $theme ) === true && $theme !== '' ) {

				if( Themes::exists( $appData, $libraryDir, $theme ) === true )
					$setup['theme'] = $theme;
				else
					$notes[] = self::note( 'design_theme_missing', self::shown( $theme ) );
			}

			$setup['size'] = self::position( $raw['size'] ?? 0 );
			$setup['width'] = self::position( $raw['width'] ?? 0 );
			$setup['whitespace'] = self::position( $raw['whitespace'] ?? 0 );

			// The two strengths of the radius are positions as well; what is none of them is the kernel's own, which is not the middle of the alternative one
			$setup['radius'] = self::position( $raw['radius'] ?? 0 );
			$setup['radius-alt'] = self::position( $raw['radius-alt'] ?? 1, 'scale', 1 );

			// A kind of surface takes one of the two strengths: a kind that is none of Nino.css's is dropped and named, a word that is none of the two is the standard one
			$radii = is_array( $raw['radii'] ?? null ) === true ? $raw['radii'] : [];

			foreach( array_keys( $radii ) as $kind )
				if( in_array( $kind, self::SURFACES, true ) === false )
					$notes[] = 'there is no kind of surface "'. self::shown( $kind ). '" - dropped';

			foreach( self::SURFACES as $kind )
				$setup['radii'][$kind] = in_array( $radii[$kind] ?? null, self::RADIUS_WORDS, true ) === true ? $radii[$kind] : self::RADIUS_WORDS[0];

			$motion = is_array( $raw['motion'] ?? null ) === true ? $raw['motion'] : [];

			foreach( array_keys( $motion ) as $key )
				if( isset( $setup['motion'][$key] ) === false )
					$notes[] = 'the motion has no "'. self::shown( $key ). '" - dropped';

			$effect 	= $motion['effect'] ?? null;
			$strength = $motion['strength'] ?? null;
			$speed 		= $motion['speed'] ?? null;
			$hover 		= $motion['hover'] ?? null;
			$hoverStrength = $motion['hover-strength'] ?? null;

			$setup['motion'] = [
				'effect' 		=> is_string( $effect ) === true && isset( self::EFFECTS[$effect] ) === true ? $effect : '',
				'strength' 	=> is_string( $strength ) === true && in_array( $strength, self::STRENGTHS, true ) === true ? $strength : '',
				'speed' 		=> is_string( $speed ) === true && isset( self::SPEEDS[$speed] ) === true ? $speed : '',
				'hover' 		=> is_string( $hover ) === true && isset( self::HOVERS[$hover] ) === true ? $hover : '',
				'hover-strength' => is_string( $hoverStrength ) === true && in_array( $hoverStrength, self::STRENGTHS, true ) === true ? $hoverStrength : '',
			];

			$given = is_array( $raw['parts'] ?? null ) === true ? $raw['parts'] : [];

			foreach( array_keys( $given ) as $part )
				if( isset( self::PARTS[$part] ) === false )
					$notes[] = 'there is no part "'. self::shown( $part ). '" - dropped';

			foreach( array_keys( self::PARTS ) as $part ) {

				$available 	= self::available( $libraryDir, $part );
				$wanted 		= is_string( $given[$part] ?? null ) === true ? $given[$part] : '';

				if( $available === [] ) {
					$notes[] = 'the library holds no set for "'. $part. '"';
					$setup['parts'][$part] = '';
				}
				else if( in_array( $wanted, $available, true ) === true )
					$setup['parts'][$part] = $wanted;
				else if( $wanted !== '' )
					$notes[] = self::note( 'design_set_missing', $part, self::shown( $wanted ), $setup['parts'][$part] );
			}

			/*	Where every knob stands. A knob is a position of its own part, held to
				the range of its kind - an ink is a tone of the page or none -, and a name that is none of the part's - or a part that
				is none of the eight - is dropped with a note rather than carried: a
				position nothing reads is a position nobody can find again. One that is
				missing stands where the defaults put it, which is where the shipped theme does	*/
			$given = is_array( $raw['knobs'] ?? null ) === true ? $raw['knobs'] : [];

			foreach( $given as $part => $values ) {

				if( isset( self::KNOBS[$part] ) === false ) {
					$notes[] = 'there is no part "'. self::shown( $part ). '" with knobs - dropped';
					continue;
				}

				foreach( array_keys( is_array( $values ) === true ? $values : [] ) as $knob )
					if( isset( self::KNOBS[$part][$knob] ) === false )
						$notes[] = 'the part "'. $part. '" has no knob "'. self::shown( $knob ). '" - dropped';
			}

			foreach( self::KNOBS as $part => $kinds )
				foreach( $kinds as $knob => $kind ) {

					$stands = is_array( $given[$part] ?? null ) === true ? ( $given[$part][$knob] ?? $setup['knobs'][$part][$knob] ) : $setup['knobs'][$part][$knob];
					$setup['knobs'][$part][$knob] = $kind === 'ink' ? self::tone( $stands ) : self::position( $stands, $kind );
				}

			$setup['styles'] = self::picked( $libraryDir, $setup['parts'], is_array( $raw['styles'] ?? null ) === true ? $raw['styles'] : [], $notes );
			$setup['features'] = self::chosen( $appData, is_array( $raw['features'] ?? null ) === true ? $raw['features'] : [], $notes );

			/*	The five font roles. A family has to be one the role's select offers -
				the code is given the monospace ones only, and the families the project
				installed are among them - and a weight one of the three words;
				anything else is what the role starts from. A size is a number of rem
				held inside the bounds of the role, and what is no number is the default
				(see Fonts::size()). A role has these three and nothing else, a key of
				any other - the case of a role was one, once - is dropped and named	*/
			$given = is_array( $raw['fonts'] ?? null ) === true ? $raw['fonts'] : [];

			foreach( array_keys( $given ) as $role )
				if( in_array( $role, Fonts::ROLES, true ) === false )
					$notes[] = 'there is no font role "'. self::shown( $role ). '" - dropped';

			foreach( Fonts::ROLES as $role ) {

				$stored = is_array( $given[$role] ?? null ) === true ? $given[$role] : [];

				foreach( array_keys( $stored ) as $key )
					if( isset( Fonts::DEFAULTS[$role][$key] ) === false )
						$notes[] = 'the font role "'. $role. '" has no "'. self::shown( $key ). '" - dropped';

				$family = $stored['family'] ?? null;
				$weight = $stored['weight'] ?? null;

				$setup['fonts'][$role] = [
					'family' 		=> is_string( $family ) === true && in_array( $family, Fonts::options( $appData, $role ), true ) === true ? $family : Fonts::DEFAULTS[$role]['family'],
					'weight' 		=> is_string( $weight ) === true && in_array( $weight, Fonts::WEIGHTS, true ) === true ? $weight : Fonts::DEFAULTS[$role]['weight'],
					'size' 			=> Fonts::size( $role, $stored['size'] ?? '' ),
				];
			}

			/*	The palette. Normalized by Colours rather than here, because the
				vocabulary is its own - a hex, five positions and the mode, none of
				them a step word - and a second copy of those tables in this file is a
				second place for them to drift	*/
			$setup['colours'] = Colours::normalize( is_array( $raw['colours'] ?? null ) ? $raw['colours'] : [] );

			// The css as text, whatever the line breaks of the machine it was typed or pasted on
			$css = $raw['css'] ?? '';
			$setup['css'] = is_string( $css ) === true ? str_replace( [ "\r\n", "\r" ], "\n", $css ) : '';

			$setup['compiled'] = is_array( $raw['compiled'] ?? null ) ? $raw['compiled'] : [];

			return $setup;
		}

		/**
		 *	What a setup chooses for the styles of the templates, held against the template that is chosen for each part (see styles()):
		 *	a pair of a style and an option stays where the template declares the style and has a file for the option, and is dropped
		 *	and named where it does not - so a choice never outlives the template it was made for, and a part has an entry whether it
		 *	chose something or not. An option that is '' is the template as it is and no choice. What is work in progress in the files
		 *	of the chosen templates is named as well (see styles())
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$parts				part => the set chosen for it, normalised
		 *	@param		array 		$given				What the file held, or the screen posted, under `styles`
		 *	@param		array 		&$notes				(reference) What had to be dropped
		 *
		 *	@return 	array										part => style => option, a part with none chosen an empty list
		 */
		private static function picked( string $libraryDir, array $parts, array $given, array &$notes ): array {

			foreach( array_keys( $given ) as $part )
				if( isset( self::PARTS[$part] ) === false )
					$notes[] = 'there is no part "'. self::shown( $part ). '" with styles - dropped';

			$picked = [];

			foreach( array_keys( self::PARTS ) as $part ) {

				$set 			= (string) ( $parts[$part] ?? '' );
				$declared = self::styles( $libraryDir, $part, $set, 'en_US', $notes );

				if( array_key_exists( $part, $given ) === true && is_array( $given[$part] ) === false )
					$notes[] = 'the styles of "'. $part. '" are no list of styles with an option each - dropped';

				$picked[$part] = self::held( $declared, is_array( $given[$part] ?? null ) === true ? $given[$part] : [],
					[ 'the template "'. $set. '" of "'. $part. '"', '"'. $part. '"', 'the template "'. $set. '"' ], $notes );
			}

			return $picked;
		}

		/**
		 *	A choice of styles held to the styles that are declared and their files: a pair of a style and an option stays where the style is
		 *	declared and the option has a file, and is dropped and named where it does not. An option that is '' is no choice. The pairs
		 *	stand in the order the head declares the styles in, so that the same choice is the same bytes. The one holding of the styles of a
		 *	template (see picked()) and of a feature (see chosen())
		 *
		 *	@param		array 		$declared			style => label, options (option => ...), as styles() and featureStyles() give them
		 *	@param		array 		$values				What the file held, or the screen posted: style => option
		 *	@param		array 		$words				How the notes name the owner: who declares a style, what the style belongs to, and who has the option
		 *	@param		array 		&$notes				(reference) What had to be dropped
		 *
		 *	@return 	array										style => option, none chosen an empty list
		 */
		private static function held( array $declared, array $values, array $words, array &$notes ): array {

			$chosen = [];

			foreach( $values as $style => $option ) {

				if( isset( $declared[$style] ) === false )
					$notes[] = $words[0]. ' declares no style "'. self::shown( $style ). '" - dropped';
				else if( is_string( $option ) === false )
					$notes[] = 'the style "'. $style. '" of '. $words[1]. ' is not given an option - dropped';
				else if( $option !== '' && isset( $declared[$style]['options'][$option] ) === false )
					$notes[] = 'the style "'. $style. '" of '. $words[2]. ' has no option "'. self::shown( $option ). '" - dropped';
				else if( $option !== '' )
					$chosen[$style] = $option;
			}

			// In the order the head declares them: the same choice is the same bytes
			return array_intersect_key( array_replace( array_fill_keys( array_keys( $declared ), '' ), $chosen ), $chosen );
		}

		/**
		 *	What a setup chooses for the features, held against the catalogue of the features that are there (see features()).
		 *	A feature that has an entry has its variant and a value for each knob that variant declares, every one held to
		 *	its kind (see value()); one that has none is the feature as it is, which nothing is compiled for. A variant that
		 *	is none of the feature's falls to the one it has before anything is chosen, and a knob the variant does not declare
		 *	is dropped - each with a note, as a part's is. It has the styles chosen for it as well, a pair of a style and an option each: they
		 *	belong to the feature and are held to what the feature declares (see featureStyles()) whichever variant is chosen, so a change of the
		 *	variant leaves them as they are; one the feature does not declare, or an option without a file, is dropped and named
		 *
		 *	An entry of a feature that is not in the catalogue - switched off, or taken away, or without variants now - is kept,
		 *	its three keys held to their types and nothing else looked at, since nothing about the feature can be asked: the choice
		 *	stays for the day it is back, and the note says nothing is compiled for it meanwhile
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$given				What the file held, or the screen posted, under `features`
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped
		 *
		 *	@return 	array										key => [ set, knobs, styles ], sorted by the key
		 */
		private static function chosen( array &$appData, array $given, array &$notes ): array {

			$catalogue = self::features( $appData, $notes );
			$chosen = [];

			foreach( $given as $key => $entry ) {

				$key = (string) $key;

				if( preg_match( \Nino\Features::KEY_PATTERN, $key ) !== 1 ) {
					$notes[] = 'the feature "'. self::shown( $key ). '" is no key a feature can have - its choice is dropped';
					continue;
				}

				if( is_array( $entry ) === false ) {
					$notes[] = 'the choice for the feature "'. self::shown( $key ). '" is no entry of a variant and its knobs - dropped';
					continue;
				}

				foreach( array_keys( $entry ) as $name )
					if( in_array( $name, [ 'set', 'knobs', 'styles' ], true ) === false )
						$notes[] = 'the feature "'. $key. '" has no "'. self::shown( $name ). '" - dropped';

				$wanted = is_string( $entry['set'] ?? null ) === true ? $entry['set'] : '';
				$values = is_array( $entry['knobs'] ?? null ) === true ? $entry['knobs'] : [];
				$styled = is_array( $entry['styles'] ?? null ) === true ? $entry['styles'] : [];

				if( array_key_exists( 'styles', $entry ) === true && is_array( $entry['styles'] ) === false )
					$notes[] = 'the styles of the feature "'. $key. '" are no list of styles with an option each - dropped';

				if( isset( $catalogue[$key] ) === false ) {
					$notes[] = self::inactive( $key );
					$chosen[$key] = [ 'set' => $wanted, 'knobs' => $values, 'styles' => array_filter( $styled, 'is_string' ) ];
					continue;
				}

				$feature = $catalogue[$key];
				$set = isset( $feature['catalogue'][$wanted] ) === true ? $wanted : $feature['default'];

				if( $wanted !== '' && $set !== $wanted )
					$notes[] = self::note( 'design_set_missing', $key, self::shown( $wanted ), $set );

				$declared = $feature['catalogue'][$set]['knobs'];

				foreach( array_keys( $values ) as $knob )
					if( isset( $declared[$knob] ) === false )
						$notes[] = 'the variant "'. $set. '" of the feature "'. $key. '" has no knob "'. self::shown( $knob ). '" - dropped';

				$knobs = [];

				foreach( $declared as $knob => $meta )
					$knobs[$knob] = self::value( $meta, $values[$knob] ?? null );

				/*	The styles belong to the feature and not to the variant: a change of the variant leaves them as they are, and they are
					held to what the feature declares, whichever variant is chosen	*/
				$chosen[$key] = [ 'set' => $set, 'knobs' => $knobs, 'styles' => self::held( $feature['styles'], $styled, [ 'the feature "'. $key. '"', 'the feature "'. $key. '"', 'the feature "'. $key. '"' ], $notes ) ];
			}

			ksort( $chosen );

			return $chosen;
		}

		/**
		 *	The value of one knob of a feature, held to its kind: a position for a scale and a choice (see position()), one of the
		 *	surfaces of the palette for a ground, one of the tones of INKS for an ink, two hex colours for a colour (see pair()).
		 *	What is none of that is what the head of the variant says the knob stands at
		 *
		 *	@param		array 		$knob					One knob of a head: kind, default
		 *	@param		mixed			$value				Straight off the wire
		 *
		 *	@return 	int|string|array
		 */
		private static function value( array $knob, mixed $value ): int|string|array {

			return match( $knob['kind'] ) {
				'ground' 	=> is_string( $value ) === true && in_array( $value, Colours::SURFACES, true ) === true ? $value : $knob['default'],
				'ink' 		=> is_string( $value ) === true && isset( self::INKS[$value] ) === true ? $value : $knob['default'],
				'colour' 	=> self::pair( $value ) ?? $knob['default'],
				default 	=> self::position( $value, $knob['kind'], (int) $knob['default'] ),
			};
		}

		/**
		 *	A pair of colours, light then dark, each held to what Colours::color() reads as a hex - three or six digits, the hash
		 *	or none - and written as six digits, lower case
		 *
		 *	@param		mixed			$value				Straight off the wire
		 *
		 *	@return 	array|null								[ light, dark ]; null where it is not two colours
		 */
		private static function pair( mixed $value ): ?array {

			if( is_array( $value ) === false || array_is_list( $value ) === false || count( $value ) !== 2 )
				return null;

			$pair = [];

			foreach( $value as $color ) {

				$hex = is_string( $color ) === true ? Colours::color( $color, '' ) : '';

				if( $hex === '' )
					return null;

				$pair[] = $hex;
			}

			return $pair;
		}

		/**
		 *	One position, as a number among the positions of its kind (see positions()): -2 to 2
		 *	for a scale (the default kind - the root size, the width, the whitespace and the two
		 *	strengths of the radius are scales as well), -1 to 1 for a choice, 0 or 1 for a
		 *	toggle. Anything else - a word, a boolean, an array, and a number past the ends - is
		 *	the middle, which is the template as it is, or the position the caller says is the
		 *	one to stand at where there is no choice made
		 *
		 *	@param		mixed			$value				Straight off the wire
		 *	@param		string		$kind					'scale', 'choice' or 'toggle'
		 *	@param		int				$fallback			What anything else is
		 *
		 *	@return 	int
		 */
		public static function position( mixed $value, string $kind = 'scale', int $fallback = 0 ): int {

			if( is_string( $value ) === true )
				$value = is_numeric( $value ) === true ? (float) $value : null;

			if( is_int( $value ) === false && is_float( $value ) === false )
				return $fallback;

			$position = (int) round( (float) $value );

			return in_array( $position, self::positions( $kind ), true ) === true ? $position : $fallback;
		}

		/**
		 *	The step a knob stands at, as the word its stylesheet declares it
		 *	under
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					A key of KNOBS
		 *	@param		string		$knob					A name in KNOBS[$part]
		 *
		 *	@return 	string								One of STEPS
		 */
		public static function step( array $setup, string $part, string $knob ): string {
			return self::word( self::position( $setup['knobs'][$part][$knob] ?? 0, self::kind( $part, $knob ) ) );
		}

		/**
		 *	The step a scale or a choice of a feature stands at, as the word the stylesheet of its variant declares it under
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$key					The key of a feature
		 *	@param		string		$knob					A knob of the variant chosen for it
		 *	@param		string		$kind					'scale' or 'choice'
		 *
		 *	@return 	string								One of STEPS
		 */
		public static function featureStep( array $setup, string $key, string $knob, string $kind ): string {
			return self::word( self::position( $setup['features'][$key]['knobs'][$knob] ?? 0, $kind ) );
		}

		/**
		 *	Which knobs a set answers to.
		 *
		 *	Read out of the stylesheet itself rather than out of a manifest
		 *	beside it, the same bargain @name makes: a set is one file, and the
		 *	person writing sets is going to write a lot of them. A set that
		 *	declares --section-space--least / --less / --default / --more /
		 *	--most answers to the space of a section; one that does not, does not, and the
		 *	panel greys that knob out for it.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										Knob names, in KNOBS order
		 */
		public static function knobs( string $libraryDir, string $part, string $set ): array {
			return array_keys( self::ladders( $libraryDir, $part, $set ) );
		}

		/**
		 *	The ladders a set declares for each knob it answers to: the one named
		 *	after the knob and, where it has them, those named after an aspect of
		 *	it (see KNOB_TOKEN). What the compiler writes a selection line for.
		 *
		 *	A ladder is complete when it is declared under every word of its kind - the
		 *	five of a scale, the three of a choice, the two of a toggle (see words()) - and a knob is
		 *	published when its own ladder is and so is each of its aspects. A knob with
		 *	less or more words than its kind has, or with an aspect that has, is not in
		 *	the answer: the panel greys it out
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										knob => [ name, ... ], the knob's own first and the others as they are declared; a name is the ladder without its step
		 */
		public static function ladders( string $libraryDir, string $part, string $set ): array {

			$file = self::file( $libraryDir, $part, $set );

			if( $file === '' || isset( self::KNOBS[$part] ) === false )
				return [];

			return self::declarations( self::uncomment( (string) @file_get_contents( $file ) ), $part, self::KNOBS[$part] );
		}

		/**
		 *	The ladders a stylesheet declares for the knobs it is asked for (see ladders(), which asks
		 *	for the knobs of a part, and variants(), which asks for those a feature's head declares)
		 *
		 *	@param		string		$css					Without its comments
		 *	@param		string		$part					The part's name, or the key of a feature
		 *	@param		array 		$kinds				knob => 'scale', 'choice' or 'toggle'
		 *
		 *	@return 	array										knob => [ name, ... ] for each knob with a complete ladder
		 */
		private static function declarations( string $css, string $part, array $kinds ): array {

			preg_match_all( '/'. sprintf( preg_quote( self::KNOB_TOKEN, '/' ), preg_quote( $part, '/' ), '([a-z0-9]+(?:-[a-z0-9]+)*)', '('. implode( '|', self::STEPS ). ')' ). '\s*:/', $css, $found, PREG_SET_ORDER );

			// The words each name is declared under - once each, however often a media query says it again
			$declared = [];

			foreach( $found as $hit )
				$declared[ $hit[1] ][ $hit[2] ] = true;

			$out = [];

			foreach( $kinds as $knob => $kind ) {

				if( self::complete( $declared[$knob] ?? [], $kind ) === false )
					continue;

				$names = [ $knob ];

				// A name that is a knob of its own - title-case beside title - is that knob's and no aspect of this one
				foreach( array_keys( $declared ) as $name )
					if( str_starts_with( $name, $knob. '-' ) === true && isset( $kinds[$name] ) === false )
						$names[] = $name;

				foreach( $names as $name )
					if( self::complete( $declared[$name], $kind ) === false )
						continue 2;

				$out[$knob] = $names;
			}

			return $out;
		}

		/**
		 *	Whether the words a name is declared under are those of a ladder of that
		 *	kind: every one of them and no other
		 *
		 *	@param		array 		$declared			word => true
		 *	@param		string		$kind					'scale', 'choice' or 'toggle'
		 *
		 *	@return 	bool
		 */
		private static function complete( array $declared, string $kind ): bool {

			$words = self::words( $kind );

			return count( $declared ) === count( $words ) && array_diff( $words, array_keys( $declared ) ) === [];
		}

		/**
		 *	A stylesheet without its comments - shared with the compiler, which
		 *	scans the same ladders for the same reason
		 *
		 *	@param		string		$css
		 *
		 *	@return 	string
		 */
		public static function uncomment( string $css ): string {

			return (string) preg_replace( '~/\*.*?\*/~s', '', $css );
		}

		/**
		 *	Read the stored setup, normalised.
		 *
		 *	A file of another format - or none - is read as the defaults, and the
		 *	first says so: the file itself stays where it is until the next save
		 *	writes the new one. Nothing maps what the older shape chose onto this
		 *	one: there are no projects to carry over, and a mapping would be a
		 *	second model to keep right
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced
		 *
		 *	@return 	array
		 */
		public static function read( array &$appData, string $libraryDir, array &$notes = [] ): array {

			$raw = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );
			$raw = is_array( $raw ) === true ? $raw : [];

			if( $raw !== [] && ( $raw['format'] ?? null ) !== self::FORMAT ) {
				$notes[] = ltrim( self::PATH, '/' ). ' was written in another format - it is not read, the defaults stand in until the next save';
				$raw = [];
			}

			return self::normalize( $appData, $raw, $libraryDir, $notes );
		}

		/**
		 *	Write the setup back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, array $setup ): bool {

			$setup['format'] = self::FORMAT;

			return \Nino\Filesystem::putFileContent( $appData, self::PATH, $setup );
		}

		/**
		 *	A note that carries a code: the English sentence for a log, the code and the params of it -
		 *	the shape a refused request answers in (see \Nino\Http::fail()), so that the screen says it
		 *	the way it says a refusal
		 *
		 *	@param		string		$code					A key of NOTES
		 *	@param		string		...$params		What the %s of its sentence are, in turn
		 *
		 *	@return 	array										error, code, params
		 */
		public static function note( string $code, string ...$params ): array {

			return [ 'error' => vsprintf( self::NOTES[$code], $params ), 'code' => $code, 'params' => $params ];
		}

		/**
		 *	The note for a choice that is kept for a feature that is not there - switched off, taken away, or
		 *	without variants now - and that nothing is compiled for. Made here for the two that tell it, the
		 *	normalising of a setup and the compiler, so that they tell it alike
		 *
		 *	@param		string		$key					The key of the feature
		 *
		 *	@return 	array										error, code, params (see note())
		 */
		public static function inactive( string $key ): array {
			return self::note( 'design_feature_inactive', self::shown( $key ) );
		}

		/**
		 *	A key out of a file or a request, short enough to stand in a note.
		 *	Whatever it is, it is shown as text and cut where it runs on
		 *
		 *	@param		mixed			$key
		 *	@param		int				$length				Where it is cut
		 *
		 *	@return 	string
		 */
		public static function shown( mixed $key, int $length = 40 ): string {
			return substr( (string) $key, 0, $length );
		}

	}
}
