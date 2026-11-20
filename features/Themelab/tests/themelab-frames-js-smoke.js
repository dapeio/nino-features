/**
 *	Nino features
 *	themelab-frames-js-smoke.js	What the script of Themelab does for the frames of the site - read from its source, as the page that draws them is looked at in a browser:
 *
 *	A. the third tab "Frames" lists the header and the footer, and an address names a frame; nothing else is one
 *	B. a frame opens in the editor of the Builder with an adapter of its own: every request goes to the panel as the action of a frame and names the frame
 *	C. a block of the library takes the place of a frame - after the editor's own steps, with the undo - and the list gives up its previews when the editor is open
 *
 *	Usage: node features/Themelab/tests/themelab-frames-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const script = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
const builder = fs.readFileSync( path.join( featureDir, '../Builder/assets/admin.js' ), 'utf8' );
const has = function( pattern ) { return pattern.test( script ) };

console.log( '\nA. The tab and the address' );

check( 'A1. the frames are the second tab - after the blocks and before the theme - and the list of them is two rows, each a link to the frame, with no button of a new block and no table',
	has( /const items = \[ \{ key : 'blocks', label : word\('tab\/blocks'\) \}, \{ key : 'frames', label : word\('tab\/frames'\) \}, \{ key : 'info', label : word\('tab\/info'\) \} \];/ ) === true && has( /items\.(reverse|sort|unshift)\(/ ) === false && has( /if\( data\.tab === 'frames' \) \{[\s\S]*?return;\s*\}/ ) === true && has( /Nino\.admin\.themelab\.go\( partsOf\( 'frames', frame\.key \) \)/ ) === true );
check( 'A2. an address names a frame by the word of its side and the word of the frame, if it has one: #themelab/frames/header, #themelab/frames/footer, #themelab/frames/header-landing - and no other; a key of a block is no frame and a frame is no key of a block',
	script.includes( "tab === 'frames' ? /^(?:header|footer)(?:-[a-z0-9]+(?:-[a-z0-9]+)*)?$/ :" ) === true && has( /if\( target\.tab === 'frames' \)\s*openFrame\( target\.key \)/ ) === true );

console.log( '\nB. The sheet of a frame' );

check( 'B1. the sheet of a frame is opened by the editor of the Builder with the adapter of the frame: frame true, the template of the frame as every request\'s base, the stamp restore, the way back to the tab of the frames',
	has( /frame\s*: true,/ ) === true && has( /base\s*: function\(\) \{ return \{ template : template,/ ) === true && has( /restore\s*: true,/ ) === true && has( /back\s*: function\(\) \{ return \{ parts : tabParts\( 'frames' \)/ ) === true );
check( 'B3. the properties of a component open in the column beside the preview - in the editor of a block of the library and in the editor of a frame alike: both adapters say props : \'panel\', and no other adapter of this panel does',
	script.match( /props\s*:\s*'panel'/g ) !== null && script.match( /props\s*:\s*'panel'/g ).length === 2 && has( /frame\s*: true,[\s\S]*?props\s*: 'panel',/ ) === true );
check( 'B2. every request of the sheet of a frame is the action of a frame - themelab/frame-<action> - of this panel, and the open of one asks for the frame by its template',
	has( /Nino\.admin\.themelab\.call\( 'frame-'\+ action, payload, done, extra \)/ ) === true && has( /Nino\.admin\.themelab\.call\( 'frame-open', \{ template : 'frame-'\+ frame,/ ) === true );

console.log( '\nC. The block in the frame and the list' );

check( 'C0. the dialog that gives a frame its block is the Builder\'s - one code for the list of the pages and for this editor: the editor of the frames calls Nino.admin.builder.chooseFrame with what it does with the choice, and has no question and no word of that choice of its own',
	has( /Nino\.admin\.builder\.chooseFrame\( \{/ ) === true && has( /frames\/confirm|frames\/insert-note/ ) === false && /chooseFrame\s*:\s*chooseFrameBlock/.test( builder ) === true );
check( 'C1. "Insert block" in a frame offers the blocks that this panel has for it and puts the one that is chosen in the place of the frame, asked about nothing - Undo brings the frame back - by the editor of the Builder',
	has( /editor\(\)\.call\( 'blocks', \{\}/ ) === true && has( /editor\(\)\.call\( 'block', \{ key : key \}/ ) === true && has( /editor\(\)\.replace\( block,/ ) === true && /replace : replaceFrame,/.test( builder ) === true );
check( 'C2. the list gives up its previews when an editor is open - a frame of the site each - and is drawn again where the person comes back to it',
	has( /const unloadList = function\(\) \{[\s\S]*?list\.innerHTML = '';/ ) === true && ( script.match( /unloadList\(\);/g ) ?? [] ).length === 2 );
check( 'C3. "Save as frame block" is a button of the editor of a frame, and unsaved work is asked about first - the question of the workbench, Save or Discard - before the dialog, which keeps the frame as it is SAVED',
	has( /key : 'frameadopt',[\s\S]*?Nino\.admin\.dirty\.guard\( \[ 'themelab' \], function\(\) \{ askFrameAdopt\( frame,/ ) === true && has( /Nino\.admin\.themelab\.call\( 'frame-adopt', asked\( values,/ ) === true && has( /template : 'frame-'\+ frame, key :/ ) === true );


check( 'C4. a frame of the list is named by its word - a named one - or by the word of the panel - the standard ones - in the tab, in the editor and in the dialog that keeps it as a block; the request names the template as frame-<key>',
	has( /const title = frameName\( frame\.key \);[\s\S]*?title\s*: title/ ) === true && has( /const title = frameName\( frame \);\s*const template = 'frame-'\+ frame;/ ) === true && has( /String\( key \)\.replace\( \/\^\(\?:header\|footer\)-\/, '' \)/ ) === true );

done();
