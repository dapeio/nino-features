<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md,
// "The install unit"). Search ships no template, no route and no config
// default of its own: the components are in components/ and the block in
// blocks/search-page/. All this unit carries is the words of the field and of
// the list, merged into text/<locale>.php for every available locale.
return [];
