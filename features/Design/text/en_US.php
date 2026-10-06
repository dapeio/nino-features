<?php
// The Design feature's own workbench strings, merged into its fills while the
// feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has
return [
	'[[/_admin/nav/design]]'								=> 'Design',
	'[[/_admin/design/label/title]]'				=> 'Design',

	'[[/_admin/design/label/global]]'				=> 'Global',
	'[[/_admin/design/label/size]]'					=> 'Root size',
	'[[/_admin/design/label/follow]]'				=> 'Follow again',
	'[[/_admin/design/label/save]]'					=> 'Save draft',
	'[[/_admin/design/label/reset]]'				=> 'Reset',
	'[[/_admin/design/label/apply]]'				=> 'Apply to website',
	'[[/_admin/design/label/restore]]'				=> 'Restore previous version',

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
	'[[/_admin/design/hint/frames]]'				=> 'The header and the footer bring their own markup: applying overwrites the project\'s two frame templates, after keeping what is there as the previous version.',
	'[[/_admin/design/hint/actions]]'				=> '"Save draft" remembers your selection and leaves the website as it is. "Apply to website" rewrites assets/theme.css and the two frame templates, and keeps what is there now as the previous version in data/design-previous.php first.',

	// What the confirmation before an apply says, one line per fact - built in the panel's script and joined with line breaks, so no value here carries one
	'[[/_admin/design/confirm/apply]]'				=> 'Applying rewrites: %s.',
	'[[/_admin/design/confirm/foreign]]'			=> 'Not written by Design, and replaced: %s.',
	'[[/_admin/design/confirm/lost]]'				=> 'In your file, but not in the new variant: %s - add it again by hand afterwards if you still need it.',
	'[[/_admin/design/confirm/copy]]'				=> 'What is there now is kept first, in data/design-previous.php.',
	'[[/_admin/design/confirm/replaces]]'			=> 'That replaces the previous version from %s.',
	'[[/_admin/design/confirm/restore]]'			=> 'Restore the previous version? Its setup is loaded with it, so unsaved changes on screen are lost. What is there now takes its place, so this can be undone.',

	'[[/_admin/design/state/current]]'			=> 'The file answers to this selection.',
	'[[/_admin/design/state/drifted]]'			=> 'The draft is saved but not applied - the site still shows the previous one.',
	'[[/_admin/design/state/missing]]'			=> 'Never applied.',
	'[[/_admin/design/state/foreign]]'			=> 'Not written by Design: %s - delivered with the project or edited by hand. Applying asks first.',
	'[[/_admin/design/state/compiled]]'			=> 'Last applied %s',
	'[[/_admin/design/state/previous]]'				=> 'Previous version from %s',

	// The same four states in one word, for the summary under the preview
	'[[/_admin/design/state/short/current]]'	=> 'Up to date',
	'[[/_admin/design/state/short/drifted]]'	=> 'Saved, not applied',
	'[[/_admin/design/state/short/missing]]'	=> 'Never applied',
	'[[/_admin/design/state/short/foreign]]'	=> 'Not written by Design',

	'[[/_admin/design/msg/saved]]'					=> 'Draft saved.',
	'[[/_admin/design/msg/reverted]]'			=> 'Selection back to the stored one.',
	'[[/_admin/design/msg/applied]]'				=> 'Applied. This is what the site looks like from now on.',
	'[[/_admin/design/msg/takenover]]'			=> 'Files Design had not written were replaced. Design writes them from now on.',
	'[[/_admin/design/msg/cancelled]]'				=> 'Draft saved, not applied.',
	'[[/_admin/design/msg/lost]]'					=> 'Not in the frame templates any more: %s - add back by hand if you still need it.',
	'[[/_admin/design/msg/restored]]'				=> 'Previous version restored.',
	'[[/_admin/design/error/save]]'					=> 'The selection could not be saved.',
	'[[/_admin/design/error/apply]]'				=> 'Applying failed.',
	'[[/_admin/design/error/restore]]'				=> 'The previous version could not be restored.',

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
	'[[/_admin/design/preview/eyebrow]]'		=> 'This selection',

	'[[/_admin/design/label/picker]]'				=> 'Part',
	'[[/_admin/design/label/variant]]'			=> 'Variant',
	'[[/_admin/design/label/state]]'				=> 'Applied file',

	// The eyebrow over a card, and the two blocks of rows under one
	'[[/_admin/design/group/selection]]'		=> 'Selection',
	'[[/_admin/design/group/palette]]'			=> 'Palette',
	'[[/_admin/design/label/finetune]]'			=> 'Finetuning',
	'[[/_admin/design/hint/finetune]]'			=> 'One step below what the chosen set declares, the set as it is, or one step above.',
	'[[/_admin/design/label/tuning]]'				=> 'Tuning',
	'[[/_admin/design/hint/tuning]]'				=> 'How the rest of the palette is solved out of the brand colour.',

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

	'[[/_admin/design/tab/structure]]'			=> 'Structure',
	'[[/_admin/design/tab/colours]]'				=> 'Colours',

	'[[/_admin/design/label/primary]]'			=> 'Brand colour',
	'[[/_admin/design/hint/primary]]'				=> 'used exactly as picked',
	'[[/_admin/design/label/secondary]]'		=> 'Second colour',
	'[[/_admin/design/hint/derived]]'				=> 'set it automatically again',
	'[[/_admin/design/msg/brand-unsafe]]'		=> 'Text on the brand colour itself reads at %s:1 where %s:1 is needed. Anything written on is written on the solved one instead - the colour stays as it is.',

	'[[/_admin/design/colour/harmony/label]]'	=> 'Second colour',
	'[[/_admin/design/colour/harmony/note]]'	=> 'set it automatically, or pick it on the right',
	'[[/_admin/design/colour/harmony/1]]'			=> 'Monochrome',
	'[[/_admin/design/colour/harmony/2]]'			=> 'Analogous',
	'[[/_admin/design/colour/harmony/3]]'			=> 'Triadic',
	'[[/_admin/design/colour/harmony/4]]'			=> 'Complementary',

	'[[/_admin/design/colour/temperature/label]]'	=> 'Temperature',
	'[[/_admin/design/colour/temperature/note]]'	=> 'which way the greys lean',
	'[[/_admin/design/colour/temperature/1]]'			=> 'Neutral',
	'[[/_admin/design/colour/temperature/2]]'			=> 'Cool',
	'[[/_admin/design/colour/temperature/3]]'			=> 'Brand',
	'[[/_admin/design/colour/temperature/4]]'			=> 'Warm',

	'[[/_admin/design/colour/saturation/label]]'	=> 'Saturation',
	'[[/_admin/design/colour/saturation/note]]'		=> 'how much colour',
	'[[/_admin/design/colour/saturation/1]]'			=> 'Muted',
	'[[/_admin/design/colour/saturation/2]]'			=> 'Standard',
	'[[/_admin/design/colour/saturation/3]]'			=> 'Rich',

	'[[/_admin/design/colour/contrast/label]]'	=> 'Contrast',
	'[[/_admin/design/colour/contrast/note]]'		=> 'how hard text reads',
	'[[/_admin/design/colour/contrast/1]]'			=> 'Soft',
	'[[/_admin/design/colour/contrast/2]]'			=> 'Standard',
	'[[/_admin/design/colour/contrast/3]]'			=> 'Strong',

	'[[/_admin/design/colour/depth/label]]'		=> 'Depth',
	'[[/_admin/design/colour/depth/note]]'		=> 'how far surfaces lift',
	'[[/_admin/design/colour/depth/1]]'				=> 'Flat',
	'[[/_admin/design/colour/depth/2]]'				=> 'Standard',
	'[[/_admin/design/colour/depth/3]]'				=> 'Raised',
];
