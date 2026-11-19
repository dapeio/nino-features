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
	has( /Nino\.admin\.dirty\.guard\( \[ 'builder' \], function\(\) \{ Nino\.admin\.router\.go\( 'blocks', \[ key \] \) \} \)/ ) === true && has( /editInBlocks\( S\.own\[box\.block\] \)/ ) === true );
check( 'B3. the rows of the header and the footer in the outline are a display - "applies to all pages" - and have a button to the editor of the blocks only for the one who holds its right',
	has( /item\.kind === 'frame' && S\.bridge === true && \( row\.key === '@header' \|\| row\.key === '@footer' \)/ ) === true && has( /'blocks', \[ 'frames', id === '@footer' \? 'footer' : 'header' \]/ ) === true );
check( 'B4. the words of the two ways are in both languages and say what they do: "take over into own blocks", "open in the Blocks editor"',
	word( de, 'own/open' ).indexOf( 'Eigene Blocks' ) !== -1 && word( en, 'own/open' ).indexOf( 'Own blocks' ) !== -1 && word( de, 'own/edit' ).indexOf( 'Blocks-Editor' ) !== -1 && word( en, 'own/edit' ).indexOf( 'Blocks editor' ) !== -1 );

check( 'B5. the rows of the header and the footer have "Edit texts" for every step - the words the frame holds as its own, in every language, saved at once for every page, never a tree - and the actions that answer are the Builder\'s own and shut to a step below the content',
	has( /actions\.push\( \{ key : 'texts', icon : 'pencil', name : word\( 'frame\/texts-of', row\.label \) \} \)/ ) === true && has( /api\( 'frametexts', \{ template : template \}/ ) === true && has( /api\( 'frametextssave', \{ template : template, texts : changed, base : response\.stamp \}/ ) === true
	&& has( /api\( 'frametextssave', \{[^}]*tree/ ) === false && /builder\/frametexts'\s*=> \[ self::class, 'apiFrameTexts' \]/.test( panelPhp ) === true && /_gateTexts[\s\S]*LEVEL_CONTENT/.test( panelPhp ) === true );

console.log( '\nC. The work surface' );

check( 'C1. the words of a width say what is inherited: "Inherited from tablet", "Inherited from smartphone", and the first step is the smartphone - in both languages, and from this panel',
	word( de, 'rows/below-desktop' ) === 'Geerbt von Tablet' && word( de, 'rows/below-tablet' ) === 'Geerbt von Smartphone' && word( de, 'rows/all' ) === 'Smartphone'
	&& word( en, 'rows/below-desktop' ) === 'Inherited from tablet' && word( en, 'rows/below-tablet' ) === 'Inherited from smartphone' && word( en, 'rows/all' ) === 'Smartphone' );
check( 'C2. the pencil has its field at the right of every component, in its mark - and inside only where the window has no room - and a row that fills its column has it at the top left',
	has( /\[ sides\[0\], inside, sides\[1\], sides\[2\] \]/ ) === true && has( /row === true \? box\.left \+ 2/ ) === true && has( /\( nodeAt\( aimed\.entry\.path \) \?\? \{\} \)\.n === 'section-row'/ ) === true );
check( 'C3. the pencil and the plus change their ground and their edge on the pointer and nothing else - no growth, no shadow ring - with a soft change that the setting for reduced motion takes away',
	/button\.pen:hover\{border-color:var\(--b-blue\);background:var\(--b-soft\)\}/.test( script ) === true && /button\.insert:hover,button\.insert:focus-visible\{border-color:var\(--b-blue\);background:var\(--b-soft\)\}/.test( script ) === true
	&& /box-shadow:0 0 0 3px var\(--b-soft\)/.test( script ) === false && /transition:opacity \.12s,visibility 0s \.12s,background-color \.12s,border-color \.12s/.test( script ) === true && /@media \(prefers-reduced-motion:reduce\)\{button\.pen,\.mark\{transition:none\}\}/.test( script ) === true );
check( 'C4. the pictures of the devices stand in the middle of their buttons, both ways, and the phone, the tablet and the desktop differ in shape: narrow, square, wide with a foot',
	/\.builder-step-glyph,\s*\.builder-step-glyph > span \{\s*display: inline-flex;\s*align-items: center;\s*justify-content: center;/.test( sheet ) === true
	&& has( /smartphone\t\t: \[ \[ 'rect', 7, 2, 10, 20, 2 \]/ ) === true && has( /tablet\t\t\t\t: \[ \[ 'rect', 3, 3, 18, 18, 2 \]/ ) === true && has( /monitor\t\t\t\t: \[ \[ 'rect', 2, 3, 20, 14, 2 \], \[ 'line', 8, 21, 16, 21 \]/ ) === true );
check( 'C5. a section has the place above it as well as the one below: where the page has none above it of its own, the plus is the one below the block before',
	has( /const earlier = index > 0 \? blocks\[index - 1\] : undefined;/ ) === true && has( /spot\.block === earlier\.at && spot\.carry === 'after'/ ) === true );
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

check( 'D1. a press in the dimmed area closes the lightbox like "Close" - only one that began there and ended there - and no dialog with a form is given this',
	has( /dimmedClose\( holder\.closest\('dialog'\), box \)/ ) === true && has( /began = event\.target === dialog && outside\( event \)/ ) === true && has( /closes = began === true && event\.target === dialog && outside\( event \)/ ) === true
	&& ( script.match( /dimmedClose\(/g ) ?? [] ).length === 1
	&& has( /open\[open\.length - 1\] === dialog/ ) === true );
check( 'D2. the width that is chosen in the lightbox is the width of the preview, and the choice in the bar of the preview is the same choice',
	has( /const showWidth = function\( device \)/ ) === true && has( /S\.view\.preview\.width\( device\.width \)/ ) === true && has( /S\.tools\.setWidth\( device\.width \)/ ) === true && has( /showWidth\( DEVICES\.find\( function\( device \) \{ return device\.width === value \} \) \)/ ) === true );
check( 'D3. the row of the devices has no caption on the screen and keeps its name for a screen reader, the devices stand at its right; the inputs that the width switches share one quiet surface with it - and so does a setting that has one value for each width',
	has( /el\( 'span', 'nino-admin-sr-only', word\('rows\/width'\) \)/ ) === true && has( /'builder-widths-group builder-widths-box'/ ) === true && has( /'nino-admin-field builder-widths-set builder-widths-box'/ ) === true
	&& /\.builder-lightbox \.builder-widths-box \{[^}]*background: var\(--editor-bg\);/.test( sheet ) === true && /\.builder-lightbox \.builder-width-pick \{[^}]*justify-content: flex-end/.test( sheet ) === true );
check( 'D4. the notation of a component is no part of a page: the button behind the code is for a sheet of the editor of the blocks and for the one who designs',
	has( /S\.level >= 2 && only === undefined && S\.sheet === 'block'/ ) === true );
// the notices are the catalogue's own file: where the features stand in a kernel of their own the file there is the kernel's, and says nothing of them
check( 'D5. the icons of the Builder are named in the notices of the third party: the devices, the alignments and the directions are Lucide geometry',
	/The catalogue's own code/.test( notices ) === false || ( /GLYPHS/.test( notices ) === true && /features\/Builder\/assets\/admin\.js/.test( notices ) === true ) );

done();
