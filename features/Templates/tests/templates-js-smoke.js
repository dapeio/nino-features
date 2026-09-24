/**
 *	Dependency-free tests for Template Builder's pure client-side model helpers.
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

// This test travels with the feature: its own directory is the module root,
// and the Nino checkout it runs against is the one NINO_ROOT names or the one
// three levels up - the same rule the feature's php test follows
const FEATURE = path.join( __dirname, '..' );
const NINO = process.env.NINO_ROOT || path.join( __dirname, '../../..' );

let failures = 0;
let checks = 0;

function check( label, condition ) {
	checks++;
	if( condition ) {
		console.log( '  ok  - '+ label );
		return;
	}
	failures++;
	console.log( 'FAIL  - '+ label );
}

const callbacks = [];
const documentStub = {
	getElementById : function() { return null },
	querySelectorAll : function() { return [] },
	createElement : function() { return {} },
};
// Assigned, not declared: Nino is the one global the panel scripts expect, and
// the lint config knows it as one - a second declaration of the same name here
// would be a redeclaration of it
globalThis.Nino = {
	events : { bindCallback : function( event, callback ) { if( event === 'ready' ) callbacks.push( callback ) } },
	http : { sendRequest : function() {} },
	// The panel says nothing in its own words any more - every string it
	// renders is a fill (see the feature's text/). The key stands
	// in for the sentence here, so the checks below read the same either way
	content : { getText : function( key ) { return key } },
	// And a label the server sends is either a fill key or literal text - the
	// same rule the workbench applies everywhere (Nino.adminUi.text()), so a
	// section library manifest may keep naming its areas in plain words
	adminUi : { text : function( value ) {
		value = String( value ?? '' );
		return value.charAt(0) === '/' ? ( Nino.content.getText( value ) || value ) : value;
	} },
};
const context = vm.createContext( {
	window : { Nino : Nino, clearTimeout : clearTimeout, setTimeout : setTimeout, confirm : function() { return true } },
	document : documentStub,
	Nino : Nino,
	console : console,
	Promise : Promise,
	Set : Set,
	URLSearchParams : URLSearchParams,
} );

[ 'script.js', 'sections.js', 'composer.js', 'area-composer.js' ].forEach( function( file ) {
	vm.runInContext( fs.readFileSync( path.join( FEATURE, 'assets/', file ), 'utf8' ), context, { filename : file } );
} );

const model = Nino.admin.templates.model;

console.log('Template Builder model');

const raw = { type : 'raw', source : '<!-- locked -->\n' };
const header = { type : 'slot', slot : 'header', path : '/templates/html-header', source : model.slotSource( 'header', '/templates/html-header' ) };
const footer = { type : 'slot', slot : 'footer', path : '/templates/html-footer', source : model.slotSource( 'footer', '/templates/html-footer' ) };
const include = { type : 'template', template : 'section-nav', source : '[template /templates/section-nav]\n', _clientId : 'include' };
const first = { type : 'section', source : '<section id="one"></section>', htmlId : 'one', _clientId : 'one' };
const second = { type : 'section', source : '<section id="two"></section>', htmlId : 'two', _clientId : 'two' };
const segments = [ header, raw, first, second, include, footer ];

check( 'finds HTML and ordinary template sections but excludes page slots', JSON.stringify( model.sectionIndices( segments ) ) === JSON.stringify( [ 2, 3, 4 ] ) );
check( 'moves canvas objects while locked raw and shell slots stay fixed', model.moveSection( segments, 'one', 1 ) && segments[0] === header && segments[1] === raw && segments[2] === second && segments[3] === first && segments[5] === footer );
check( 'moves ordinary template sections through the component model', model.moveSection( segments, 'include', -1 ) && segments[3] === include && segments[4] === first );
check( 'refuses to move beyond the canvas list', model.moveSection( segments, 'second', -1 ) === false );

const inserted = { type : 'section', source : '<section id="three"></section>', htmlId : 'three', _clientId : 'three' };
model.insertSection( segments, inserted, 'two' );
check( 'inserts after the intended section', segments.indexOf( inserted ) === segments.indexOf( second ) + 1 && segments.indexOf( raw ) === 1 );
check( 'removes a section without touching locked raw segments', model.removeSection( segments, 'three' ) && segments.includes( raw ) && segments.includes( footer ) );

const emptyPage = [ Object.assign( {}, header ), Object.assign( {}, footer ) ];
model.insertSection( emptyPage, inserted, null );
check( 'puts a first HTML section between fixed header and footer slots', emptyPage[0].slot === 'header' && emptyPage[1] === inserted && emptyPage[2].slot === 'footer' );
const rawOnly = [ raw ];
model.insertSection( rawOnly, inserted, null );
check( 'puts a first section after a lone locked raw frame', rawOnly[0] === raw && rawOnly[1] === inserted );
check( 'serializes an optional shell template as an ordinary marked shortcode', model.slotSource( 'footer', '' ) === '<!-- nino:template-slot footer -->\n' && model.slotSource( 'header', '/templates/site-header' ).includes( '[template /templates/site-header]' ) );
check( 'creates a stable unique section id', model.nextId( segments, 'Main Hero') === 'main-hero' && model.nextId( segments.concat( [ { type : 'section', htmlId : 'main-hero' } ] ), 'Main Hero') === 'main-hero-2' );
check( 'document search covers display name, filename and page id', model.matchesDocument( { name : 'page-about-us', filename : 'page-about-us.tpl', displayName : 'About the studio', pageId : 'about-us' }, 'studio' ) && model.matchesDocument( { name : 'page-about-us', filename : 'page-about-us.tpl', displayName : 'About the studio', pageId : 'about-us' }, '.tpl' ) && !model.matchesDocument( { name : 'page-home', displayName : 'Homepage', pageId : 'home' }, 'contact' ) );
check( 'validates real page template filenames without hiding prefix or suffix', model.validFilename('page-error-404.tpl') && !model.validFilename('error-404') && !model.validFilename('page-../config.tpl') );
check( 'derives a readable initial name from the filename', model.displayNameFromFilename('page-error-404.tpl') === 'Error 404' );
check( 'rejects names that cannot be safely stored in an HTML comment', model.validDisplayName('Error 404') && !model.validDisplayName('Broken --> comment') );
check( 'HTML+ editing detaches generated composer metadata', Nino.admin.templates.sectionsUI.detachMetadata( '<section>\n\t<!-- nino:section {"preset":"blank"} -->\n\t<p>Kept</p>\n</section>' ) === '<section>\n\t<p>Kept</p>\n</section>' );

console.log('\nSection Library filtering');

const matches = Nino.admin.templates.composer.matchesPreset;
const preset = { name : 'FAQ — Accordion', description : 'Questions and answers', category : 'Content', tags : [ 'faq', 'support' ] };
// '*' rather than 'All': the one chip this panel adds itself is a slug, so the
// comparison holds in every interface language (see composer.js's
// categoryLabel(), which is what names it on screen)
check( 'matches preset names and tags case-insensitively', matches( preset, 'accordion', '*' ) && matches( preset, 'SUPPORT', '*' ) );
check( 'applies category and text filters together', matches( preset, 'questions', 'Content' ) && !matches( preset, 'questions', 'Hero' ) );
check( 'empty search keeps the selected category visible', matches( preset, '', 'Content' ) );
// A manifest names an area twice: labelKey for the panel, label for the
// strings the server composes and stores (see AreaComposer::normalizeArea())
check( 'an area is named in the interface language, and falls back to its English label', Nino.admin.templates.areaComposer.areaLabel( { label : 'Title area', labelKey : '/_admin/templates/area/title-area' } ) === '/_admin/templates/area/title-area'
	&& Nino.admin.templates.areaComposer.areaLabel( { label : 'Title area', labelKey : '' } ) === 'Title area'
	&& Nino.admin.templates.areaComposer.areaLabel( undefined ) === '' );
Nino.admin.templates._library.previewCss = '/* project-preview-css */ .nino-section{display:block}';
const previewDocument = Nino.admin.templates.composer.previewDocument( '<section id="sample"></section>' );
check( 'preview documents inline the project bundle without another stylesheet request', previewDocument.includes( 'project-preview-css' )
	&& previewDocument.includes( '<section id="sample"></section>' )
	&& !previewDocument.includes( '<link rel="stylesheet"' )
	&& !previewDocument.includes( '/.cache/style.css' ) );
check( 'preview documents block scripts, forms and third-party network access', previewDocument.includes( 'Content-Security-Policy' ) && previewDocument.includes( "script-src 'none'" ) && previewDocument.includes( "form-action 'none'" ) );
check( 'script-free previews reproduce configured cover heights and a stable parallax image', previewDocument.includes( '[data-cover-height="100"]{min-height:100vh!important}' )
	&& previewDocument.includes( '.nino-parallex>img{top:0!important;height:100%!important;transform:none!important}' ) );
const focusedPreview = Nino.admin.templates.composer.previewDocument( '<section id="sample"></section>', 'heading' );
check( 'a preview dims every area but the one whose editor is open', focusedPreview.includes( '[data-pd-area]:not([data-pd-area="heading"]){opacity:.5}' )
	&& previewDocument.includes( 'data-pd-area' ) === false );
check( '...and an area name that is not a slug dims nothing rather than escaping into the stylesheet', Nino.admin.templates.composer.previewDocument( '<section></section>', 'heading"]){}*{display:none' ).includes( 'display:none' ) === false );
const hostilePreview = Nino.admin.templates.composer.previewDocument( '<script>alert(1)</script><a href="javascript:alert(2)" onclick="alert(3)">Safe</a><a href=javascript:alert(4)>Still safe</a><img src=x onerror=alert(5)>' );
check( 'preview documents remove executable markup before assigning srcdoc', !hostilePreview.includes( '<script' )
	&& !hostilePreview.includes( 'javascript:' )
	&& !/\son[a-z]+=/i.test( hostilePreview ) );

const composerSource = fs.readFileSync( path.join( FEATURE, 'assets/composer.js' ), 'utf8' );

/*	Which presets are named-area ones is the server's answer, not this
	script's: Library::presets() drops every manifest whose version is not 3
	before the panel is handed one (templates-smoke.php asserts that). The
	script used to ask again, per preset, in four places - a question with one
	answer over that list	*/
check( 'the script does not filter by preset version - the library it is handed holds only v3', /version\s*\)\s*===\s*3/.test( composerSource ) === false );
const areaComposerSource = fs.readFileSync( path.join( FEATURE, 'assets/area-composer.js' ), 'utf8' );
const sectionsSource = fs.readFileSync( path.join( FEATURE, 'assets/sections.js' ), 'utf8' );
const scriptSource = fs.readFileSync( path.join( FEATURE, 'assets/script.js' ), 'utf8' );
const styleSource = fs.readFileSync( path.join( FEATURE, 'assets/style.css' ), 'utf8' );

/*	A card does not depend on the search text, only on whether it matches it -
	so the gallery is built once and afterwards only shows and hides. It used
	to be emptied and rebuilt per keystroke, and each rebuilt card carried a
	fresh <iframe> whose srcdoc embeds the whole project stylesheet: five
	letters over the shipped seventeen wrote 2.8 MB and 230 ms. The dom this
	file stands up has no layout, so what it can hold is the shape; the effect
	was measured in a browser	*/
check( 'the gallery is filled once and filtered by class from then on', composerSource.includes( 'pd.composer.buildLibrary( wrap )' )
	&& /renderLibrary : function\(\)[\s\S]*?card\.classList\.toggle\( 'pd-hidden'/.test( composerSource )
	&& /renderLibrary : function\(\)[\s\S]*?\},/.exec( composerSource )[0].includes( "wrap.innerHTML = ''" ) === false );
check( '...and rebuilds only when the library itself changed, or the shell was built again around it', composerSource.includes( 'pd.composer._librarySignature === signature && first && first.parentNode === wrap' ) );
const ninoCssSource = fs.readFileSync( path.join( NINO, '_nino/Nino.css' ), 'utf8' );
const ninoAdminCssSource = fs.readFileSync( path.join( NINO, '_admin/assets/style.css' ), 'utf8' );
const ninoUiJsSource = fs.readFileSync( path.join( NINO, '_nino/Nino.ui.js' ), 'utf8' );
const ninoShellJsSource = fs.readFileSync( path.join( NINO, '_admin/assets/script.js' ), 'utf8' );
const articlesManifestSource = fs.readFileSync( path.join( FEATURE, 'library/articles-grid/manifest.php' ), 'utf8' );
const templateMarkup = fs.readFileSync( path.join( FEATURE, 'templates/panel.tpl' ), 'utf8' );
const templatesPhpSource = fs.readFileSync( path.join( FEATURE, 'Library/Library.php' ), 'utf8' );
const panelPhpSource = fs.readFileSync( path.join( FEATURE, 'Admin/Admin.php' ), 'utf8' );
const contentPhpSource = fs.readFileSync( path.join( FEATURE, 'Content/Content.php' ), 'utf8' );
const sandboxAssignments = composerSource.match( /iframe\.setAttribute\(\s*'sandbox',\s*PREVIEW_SANDBOX\s*\)/g ) || [];
check( 'the backend refreshes the configured CSS bundle before embedding it', /Assets::doShortcode\(\s*\$appData,\s*\[\s*'\/\.cache\/style\.css'\s*\]/.test( templatesPhpSource ) );
check( 'gallery and detail previews use an opaque sandbox while CSP still denies scripts', composerSource.includes( "const PREVIEW_SANDBOX = 'allow-scripts'" )
	&& !composerSource.includes( "const PREVIEW_SANDBOX = 'allow-same-origin'" )
	&& sandboxAssignments.length === 2 );
check( 'the initial detail preview uses the same opaque sandbox', templateMarkup.includes( 'sandbox="allow-scripts"' )
	&& !templateMarkup.includes( 'sandbox="allow-same-origin"' )
	&& templateMarkup.includes( 'sandbox=""' ) === false );
/*	The head the workbench renders over the pane names the panel, so the panel
	draws no bar of its own over its three columns: the one row it keeps is the
	save state, Delete and Save, which the script hands to that head at init
	(see the dom checks further down) - brand, rail, account and name are the
	workbench's	*/
check( 'nothing of the panel\'s own stands over the three columns but the row of controls it hands to the head', /<div id="pd-app"[^>]*>\s*<div id="pd-top-actions">(\s*<(span|button)[^>]* id="pd-[a-z-]+"[^>]*>[^<]*<\/(span|button)>){3}\s*<\/div>\s*<div id="pd-shell">/.test( templateMarkup )
	&& templateMarkup.includes('pd-head-rail') === false
	&& templateMarkup.includes('admin-tools') === false
	&& templateMarkup.includes('<html') === false
	&& templateMarkup.includes('<script') === false );
check( 'the panel declares itself a workspace, which folds the workbench rail and drops the reading width - and sizes nothing by a bar of its own', panelPhpSource.includes("return 'workspace'")
	&& styleSource.includes('--pd-topbar-height') === false
	&& styleSource.includes('@media (min-width: 58.001rem) and (max-width: 63.999rem)') );
/*	The pane holds the head and then #pd-app. #pd-app used to be the pane's
	whole height under a head that already took 52px of it, and the shell the
	window's height minus the bar the panel drew, so the workspace scrolled by
	the head's height: measured on a 1440x950 workbench, a pane of 1002px in a
	window of 950. The pane is a column the two share now, and the shell takes
	what #pd-app is given	*/
check( 'the pane is one column for the head and the panel, and the shell is not the window\'s height minus a bar of the panel\'s own', /#admin-content-templates\s*\{[^}]*display:\s*flex;[^}]*flex-direction:\s*column;/.test( styleSource )
	&& /(^|\n)#pd-app\s*\{[^}]*flex:\s*1 1 auto;/.test( styleSource )
	&& /(^|\n)#pd-shell\s*\{[^}]*flex:\s*1 1 0;/.test( styleSource )
	&& /calc\(\s*100d?vh\s*-\s*var\(--pd-/.test( styleSource ) === false );
check( 'no element-wide rule leaks out of the panel\'s stylesheet into the workbench', /^body\s*\{/m.test( styleSource ) === false
	&& /^code\s*\{/m.test( styleSource ) === false
	&& styleSource.includes('#pd-app code {') );
check( 'the panel loads nothing until its tab is selected, then keeps its state across switches', scriptSource.includes('showCurrent : function()')
	&& scriptSource.includes('Nino.admin.templates._loaded === true')
	&& scriptSource.includes("Nino.http.sendRequest( '/_admin/', 'POST'") );
check( 'a link into the Elements panel is a hash deep-link, the way the workbench routes', sectionsSource.includes("'/_admin/#elements/'")
	&& [ composerSource, areaComposerSource, sectionsSource ].every( function( source ) { return source.includes('?tab=elements') === false } ) );
/*	And so is every other link out of the inspector. The shell reads
	location.hash and no query at all (see its own script.js), so the two
	'?tab=' links resolved to nothing: loaded against a stand-in of the
	rendered shell, the real router answered '?tab=images' and '?tab=types'
	with the first panel in the rail, while '#images', '#slots' and '#types'
	each select their own screen. An image slot that exists is an upload on
	the Images panel, which restores the slot group from the hash; one that
	does not exist yet is defined on the Slots tab	*/
check( 'the inspector\'s image and type links are hashes as well, none of them a query', sectionsSource.includes( "'/_admin/#images/'" )
	&& sectionsSource.includes( "'/_admin/#slots'" )
	&& sectionsSource.includes( "'/_admin/#types'" )
	&& /assetUrl\( '\/_admin\/\?/.test( sectionsSource ) === false
	&& ninoShellJsSource.includes( 'wn.location.hash.replace' )
	&& /location\.search/.test( ninoShellJsSource ) === false );
check( 'new-template UI asks for filename, name, shell slots and VPA', [ 'pd-create-filename', 'pd-create-name', 'pd-create-header', 'pd-create-footer', 'pd-create-vpa' ].every( function( id ) { return templateMarkup.includes( 'id="'+ id+ '"' ) } ) );
check( 'the primary toolbar exposes one Add Section entry point', templateMarkup.includes( 'id="pd-add-section"' ) && templateMarkup.includes( 'id="pd-add-template"' ) === false );
check( 'Add Section is the final workspace control instead of a template setting',
	/<div id="pd-canvas"[^>]*><\/div>\s*<button[^>]*id="pd-add-section"[^>]*>[\s\S]*?<\/button>\s*<\/main>/.test( templateMarkup ) );
check( 'template VPA shares the labeled settings row and uses joined controls', templateMarkup.includes( 'class="pd-slot-setting pd-vpa-setting"' )
	&& templateMarkup.includes( 'id="pd-page-motion"' ) );
check( 'dialog close controls use the shared stroke SVG instead of text glyphs', ( templateMarkup.match( /class="pd-icon-button pd-[^"]+-close"[^>]*><svg/g ) || [] ).length === 4
	&& templateMarkup.includes( '<path d="M18 6 6 18"/>' ) );
/*	The library lists presets and nothing else: a reusable .tpl is not a
	pseudo-section but a data source an Area binds to, offered as a Template
	component inside one. It used to say so through an include gallery that
	could never run - 'includes' was a literal empty array - and says it now by
	having no card-building code for one at all	*/
check( 'Add Section lists presets only while reusable templates remain Area data inputs', composerSource.includes( 'includes.forEach' ) === false
	&& composerSource.includes( 'const includes = []' ) === false
	&& composerSource.includes( 'pd-template-preset' ) === false
	&& areaComposerSource.includes( "propertyDefinition.kind === 'template'" )
	&& areaComposerSource.includes( "include.kind !== 'frame'" ) );
check( 'the removed Classic switch cannot reappear in the library UI', !templateMarkup.includes( 'pd-library-scope' )
	&& !composerSource.includes( 'matchesScope' )
	&& !composerSource.includes( 'selectScope' ) );
check( 'dialogs share the #pd-app design scope instead of sitting beside it', /<div id="pd-app"[\s\S]*<dialog id="pd-composer"[\s\S]*<div id="pd-toast"[\s\S]*<\/div>\s*<\/div>\s*$/.test( templateMarkup ) );

// These three held for every workbench panel while this one was a kernel
// module, checked across all of them by tests/admin-lists-js-smoke.js. A panel
// that ships from the catalogue is out of that sweep's reach, so it carries
// its own copy of the conventions rather than quietly leaving them behind
[ 'script.js', 'sections.js', 'composer.js', 'area-composer.js' ].forEach( function( file ) {
	check( 'the builder\'s '+ file+ ' speaks through the text system, never its own words', fs.readFileSync( path.join( FEATURE, 'assets', file ), 'utf8' ).includes( "Nino.content.getText('/_admin/templates/" ) );
} );
check( 'its stylesheet stays inside the panel\'s own root, so it cannot reach the workbench around it', /(^|\n)\s*#pd-app/.test( styleSource ) && styleSource.includes( '#admin-page-wrap' ) === false );
check( 'and its panel template is a fragment: no document, no stylesheet link, no script of its own', templateMarkup.includes('<html') === false
	&& templateMarkup.includes('<link') === false && templateMarkup.includes('<script') === false
	&& templateMarkup.includes('nino-admin-rail') === false );

console.log('\nNamed area composer');

/*	HTML+ as a component. Coding in a one-line input is not coding, so the row
	carries what is written and a button, and the button opens the same large
	editor the section's own escape hatch opens - the difference being that
	this one writes back into one component instead of detaching the section.
	Node has no DOM, so what is measured is that both halves reach for the one
	dialog rather than building a second	*/
check( 'an HTML+ row is a preview and a button, not a field to type source into', areaComposerSource.includes( "propertyDefinition.kind === 'source'" )
	&& areaComposerSource.includes( "pd-v3-source-preview" )
	&& areaComposerSource.includes( "label/edit-source-component" ) );

check( 'the button opens the section editor, scoped to the component', areaComposerSource.includes( "pd.sectionsUI.openCode( {" )
	&& areaComposerSource.includes( "mode : 'component'" )
	&& sectionsSource.includes( "context.mode === 'component'" ) );

// ...and the dialog is the one that is already there: one #pd-code-source,
// one place it is styled, one thing to keep working
check( 'there is one code editor in this panel, not two', ( templateMarkup.match( /id="pd-code-source"/g ) || [] ).length === 1
	&& styleSource.includes( '#pd-code-source' ) );

/*	A component's source neither starts from the <section> skeleton nor shows
	the detach warning - nothing detaches, which is the reason the mode exists	*/
check( 'a component gets neither the section skeleton nor the detach warning', sectionsSource.includes( "hint/one-component" )
	&& sectionsSource.includes( "? ( source || '' )" ) );

/*	What the dialog accepts is what the composer accepts, because it asks it:
	no second rule in the client to drift from the one on the server	*/
check( 'the source is checked by composing it, not by a rule of its own', areaComposerSource.includes( "pd.api( 'library/compose', draft )" )
	&& areaComposerSource.includes( 'applyComponentSource' )
	&& /source|markup/i.test( areaComposerSource.slice( areaComposerSource.indexOf( 'applyComponentSource' ) ) ) );

// A refused source is put back, or every later preview and the submit fail
// with the same message somewhere else
check( 'a refused source leaves the draft as it was', areaComposerSource.includes( 'component.bindings[target.property] = previous;' ) );

check( 'the Area editor loads after the established composer and exposes bounded pure helpers', panelPhpSource.indexOf( "'composer.js'" ) < panelPhpSource.indexOf( "'area-composer.js'" )
	&& typeof Nino.admin.templates.areaComposer.nextComponentId === 'function'
	&& typeof Nino.admin.templates.areaComposer.moveComponent === 'function' );
/*	What the Update button saves. The values it saves come from
	content/fields, which the dialog starts on open and does not wait for - so
	pressing Update before that answer lands used to save every fill of an
	existing section as the preset's catalogue placeholder, over whatever was
	written there. The section composed, the save succeeded, and the page read
	as the demo text again	*/
const editFields = [
	{ key : '/page-home/hero/title', mode : 'new', default : 'A clear headline for this section' },
	{ key : '/page-home/hero/subtitle', mode : 'new', default : 'A concise supporting line' },
];
const existing = [ { key : '/page-home/hero/title' }, { key : '/page-home/hero/subtitle' } ];

/*	Through a guard rather than called directly: the builder used to be an
	inline expression inside the submit chain, so against the code before this
	these read as five failures instead of one thrown error that reports
	nothing	*/
function contentItems( fields, entries, values ) {
	const builder = Nino.admin.templates.areaComposer.contentItems;
	return typeof builder === 'function' ? builder( fields, entries, values ) : null;
}

check( 'an existing fill the dialog has not loaded yet is not saved at all', ( contentItems( editFields, existing, {} ) || [ 'unreachable' ] ).length === 0 );

check( '...and one it has loaded is saved as what it loaded', JSON.stringify( contentItems( editFields, existing, { '/page-home/hero/title' : 'Was da stand' } ) )
	=== JSON.stringify( [ { key : '/page-home/hero/title', value : 'Was da stand', create : false } ] ) );

// A key that does not exist yet is the other case: nothing to lose, and the
// default is what a new section is meant to start as
check( 'a fill that does not exist yet is created with the preset default', JSON.stringify( contentItems( editFields, [], {} ) )
	=== JSON.stringify( [
		{ key : '/page-home/hero/title', value : 'A clear headline for this section', create : true },
		{ key : '/page-home/hero/subtitle', value : 'A concise supporting line', create : true },
	] ) );

// An emptied field is a value like any other - held, and saved as what it is
check( 'a field somebody emptied is saved empty, not refilled from the catalogue', JSON.stringify( contentItems( editFields, existing, { '/page-home/hero/title' : '' } ) )
	=== JSON.stringify( [ { key : '/page-home/hero/title', value : '', create : false } ] ) );

// Only what this section owns: a binding pointing at a fill somebody else
// wrote is not this dialog's to save
check( 'a field bound to an existing fill elsewhere is left alone', ( contentItems(
	[ { key : '/company/name', mode : 'existing', default : 'x' } ], existing, { '/company/name' : 'Acme' } ) || [ 'unreachable' ] ).length === 0 );

const componentList = [ { id : 'title' }, { id : 'title-2' }, { id : 'image' } ];
check( 'new component IDs remain stable and unique within an Area', Nino.admin.templates.areaComposer.nextComponentId( componentList, 'title' ) === 'title-3'
	&& Nino.admin.templates.areaComposer.nextComponentId( componentList, 'button' ) === 'button' );
// A button's link is stored as it was written, and the composer form is a real
// form: an <input type="url"> holding #prices makes the browser refuse the
// submit before the handler runs, so the section could not be saved at all.
// The field is a text input carrying the server's own rule instead
const linkAccepted = Nino.admin.templates.areaComposer.linkAccepted;
check( 'a fragment and a relative link are accepted, which type="url" never allowed', [ '#prices', '/kontakt', 'preise.html', '../oben', '', '  ' ].every( linkAccepted ) );
check( 'and an absolute one, in the four schemes the server takes', [ 'https://example.com/a', 'http://example.com', 'mailto:a@example.com', 'tel:+4989123' ].every( linkAccepted ) );
check( 'what the server refuses the field refuses too - a foreign scheme, //host, whitespace', [ 'javascript:alert(1)', 'data:text/html,x', 'ftp://example.com', '//example.com/a', 'https://example.com/a b' ].every( function( value ) { return linkAccepted( value ) === false } ) );
check( 'and the two link fields are text inputs, so the browser stops enforcing a scheme', areaComposerSource.includes( "input.type = options === 'url' ? 'text' : ( options || 'text' )" )
	&& /asLinkInput\( input \)/.test( areaComposerSource )
	&& areaComposerSource.includes( "input.type = 'url'" ) === false );

const movedComponents = Nino.admin.templates.areaComposer.moveComponent( componentList, 2, -1 );
check( 'ordered components move without mutating the previous state', movedComponents[1].id === 'image'
	&& componentList[1].id === 'title-2'
	&& Nino.admin.templates.areaComposer.moveComponent( componentList, 0, -1 ) === componentList );
check( 'one area editor per step, and no Design/Data switch left beside it', [ "'/_admin/templates/label/panel-areas'", 'collection.area', 'image.component' ].every( function( marker ) { return areaComposerSource.includes( marker ) } )
	&& [ "[ 'design', 'data' ]", 'pd-v3-view-tabs', 'function renderData(' ].every( function( marker ) { return areaComposerSource.includes( marker ) === false } )
	&& styleSource.includes( '.pd-v3-view-tabs' ) === false );
check( 'Add Section still leaves out the fine tuning an Edit keeps', [
	'function quickMode()', 'function renderQuickArea(', 'pd-v3-quick-components', "if( !quick ) {",
].every( function( marker ) { return areaComposerSource.includes( marker ) } )
	&& composerSource.includes( "step === 'library' && pd.composer._context && pd.composer._context.mode === 'replace'" ) );
// The frame axes rather than their labels: the labels are fills now, and the
// paths are what actually says which control the quick view leaves out
check( 'Add Section omits visual frame and stack styles without dropping background or data controls', /if\( !quick \) \{[\s\S]*?'frame\.screen'[\s\S]*?'frame\.container'[\s\S]*?'frame\.margin'[\s\S]*?'frame\.padding'[\s\S]*?\}\s*grid\.appendChild\( formField\([\s\S]{0,120}?'frame\.background'/.test( areaComposerSource )
	&& areaComposerSource.includes( "'/_admin/templates/label/area-style'" )
	&& areaComposerSource.includes( 'renderBindingFields( group' ) );
check( 'binding controls expose collection fields, existing textfills and fixed values', [
	"{ value : 'field', label : Nino.content.getText('/_admin/templates/label/collection-field') }",
	"{ value : 'textfill', label : Nino.content.getText('/_admin/templates/label/textfill-existing') }",
	"{ value : 'fixed', label : Nino.content.getText('/_admin/templates/label/value-fixed') }",
].every( function( marker ) { return areaComposerSource.includes( marker ) } ) );
check( 'new-section key regeneration preserves explicitly stored shared and fixed bindings', areaComposerSource.includes( "bindingSource( component, property ) !== 'new'" ) );
check( 'binding sources are read from persisted metadata rather than inferred from values', areaComposerSource.includes( "return component.bindingSources && component.bindingSources[property] || '';" )
	&& areaComposerSource.includes( 'normalizeExistingSources' ) === false );
check( 'blacklisted textfills remain selectable in a separate technical group', areaComposerSource.includes( "label : Nino.content.getText('/_admin/templates/label/fills-technical')" )
	&& contentPhpSource.includes( "'blacklisted' => ( $entry['blacklisted'] ?? false ) === true" ) );
check( 'named Areas render as semantic tabs above one Design/Data workspace', [ "'pd-v3-area-workspace'", "setAttribute( 'role', 'tablist' )", "setAttribute( 'role', 'tabpanel' )" ].every( function( marker ) { return areaComposerSource.includes( marker ) } ) );

/*	Inserting a named-area preset walks three steps, not one: choose it,
	design it, fill it. Deciding how a section looks and what it says are
	two jobs, and one screen carrying both is the wall of controls this
	dialog was accused of being. An edit walks the same two configuration
	steps - it only skips the library, because the section already has its
	preset	*/
const stepComposer = Nino.admin.templates.composer;
const stepAreas = Nino.admin.templates.areaComposer;
const stepLibrary = Nino.admin.templates._library.presets;

Nino.admin.templates._library.presets = [ { key : 'v3-hero', version : 3, category : 'Hero', name : 'Hero' } ];
stepComposer._context = { mode : 'insert' };
stepComposer._presetKey = 'v3-hero';

stepComposer._step = 'library';
check( 'inserting a preset splits configuration in two', stepComposer.nextStep() === 'design' && stepComposer.STEPS.join(' ') === 'library design content' );
stepComposer._step = 'design';
check( '...where the primary button opens the content step rather than inserting', stepComposer.nextStep() === 'content' && stepAreas.areaStep() === 'design' );
stepAreas._areaKey = 'heading';
stepComposer._draft = { areas : { heading : { components : [ { id : 'title' } ] }, action : { components : [] } } };
check( '...and the preview puts the area being designed in front', stepComposer.previewFocus() === 'heading' );
check( '...unless that area renders nothing, where a frame dimmed end to end would only read as broken', ( function() {
	stepAreas._areaKey = 'action';
	const empty = stepComposer.previewFocus() === '';
	stepAreas._areaKey = 'heading';
	return empty;
} )() );
stepComposer._step = 'content';
check( '...and the content step is the one that inserts', stepComposer.nextStep() === '' && stepAreas.areaStep() === 'content' );
check( '...where the area tabs stay, and the preview keeps following them', stepComposer.previewFocus() === 'heading' );

/*	The composer knows one kind of preset - the library hands it no other
	(Library::presets() drops every manifest whose version is not 3) - so a
	step that belonged to another kind is one it no longer offers, and a
	request for it is answered with nothing rather than with a screen	*/
check( 'there is no single configuration screen for another kind of preset to land on', ( function() {
	stepComposer._step = 'design';
	// A step that exists would render, and this stand-in has no dialog to
	// render into - so a composer that still offers it fails here rather
	// than taking the rest of the suite down with it
	try {
		stepComposer.setStep( 'config' );
	} catch( error ) {
		return false;
	}
	return stepComposer._step === 'design' && stepComposer.STEPS.includes('config') === false;
} )() );

stepComposer._context = { mode : 'replace' };
stepComposer._step = 'design';
check( 'an edit walks the same two steps, starting at the first one', stepAreas.areaStep() === 'design' && stepComposer.nextStep() === 'content' );
stepComposer._step = 'content';
check( '...and the content step is the one that updates', stepComposer.nextStep() === '' && stepAreas.areaStep() === 'content' );
check( '...and a stepper of one would show no progress, so it is not drawn', composerSource.includes( "stepper.classList.toggle( 'pd-hidden', steps.filter( function( entry ) { return entry[2] } ).length < 2 )" ) );

/*	What the builder did not write in its current form it opens as source.
	A section's marker names a preset key and a version; one that is not
	version 3 is not a spec the composer can edit - it would be handed a
	draft without areas - so the section is what the builder does not
	recognise, and its pencil opens the HTML+ editor, the same as for a
	section with no marker at all	*/
check( 'a stored section whose spec is not version 3 opens as source, whatever preset key it names', ( function() {
	const ui = Nino.admin.templates.sectionsUI;
	const opened = [];
	const keepSection = Nino.admin.templates.section;
	const keepOpen = stepComposer.open;
	const keepCode = ui.openCode;
	stepComposer.open = function() { opened.push('composer') };
	ui.openCode = function() { opened.push('source') };
	[ { version : 3, preset : 'v3-hero', areas : {} }, { version : 2, preset : 'v3-hero', content : 'text' } ].forEach( function( spec ) {
		Nino.admin.templates.section = function() { return { type : 'section', spec : spec, source : '<section></section>' } };
		ui.edit('any');
	} );
	Nino.admin.templates.section = keepSection;
	stepComposer.open = keepOpen;
	ui.openCode = keepCode;
	return opened.join(' ') === 'composer source';
} )() );

check( 'the section frame belongs to the design step, the area editor to both', areaComposerSource.includes( "if( areaStep() !== 'content' )" )
	&& /if\( areaStep\(\) === 'design' \)\s*\n\s*renderDesign\(/.test( areaComposerSource ) );
check( 'every configuration step answers the one test the renderers ask', composerSource.includes( "return pd.composer._step !== 'library';" )
	&& composerSource.includes( "_step === 'config'" ) === false );

Nino.admin.templates._library.presets = stepLibrary;
stepComposer._draft = null;
stepComposer._context = null;
stepComposer._step = 'library';

/*	A generated textfill is named after the section, so renaming the section
	renames every key it owns - and what was typed into the dialog is held by
	key. loadTextValues() fills every key it was not told had been typed in
	with the preset's default, so a value left behind under the old key was
	replaced by that default the moment the id changed, and the operator's own
	sentence was gone without a word. The held values move with the binding	*/
const renameLibrary = Nino.admin.templates._library.presets;
const renameComposer = Nino.admin.templates.composer;
Nino.admin.templates._library.presets = [ { key : 'rename-hero', version : 3, recommend : { layout : 'stacked' }, layouts : { stacked : { label : 'Stacked' } }, componentCatalog : { title : { label : 'Title', styles : [ 'auto' ], properties : { text : { kind : 'text', default : 'A clear headline' } } } }, areas : { body : { source : 'single', label : 'Body', allowed : [ 'title' ], maxComponents : 4, styles : { plain : { label : 'Plain' } }, recommend : { style : 'plain' } } } } ];
renameComposer._presetKey = 'rename-hero';
renameComposer._draft = { pageId : 'home', id : 'hero', layout : 'auto', frame : {}, areas : { body : { style : 'auto', source : {}, components : [ { id : 'title', type : 'title', style : 'auto', settings : {}, bindings : { text : '/page-home/hero/title' }, bindingSources : { text : 'new' } } ] } } };
renameComposer._textValues = { '/page-home/hero/title' : 'What the operator typed' };
renameComposer._touched = new Set( [ '/page-home/hero/title' ] );
renameComposer.updateDraft( { dataset : { path : 'id' }, tagName : 'INPUT', type : 'text', value : 'intro' }, false );
check( 'renaming a section carries the texts typed for it to their new keys', renameComposer._draft.areas.body.components[0].bindings.text === '/page-home/intro/title'
	&& renameComposer._textValues['/page-home/intro/title'] === 'What the operator typed'
	&& Object.prototype.hasOwnProperty.call( renameComposer._textValues, '/page-home/hero/title' ) === false
	&& renameComposer._touched.has( '/page-home/intro/title' ) === true
	&& renameComposer._touched.has( '/page-home/hero/title' ) === false );
Nino.admin.templates._library.presets = renameLibrary;
renameComposer._draft = null;
renameComposer._presetKey = '';
renameComposer._textValues = {};
renameComposer._touched = new Set();

/*	The save status carries two unrelated things at once: the design system's
	own class (.nino-admin-actionbar-status, from panel.tpl) and whichever
	state the panel is in. setDirty() says so and toggles; save() wrote
	className outright, so the very first save stripped the design-system
	class off the element and the status stayed unstyled until the panel was
	reloaded. The kernel makes the same point where it hands every shell a
	setStateClass() instead of a className write	*/
function statusStub( initial ) {
	const classes = new Set( String( initial ).split(' ').filter( Boolean ) );
	return {
		textContent : '',
		disabled : false,
		classList : {
			add : function( name ) { classes.add( name ) },
			remove : function( name ) { classes.delete( name ) },
			toggle : function( name, on ) { if( on ) classes.add( name ); else classes.delete( name ) },
			contains : function( name ) { return classes.has( name ) },
		},
		get className() { return Array.from( classes ).join(' ') },
		set className( value ) { classes.clear(); String( value ).split(' ').filter( Boolean ).forEach( function( name ) { classes.add( name ) } ) },
	};
}
const saveState = statusStub( 'nino-admin-actionbar-status' );
const saveButton = statusStub( '' );
const plainGetElementById = documentStub.getElementById;
documentStub.getElementById = function( id ) {
	if( id === 'pd-save-state' ) return saveState;
	if( id === 'pd-save' ) return saveButton;
	return null;
};
Nino.admin.templates._current = { name : 'page-home', displayName : 'Home', pageMotion : 'none', revision : 1, readonly : null, segments : [] };
Nino.admin.templates._dirty = true;
Nino.admin.templates._saving = false;
Nino.admin.templates.save();
check( 'saving leaves the status its design-system class and only drops its state', saveState.classList.contains( 'nino-admin-actionbar-status' ) === true
	&& saveState.classList.contains( 'is-dirty' ) === false
	&& saveState.classList.contains( 'is-error' ) === false
	&& saveState.textContent === '/_admin/templates/msg/saving' );
documentStub.getElementById = plainGetElementById;
Nino.admin.templates._current = null;
Nino.admin.templates._saving = false;
Nino.admin.templates._dirty = false;

/*	The panel under the workbench's head. The shell renders one row over every
	pane but the Dashboard (see \Nino\Admin\Panels::panesHtml()): the panel's
	name, and a slot at its end for the buttons a panel keeps over its screen.
	This panel drew a bar of its own under that row - the open document's name
	and file on one side, the save state, Delete and Save on the other - so the
	name stood twice and the controls a bar lower than every other panel's.
	init() hands the three to the head now, as the same elements: their ids,
	their disabled state and their listeners are what the rest of the script
	reaches for. The document's name is in the list and the settings row.

	Driven over a dom stand-in of the rendered pane, the head the way the
	kernel draws it and Nino.adminUi.panelHead() the way Nino.admin.js answers
	it - the one in the kernel's own tests/admin-script-js-smoke.js	*/
function domNode( tag, id ) {
	const classes = new Set();
	const el = {
		tagName : String( tag ).toUpperCase(), id : id || '', children : [], parent : null,
		dataset : {}, listeners : {}, disabled : false, value : '', textContent : '', title : '', type : '',
		classList : {
			add : function( name ) { classes.add( name ) },
			remove : function( name ) { classes.delete( name ) },
			toggle : function( name, on ) { if( on === undefined ? !classes.has( name ) : on ) classes.add( name ); else classes.delete( name ) },
			contains : function( name ) { return classes.has( name ) },
		},
		get className() { return Array.from( classes ).join(' ') },
		set className( value ) { classes.clear(); String( value ).split(' ').filter( Boolean ).forEach( function( name ) { classes.add( name ) } ) },
		set innerHTML( value ) {
			if( value !== '' )
				throw new Error( 'the stand-in only takes innerHTML = \'\'' );
			el.children.forEach( function( child ) { child.parent = null } );
			el.children = [];
		},
		appendChild : function( child ) {
			if( child.parent !== null )
				child.remove();
			child.parent = el;
			el.children.push( child );
			return child;
		},
		append : function() { Array.from( arguments ).forEach( el.appendChild ) },
		remove : function() {
			if( el.parent === null )
				return;
			el.parent.children.splice( el.parent.children.indexOf( el ), 1 );
			el.parent = null;
		},
		addEventListener : function( type, fn ) { ( el.listeners[type] = el.listeners[type] || [] ).push( fn ) },
		setAttribute : function() {},
		closest : function() {
			let at = el;
			while( at !== null && at.dataset.panel === undefined )
				at = at.parent;
			return at;
		},
		querySelector : function( selector ) {
			return el.children.filter( function( child ) { return child.classList.contains( selector.replace( ':scope > .', '' ) ) } )[0] || null;
		},
	};
	return el;
}

/** Every element below $root, depth first */
function domAll( root ) {
	return root.children.reduce( function( all, child ) { return all.concat( [ child ], domAll( child ) ) }, [] );
}

/**
 *	The pane as the workbench renders it: the head (unless { head : false }),
 *	then panel.tpl's #pd-app with the row of three controls and every element
 *	init() and the document screen reach for by id
 */
function templatesPane( options ) {
	const pane = domNode( 'div', 'admin-content-templates' );
	pane.dataset.panel = 'templates';
	const head = domNode('div');
	head.className = 'admin-panel-head';
	const title = domNode('h2');
	title.className = 'admin-panel-title';
	title.textContent = 'Templates';
	const actions = domNode('div');
	actions.className = 'admin-panel-actions';
	head.append( title, actions );
	if( ( options || {} ).head !== false )
		pane.appendChild( head );

	const app = domNode( 'div', 'pd-app' );
	const row = domNode( 'div', 'pd-top-actions' );
	const state = domNode( 'span', 'pd-save-state' );
	state.className = 'nino-admin-actionbar-status';
	const remove = domNode( 'button', 'pd-delete-template' );
	const save = domNode( 'button', 'pd-save' );
	remove.disabled = true;
	save.disabled = true;
	row.append( state, remove, save );
	const shell = domNode( 'div', 'pd-shell' );
	[ 'pd-new-template', 'pd-reload-pages', 'pd-page-search', 'pd-page-list', 'pd-page-toolbar', 'pd-template-name', 'pd-header-template', 'pd-footer-template',
		'pd-notice', 'pd-empty', 'pd-canvas', 'pd-add-section', 'pd-create-form', 'pd-create-filename', 'pd-create-name', 'pd-include-search', 'pd-toast' ].forEach( function( id ) {
		shell.appendChild( domNode( 'div', id ) );
	} );
	app.append( row, shell );
	pane.appendChild( app );

	return { pane : pane, head : head, title : title, actions : actions, app : app, row : row, state : state, remove : remove, save : save, shell : shell,
		byId : function( id ) { return domAll( pane ).filter( function( el ) { return el.id === id } )[0] || null } };
}

const headless = { getElementById : documentStub.getElementById, querySelectorAll : documentStub.querySelectorAll, createElement : documentStub.createElement };
const withPane = function( screen ) {
	documentStub.getElementById = screen.byId;
	documentStub.querySelectorAll = function() { return [] };
	documentStub.createElement = function( tag ) { return domNode( tag ) };
};
const withoutPane = function() {
	documentStub.getElementById = headless.getElementById;
	documentStub.querySelectorAll = headless.querySelectorAll;
	documentStub.createElement = headless.createElement;
};

Nino.adminUi.panelHead = function( el ) {
	const pane = el && typeof el.closest === 'function' ? el.closest('[data-panel]') : null;
	const head = pane ? pane.querySelector(':scope > .admin-panel-head') : null;
	if( !head )
		return null;
	return { element : head, title : head.querySelector(':scope > .admin-panel-title'), actions : head.querySelector(':scope > .admin-panel-actions'), tabs : function() {} };
};
context.window.addEventListener = function() {};

const headed = templatesPane();
withPane( headed );
Nino.admin.templates.init();

check( 'the save state, Delete and Save are handed to the head over the pane, at its end and in that order - the same three elements, ids and all',
	headed.head.children.length === 2 && headed.head.children[1] === headed.actions
	&& headed.actions.children.length === 3 && headed.actions.children[0] === headed.state && headed.actions.children[1] === headed.remove && headed.actions.children[2] === headed.save
	&& [ 'pd-save-state', 'pd-delete-template', 'pd-save' ].every( function( id, at ) { return headed.actions.children[at].id === id } ) );
check( '...and the row they waited in is gone, so nothing of the panel\'s own stands between the head and the three columns',
	headed.app.children[0] === headed.shell && headed.byId('pd-top-actions') === null );

// The controls in the head are the live ones: the listeners init() gives them,
// and the disabled state the document and its changes decide
const openDocument = { name : 'page-home', filename : 'page-home.tpl', pageId : 'home', displayName : 'Home', pageMotion : 'off', revision : 1, readonly : null, segments : [] };
const heldSections = Nino.admin.templates.sectionsUI;
const heldDocuments = Nino.admin.templates._documents;
Nino.admin.templates.sectionsUI = null;
Nino.admin.templates._documents = [ { name : 'page-home', filename : 'page-home.tpl', pageId : 'home', displayName : 'Home', editable : true, sections : 0, components : 0 } ];
Nino.admin.templates._current = openDocument;
Nino.admin.templates.setDirty( true );
const dirtyEnables = headed.save.disabled === false && headed.state.classList.contains('is-dirty');
Nino.admin.templates.setDirty( false );
const cleanDisables = headed.save.disabled === true;
Nino.admin.templates.renderTemplateSettings();
const openEnables = headed.remove.disabled === false;

check( 'Save and Delete in the head are the buttons that save and delete, and follow the open document and its changes',
	headed.save.parent === headed.actions && headed.remove.parent === headed.actions
	&& ( headed.save.listeners.click || [] ).includes( Nino.admin.templates.save ) && ( headed.remove.listeners.click || [] ).includes( Nino.admin.templates.deleteTemplate )
	&& dirtyEnables && cleanDisables && openEnables );

/*	The bar held the document's name and file; the name is in the list and the
	settings row, and a script still writing a title of its own would reach for
	an element that is not there	*/
let named = false;
try {
	Nino.admin.templates.renderDocument();
	const shown = headed.byId('pd-template-name').value === 'Home';
	Nino.admin.templates.setTemplateName('Home, renamed');
	const listed = domAll( headed.byId('pd-page-list') ).filter( function( el ) { return el.tagName === 'STRONG' } ).map( function( el ) { return el.textContent } );
	named = shown && JSON.stringify( listed ) === JSON.stringify( [ 'Home, renamed' ] ) && headed.save.disabled === false;
} catch( error ) {
	named = false;
}
check( 'a document opened and renamed shows its name in the settings row and the list, and the panel keeps no title of its own to write it into', named );

Nino.admin.templates._current = null;
Nino.admin.templates._dirty = false;
Nino.admin.templates._documents = heldDocuments;
Nino.admin.templates.sectionsUI = heldSections;

// A kernel from before the head: the three stay in their row over the columns
const bare = templatesPane( { head : false } );
withPane( bare );
if( typeof Nino.admin.templates.placeActions === 'function' )
	Nino.admin.templates.placeActions();
check( 'where the pane has no head, the three stay in their own row over the columns, as they were',
	typeof Nino.admin.templates.placeActions === 'function' && bare.app.children[0] === bare.row && bare.row.children.length === 3 && bare.row.children[2] === bare.save );
withoutPane();

check( 'the background image offers a fixed value next to the two slot choices', areaComposerSource.includes( "{ value : 'fixed', label : Nino.content.getText('/_admin/templates/label/value-fixed') }" )
	&& /formField\( Nino\.content\.getText\('\/_admin\/templates\/label\/background-image'\), '', \[[^\]]*value : 'fixed'/.test( areaComposerSource )
	&& areaComposerSource.includes( "'frame.backgroundImage', 'text'" )
	&& areaComposerSource.includes( "backgroundSource( draft ) !== 'fixed'" ) );
check( 'every binding keeps its source and its value on one row', areaComposerSource.includes( 'function bindingRow(' )
	&& areaComposerSource.includes( "node( 'div', 'pd-v3-binding-row' )" )
	&& /\.pd-v3-binding-row\s*\{[\s\S]*?grid-template-columns:\s*minmax/.test( styleSource ) );
check( 'a link target is one checkbox after the address it applies to, in the composer\'s own scope', areaComposerSource.includes( "node( 'label', 'pd-check pd-v3-binding-toggle' )" )
	&& areaComposerSource.includes( "'/_admin/templates/label/link-target'" )
	&& areaComposerSource.includes( 'dataset.targetToggle' )
	&& areaComposerSource.includes( '[data-target-toggle]' )
	&& !areaComposerSource.includes( "label : 'Same tab'" )
	&& !areaComposerSource.includes( 'Nino.adminUi.switchField(' ) );
check( 'composer controls are styled by the tool itself, because its dialogs sit outside the .nino-admin scope', templateMarkup.indexOf( '<dialog id="pd-composer"' ) > templateMarkup.indexOf( '</div>' )
	&& /:where\(\.nino-admin\)\s*\.nino-admin-switch\s*\{/.test( ninoAdminCssSource )
	&& /(^|\n)\.pd-check\s*\{/.test( styleSource ) );
check( 'the config pane gives steps, Area tabs, components, sources and bindings explicit UI structure', [
	'pd-v3-panel', 'pd-v3-area-index', 'pd-v3-area-tab-copy', 'pd-v3-component-copy',
	'pd-v3-section-label', 'pd-v3-source-panel', 'pd-v3-binding-heading', 'pd-v3-generated-value',
].every( function( marker ) { return areaComposerSource.includes( marker ) } ) );
check( 'named-area rules use maintainable component specificity in the normal tool layer', /@layer nino\.tool \{\s*#pd-composer-settings/.test( styleSource )
	&& styleSource.includes( '.pd-v3-area-tabs button' )
	&& styleSource.includes( '.pd-v3-component-identity' )
	&& !styleSource.includes( '#pd-app .pd-v3-' ) );
check( 'Area navigation stays horizontal so the editor body keeps the full config-pane width', /\.pd-v3-area-workspace\s*\{[\s\S]*?grid-template-columns:\s*minmax\(0,\s*1fr\)/.test( styleSource )
	&& /\.pd-v3-area-tabs\s*\{[\s\S]*?display:\s*flex;[\s\S]*?overflow-x:\s*auto;/.test( styleSource )
	&& !styleSource.includes( 'grid-template-columns: 10.5rem minmax(0, 1fr)' ) );
check( 'canvas cards and the inspector read named Areas instead of legacy section axes', [ 'isAreaSpec( spec )', 'areaPreview( spec, areaPreset )', "'/_admin/templates/label/areas'", "'/_admin/templates/label/collections'" ].every( function( marker ) { return sectionsSource.includes( marker ) } ) );
check( 'the Articles preset keeps every margin-bearing card inside its selected grid row', articlesManifestSource.includes( 'nino-article--grid' )
	&& [ '25', '33', '50' ].every( function( width ) { return new RegExp( '\\.nino-article--grid\\.nino-grid-m-'+ width+ '\\s*\\{[^}]*width:\\s*calc\\(' ).test( ninoCssSource ) } ) );
// The frontend speaks one namespace. (?<![-\w]) keeps '-ui-'/'-js-' inside identifiers
// out of it, the lookahead the CSS system font keywords that merely look like classes.
const legacyClass = /(?<![-\w])(?:ui|js|sc)-(?!monospace|sans-serif|serif|rounded)[a-z0-9]/;
check( 'the design system carries no legacy class prefix', legacyClass.test( ninoCssSource ) === false );

// A half-finished rename is what a namespace change actually invites: the JS keeps
// setting a class the stylesheet no longer knows, and nothing looks broken until a
// form turns red in the browser. Both sides have to name the same states.
const statesInJs = new Set( ( ninoUiJsSource.match( /'nino-is-[a-z-]+'/g ) || [] ).map( function( literal ) { return literal.slice( 1, -1 ) } ) );
const statesInCss = new Set( ( ninoCssSource.match( /\.nino-is-[a-z-]+/g ) || [] ).map( function( selector ) { return selector.slice( 1 ) } ) );
check( 'every state class the frontend JS sets is one the stylesheet styles', statesInJs.size > 0
	&& Array.from( statesInJs ).every( function( state ) { return statesInCss.has( state ) } ) );
check( 'the frontend state vocabulary is namespaced, not a bare English word', [ 'active', 'touch', 'error', 'success', 'pending', 'existing' ].every( function( state ) {
	return statesInCss.has( 'nino-is-'+ state ) && new RegExp( '\\.'+ state+ '\\b' ).test( ninoCssSource ) === false;
} ) );
check( 'type size is a modifier of the class it changes, not an em utility over it', ninoCssSource.includes( '.nino-font-big' ) === false
	&& ninoCssSource.includes( '.nino-font-small' ) === false
	&& [ 'nino-section-title', 'nino-section-subtitle', 'nino-section-text', 'nino-atf-title', 'nino-atf-subtitle', 'nino-article-title', 'nino-article-descr', 'nino-pricing-title', 'nino-pricing-price' ].every( function( base ) {
		return ninoCssSource.includes( '.'+ base+ '--quiet' ) && ninoCssSource.includes( '.'+ base+ '--loud' );
	} )
	&& /\.nino-section-title--loud \{[^}]*var\(--text-5\)/.test( ninoCssSource )
	&& /--(quiet|loud) \{[^}]*font-size:[^;]*[0-9.]em/.test( ninoCssSource ) === false );
// The sentence is a fill now (label/auto, "Auto (%s)"), so what is pinned here
// is that autoLabel() still puts the resolved value into it
check( 'every Auto option names the value it resolves to', /return Nino\.content\.getText\('\/_admin\/templates\/label\/auto'\)\.replace\( '%s', label \)/.test( areaComposerSource )
	&& /label\/auto\]\]'\s*\t*=> 'Auto \(%s\)'/.test( fs.readFileSync( path.join( FEATURE, 'text/en_US.php' ), 'utf8' ) )
	&& areaComposerSource.includes( "label : autoLabel( humanize( resolved[key] ) )" )
	&& [ 'screen', 'container', 'vertical', 'margin', 'padding', 'background', 'overlay', 'focus' ].every( function( axis ) {
		return areaComposerSource.includes( "frameChoices( '"+ axis+ "', recommended )" );
	} )
	&& areaComposerSource.includes( 'autoLabel( Nino.adminUi.text( item.layouts[item.recommend.layout].label ) )' )
	&& areaComposerSource.includes( 'autoLabel( Nino.adminUi.text( area.styles[area.recommend.style].label ) )' )
	&& areaComposerSource.includes( "'Auto · '" ) === false );
const recommendedFrameBody = areaComposerSource.slice( areaComposerSource.indexOf( 'function recommendedFrame(' ) ).split( '\n\tfunction ' )[0];
check( 'what Auto resolves to is read without the choice the user already made', recommendedFrameBody.includes( 'draft.frame' ) === false
	&& recommendedFrameBody.includes( 'FRAME_FALLBACK[key]' )
	&& areaComposerSource.includes( 'const recommended = recommendedFrame( draft, item );' ) );
check( 'the client frame fallbacks match the compiler\'s own', [ areaComposerSource, sectionsSource ].every( function( source ) {
	return source.includes( "overlay : 'dim'" ) && source.includes( "overlay : 'medium'" ) === false;
} ) );
check( 'every preview card is scaled to one viewport, so the gallery compares presets and not tile heights', composerSource.includes( "frame.dataset.viewportHeight = '760'" )
	&& composerSource.includes( 'previewHeight' ) === false );
const resourceSpec = { version : 3, preset : 'sample', pageId : 'home', id : 'services', areas : { copy : { components : [ { id : 'visual', type : 'image', bindings : { src : '/page-home/services/visual' } } ] } } };
const resourcePreset = { areas : { copy : { label : 'Copy', source : 'single' } } };
check( 'v3 image creation is limited to generated background and declared Area image slots',
	Nino.admin.templates.sectionsUI.areaImageRequest( resourceSpec, resourcePreset, '/page-home/services/background' ).slot === 'background'
	&& Nino.admin.templates.sectionsUI.areaImageRequest( resourceSpec, resourcePreset, '/page-home/services/visual' ).component === 'visual'
	&& Nino.admin.templates.sectionsUI.areaImageRequest( resourceSpec, resourcePreset, '/shared/existing-image' ) === null );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures > 0 ? 1 : 0 );
