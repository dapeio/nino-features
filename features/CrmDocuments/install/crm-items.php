<?php
declare(strict_types=1);

// install/crm-items.php - the element type the items are kept in, copied to /elements/crm-items.php where the project has none (the manifest's elementTypes, add-only: a type of this name that the project has stays as
// it is, whatever fields it changed). An item is an element of this type, entered in the screen Values of the surface or in the Elements panel; \Nino\Modules\CrmDocuments\Items reads them for the editor of the
// documents, and a page may draw them: [elements /crm-items sort="sort" query="hidden=0"].
//
// The shape is the one \Nino\Elements::insertElementType() writes: the model, then one bucket per locale plus '*' for what is not translated. Empty - an item that was seeded is an item somebody has to find and delete.
//
// The words are per language, so that a page can offer what the owner sells in the language of the visitor, and a language that has none shows the native one's. The numbers are global: the net price in euros with
// at most two decimals and the rate in percent (19, 7, 0) are the same in every language. A line of a document copies them in cents and in hundredths when the editor adds it
return [
	'title' => 'CRM items',
	'model' => [
		'title' => [
			'type'			=> 'string',
			'locale'		=> true,
			'required'	=> true,
			'maxlength'	=> 200,
			'label'			=> [ 'en_US' => 'Title', 'de_DE' => 'Bezeichnung' ],
		],
		'description' => [
			'type'			=> 'string',
			'locale'		=> true,
			'breaks'		=> true,
			'inputsize'	=> 4,
			'maxlength'	=> 2000,
			'label'			=> [ 'en_US' => 'Description', 'de_DE' => 'Beschreibung' ],
		],
		// "hrs", "month"
		'unit' => [
			'type'			=> 'string',
			'locale'		=> true,
			'maxlength'	=> 20,
			'label'			=> [ 'en_US' => 'Unit', 'de_DE' => 'Einheit' ],
		],
		// The unit of the electronic invoice, by its code of UN/ECE Recommendation 20 (the words of the codes are in the surface): a line takes it from the item and may change it. Empty is none: a line without a code
		// is missing in the checklist of the document
		'unit_code' => [
			'type'			=> 'string',
			'options'		=> [ '', 'H87', 'HUR', 'MIN', 'DAY', 'WEE', 'MON', 'LS', 'C62', 'KMT' ],
			'label'			=> [ 'en_US' => 'Unit code', 'de_DE' => 'Einheitencode' ],
		],
		// The net price in euros
		'price' => [
			'type'			=> 'double',
			'required'	=> true,
			'suffix'		=> '€',
			'label'			=> [ 'en_US' => 'Net price', 'de_DE' => 'Nettopreis' ],
		],
		// The rate in percent; a project that charges no VAT does not ask for it and the line gets 0
		'vat' => [
			'type'			=> 'double',
			'suffix'		=> '%',
			'label'			=> [ 'en_US' => 'VAT rate', 'de_DE' => 'Steuersatz' ],
		],
		// Off is the usual case: a new item is offered in the editor and shown on a page without anybody touching the switch (the element form starts a boolean at No, which is why the field says "hidden" and not "active").
		// On takes the item out of the editor of the documents and off a page whose loop says query="hidden=0"
		'hidden' => [
			'type'			=> 'boolean',
			'label'			=> [ 'en_US' => 'Hidden', 'de_DE' => 'Ausgeblendet' ],
		],
		// The order on a page and in the editor
		'sort' => [
			'type'			=> 'integer',
			'label'			=> [ 'en_US' => 'Order', 'de_DE' => 'Reihenfolge' ],
		],
	],
	'*' => [
		'*' => [ 'hidden' => false ],
	],
];
