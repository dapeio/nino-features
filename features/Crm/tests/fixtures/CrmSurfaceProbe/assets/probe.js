/* The script of the package of the suite: it docks to every place the script of the core offers, with the real script in the browser and in the suite of the script. Nothing here is a feature of the catalogue */
( function( wn ) {

	'use strict';

	const crm = wn.Nino.crm;
	const kit = crm.kit.make( 'probe' );
	const node = function( text, id ) { return kit.el( 'p', { id : id, text : text } ) };
	const block = function( text ) { return kit.card( 'Probe block', node( text, 'probe-block' ) ) };

	wn.probeScript = true;

	crm.settings.addTab( { key : 'probe', word : 'Probe tab', rank : 500 } );
	crm.settings.addSection( { key : 'probe-section', tab : 'probe', word : 'Probe section', build : function( inner ) { return inner.card( 'Probe card', node( 'probe settings', 'probe-settings' ) ) } } );
	crm.pages.add( { key : 'probe', hash : 'probe', title : 'Probe page', draw : function( inner, where ) { return node( 'probe page '+ where.rest, 'probe-page' ) } } );
	crm.menu.add( { key : 'probe-page', word : 'Probe entry', page : 'probe', rank : 700, count : function() { return 4 } } );
	crm.overview.addTile( { key : 'probe', rank : 5, draw : function( inner, overview ) { return node( 'probe tile '+ overview.probe, 'probe-tile' ) } } );
	crm.overview.addBlock( { key : 'probe', rank : 40, draw : function() { return block( 'probe block' ) } } );
	crm.overview.addNeed( { key : 'probe-need', draw : function( inner, need ) { return kit.el( 'li', { class : 'probe-need', text : 'need '+ need.id } ) } } );
	crm.overview.addData( { key : 'probe', load : function() { return Promise.resolve( 'loaded' ) } } );
	crm.cards.addTab( 'client', { key : 'probe', word : 'Probe tab', rank : 700, count : function() { return 7 }, draw : function( inner, record ) { return node( 'client '+ record.client.number, 'probe-client-tab' ) } } );
	crm.cards.addTab( 'project', { key : 'probe', word : 'Probe tab', rank : 700, draw : function( inner, record ) { return node( 'project '+ record.project.id, 'probe-project-tab' ) } } );
	crm.cards.addHead( 'client', { key : 'probe-mark', slot : 'marks', draw : function() { return kit.el( 'span', { id : 'probe-mark', text : 'probe mark' } ) } } );
	crm.cards.addHead( 'client', { key : 'probe-note', slot : 'notes', draw : function() { return node( 'probe note', 'probe-note' ) } } );
	crm.cards.addHead( 'client', { key : 'probe-action', slot : 'actions', draw : function() { return kit.el( 'span', { id : 'probe-action', text : 'probe action' } ) } } );
	crm.cards.addHead( 'client', { key : 'probe-menu', slot : 'menu', draw : function() { return [ { label : 'Probe item', run : function() {} } ] } } );
	crm.cards.addHead( 'project', { key : 'probe-action', slot : 'actions', draw : function() { return kit.el( 'span', { id : 'probe-project-action', text : 'probe project action' } ) } } );
	crm.cards.addFact( 'client', { key : 'probe-fact', word : 'Probe fact', draw : function() { return 'fact value' } } );
	crm.cards.addFact( 'project', { key : 'probe-fact', word : 'Probe fact', draw : function() { return 'project fact' } } );
	crm.lists.addMark( 'client', { key : 'probe', draw : function() { return kit.el( 'span', { class : 'probe-mark', text : '*' } ) } } );
	crm.lists.addMark( 'project', { key : 'probe', draw : function() { return kit.el( 'span', { class : 'probe-mark', text : '+' } ) } } );

	// Three columns for each list: one that keeps its head in front of it in a card, one that goes to the second line and one that stays out of the card
	[ 'client', 'project', 'client-project' ].forEach( function( list ) {
		crm.lists.addColumn( list, { key : 'probe', head : 'Probe column', rank : 500, load : function() { return Promise.resolve() }, cell : function( inner, row ) { return 'cell '+ row.id } } );
		crm.lists.addColumn( list, { key : 'probe-second', head : 'Probe second line', rank : 510, narrow : 'second', cell : function( inner, row ) { return 'a long sentence of a package that has no end in sight for row '+ row.id } } );
		crm.lists.addColumn( list, { key : 'probe-off', head : 'Probe off', rank : 520, narrow : false, align : 'r', cell : function( inner, row ) { return String( row.id ) } } );
	} );

	crm.search.add( { key : 'probe', word : 'Probe hits', load : function() { return Promise.resolve( [ { n : 'Zebra' } ] ) }, hits : function( inner, rows, holds ) { return rows.filter( function( row ) { return holds( [ row.n ] ) } ).map( function( row ) { return { label : row.n, hint : 'probe', href : '#probe' } } ) } } );
	crm.setup.addStep( { key : 'probe', word : 'Probe step', rank : 500, done : function() { return false }, draw : function() { return kit.el( 'span', { text : 'probe step open' } ) } } );
	crm.keys.add( { key : 'z', href : '#probe', word : 'Go to the probe' } );
	crm.logbook.addKind( { kind : 'probe-done', source : 'probe', sentence : function( inner, entry ) { return 'Probe '+ ( entry.data.label ?? '' ) }, link : function() { return { href : '#probe', text : 'probe link' } } } );
	crm.wipe.addCount( { key : 'probe', rank : 60, goes : 'Probe data', count : 'probe rows: %s' } );
	crm.forms.extend( 'client-new', { key : 'probe', draw : function( inner, form ) { return kit.el( 'div', { class : 'card pad', id : 'probe-form' }, kit.formList( [ { name : 'probe_field', label : 'Probe field', control : kit.el( 'input', { id : 'f-probe', type : 'text' } ) } ], form ) ) }, values : function() { return {} } } );
	crm.forms.extend( 'project-new', { key : 'probe', draw : function() { return node( 'probe project form', 'probe-project-form' ) }, values : function() { return {} } } );
	crm.forms.extend( 'client-master', { key : 'probe', draw : function() { return node( 'probe master form', 'probe-master-form' ) } } );
	crm.records.addKind( { kind : 'probe-record', title : function() { return 'Probe record' }, body : function() { return [ [ 'Probe fact', 'x' ] ] } } );
	crm.sites.define( 'probe' );
	crm.sites.add( { key : 'probe', address : function( inner, thing ) { return '/probe/'+ thing.id } } );
	crm.extras.add( { key : 'mails-note', word : 'Probe mails note' } );
	crm.extras.add( { key : 'probe:own-place', word : ' own place' } );
	crm.kit.add( 'probeHelper', function() { return 'helped' } );
	crm.apps.add( { key : 'probeapp', route : '/crm/probeapp', home : 'probe.home-', draw : function( inner ) { inner.main.appendChild( node( 'probe app', 'probe-app' ) ) } } );
	crm.state.own( 'probe', { count : 1 } ).count = 5;
	wn.probe = crm.kit.publish( 'probe', { x : 1 } );
}( window ) );
