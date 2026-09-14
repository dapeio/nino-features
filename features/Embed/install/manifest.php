<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Embed ships no template, no route
// and no config default of its own - a project writes [embed ...] where an
// embed belongs, the way docs/recipes/feature.md's own example leaves a
// feature's markup to the project. All this unit carries is the two
// sentences the surface says, merged into text/<locale>.php for every
// available locale.
return [];
