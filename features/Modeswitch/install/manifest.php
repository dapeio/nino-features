<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Modeswitch ships no template, no
// route and no config default of its own - the site owner puts
// [mode-switch] where the switch belongs, and the markup is the project's to
// place. All this unit carries is the switch's four words, merged into
// text/<locale>.php for every available locale.
return [];
