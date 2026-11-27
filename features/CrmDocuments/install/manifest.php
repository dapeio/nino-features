<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"): the ten fragments of the PDF of a document - the markup is the project's, so that it can look like the project -, and the
// element type the items are kept in. The words of the PDF come with the text files. The templates, the text blocks and the layout of the six kinds of document are no part of the unit: Pdf keeps them in files of
// its own, and the line of the status that says they are missing mends them (the fix reads pdf-<locale>.php). No page and no route: the places of the package are registered by CrmDocuments::init(). Nothing is
// filled at request time as a text key, so there is no blacklist
return [
	'templates'		=> [
		'pdf/crm-documents-address.tpl', 'pdf/crm-documents-address-line.tpl', 'pdf/crm-documents-line.tpl', 'pdf/crm-documents-line-description.tpl', 'pdf/crm-documents-line-break.tpl', 'pdf/crm-documents-line-rate.tpl',
		'pdf/crm-documents-lines.tpl', 'pdf/crm-documents-lines-rate-head.tpl', 'pdf/crm-documents-totals.tpl', 'pdf/crm-documents-totals-rate.tpl',
	],
	'elementTypes'	=> [ 'crm-items.php' ],
];
