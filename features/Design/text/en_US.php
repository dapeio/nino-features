<?php
// The Design feature's own workbench strings, merged into its fills while the
// feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has
return [
	'[[/_admin/nav/design]]'								=> 'Design',
	'[[/_admin/design/label/title]]'				=> 'The look of the site',
	'[[/_admin/design/hint/intro]]'					=> 'One set per part of a page. Nobody picks a whole theme here - loud headings from one design and round buttons from another is the point.',

	'[[/_admin/design/label/parts]]'				=> 'Parts',
	'[[/_admin/design/label/global]]'				=> 'For the whole site',
	'[[/_admin/design/label/knob]]'					=> 'Finetune',
	'[[/_admin/design/label/size]]'					=> 'Root size',
	'[[/_admin/design/label/follow]]'				=> '— follows the knob —',
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

	'[[/_admin/design/step/less]]'					=> 'less',
	'[[/_admin/design/step/default]]'				=> 'default',
	'[[/_admin/design/step/more]]'					=> 'more',
	'[[/_admin/design/size/s]]'							=> 'small',
	'[[/_admin/design/size/m]]'							=> 'default',
	'[[/_admin/design/size/l]]'							=> 'large',

	'[[/_admin/design/hint/knob]]'					=> 'Every set declares three steps for each of its values. The knob picks one of them - it never computes. A single part may deviate ("articles rounder, buttons squarer"); the rest follow the global position, and keep following it when it moves.',
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
	'[[/_admin/design/hint/preview]]'				=> 'The selection on this screen, rendered against this project - its menu, its logo, its fonts. None of it is written: what you see here reaches the site when you compile.',
	'[[/_admin/design/label/width]]'				=> 'Width',
	'[[/_admin/design/width/phone]]'				=> 'Phone',
	'[[/_admin/design/width/tablet]]'				=> 'Tablet',
	'[[/_admin/design/width/desktop]]'			=> 'Desktop',
	'[[/_admin/design/label/reload]]'				=> 'Reload the preview',
	'[[/_admin/design/msg/previewing]]'			=> 'Building the preview …',
	'[[/_admin/design/error/preview]]'			=> 'The preview could not be built.',
	'[[/_admin/design/preview/page]]'				=> 'Preview',
];
