<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only: the signing page of the feature - made of components and the header and footer of the site -, its
// words (the page, the form and its states, the list, the record and the three mails) and its section of the privacy policy. No route: the route /.sign,
// which Sign::init() registers, renders the page in place (see Sign::_page()); a project that wants an address of its own for it gives it a route in the Routes
// panel. Nothing here is filled at request time as a text key - the request-time values of the components and of the mails are bare tokens the class fills, and
// the words they choose between are all in the text files - so there is no blacklist
return [
	'templates' => [ 'page-sign.tpl' ],
	'elements' => [ 'privacy' => 'elements/privacy.php' ],
];
