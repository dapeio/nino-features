<?php
declare(strict_types=1);

// The words of the panel, one file per interface language (see Admin::text()), merged into the
// workbench's fills while the feature is active. The word of the toggle that opens a panel, which the
// site reads, is delivered by install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/menu/nav]]'	=> 'Menu',


	'[[/_admin/menu/label/new]]'	=> 'New menu',
	'[[/_admin/menu/label/kernel-key]]'	=> 'Menu of the kernel\'s navigation',
	'[[/_admin/menu/label/own-key]]'	=> 'A key of its own',
	'[[/_admin/menu/label/key]]'	=> 'Key',
	'[[/_admin/menu/label/create]]'	=> 'Create',
	'[[/_admin/menu/label/delete]]'	=> 'Delete',
	'[[/_admin/menu/label/entries]]'	=> 'Entries: %s',
	'[[/_admin/menu/label/replaces]]'	=> 'Replaces the kernel\'s navigation',

	'[[/_admin/menu/confirm/delete]]'	=> 'Delete the menu "%s"? Its texts and pictures stay in the Text and Images panels.',

	'[[/_admin/menu/state/no-menus]]'	=> 'There is no menu yet. A menu with the key of a navigation of the kernel stands in for it.',
	'[[/_admin/menu/state/key-invalid]]'	=> 'A key is lower case words joined by hyphens, starting with a letter, up to 32 characters.',
	'[[/_admin/menu/state/key-taken]]'	=> 'There is a menu "%s" already.',

	'[[/_admin/menu/label/back]]'	=> 'Menus',
	'[[/_admin/menu/label/language]]'	=> 'Language',
	'[[/_admin/menu/label/save]]'	=> 'Save',
	'[[/_admin/menu/label/seed]]'	=> 'Take the pages of the kernel\'s menu "%s"',

	'[[/_admin/menu/state/kernel]]'	=> 'The menu "%s" stands in for the kernel\'s navigation of that name wherever the Design puts it.',
	'[[/_admin/menu/state/no-entries]]'	=> 'No entries yet.',

	'[[/_admin/menu/label/entry]]'	=> 'Entry %s',
	'[[/_admin/menu/label/kind]]'	=> 'Entry is',

	'[[/_admin/menu/kind/page]]'	=> 'A page of the site',
	'[[/_admin/menu/kind/link]]'	=> 'An address',

	'[[/_admin/menu/label/page]]'	=> 'Page',
	'[[/_admin/menu/label/address]]'	=> 'Address',
	'[[/_admin/menu/label/target]]'	=> 'Opens',

	'[[/_admin/menu/target/same]]'	=> 'In the same window',
	'[[/_admin/menu/target/blank]]'	=> 'In a new window',

	'[[/_admin/menu/label/label]]'	=> 'Text in %s',
	'[[/_admin/menu/label/add-entry]]'	=> 'Entry',
	'[[/_admin/menu/label/up]]'	=> 'Move up',
	'[[/_admin/menu/label/down]]'	=> 'Move down',
	'[[/_admin/menu/label/remove]]'	=> 'Remove',
	'[[/_admin/menu/label/panel]]'	=> 'Dropdown panel',
	'[[/_admin/menu/label/on]]'	=> 'on',
	'[[/_admin/menu/label/off]]'	=> 'off',
	'[[/_admin/menu/label/columns]]'	=> 'Columns',
	'[[/_admin/menu/label/edit-panel]]'	=> 'Edit the panel',
	'[[/_admin/menu/label/close-panel]]'	=> 'Close the panel',
	'[[/_admin/menu/label/column]]'	=> 'Column %s',
	'[[/_admin/menu/label/item]]'	=> 'Item',

	'[[/_admin/menu/item/title]]'	=> 'Title',
	'[[/_admin/menu/item/subtitle]]'	=> 'Subtitle',
	'[[/_admin/menu/item/image]]'	=> 'Picture',
	'[[/_admin/menu/item/page]]'	=> 'Page of the site',
	'[[/_admin/menu/item/link]]'	=> 'Address',
	'[[/_admin/menu/item/html]]'	=> 'HTML+',

	'[[/_admin/menu/label/text]]'	=> 'Text in %s',
	'[[/_admin/menu/label/html]]'	=> 'HTML+, as typed',
	'[[/_admin/menu/label/add-item]]'	=> 'Item',
	'[[/_admin/menu/label/upload]]'	=> 'Upload a picture',
	'[[/_admin/menu/label/alt]]'	=> 'Alt text in %s',

	'[[/_admin/menu/picture/busy]]'	=> 'Storing the picture ...',
	'[[/_admin/menu/picture/stored]]'	=> 'The picture is stored.',
	'[[/_admin/menu/picture/small]]'	=> 'The picture is stored, but at %s it is smaller than the space it is cut to.',
	'[[/_admin/menu/picture/alt-saved]]'	=> 'The alt text is saved.',

	'[[/_admin/menu/error/load]]'	=> 'It could not be loaded.',
	'[[/_admin/menu/error/registry]]'	=> 'The pages and languages could not be loaded.',
	'[[/_admin/menu/error/save]]'	=> 'It could not be saved.',
	'[[/_admin/menu/error/delete]]'	=> 'The menu could not be deleted.',
	'[[/_admin/menu/error/upload]]'	=> 'The picture could not be stored.',
	'[[/_admin/menu/error/slot]]'	=> 'The place for the picture could not be made.',
	'[[/_admin/menu/error/alt]]'	=> 'The alt text could not be saved.',

	// The sentences of the codes the panel answers with, %s a name or a permission (Admin)
	'[[/_admin/error/menu_key]]'	=> 'A menu key is lower case words joined by hyphens, starting with a letter.',
	'[[/_admin/error/menu_invalid]]'	=> 'These entries are not valid: %s. Their ids, addresses and keys are checked on saving.',
	'[[/_admin/error/menu_list]]'	=> 'The entries are a list of at most %s.',
	'[[/_admin/error/menu_texts]]'	=> 'The texts are for the keys of this menu, in the languages of the site, one line of at most 300 characters each.',
	'[[/_admin/error/menu_missing]]'	=> 'There is no menu "%s".',
	'[[/_admin/error/menu_permission]]'	=> 'Saving this menu makes or changes texts or picture places, which asks for the permission %s.',
	'[[/_admin/error/menu_key_made]]'	=> 'The text key %s could not be made.',
	'[[/_admin/error/menu_slot_made]]'	=> 'The picture place %s could not be made.',
];
