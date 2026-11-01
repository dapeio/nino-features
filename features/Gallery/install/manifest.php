<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Gallery ships no template, no route
// and no config default of its own - a project writes [gallery] where a
// gallery belongs. All this unit carries is the one sentence that names a
// picture with neither an alt text nor a caption, merged into text/<locale>.php
// for every available locale.
return [];
