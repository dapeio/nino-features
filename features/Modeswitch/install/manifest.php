<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Modeswitch ships no template, no
// route and no config default of its own - the site owner puts
// [mode-switch] where the switch belongs, the way docs/recipes/feature.md's
// own example leaves a feature's markup to the project. All this unit
// carries is the switch's four words, merged into text/<locale>.php for
// every available locale.
return [];
