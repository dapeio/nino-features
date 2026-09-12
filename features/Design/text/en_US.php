<?php
// The Design feature's own workbench strings, merged into its fills while the
// feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has
return [
	'[[/_admin/nav/design]]'								=> 'Design',
	'[[/_admin/design/label/title]]'				=> 'Design',
	'[[/_admin/design/hint/intro]]'					=> 'One set per part of a page. Nobody picks a whole theme here - loud headings from one design and round buttons from another is the point.',

	'[[/_admin/design/label/global]]'				=> 'Global',
	'[[/_admin/design/label/knob]]'					=> 'Finetuning',
	'[[/_admin/design/label/size]]'					=> 'Root size',
	'[[/_admin/design/label/follow]]'				=> 'Follow again',
	'[[/_admin/design/label/save]]'					=> 'Save the selection',
	'[[/_admin/design/label/apply]]'				=> 'Save and compile',
	'[[/_admin/design/label/takeover]]'			=> 'Take the file over and compile',

	'[[/_admin/design/part/header]]'				=> 'Header',
	'[[/_admin/design/part/footer]]'				=> 'Footer',
	'[[/_admin/design/part/atf]]'						=> 'ATF (the hero)',
	'[[/_admin/design/part/section]]'				=> 'Section',
	'[[/_admin/design/part/article]]'				=> 'Article',
	'[[/_admin/design/part/buttons]]'				=> 'Buttons',
	'[[/_admin/design/part/forms]]'					=> 'Forms',
	'[[/_admin/design/part/lists]]'					=> 'Lists & tables',
	'[[/_admin/design/part/blocks]]'				=> 'Blocks',

	'[[/_admin/design/step/less]]'					=> '−1',
	'[[/_admin/design/step/default]]'				=> '0',
	'[[/_admin/design/step/more]]'					=> '+1',
	'[[/_admin/design/size/s]]'							=> 'small',
	'[[/_admin/design/size/m]]'							=> 'default',
	'[[/_admin/design/size/l]]'							=> 'large',

	'[[/_admin/design/hint/knob]]'					=> 'Every set declares three steps for the knobs it answers to. Finetuning picks one of them - it never computes. A part follows a knob\'s global position until somebody moves it here; then it stays where it was put.',
	'[[/_admin/design/hint/size]]'					=> 'Scales the whole page through the root font size - as a percentage of the visitor\'s own browser default, never as a fixed pixel value.',
	'[[/_admin/design/hint/frames]]'				=> 'The header and the footer bring their own markup: compiling overwrites the project\'s two frame templates.',

	'[[/_admin/design/state/current]]'			=> 'The file answers to this selection.',
	'[[/_admin/design/state/drifted]]'			=> 'The selection is saved but not compiled - the site still shows the previous one.',
	'[[/_admin/design/state/missing]]'			=> 'Never compiled.',
	'[[/_admin/design/state/foreign]]'			=> '%s is not one of ours: either the delivered file or one somebody edited. It is not overwritten unless you say so.',
	'[[/_admin/design/state/compiled]]'			=> 'Last compiled %s',

	'[[/_admin/design/msg/saved]]'					=> 'Selection saved.',
	'[[/_admin/design/msg/applied]]'				=> 'Compiled. This is what the site looks like from now on.',
	'[[/_admin/design/msg/takenover]]'			=> 'File taken over and compiled. Design writes it from now on.',
	'[[/_admin/design/error/save]]'					=> 'The selection could not be saved.',
	'[[/_admin/design/error/apply]]'				=> 'Compiling failed.',

	'[[/_admin/design/label/preview]]'			=> 'Preview',
	'[[/_admin/design/label/width]]'				=> 'Width',
	'[[/_admin/design/width/phone]]'				=> 'Phone',
	'[[/_admin/design/width/tablet]]'				=> 'Tablet',
	'[[/_admin/design/width/desktop]]'			=> 'Desktop',
	'[[/_admin/design/label/reload]]'				=> 'Reload the preview',
	'[[/_admin/design/label/jump]]'					=> 'Follow the part',
	'[[/_admin/design/msg/previewing]]'			=> 'Building the preview …',
	'[[/_admin/design/error/preview]]'			=> 'The preview could not be built.',
	'[[/_admin/design/preview/page]]'				=> 'Preview',

	'[[/_admin/design/label/picker]]'				=> 'Part',
	'[[/_admin/design/label/variant]]'			=> 'Variant',
	'[[/_admin/design/label/state]]'				=> 'How it is used',

	'[[/_admin/design/knob/empty]]'					=> 'This variant answers to no knob.',

	'[[/_admin/design/knob/volume/label]]'	=> 'Headings',
	'[[/_admin/design/knob/volume/note]]'		=> 'how far they grow',
	'[[/_admin/design/knob/volume/less]]'		=> 'Calm',
	'[[/_admin/design/knob/volume/default]]'=> 'Standard',
	'[[/_admin/design/knob/volume/more]]'		=> 'Bold',

	'[[/_admin/design/knob/spacing/label]]'		=> 'Spacing',
	'[[/_admin/design/knob/spacing/note]]'		=> 'gaps and line height',
	'[[/_admin/design/knob/spacing/less]]'		=> 'Tight',
	'[[/_admin/design/knob/spacing/default]]'	=> 'Standard',
	'[[/_admin/design/knob/spacing/more]]'		=> 'Airy',

	'[[/_admin/design/knob/shaping/label]]'		=> 'Corners',
	'[[/_admin/design/knob/shaping/note]]'		=> 'how round',
	'[[/_admin/design/knob/shaping/less]]'		=> 'Sharp',
	'[[/_admin/design/knob/shaping/default]]'	=> 'Standard',
	'[[/_admin/design/knob/shaping/more]]'		=> 'Round',

	'[[/_admin/design/knob/measure/label]]'		=> 'Width',
	'[[/_admin/design/knob/measure/note]]'		=> 'how wide content runs',
	'[[/_admin/design/knob/measure/less]]'		=> 'Narrow',
	'[[/_admin/design/knob/measure/default]]'	=> 'Standard',
	'[[/_admin/design/knob/measure/more]]'		=> 'Wide',
];
