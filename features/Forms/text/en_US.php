<?php
// The Forms feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script, or
// by the panel in a message it phrases itself (see its _say())
return [
	'[[/_admin/nav/forms]]'								=> 'Forms',
	'[[/_admin/forms/label/forms]]'				=> 'Forms',

	'[[/_admin/forms/hint/endpoint]]'			=> 'The kernel module that answers POST /.form is not switched on, so every form here would post into nothing. Add \\Nino\\Modules\\Form back to /nino/modules in config.php.',
	'[[/_admin/forms/hint/default]]'			=> 'This is the contact form Nino falls back to while a project has defined none of its own, shown as it would be. Saving anything here writes /nino/form/forms into config.php for the first time.',
	'[[/_admin/forms/hint/empty]]'				=> 'No form is defined. Add one - its own screen then names the shortcode that renders it.',
	'[[/_admin/forms/hint/shortcode]]'		=> 'Put %s into a template or a text to render this form.',
	'[[/_admin/forms/hint/fields]]'				=> 'The name is what the field is posted and exported as. The label is what a visitor reads - a text key written as a fill is resolved before it is shown, so one label can serve every language. These names are taken: %s.',
	'[[/_admin/forms/hint/to]]'						=> 'Empty sends to the address in the text fill /form/email/owner.',
	'[[/_admin/forms/hint/subject]]'			=> 'Empty uses the text fill /form/subject/owner.',
	'[[/_admin/forms/hint/templates]]'		=> 'The mail templates this form renders. Both carry a placeholder for the whole submission as a table, so a form with fields of its own needs no template of its own.',
	'[[/_admin/forms/hint/retention]]'		=> 'How many months of submissions stay on disk, 1 to 60. A month older than this is deleted the next time one comes in - the mail itself is not affected.',
	'[[/_admin/forms/hint/store]]'				=> 'Off means the mail goes out and nothing is written to disk. The Submissions panel then stays empty by design.',

	'[[/_admin/forms/label/new]]'					=> 'New form',
	'[[/_admin/forms/label/edit]]'				=> 'Edit',
	'[[/_admin/forms/label/entries]]'			=> '%s on file',
	'[[/_admin/forms/label/delete]]'			=> 'Delete',
	'[[/_admin/forms/label/form]]'				=> 'Form',
	'[[/_admin/forms/label/name]]'				=> 'Name',
	'[[/_admin/forms/label/key]]'					=> 'Key',
	'[[/_admin/forms/label/to]]'					=> 'Send to',
	'[[/_admin/forms/label/subject]]'			=> 'Subject',
	'[[/_admin/forms/label/confirm]]'			=> 'Confirmation mail to the visitor',
	'[[/_admin/forms/label/ownertpl]]'		=> 'Owner mail template',
	'[[/_admin/forms/label/usertpl]]'			=> 'Confirmation mail template',
	'[[/_admin/forms/label/fields]]'			=> 'Fields',
	'[[/_admin/forms/label/fieldname]]'		=> 'Name',
	'[[/_admin/forms/label/fieldlabel]]'	=> 'Label',
	'[[/_admin/forms/label/fieldtype]]'		=> 'Type',
	'[[/_admin/forms/label/required]]'		=> 'Required',
	'[[/_admin/forms/label/options]]'			=> 'Options, one per line',
	'[[/_admin/forms/label/addfield]]'		=> 'Add field',
	'[[/_admin/forms/label/removefield]]'	=> 'Remove',
	'[[/_admin/forms/label/submissions-settings]]'	=> 'Submissions',
	'[[/_admin/forms/label/retention]]'		=> 'Keep for (months)',
	'[[/_admin/forms/label/store]]'				=> 'Record submissions',

	'[[/_admin/forms/confirm/delete]]'		=> 'Delete this form? Its submissions stay, and the Submissions panel goes on showing them.',

	'[[/_admin/forms/error/invalid]]'			=> 'The form needs a key of its own and at least one field.',
	'[[/_admin/forms/error/duplicate]]'		=> 'Another form already has that key.',
	'[[/_admin/forms/error/save]]'				=> 'The forms could not be written.',
	'[[/_admin/forms/error/key]]'					=> 'No form has that key.',
	'[[/_admin/forms/error/last]]'				=> 'This is the last form. A project with none falls back to the built-in contact form; to have no form at all, switch the Form module off in /nino/modules.',
	'[[/_admin/forms/error/retention]]'		=> 'Submissions are kept between 1 and 60 months.',
];
