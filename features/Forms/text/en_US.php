<?php
// The Forms feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script, or
// by the panel in a message it phrases itself (see its _say())
return [
	'[[/_admin/nav/forms]]'								=> 'Forms',
	'[[/_admin/forms/label/submissions]]'	=> 'Submissions',

	'[[/_admin/forms/hint/blocked]]'			=> 'Nino\'s own contact form is still switched on in /nino/modules, so this feature does nothing: both answer POST /.form, and two handlers on one endpoint mean two mails and two records for one visitor. Remove \\Nino\\Modules\\Form from /nino/modules in config.php.',
	'[[/_admin/forms/hint/default]]'			=> 'This is the form Nino\'s contact page has always posted, shown as it would be. Nothing is stored yet - saving it once writes the definitions file.',
	'[[/_admin/forms/hint/empty]]'				=> 'No form is defined. Add one - its own screen then names the shortcode that renders it.',
	'[[/_admin/forms/hint/entries-empty]]'	=> 'Nothing has been submitted yet.',
	'[[/_admin/forms/hint/nomatch]]'			=> 'No submission matches.',
	'[[/_admin/forms/hint/shortcode]]'		=> 'Put %s into a template or a text to render this form.',
	'[[/_admin/forms/hint/fields]]'				=> 'The name is what the field is posted and exported as. The label is what a visitor reads - a text key written as a fill is resolved before it is shown, so one label can serve every language.',
	'[[/_admin/forms/hint/to]]'						=> 'Empty sends to the address in the text fill /form/email/owner.',
	'[[/_admin/forms/hint/subject]]'			=> 'Empty uses the text fill /form/subject/owner.',
	'[[/_admin/forms/hint/templates]]'		=> 'The mail templates this form renders. Both carry a placeholder for the whole submission as a table, so a form with fields of its own needs no template of its own.',

	'[[/_admin/forms/label/new]]'					=> 'New form',
	'[[/_admin/forms/label/edit]]'				=> 'Edit',
	'[[/_admin/forms/label/entries]]'			=> 'Submissions (%s)',
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
	'[[/_admin/forms/label/date]]'				=> 'Received',
	'[[/_admin/forms/label/search]]'			=> 'Search submissions',
	'[[/_admin/forms/label/export]]'			=> 'Export as CSV',
	'[[/_admin/forms/label/filename]]'		=> 'submissions.csv',
	'[[/_admin/forms/label/count]]'				=> '%s submissions on file',
	'[[/_admin/forms/label/count-one]]'		=> '1 submission on file',

	'[[/_admin/forms/confirm/delete]]'		=> 'Delete this form? Its submissions stay until the retention window removes them.',
	'[[/_admin/forms/confirm/entry]]'			=> 'Delete this submission? The mail that went out is not affected.',

	'[[/_admin/forms/error/invalid]]'			=> 'The form needs a key of its own and at least one field.',
	'[[/_admin/forms/error/duplicate]]'		=> 'Another form already has that key.',
	'[[/_admin/forms/error/save]]'				=> 'The forms could not be written.',
	'[[/_admin/forms/error/key]]'					=> 'No form has that key.',
	'[[/_admin/forms/error/entry]]'				=> 'That submission is not on file any more.',
];
