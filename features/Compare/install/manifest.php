<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Compare ships no template, no route
// and no config default of its own: the component is in components/compare/
// and the block in blocks/compare-before-after/. All this unit carries is the
// three words a pair says, merged into text/<locale>.php for every available
// locale.
return [];
