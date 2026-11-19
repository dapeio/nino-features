/**
 *	Nino features
 *	blocks-frames-js-smoke.js	What the script of the panel Blocks does for the frames of the site - read from its source, as the page that draws them is looked at in a browser:
 *
 *	A. the third tab "Frames" lists the header and the footer, and an address names a frame; nothing else is one
 *	B. a frame opens in the editor of the Builder with an adapter of its own: every request goes to the panel as the action of a frame and names the frame
 *	C. a block of the library takes the place of a frame - after the editor's own steps, with the undo - and the list gives up its previews when the editor is open
 *
 *	Usage: node features/Blocks/tests/blocks-frames-js-smoke.js
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

check( 'A1. the frames are the third tab - after the project and the theme - and the list of them is two rows, each a link to the frame, with no button of a new block and no table',
	has( /items\.push\( \{ key : 'frames', label : word\('tab\/frames'\) \} \)/ ) === true && script.indexOf( "key : 'project'" ) < script.indexOf( "key : 'theme'" ) && script.indexOf( "key : 'theme'" ) < script.indexOf( "key : 'frames'" ) && has( /items\.(reverse|sort|unshift)\(/ ) === false && has( /if\( data\.tab === 'frames' \) \{[\s\S]*?return;\s*\}/ ) === true && has( /Nino\.admin\.blocks\.go\( partsOf\( 'frames', frame\.key \) \)/ ) === true );
check( 'A2. an address names a frame by the word of its side and no other: #blocks/frames/header and #blocks/frames/footer; a key of a block is no frame and a frame is no key of a block',
	/\( tab === 'frames' \? \/\^\(\?:header\|footer\)\$\//.test( script ) === true && has( /if\( target\.tab === 'frames' \)\s*openFrame\( target\.key \)/ ) === true );

console.log( '\nB. The sheet of a frame' );

check( 'B1. the sheet of a frame is opened by the editor of the Builder with the adapter of the frame: frame true, the template of the frame as every request\'s base, the stamp restore, the way back to the tab of the frames',
	has( /frame\s*: true,/ ) === true && has( /base\s*: function\(\) \{ return \{ template : template,/ ) === true && has( /restore\s*: true,/ ) === true && has( /back\s*: function\(\) \{ return \{ parts : tabParts\( 'frames' \)/ ) === true );
check( 'B3. the properties of a component open in the column beside the preview - in the editor of a block of the library and in the editor of a frame alike: both adapters say props : \'panel\', and no other adapter of this panel does',
	script.match( /props\s*:\s*'panel'/g ) !== null && script.match( /props\s*:\s*'panel'/g ).length === 2 && has( /frame\s*: true,[\s\S]*?props\s*: 'panel',/ ) === true );
check( 'B2. every request of the sheet of a frame is the action of a frame - blocks/frame-<action> - of this panel, and the open of one asks for the frame by its template',
	has( /Nino\.admin\.blocks\.call\( 'frame-'\+ action, payload, done, extra \)/ ) === true && has( /Nino\.admin\.blocks\.call\( 'frame-open', \{ template : 'frame-'\+ frame,/ ) === true );

console.log( '\nC. The block in the frame and the list' );

check( 'C1. "Insert block" in a frame offers the blocks that this panel has for it and puts the one that is chosen in the place of the frame, asked about nothing - Undo brings the frame back - by the editor of the Builder',
	has( /editor\(\)\.call\( 'blocks', \{\}/ ) === true && has( /editor\(\)\.call\( 'block', \{ key : key \}/ ) === true && has( /editor\(\)\.replace\( block,/ ) === true && /replace : replaceFrame,/.test( builder ) === true );
check( 'C2. the list gives up its previews when an editor is open - a frame of the site each - and is drawn again where the person comes back to it',
	has( /const unloadList = function\(\) \{[\s\S]*?list\.innerHTML = '';/ ) === true && ( script.match( /unloadList\(\);/g ) ?? [] ).length === 2 );

done();
