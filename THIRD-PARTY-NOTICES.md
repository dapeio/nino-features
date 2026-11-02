# Third-party notices

The catalogue's own code is released under the [MIT License](LICENSE). It
bundles no third-party library, but its features draw icons from one: the
`icon()` of every feature's workbench panel (`features/*/Admin/Admin.php`),
the Builder's panel (`features/Builder/templates/panel.tpl`), the `ICONS` of
Design's panel script (`features/Design/assets/admin.js`) and the `icon` of every
component a manifest declares (`features/*/feature.php`) carry the geometry of inline SVG icons
from [Lucide](https://lucide.dev). Lucide is released under the ISC License; the
icons it took over from Feather, the project it grew out of, are under the
MIT License as well. Both licenses ask for their notice to travel with every
copy of the icons, and this file is where it does. The one other thing the
catalogue carries that is not its own is data: the character metrics of Adobe's
Core 14 fonts, which the [Pdf feature](#adobe-core-14-afm-metrics) turns into its
font tables.

A feature is published as an archive of its own directory, which does not
carry this file. Nino, which every feature runs in, carries the same notice
in its own `THIRD-PARTY-NOTICES.md`. The Social links feature, whose icons are
what it draws on a site, carries its licenses in its own directory:
`features/Social/icons/LICENSE`, and `LICENSE-0.577.0` for the six brand icons
it takes from Lucide 0.577.0.

## Lucide

ISC License

Copyright (c) 2026 Lucide Icons and Contributors

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

---

The following Lucide icons are derived from the Feather project:

airplay, alert-circle, alert-octagon, alert-triangle, aperture, arrow-down-circle, arrow-down-left, arrow-down-right, arrow-down, arrow-left-circle, arrow-left, arrow-right-circle, arrow-right, arrow-up-circle, arrow-up-left, arrow-up-right, arrow-up, at-sign, calendar, cast, check, chevron-down, chevron-left, chevron-right, chevron-up, chevrons-down, chevrons-left, chevrons-right, chevrons-up, circle, clipboard, clock, code, columns, command, compass, corner-down-left, corner-down-right, corner-left-down, corner-left-up, corner-right-down, corner-right-up, corner-up-left, corner-up-right, crosshair, database, divide-circle, divide-square, dollar-sign, download, external-link, feather, frown, hash, headphones, help-circle, info, italic, key, layout, life-buoy, link-2, link, loader, lock, log-in, log-out, maximize, meh, minimize, minimize-2, minus-circle, minus-square, minus, monitor, moon, more-horizontal, more-vertical, move, music, navigation-2, navigation, octagon, pause-circle, percent, plus-circle, plus-square, plus, power, radio, rss, search, server, share, shopping-bag, sidebar, smartphone, smile, square, table-2, tablet, target, terminal, trash-2, trash, triangle, tv, type, upload, x-circle, x-octagon, x-square, x, zoom-in, zoom-out

The MIT License (MIT) (for the icons listed above)

Copyright (c) 2013-present Cole Bemis

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Adobe Core 14 AFM metrics

The Pdf feature measures text in the twelve standard fonts it can set - Helvetica,
Times and Courier, each in regular, bold, italic and bold italic - by their
character widths, and those are the metrics of Adobe's Core 14 AFM files as
[Apache PDFBox](https://github.com/apache/pdfbox) redistributes them
(`pdfbox/src/main/resources/org/apache/pdfbox/resources/afm/`). Each font has a
file of its own in `features/Pdf/Fonts/Metrics/`, a modified form of the AFM file:
the widths of the glyphs WinAnsiEncoding addresses - the codes 32 to 255 - are all
it keeps, and nothing of a font program is part of it. The two other fonts of the
set, Symbol and ZapfDingbats, are not used and not shipped.

Adobe asks that its copyright notices are retained, that a modification is noted
in the modified file and that its paragraph travels with the files. The head of
each of the twelve files carries the notices, says that it is modified and quotes
the paragraph - a feature is published as an archive of its own directory, which
does not carry this file - and this file does as well.

| Files | Font | Notice of the AFM file |
| --- | --- | --- |
| `Fonts/Metrics/Helvetica.php`, `Helvetica-Bold.php`, `Helvetica-Oblique.php`, `Helvetica-BoldOblique.php` | Helvetica | Copyright (c) 1985, 1987, 1989, 1990, 1997 Adobe Systems Incorporated.  All Rights Reserved. Helvetica is a trademark of Linotype-Hell AG and/or its subsidiaries. |
| `Fonts/Metrics/Times-Roman.php`, `Times-Bold.php`, `Times-Italic.php`, `Times-BoldItalic.php` | Times | Copyright (c) 1985, 1987, 1989, 1990, 1993, 1997 Adobe Systems Incorporated.  All Rights Reserved. Times is a trademark of Linotype-Hell AG and/or its subsidiaries. |
| `Fonts/Metrics/Courier.php`, `Courier-Oblique.php` | Courier | Copyright (c) 1989, 1990, 1991, 1992, 1993, 1997 Adobe Systems Incorporated.  All Rights Reserved. |
| `Fonts/Metrics/Courier-Bold.php`, `Courier-BoldOblique.php` | Courier | Copyright (c) 1989, 1990, 1991, 1993, 1997 Adobe Systems Incorporated.  All Rights Reserved. |

The terms Adobe distributes the Core 14 AFM files under, as the `MustRead.html` of the
set states them and as the `LICENSE.txt` of Apache PDFBox quotes them:

```text
This file and the 14 PostScript(R) AFM files it accompanies may be used,
copied, and distributed for any purpose and without charge, with or without
modification, provided that all copyright notices are retained; that the
AFM files are not distributed without this file; that all modifications
to this file or any of the AFM files are prominently noted in the modified
file(s); and that this paragraph is not modified. Adobe Systems has no
responsibility or obligation to support the use of the AFM files.
```
