/**
 *	Nino features
 *	builder-bridge-js-smoke.js	What the script and the style of the panel do since the extended view and the frames went to the editor of the blocks - read
 *															from their source, as the page that draws them is looked at in a browser:
 *
 *															A. the page has no extended view, no frames and no own-block drop; the old address of a block marks it in the page
 *															B. the way to the editor of the blocks is for the one who holds its right, and is the only way
 *															C. the work surface: the field of the pencil, the quiet hover, the place above a section, the roll under a pointer that stands still,
 *															   the background of a section and the pictures of the devices
 *															D. the lightbox: the dimmed area closes it and nothing else, the width of it is the width of the preview, the box of the widths
 *
 *	Usage: node features/Builder/tests/builder-bridge-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const script = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
const sheet = fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );
const de = fs.readFileSync( path.join( featureDir, 'text/de_DE.php' ), 'utf8' );
const en = fs.readFileSync( path.join( featureDir, 'text/en_US.php' ), 'utf8' );
const panelPhp = fs.readFileSync( path.join( featureDir, 'Admin/Admin.php' ), 'utf8' );
const notices = fs.readFileSync( path.join( featureDir, '../../THIRD-PARTY-NOTICES.md' ), 'utf8' );

const has = function( pattern ) { return pattern.test( script ) };
const word = function( text, key ) {

	const found = new RegExp( "\\[\\[/_admin/builder/"+ key.replace( /[/]/g, '\\/' )+ "\\]\\]'\\s*=> '([^']*)'" ).exec( text );

	return found === null ? '' : found[1];
};

console.log( '\nA. The page' );

check( 'A1. the page has no extended view and no frame view: no button "Advanced", no way into one, no choice of the two frames, no frame in an address of the panel',
	has( /openExtended|openFrames|advancedParts|builder-frames|label\/advanced|label\/frames|ACTION_ICONS\.advanced/ ) === false && has( /actions\.push\( \{ key : 'advanced'/ ) === false );
check( 'A2. the old address #builder/<page>/<block> opens the page with the block marked - and tells where the block is gone - and a frame is no address of the panel',
	/block : \/\^\(\?:@\(\?:header\|footer\)\|\[a-z\]\[a-z0-9-\]\*\)\$\/\.test\( parts\[1\]/.test( script ) === true && has( /if\( atOf\( target\.block \) !== -1 \)\s*selectBlock\( target\.block, true \)/ ) === true && has( /msg\/no-block/ ) === true
	&& has( /if\( part === '@header' \)\s*return 'frame-header'/ ) === false );
check( 'A3. the dialog "Insert block" has no button that takes a block of the project away, and the panel asks for no action that takes one: dead code, dead texts, dead actions are gone',
	has( /blockdrop|askDrop|builder-tile-drop|insert\/drop|confirm\/drop/ ) === false && sheet.indexOf( 'builder-tile-drop' ) === -1 && /builder\/(blocksave|blockdrop|offer|notation)/.test( panelPhp ) === false
	&& has( /api\( '(blocksave|blockdrop)'/ ) === false );
check( 'A4. the words of what is gone are gone in both languages: Advanced, the frames of the list, the step of the development, the question of a drop - and the two files have the same keys',
	[ 'label/advanced', 'label/frames', 'label/frames-edit', 'msg/no-advanced', 'scope/develop', 'scope/develop-edit', 'confirm/drop-title', 'insert/drop', 'msg/dropped' ].every( function( key ) { return word( de, key ) === '' && word( en, key ) === '' } ) );

console.log( '\nB. The way to the editor of the blocks' );

check( 'B1. a block of the page is taken as a block of the project - the page unchanged - or opened in the editor where it is built like one: both in the lightbox of the block, for the one who holds the right, and nowhere else',
	has( /if\( rel === 'root' && S\.sheet === 'page' && S\.bridge === true \)/ ) === true && has( /own\/open/ ) === true && has( /S\.own\[box\.block\] !== undefined/ ) === true && has( /askBlocks\( 'adopt'/ ) === true && has( /api\( 'adopt'/ ) === false
	&& has( /S\.bridge = data\.bridge === true/ ) === true && has( /S\.own = response\.own \?\? /) === true );
check( 'B2. the way is asked about unsaved work first, and goes to the address of the block in the panel of the blocks - never to a block the page names itself',
	has( /Nino\.admin\.dirty\.guard\( \[ 'builder' \], function\(\) \{ Nino\.admin\.router\.go\( 'themelab', \[ key \] \) \} \)/ ) === true && has( /editInBlocks\( S\.own\[box\.block\] \)/ ) === true );
check( 'B3. the rows of the header and the footer in the outline are a display - "applies to all pages" - and have a button to the editor of the blocks only for the one who holds its right',
	has( /item\.kind === 'frame' && S\.bridge === true && \( row\.key === '@header' \|\| row\.key === '@footer' \)/ ) === true && has( /'themelab', \[ '@frames', id === '@footer' \? 'footer' : 'header' \]/ ) === true );
check( 'B3b. the way from a row of the frames in the list of the pages to the editor of that frame is the address of the tab with the @: #themelab/@frames/<key>',
	has( /router\.go\( 'themelab', \[ '@frames', frame\.key \] \)/ ) === true );
check( 'B4. the words of the two ways are in both languages and say what they do: "take over into own blocks", "open in Themelab"',
	word( de, 'own/open' ).indexOf( 'Eigene Blocks' ) !== -1 && word( en, 'own/open' ).indexOf( 'Own blocks' ) !== -1 && word( de, 'own/edit' ).indexOf( 'Themelab' ) !== -1 && word( en, 'own/edit' ).indexOf( 'Themelab' ) !== -1 );

check( 'B4b. the question before a frame is replaced says what goes with it - its texts and links are replaced by those of the block - and what the way back does, in both languages and with the capital Du',
	/Texte und Links/.test( word( de, 'frame/confirm-message' ) ) === true && /Rückgängig/.test( word( de, 'frame/confirm-message' ) ) === true && /\bDu\b/.test( word( de, 'frame/confirm-message' ) ) === true && /texts and links/.test( word( en, 'frame/confirm-message' ) ) === true && /Undo/.test( word( en, 'frame/confirm-message' ) ) === true );

check( 'B5. the rows of the header and the footer have "Edit texts" for every step - the words the frame holds as its own, in every language, saved at once for every page, never a tree - and the actions that answer are the Builder\'s own and shut to a step below the content',
	has( /actions\.push\( \{ key : 'texts', icon : 'pencil', name : word\( 'frame\/texts-of', row\.label \) \} \)/ ) === true && has( /api\( 'frametexts', \{ template : template \}/ ) === true && has( /api\( 'frametextssave', \{ template : template, texts : changed, base : response\.stamp \}/ ) === true
	&& has( /api\( 'frametextssave', \{[^}]*tree/ ) === false && /builder\/frametexts'\s*=> \[ self::class, 'apiFrameTexts' \]/.test( panelPhp ) === true && /_gateTexts[\s\S]*LEVEL_CONTENT/.test( panelPhp ) === true );

console.log( '\nC. The work surface' );

check( 'C1. the words of a width say what is inherited: "Inherited from tablet", "Inherited from smartphone", and the first step is the smartphone - in both languages, and from this panel',
	word( de, 'rows/below-desktop' ) === 'Geerbt von Tablet' && word( de, 'rows/below-tablet' ) === 'Geerbt von Smartphone' && word( de, 'rows/all' ) === 'Smartphone'
	&& word( en, 'rows/below-desktop' ) === 'Inherited from tablet' && word( en, 'rows/below-tablet' ) === 'Inherited from smartphone' && word( en, 'rows/all' ) === 'Smartphone' );
check( 'C2. the pencil is inside the frame of its component, in its top corner on the right, and nowhere else: one place for it, no side to go to and no row that has it at the left',
	has( /const spot = penSpot\( box, size, view \);/ ) === true && has( /sides\[0\]|side : 'right'|row === true \? box\.left/ ) === false && has( /const PEN_INSET = 2;/ ) === true );
check( 'C3. the pencil, the plus and the bar have a ground and a colour of their own from the tokens of the workbench - not the colours of the page, which they are drawn over - and the same in rest, over and in focus: a solid ground that is no tint of what is behind it, and two rings that tell it from a light page and a dark one',
	/button\.pen\{[^']*border:1px solid var\(--b-blue\);[^']*background:var\(--b-blue\);color:var\(--b-ground\);box-shadow:var\(--b-ring\)/.test( script ) === true && /button\.pen:hover\{border-color:var\(--b-bluehover\);background:var\(--b-bluehover\)\}/.test( script ) === true
	&& /button\.insert\{[^']*box-shadow:var\(--b-ring\)/.test( script ) === true && /\.bar\{[^']*box-shadow:var\(--b-ring\)/.test( script ) === true && /button:hover\{background:var\(--b-soft\);background:color-mix\(in srgb,var\(--b-blue\) 16%,var\(--b-ground\)\)\}/.test( script ) === true
	&& /ring : '0 0 0 1px '\+ read\('--editor-bg-elevated'\)\+ ', 0 0 0 2px '\+ read\('--editor-text'\)/.test( script ) === true && /bluehover : read\('--editor-blue-hover'\)/.test( script ) === true
	&& /PEN_CSS = [\s\S]*?prefers-reduced-motion:reduce\)\{button\.pen\{transition:none\}\}/.test( script ) === true && /(?:BAR_CSS|PEN_CSS) = [^;]*#[0-9a-fA-F]{3,6}\b/.test( script ) === false );
check( 'C4. the pictures of the devices, the alignments and the directions are the workbench\'s: no strokes of the panel, steps() with an icon in the lightbox, the devices of the preview bar by previewTools({ icons }), one table that says which picture a value has - and the stretch is the tall bars',
	has( /GLYPHS/ ) === false && has( /createElementNS/ ) === false && /builder-step-glyph/.test( sheet ) === false && has( /icon : pictured_ === true \? pictures\[at\] : undefined/ ) === true && has( /icon : device\.glyph/ ) === true
	&& has( /previewTools\( \{\s*icons\s*: true,/ ) === true && has( /pictured\(/ ) === false && has( /valign\s*: \{ stretch : 'stretch-vertical'/ ) === true && has( /smartphone/ ) === true && has( /monitor/ ) === true );
check( 'C5. the places to put a block in are ONE button for each seam - the top edge of the first section, the edge between two, the bottom edge of the last - put on the edge by the boxes of the sections and by nothing that is on: the seam is not moved, what changes is whether it is shown',
	has( /const edges = \[\];/ ) === true && has( /spots\.filter\( function\( spot \) \{ return spot\.block !== null \} \)\.forEach/ ) === true && has( /const earlier = index > 0/ ) === false && has( /pills\.before|pills\.after|item\.pills/ ) === false
	&& ( function() { const place = script.slice( script.indexOf( 'const boxes = placed.map' ), script.indexOf( 'loopPens.forEach( function( held ) {\n\n				const box = boxOf( shownOf' ) ); return place.indexOf( 'seamAt(' ) !== -1 && /scrollY|data-on|S\.hot|S\.focus|S\.selected/.test( place ) === false; } )() === true
	&& has( /edge\.pill\.toggleAttribute\( 'data-on', keys\.some/ ) === true );
check( 'C5c. the bar of a section has the pencil of its section and a button for each row - no menu - and the pencil of a row is the one of every component: the section and its rows are opened by the lightbox that a component has, and the one who may only write the content has them where it has something to show',
	has( /const sectionTools = function\( node, block \)/ ) === true && has( /pencil\.dataset\.action = 'properties'/ ) === true && has( /button\.dataset\.action = 'row'/ ) === true && has( /openProperties\( at, 'root', opener \)/ ) === true
	&& has( /const ROW_BUTTONS\s*= 6;/ ) === true && has( /const sectionPen = function\( at \)[\s\S]*?hasProperties\( 'root' \)/ ) === true && has( /button\.addEventListener\( 'pointerenter', function\(\) \{ show\( true \) \} \)/ ) === true
	&& [ 'label/edit-section', 'label/edit-row', 'ident/change-of', 'ident/field-of', 'ident/fixed-of', 'ident/why-frame', 'ident/why-block', 'ident/why-content', 'ident/why-locked', 'ident/form', 'ident/long', 'ident/taken' ].every( function( key ) { return word( de, key ) !== '' && word( en, key ) !== '' && word( de, key ) !== word( en, key ) } ) === true );
check( 'C5d. the frames of the list of the pages are all the frames of the site: a named one by the name it was given with its word beside it, and by its word where it has no name; the standard ones by the words of the panel',
	has( /const name = frameName\( frame\.key, frame \);/ ) === true && has( /entry\.name !== '' \? entry\.name : key\.replace\( \/\^\(\?:header\|footer\)-\/, '' \)/ ) === true && has( /frame\.default === true \? word\( 'frame\/standard', count \) : word\( 'frame\/word', frame\.key\.replace\( \/\^\(\?:header\|footer\)-\/, '' \), count \)/ ) === true
	&& has( /key === 'header' \|\| key === 'footer' \? word\( 'frame\/'\+ key \)/ ) === true && has( /data\.frames\.map\( function\( frame \) \{/ ) === true );
check( 'C6. the pencil belongs to the component under a pointer that stands still when the page is rolled: the roll asks what is under the pointer again, and a pointer that left the page asks nothing',
	has( /let pointerAt = null;/ ) === true && has( /doc\.addEventListener\( 'pointerleave', function\(\) \{ pointerAt = null; outerAt = null \} \)/ ) === true && has( /doc\.elementFromPoint\( pointerAt\.x, pointerAt\.y \)/ ) === true
	&& /place\(\);\s*rollAim\(\);/.test( script ) === true );
check( 'C6b. the window of the workbench rolled under a pointer that stands still asks again what is under it - from where the pointer of the window falls on the page - and its listener goes with the page',
	has( /let outerAt = null;/ ) === true && has( /wn\.addEventListener\( 'scroll', function\(\) \{\s*if\( outerAt === null \)/ ) === true && has( /signal : outer\.signal/ ) === true && has( /win\.addEventListener\( 'pagehide', function\(\) \{ outer\.abort\(\) \} \)/ ) === true );
check( 'C7. every section has "Edit background" for the one who designs - with or without a picture -, and the one who writes the content keeps "Replace picture" where there is a picture',
	has( /background : S\.level >= 2 && \( nodeAt\( target\.path \) \?\? \{\} \)\.n === 'section-wrap'/ ) === true && has( /node\.n !== 'section-wrap'/ ) === true && has( /if\( S\.level >= 2 \)\s*found\.blocks\.forEach/ ) === true
	&& word( de, 'bg/edit' ) === 'Hintergrund bearbeiten' && word( en, 'bg/edit' ) === 'Edit background' );
check( 'C8. the dialog of the background has the settings the register names - the ground, the picture, whether it shows, whether it is dimmed - and the picture itself, nothing free: each by the same control and the same step of the undo as in the lightbox',
	has( /const BACKGROUND = \[ 'ground', 'picture', 'dim' \];/ ) === true && has( /if\( declared\[name\] === undefined \)\s*return;/ ) === true && has( /rows\.push\( attributeField\( rel, name, declared\[name\] \) \)/ ) === true
	&& has( /openProperties\( at, 'root', opener, undefined, BACKGROUND \)/ ) === true );

check( 'C9. the background of a section that has no picture is the ground and "Add picture" - no sentence about a place of another page, and "show" and "dim" only where there is a picture; the place is made with the same step of the undo as the setting, and the page makes it when it is saved',
	has( /\( name === 'picture' \|\| name === 'dim' \) && key === ''\s*\)?\s*return;/ ) === true && has( /name === 'ground' && key === '' && declared\.image !== undefined/ ) === true
	&& has( /relNode\( rel \)\.a = Object\.assign\( \{\}, relNode\( rel \)\.a \?\? \{\}, \{ image : '~background' \} \);\s*S\.pictures\[place\] =/ ) === true && word( de, 'bg/add' ) === 'Bild hinzufügen' && word( en, 'bg/add' ) === 'Add picture' );

check( 'C10. the picture of a section whose place is written and not made yet is the placeholder with "Upload picture" - the place is the page\'s own - and no sentence of a place of another page; a place that is another page\'s still says so',
	has( /const pictureBox = function\( key, label, again, fallback \)/ ) === true && has( /Object\.assign\( \{\}, fallback, S\.images\[key\], S\.pictures\[key\] \)/ ) === true && has( /pictureBox\( key, label, draw, key\.indexOf\( '\/template\/'\+ S\.template\+ '\/' \) === 0 \? \{ label : label, width : 1920, height : 1080 \} : undefined \)/ ) === true
	&& has( /word\( key === '' \? 'picture\/no-slot' : 'picture\/other' \)/ ) === true );

console.log( '\nD. The lightbox' );

check( 'D1. a press in the dimmed area closes the lightbox like "Close" by the dismiss of the workbench - it asks the top dialog only and no dialog with a form is given it - and the panel has no listener of its own for it',
	has( /dismiss\s*: true,/ ) === true && ( script.match( /dismiss\s*: true/g ) ?? [] ).length === 1 && has( /dimmedClose/ ) === false && has( /outside\( event \)/ ) === false );
check( 'D2. the width that is chosen in the lightbox is the width of the preview, and the choice in the bar of the preview is the same choice',
	has( /const showWidth = function\( device \)/ ) === true && has( /S\.view\.preview\.width\( device\.width \)/ ) === true && has( /S\.tools\.setWidth\( device\.width \)/ ) === true && has( /showWidth\( DEVICES\.find\( function\( device \) \{ return device\.width === value \} \) \)/ ) === true );
check( 'D6. the wide column of the settings beside the preview is the workbench\'s: split() is given settingsWidth \'wide\' for a sheet that opens its properties in the column - the Blocks editors - and for no other, and the stylesheet of the feature has no rule of its own for the width of the column of a split',
	has( /settingsWidth\s*: S\.adapter\.props === 'panel' \? 'wide' : undefined/ ) === true && /nino-admin-split--fill > \.nino-admin-split-grid \{\s*grid-template-columns/.test( sheet ) === false && /480px/.test( sheet ) === false );
check( 'D7. a column too narrow for a label beside its field stacks them - the label over the field -, so that a choice is never cut: the label goes to the left of the field only where the column is 22 rem wide - the column is the one of the workbench, and the preview gives way - and the strip of the preview over the column of a narrow window is the one rule for it, in a container query of the split, for #themelab-app',
	/@container builder-props \(min-width: 22rem\) \{\s*\.builder-props \.nino-admin-field \{\s*display: grid;/.test( sheet ) === true && /@container nino-split \(max-width: 44rem\) \{[\s\S]*?#themelab-app \.nino-admin-split--fill\.builder-props-live\[data-view="settings"\] > \.nino-admin-split-grid \{\s*grid-template-rows: 9rem minmax\(0, 1fr\);/.test( sheet ) === true );
check( 'D3. the row of the devices has no caption on the screen and keeps its name for a screen reader, the devices stand at its right; the inputs that the width switches share one quiet surface with it - and so does a setting that has one value for each width',
	has( /el\( 'span', 'nino-admin-sr-only', word\('rows\/width'\) \)/ ) === true && has( /'builder-widths-group builder-widths-box'/ ) === true && has( /'nino-admin-field builder-widths-set builder-widths-box'/ ) === true
	&& /\.builder-lightbox \.builder-widths-box \{[^}]*background: var\(--editor-bg\);/.test( sheet ) === true && /\.builder-lightbox \.builder-width-pick \{[^}]*justify-content: flex-end/.test( sheet ) === true );
check( 'D4. the notation of a component is no part of a page: the button behind the code is for a sheet of the editor of the blocks and for the one who designs',
	has( /S\.level >= 2 && only === undefined && S\.sheet === 'block'/ ) === true );
check( 'D5. the notices of the third party name no strokes of the Builder: it draws no icon of its own',
	/The catalogue's own code/.test( notices ) === false || /GLYPHS/.test( notices ) === false );

console.log( '\nF294. The panel after the owner\'s look at it' );

check( 'F294 T2. a double click on a row of the tree of the extended view, of the outline or of the list marks no text: user-select is off for the two places of the tree - the feature names no class of the workbench tree - and for the rows of the list, and stays on for a field in them',
	/\.builder-adv-tree,\s*#builder-outline,\s*\.builder-list-row,\s*#builder-list \.nino-admin-table tbody tr\[data-row\] \{\s*-webkit-user-select: none;\s*user-select: none;\s*\}/.test( sheet ) === true
	&& /:is\(\.builder-adv-tree, #builder-outline, #builder-list \.nino-admin-table tbody tr\[data-row\]\) :is\(input, textarea, select\) \{\s*-webkit-user-select: text;\s*user-select: text;/.test( sheet ) === true && sheet.indexOf( 'nino-admin-tree' ) === -1 );
check( 'F294 T4. the label of a property stands at the left of its field in every field of the column, not only in a field that is the direct child of the form - the fields stand in the group of a place or of an own text -, from 22 rem of the column on; narrower the field stands under it, which is the rule of the workbench for a narrow form',
	/@container builder-props \(min-width: 22rem\) \{\s*\.builder-props \.nino-admin-field \{\s*display: grid;\s*grid-template-columns: minmax\(6rem, 9rem\) minmax\(0, 1fr\);/.test( sheet ) === true && /30rem/.test( sheet.slice( sheet.indexOf( '@container builder-props' ), sheet.indexOf( '@container builder-props' ) + 400 ) ) === false
	&& sheet.indexOf( '.nino-admin-dialog-fields > .nino-admin-field {' ) === -1 );
check( 'F294 T5. the advanced part has no id row for a component below a section and is not there where nothing else is in it: it is asked by advancedWants() - the id of a section of a page, the html of the one who develops, the way to the editor of the blocks -, and a component with no part at all says so in one line',
	has( /const advancedWants = function\( rel \)/ ) === true && has( /S\.level >= 2 && advancedWants\( rel \) === true \)\s*sections\.push\( \{ key : 'advanced'/ ) === true
	&& has( /const id = rel === 'root' && S\.sheet === 'page' \? Object\.keys/ ) === true && has( /if\( sections\.length === 0 \) \{\s*holder\.appendChild\( el\( 'p', 'builder-props-none', word\('box\/none'\) \) \);/ ) === true
	&& word( de, 'box/none' ) !== '' && word( en, 'box/none' ) !== '' );
check( 'F294 T6. the heading of the tab of the frames says "Standard-Auswahl der Seite" and "Default choice of the site" - the word "Rahmen" in the other words of the panel is not part of it',
	word( de, 'frame/title' ) === 'Standard-Auswahl der Seite' && word( en, 'frame/title' ) === 'Default choice of the site' );

{
	// The word for the head and the foot of a page: in German "Kopfbereich", "Fußbereich" and "Kopf-/Fußbereich", in English "header" and "footer" and "header/footer" - the old words are in no text of the
	// panel and in no line of its manual - a technical name, as frame-header or [frame ...], is no word of it. The exceptions are named by their text, each with its reason
	const readWords = function( file, locale ) {

		const text = fs.readFileSync( path.join( featureDir, file ), 'utf8' );
		const pairs = [ ...text.matchAll( /^\t'\[\[([^\]]+)\]\]'\s*=> '((?:[^'\\]|\\.)*)',\s*$/gm ) ].map( function( found ) { return [ found[1], found[2] ] } );

		return pairs;
	};
	const manual = function( locale ) {

		return [ ...fs.readFileSync( path.join( featureDir, 'feature.php' ), 'utf8' ).matchAll( new RegExp( "'"+ locale+ "' => '((?:[^'\\\\]|\\\\.)*)'", 'g' ) ) ].map( function( found ) { return [ 'feature.php', found[1] ] } );
	};
	const OLD = { de_DE : /Rahmen|\bHeader|\bFooter|Kopfzeile|Fußzeile/, en_US : /\b[Ff]rames?\b/ };
	const EXCEPTIONS = ['Rahmen um den Block','Frame round the block'];
	const offending = function( locale ) {

		return readWords( 'text/'+ locale+ '.php', locale ).concat( manual( locale ) ).filter( function( pair ) { return OLD[locale].test( pair[1].replace( /\b(?:frame|header|footer)-[a-z.-]+|\[frame\b[^\]]*\]/g, '' ) ) === true && EXCEPTIONS.indexOf( pair[1] ) === -1 } ).map( function( pair ) { return pair[0] } );
	};
	const keysOf = function( locale ) { return readWords( 'text/'+ locale+ '.php', locale ).map( function( pair ) { return pair[0] } ).sort().join() };

	check( 'F296. the words of the Builder say "Kopfbereich", "Fußbereich" and "Kopf-/Fußbereich" in German and "header", "footer" and "header/footer" in English: no text of the panel and no line of its manual has "Rahmen", "Header" or "Footer" in German or "frame" in English for the head and the foot of a page, but the exceptions that are named here - and both languages have the same keys',
		offending('de_DE').length === 0 && offending('en_US').length === 0 && keysOf('de_DE') === keysOf('en_US') && /Kopf-\/Fußbereich/.test( fs.readFileSync( path.join( featureDir, 'text/de_DE.php' ), 'utf8' ) ) === true );
}

done();
