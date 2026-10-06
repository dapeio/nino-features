<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Typewriter ships no template, no
// route and no config default of its own - a project writes the container
// where a typewriter belongs. All this unit carries is the label of the pause
// button a container can ask for with data-typewriter-toggle, merged into
// text/<locale>.php for every available locale.
return [];
