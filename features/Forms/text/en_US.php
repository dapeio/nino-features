<?php
// The Forms feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script
// (Nino.adminUi.format()), or by the workbench in the sentence of a code
return [
	'[[/_admin/nav/forms]]'								=> 'Forms',
	'[[/_admin/forms/label/forms]]'				=> 'Forms',

	'[[/_admin/forms/hint/endpoint]]'			=> 'The kernel module that answers POST /.form is not switched on, so every form here would post into nothing. Add \\Nino\\Modules\\Form back to /nino/modules in config.php.',
	'[[/_admin/forms/hint/default]]'			=> 'This is the contact form Nino falls back to while a project has defined none of its own, shown as it would be. Saving anything here writes /nino/form/forms into config.php for the first time.',
	'[[/_admin/forms/hint/shortcode]]'		=> 'Put %s into a template or a text to render this form.',
	'[[/_admin/forms/hint/fields]]'				=> 'The name is what the field is posted and exported as. A field you add takes its name from the label until you change the name by hand; a saved name stays. The label is what a visitor reads - a text key written as a fill is resolved before it is shown, so one label can serve every language, and the name is then taken from the last part of the key. A checkbox suits a consent: it posts 1 when it is ticked. These names are taken: %s.',
	'[[/_admin/forms/hint/to]]'						=> 'Empty sends to the address in the text fill /project/mail/address/owner.',
	'[[/_admin/forms/hint/subject]]'			=> 'Empty uses the text fill /module/form/subject/owner.',
	'[[/_admin/forms/hint/templates]]'		=> 'The mail templates this form renders. A field reaches the mail only where its template shows it: %s draws every field of the form, and a template you wrote yourself can name single ones. The templates of a new project carry it; a project installed earlier adds it by hand.',
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
	'[[/_admin/forms/label/options]]'			=> 'Options',
	'[[/_admin/forms/label/addfield]]'		=> 'Add field',
	'[[/_admin/forms/label/removefield]]'	=> 'Remove',
	'[[/_admin/forms/label/submissions-settings]]'	=> 'Submissions',
	'[[/_admin/forms/label/retention]]'		=> 'Keep for',
	'[[/_admin/forms/label/store]]'				=> 'Record submissions',

	'[[/_admin/forms/options/add]]'				=> 'Add option',
	'[[/_admin/forms/options/remove]]'		=> 'Remove',
	'[[/_admin/forms/options/up]]'				=> 'Move up',
	'[[/_admin/forms/options/down]]'			=> 'Move down',
	'[[/_admin/forms/options/empty]]'			=> 'No options yet',
	'[[/_admin/forms/options/item]]'			=> 'Option %d',

	'[[/_admin/forms/type/text]]'		=> 'Text',
	'[[/_admin/forms/type/email]]'		=> 'Email address',
	'[[/_admin/forms/type/tel]]'		=> 'Phone number',
	'[[/_admin/forms/type/url]]'		=> 'Web address',
	'[[/_admin/forms/type/number]]'		=> 'Number',
	'[[/_admin/forms/type/textarea]]'	=> 'Text area',
	'[[/_admin/forms/type/select]]'		=> 'Drop-down list',
	'[[/_admin/forms/type/checkbox]]'	=> 'Checkbox',
	'[[/_admin/forms/type/radio]]'		=> 'Radio buttons',
	'[[/_admin/forms/type/date]]'		=> 'Date',

	'[[/_admin/forms/confirm/delete]]'		=> 'Delete this form? Its submissions stay, and the Submissions panel goes on showing them.',

	// The sentences of the codes Admin answers a refusal with (\Nino\Http::fail()).
	// forms_fields and forms_duplicate carry where it went wrong as their params,
	// in the words below
	'[[/_admin/error/forms_fields]]'		=> 'The form was not saved. Change what is marked and save again.',
	'[[/_admin/error/forms_invalid]]'		=> 'The form needs a key of its own and at least one field.',
	'[[/_admin/error/forms_duplicate]]'		=> 'Another form already has that key.',
	'[[/_admin/error/forms_key]]'			=> 'No form has that key.',
	'[[/_admin/error/forms_last]]'			=> 'This is the last form. A project with none falls back to the built-in contact form; to have no form at all, switch the Form module off in /nino/modules.',
	'[[/_admin/error/forms_retention]]'		=> 'Submissions are kept between 1 and 60 months.',

	// What is wrong with a field or a control of a form that was refused: the
	// word Admin names, said under it
	'[[/_admin/forms/problem/key]]'			=> 'The key starts with a letter and has only lower-case letters, digits and hyphens.',
	'[[/_admin/forms/problem/keytaken]]'	=> 'Another form already has this key.',
	'[[/_admin/forms/problem/field]]'		=> 'This field cannot be read.',
	'[[/_admin/forms/problem/name]]'		=> 'The name starts with a letter and has only letters, digits, hyphens and underscores, 64 characters at most.',
	'[[/_admin/forms/problem/reserved]]'	=> 'The form keeps this name for itself. Choose another one.',
	'[[/_admin/forms/problem/duplicate]]'	=> 'Another field already has this name.',
	'[[/_admin/forms/problem/type]]'		=> 'This type does not exist.',
	'[[/_admin/forms/problem/options]]'		=> 'Radio buttons need at least one option.',
	'[[/_admin/forms/problem/fields]]'		=> 'The form needs at least one field.',
	'[[/_admin/forms/problem/to]]'			=> 'This is not an email address.',
	'[[/_admin/forms/problem/ownertpl]]'	=> 'This is not the path of a template. Choose one from the list.',
	'[[/_admin/forms/problem/usertpl]]'		=> 'This is not the path of a template. Choose one from the list.',
	'[[/_admin/forms/problem/missingtpl]]'	=> 'There is no such template in this project. Choose another one.',
	'[[/_admin/forms/problem/form]]'		=> 'The form cannot be saved as it is.',
];
