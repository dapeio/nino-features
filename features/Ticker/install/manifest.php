<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Ticker ships no template, no
// route and no config default of its own: the component is in
// components/ticker/ and the block in blocks/ticker-line/. All this unit
// carries is the label of the pause button a ticker can ask for with toggle,
// merged into text/<locale>.php for every available locale.
return [];
