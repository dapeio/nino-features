/**
 *	Nino - Table of Contents
 *	toc.js			Fills the nav [toc] wrote from the headings the finished page
 *					actually has, gives each of them an id to be reached by, marks
 *					the one being read, and - where the setting asks for it - puts
 *					a link on every heading so a passage can be linked to. No
 *					dependencies, no build step - bundled into the project's own
 *					/.cache/script.js the same way the kernel bundles
 *					Nino.js/Nino.ui.js (see Toc::init()).
 *
 *					Built here rather than on the server because a Nino page is
 *					assembled out of a template, sections, shortcodes and elements,
 *					and what the headings finally are is only settled once all of
 *					that has run. The finished page is the only place the answer is
 *					complete, and this is standing in it.
 *
 *					The list is flat, with the level on each item and the indent in
 *					the stylesheet, rather than an <ol> inside an <ol>. It is one
 *					ordered list of links to one page either way, and a nested list
 *					built from a flat run of headings has to guess what to do with
 *					an h3 that comes before any h2.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-toc';

	// How far down the viewport a heading counts as "the one being read".
	// A third: high enough that a heading reaches it soon after it appears,
	// low enough that the section above stays current while it is still there
	var MARK_AT = 0.34;

	var lists = [];
	var pending = false;

	/**
	 *	A heading's own words - what it says, without the anchor this script
	 *	may have put on it. Once a list has run with anchors switched on, the
	 *	'#' of that link is part of every one of those headings' textContent,
	 *	and a second list built afterwards would read it as part of the words
	 *
	 *	@param		{Element}		heading
	 *
	 *	@return		{string}
	 */
	function words( heading ) {

		var text = '';

		for( var i = 0; i < heading.childNodes.length; i++ ) {

			var node = heading.childNodes[i];

			if( node.nodeType === 1 && node.classList.contains( 'nino-toc-anchor' ) === true )
				continue;

			text += node.textContent || '';
		}

		return text;
	}

	/**
	 *	A heading's own words, turned into something that can be an id
	 *
	 *	@param		{string}		text
	 *
	 *	@return		{string}
	 */
	function slug( text ) {

		return String( text )
			.toLowerCase()
			.replace( /ä/g, 'ae' ).replace( /ö/g, 'oe' ).replace( /ü/g, 'ue' ).replace( /ß/g, 'ss' )
			.normalize( 'NFKD' ).replace( /[̀-ͯ]/g, '' )
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' )
			.slice( 0, 60 );
	}

	/**
	 *	Give one heading an id, unless it has one already - that one may be
	 *	linked to from somewhere else, and taking it away would break a link
	 *	nobody here can see
	 *
	 *	@param		{Element}		heading
	 *	@param		{Object}		taken			ids already handed out on this page
	 *
	 *	@return		{string}
	 */
	function identify( heading, taken ) {

		if( heading.id !== '' ) {
			taken[heading.id] = true;
			return heading.id;
		}

		var base = slug( words( heading ) ) || 'abschnitt';
		var id = base;
		var n = 2;

		while( taken[id] === true || document.getElementById( id ) !== null ) {
			id = base + '-' + n;
			n++;
		}

		taken[id] = true;
		heading.id = id;

		return id;
	}

	/**
	 *	The headings one list is built from: inside what it was pointed at,
	 *	of the levels it names, and never one of a table of contents' own -
	 *	a list that listed its own title would be listing itself
	 *
	 *	@param		{Element}		nav
	 *
	 *	@return		{Array}
	 */
	function headings( nav ) {

		var within = nav.getAttribute( 'data-toc-within' ) || '';
		var scope = null;

		if( within !== '' ) {
			try { scope = document.querySelector( within ) }
			catch( e ) { scope = null }
		}

		var levels = ( nav.getAttribute( 'data-toc-levels' ) || '2,3' ).split( ',' );
		var wanted = [];

		for( var i = 0; i < levels.length; i++ ) {
			var level = parseInt( levels[i], 10 );
			if( level >= 2 && level <= 6 )
				wanted.push( 'h' + level );
		}

		if( wanted.length === 0 )
			return [];

		var found = ( scope || document ).querySelectorAll( wanted.join( ',' ) );
		var out = [];

		for( var h = 0; h < found.length; h++ ) {

			if( found[h].closest( SELECTOR ) !== null )
				continue;

			if( found[h].classList.contains( 'nino-toc-skip' ) === true )
				continue;

			if( words( found[h] ).trim() === '' )
				continue;

			out.push( found[h] );
		}

		return out;
	}

	/**
	 *	Put a link on one heading, so the passage under it can be linked to.
	 *	The mark is the stylesheet's; what is here is a link with a name, for
	 *	a reader who reaches it without seeing it
	 *
	 *	@param		{Element}		heading
	 *	@param		{string}		id
	 *	@param		{string}		label
	 *
	 *	@return		void
	 */
	function anchor( heading, id, label ) {

		if( heading.querySelector( '.nino-toc-anchor' ) !== null )
			return;

		var link = document.createElement( 'a' );
		link.className = 'nino-toc-anchor';
		link.href = '#' + id;
		link.setAttribute( 'aria-label', label );
		link.textContent = '#';

		heading.appendChild( link );
	}

	/**
	 *	Build one list, and hand back what it is watching
	 *
	 *	@param		{Element}		nav
	 *	@param		{Object}		taken
	 *
	 *	@return		{?Object}					{ nav, entries } or null where there was nothing to list
	 */
	function build( nav, taken ) {

		var list = nav.querySelector( '.nino-toc-list' );

		if( list === null )
			return null;

		var found = headings( nav );

		if( found.length === 0 )
			return null;

		var withAnchors = nav.getAttribute( 'data-toc-anchors' ) === '1';
		var label = nav.getAttribute( 'data-toc-label' ) || '';
		var entries = [];

		for( var i = 0; i < found.length; i++ ) {

			var heading = found[i];
			var id = identify( heading, taken );
			var level = parseInt( heading.tagName.slice( 1 ), 10 );

			var item = document.createElement( 'li' );
			item.className = 'nino-toc-item nino-toc-item--' + level;

			var link = document.createElement( 'a' );
			link.className = 'nino-toc-link';
			link.href = '#' + id;
			link.textContent = words( heading ).trim();

			item.appendChild( link );
			list.appendChild( item );

			if( withAnchors === true )
				anchor( heading, id, label );

			entries.push( { heading : heading, link : link } );
		}

		nav.hidden = false;

		return { nav : nav, entries : entries };
	}

	/**
	 *	Mark the section being read, on every list on the page. The last
	 *	heading above the mark is the one, and the first one until the first
	 *	heading has been reached at all
	 *
	 *	@return		void
	 */
	function mark() {

		pending = false;

		var line = ( window.innerHeight || 0 ) * MARK_AT;

		for( var l = 0; l < lists.length; l++ ) {

			var entries = lists[l].entries;
			var current = 0;

			for( var i = 0; i < entries.length; i++ )
				if( entries[i].heading.getBoundingClientRect().top <= line )
					current = i;

			for( var m = 0; m < entries.length; m++ )
				if( m === current )
					entries[m].link.setAttribute( 'aria-current', 'true' );
				else
					entries[m].link.removeAttribute( 'aria-current' );
		}
	}

	/**
	 *	One frame at most per scroll, rather than one call per scroll event
	 *
	 *	@return		void
	 */
	function schedule() {

		if( pending === true )
			return;

		pending = true;

		if( typeof window.requestAnimationFrame === 'function' )
			window.requestAnimationFrame( mark );
		else
			mark();
	}

	/**
	 *	Every list on the page
	 *
	 *	@return		void
	 */
	function init() {

		var navs = document.querySelectorAll( SELECTOR );
		var taken = {};

		for( var i = 0; i < navs.length; i++ ) {

			/*	More than one list on a page would otherwise be more than one
				element with the same id, and aria-labelledby would then name
				whichever the browser found first	*/
			var title = navs[i].querySelector( '.nino-toc-title' );

			if( i > 0 && title !== null ) {
				title.id = 'nino-toc-title-' + ( i + 1 );
				navs[i].setAttribute( 'aria-labelledby', title.id );
			}

			var built = build( navs[i], taken );

			if( built !== null )
				lists.push( built );
		}

		if( lists.length === 0 )
			return;

		mark();
		window.addEventListener( 'scroll', schedule, { passive : true } );
		window.addEventListener( 'resize', schedule, { passive : true } );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
