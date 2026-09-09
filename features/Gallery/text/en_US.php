<?php
// The Gallery feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script, or by
// the panel in a message it phrases itself (see its _say())
return [
	'[[/_admin/nav/gallery]]'						=> 'Gallery',
	'[[/_admin/gallery/label/images]]'	=> 'Gallery images',

	'[[/_admin/gallery/hint/empty]]'		=> 'No album yet. Add one - its key is what the shortcode names and what its images are filed under, so it cannot be changed afterwards.',
	'[[/_admin/gallery/hint/no-images]]'	=> 'No image in this album yet.',
	'[[/_admin/gallery/hint/shortcode]]'	=> 'Put %s into a template or a text',
	'[[/_admin/gallery/hint/sizes-fit]]'	=> 'Every upload becomes two pictures: a thumbnail cropped to %1, and a large view of the whole picture scaled into %2. The file you choose is not kept - the large view is the biggest a visitor can ever see.',
	'[[/_admin/gallery/hint/sizes-crop]]'	=> 'Every upload becomes two pictures: a thumbnail cropped to %1, and a large view cropped to %2. The file you choose is not kept - the large view is the biggest a visitor can ever see.',

	'[[/_admin/gallery/label/new]]'			=> 'Add album',
	'[[/_admin/gallery/label/open]]'		=> 'Images',
	'[[/_admin/gallery/label/delete]]'	=> 'Delete',
	'[[/_admin/gallery/label/key]]'			=> 'Key',
	'[[/_admin/gallery/label/name]]'		=> 'Name',
	'[[/_admin/gallery/label/count]]'		=> '%s images',
	'[[/_admin/gallery/label/upload]]'	=> 'Add images',
	'[[/_admin/gallery/label/caption]]'	=> 'Caption',
	'[[/_admin/gallery/label/earlier]]'	=> 'Move one place earlier',
	'[[/_admin/gallery/label/later]]'		=> 'Move one place later',

	'[[/_admin/gallery/msg/uploading]]'	=> 'Uploading %s …',
	'[[/_admin/gallery/msg/uploaded]]'	=> '%s added.',

	'[[/_admin/gallery/confirm/album]]'	=> 'Delete the album "%s" and its %d images? The pictures go with it - nothing else points at them.',
	'[[/_admin/gallery/confirm/image]]'	=> 'Delete this image? Both sizes of it go from the server.',

	'[[/_admin/gallery/error/key]]'			=> 'An album key is a slug: lower case, starting with a letter.',
	'[[/_admin/gallery/error/album]]'		=> 'No album has that key.',
	'[[/_admin/gallery/error/image]]'		=> 'That image is not in this album.',
	'[[/_admin/gallery/error/order]]'		=> 'The order did not name every image of the album - nothing was changed.',
	'[[/_admin/gallery/error/upload]]'	=> 'The file could not be read.',
	'[[/_admin/gallery/error/save]]'		=> 'The albums could not be written.',
];
