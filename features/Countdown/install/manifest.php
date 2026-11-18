<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Countdown ships no template, no
// route and no config default of its own: the component is in
// components/countdown/ and the block in blocks/countdown-launch/. All this
// unit carries is the unit names in both their forms, the names of the months
// and days for a dateFormat="...", and the sentence for afterwards, merged
// into text/<locale>.php for every available locale.
return [];
