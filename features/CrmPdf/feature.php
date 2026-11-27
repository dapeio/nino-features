<?php
// The class is not declared here: features/CrmPdf/ can only serve \Nino\Modules\CrmPdf
return [
	'key'					=> 'crm-pdf',
	'name'				=> [ 'en_US' => 'CRM PDF templates', 'de_DE' => 'CRM-PDF-Vorlagen' ],
	'description'	=> [
		'en_US' => 'The screens of the PDF feature inside the CRM: the templates, the text blocks, the layout and the design, with a preview of what is typed. It owns no data and no table; everything is read and written through the public interface of the PDF feature, under the permission of the CRM and the permission of the PDF panel. Another package that knows which templates it uses tells the screens through the callbacks of the PDF feature, so that a template in use is not deleted.',
		'de_DE' => 'Die Bildschirme des Features PDF im CRM: die Vorlagen, die Textbausteine, das Layout und das Design, mit einer Vorschau dessen, was getippt ist. Das Paket hat keine Daten und keine Tabelle; alles wird über die öffentliche Schnittstelle des Features PDF gelesen und geschrieben, unter dem Recht des CRM und dem Recht des PDF-Panels. Ein anderes Paket, das weiß, welche Vorlagen es nutzt, sagt es den Bildschirmen über die Callbacks des Features PDF, damit eine genutzte Vorlage nicht gelöscht wird.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [
			'/crm#pdf' => [
				'en_US' => 'The screens inside the CRM: #pdf the templates, #pdf-blocks the text blocks, #pdf-layout the layout with its two pictures, #pdf-design the design; for an account with both permissions.',
				'de_DE' => 'Die Bildschirme im CRM: #pdf die Vorlagen, #pdf-blocks die Textbausteine, #pdf-layout das Layout mit seinen zwei Bildern, #pdf-design das Design; für ein Konto mit beiden Rechten.',
			],
			'/crm/api' => [
				'en_US' => 'Fifteen actions crm-pdf/<name> on the endpoint of the CRM, each under the permission of the PDF panel on top of the one of the CRM.',
				'de_DE' => 'Fünfzehn Aktionen crm-pdf/<name> am Endpunkt des CRM, je unter dem Recht des PDF-Panels zusätzlich zu dem des CRM.',
			],
		],
		'panel' => [],
		'callbacks' => [
			'/pdf/editor' => [
				'en_US' => 'Answers where the editor of a template or a block is for a screen of the CRM: a hash of the page, opened inside it, where the account holds both permissions.',
				'de_DE' => 'Antwortet, wo der Editor einer Vorlage oder eines Bausteins für einen Bildschirm des CRM ist: ein Hash der Seite, in ihr geöffnet, wo das Konto beide Rechte hat.',
			],
			'/nino/http/request' => [
				'en_US' => 'Turns a request for a picture of more than 4 MB away by its length before the endpoint reads it, where it says a length; without one it is checked after reading.',
				'de_DE' => 'Weist eine Anfrage für ein Bild von mehr als 4 MB nach ihrer Länge ab, bevor der Endpunkt sie liest, wo sie eine nennt; ohne wird erst nach dem Lesen geprüft.',
			],
		],
		'install' => [],
	],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm', 'pdf' ],
	'data'				=> [],
	'settings'		=> [],
];
