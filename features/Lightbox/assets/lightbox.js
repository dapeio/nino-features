/**
 *	Nino - Lightbox
 *	lightbox.js	Opens a link that points at an image full screen instead of
 *						navigating to it, with every other link carrying the same
 *						data-lightbox value as the rest of the set.
 *
 *						No dependencies, no build step - not even on Nino.js: bundled
 *						into the project's own /.cache/script.js the same way the
 *						kernel bundles Nino.js/Nino.ui.js (see Lightbox::init()), so a
 *						project that dropped the kernel's own scripts still gets a
 *						working lightbox.
 *
 *						Delegated from the document, once: a gallery rendered after
 *						this ran - a filter, a "load more", an editor preview - needs
 *						no second call, and a page with two hundred thumbnails carries
 *						no two hundred listeners. The set a link belongs to is worked
 *						out when it is clicked, not when the page loaded, for the same
 *						reason.
 *
 *						What it never does is guess. A link is a lightbox link because
 *						it says so with data-lightbox, and its caption is what the page
 *						already says the picture is: data-caption, else the <img>'s
 *						alt, else the link's title. Nothing is read out of a filename.
 *
 *						This file is a static asset, never rendered through the fill
 *						engine (docs/development.md, "Assets Are Not Templates"), so
 *						there is no site-wide setting to read - what one lightbox does
 *						differently from another is an attribute on the link.
 */

( function( wn, dc ) {

	'use strict';

	// A link opts in with this. Its value is the group: two galleries on one
	// page are two sets, and an empty value is its own set of one
	var ATTR = 'data-lightbox';

	// What a link may point at. Checked against the href rather than trusting
	// the attribute alone: the overlay puts the value into an <img src>, and
	// a "lightbox link" to something that is not an image would otherwise be
	// a broken picture with no way back to the page
	var IMAGE = /\.(?:jpe?g|png|gif|webp|avif|svg)(?:[?#].*)?$/i;

	var box = null;			// the overlay, while one is open
	var set = [];				// the links of the current set, in document order
	var at = 0;					// which of them is on screen
	var opener = null;	// the link that opened it, for the focus to go back to

	/**
	 *	The lightbox link an event landed on, or null - the target itself or
	 *	whatever it sits inside, since the click is almost always on the <img>
	 *
	 *	@param		{EventTarget}	target
	 *
	 *	@return		{Element|null}
	 */
	function linkOf( target ) {

		var node = target;

		while( node && node !== dc ) {
			if( node.tagName === 'A' && node.hasAttribute( ATTR ) === true )
				return node;
			node = node.parentNode;
		}

		return null;
	}

	/**
	 *	Every link of one link's group, in the order the document has them -
	 *	read at click time, so a set built after this file ran is a set
	 *
	 *	@param		{Element}	link
	 *
	 *	@return		{Array}
	 */
	function setOf( link ) {

		var group = link.getAttribute( ATTR ) || '';

		if( group === '' )
			return [ link ];

		return Array.prototype.filter.call(
			dc.querySelectorAll( 'a[' + ATTR + ']' ),
			function( candidate ) {
				return ( candidate.getAttribute( ATTR ) || '' ) === group && IMAGE.test( candidate.getAttribute('href') || '' ) === true;
			}
		);
	}

	/**
	 *	What the page already says this picture is. Never the filename: a
	 *	caption nobody wrote is worse than no caption
	 *
	 *	@param		{Element}	link
	 *
	 *	@return		{string}
	 */
	function captionOf( link ) {

		var image = link.querySelector('img');

		return link.getAttribute('data-caption')
			|| ( image ? image.getAttribute('alt') : '' )
			|| link.getAttribute('title')
			|| '';
	}

	function button( className, label, onClick ) {

		var el = dc.createElement('button');
		el.type = 'button';
		el.className = 'nino-lightbox-btn ' + className;
		el.setAttribute( 'aria-label', label );
		el.addEventListener( 'click', function( ev ) {
			ev.preventDefault();
			ev.stopPropagation();
			onClick();
		} );

		return el;
	}

	/**
	 *	Build the overlay once per opening. The words come off the link that
	 *	opened it (data-label-*), because this file cannot read a textfill -
	 *	with English as the fallback, which is what the markup would have said
	 *	anyway
	 *
	 *	@param		{Element}	link
	 *
	 *	@return		void
	 */
	function open( link ) {

		set = setOf( link );
		at = set.indexOf( link );
		if( at === -1 ) {
			set = [ link ];
			at = 0;
		}
		opener = link;

		var labels = {
			close : link.getAttribute('data-label-close') || 'Close',
			prev : link.getAttribute('data-label-prev') || 'Previous image',
			next : link.getAttribute('data-label-next') || 'Next image',
		};

		box = dc.createElement('div');
		box.className = 'nino-lightbox';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-label', labels.close );

		var stage = dc.createElement('div');
		stage.className = 'nino-lightbox-stage';

		var figure = dc.createElement('figure');
		figure.className = 'nino-lightbox-figure';

		var image = dc.createElement('img');
		image.className = 'nino-lightbox-image';
		image.alt = '';

		var caption = dc.createElement('figcaption');
		caption.className = 'nino-lightbox-caption';

		figure.appendChild( image );
		figure.appendChild( caption );
		stage.appendChild( figure );

		var count = dc.createElement('span');
		count.className = 'nino-lightbox-count';
		if( set.length < 2 )
			count.hidden = true;
		stage.appendChild( count );

		stage.appendChild( button( 'nino-lightbox-close', labels.close, close ) );

		if( set.length > 1 ) {
			stage.appendChild( button( 'nino-lightbox-prev', labels.prev, function() { go( -1 ) } ) );
			stage.appendChild( button( 'nino-lightbox-next', labels.next, function() { go( 1 ) } ) );
		}

		box.appendChild( stage );

		box.image = image;
		box.caption = caption;
		box.count = count;

		// The backdrop closes, the picture does not: a click that lands on the
		// figure is somebody looking, not somebody leaving
		box.addEventListener( 'click', function( ev ) {
			if( ev.target === box || ev.target === stage )
				close();
		} );

		bindSwipe( box );

		dc.body.appendChild( box );
		dc.documentElement.classList.add('nino-lightbox-lock');

		show( at );

		// Faded in on the next frame, so the transition has a state to come
		// from - a class set in the same frame as the insert never animates
		wn.requestAnimationFrame( function() {
			if( box !== null )
				box.classList.add('is-open');
		} );

		// The focus goes into the dialog, and is kept there while it is open
		( box.querySelector('.nino-lightbox-close') || box ).focus();

		dc.addEventListener( 'keydown', onKey, true );
	}

	/**
	 *	Put one image of the set on screen
	 *
	 *	@param		{number}	index
	 *
	 *	@return		void
	 */
	function show( index ) {

		if( box === null )
			return;

		at = ( index + set.length ) % set.length;

		var link = set[at];
		var href = link.getAttribute('href') || '';

		box.classList.add('is-loading');
		box.caption.textContent = captionOf( link );
		box.count.textContent = ( at + 1 ) + ' / ' + set.length;

		// The onload is set before the src, or a cached image can finish
		// before there is anything listening for it
		box.image.onload = function() {
			if( box !== null ) {
				box.classList.remove('is-loading');
				box.classList.remove('is-swapping');
			}
		};
		box.image.onerror = box.image.onload;
		box.image.src = href;
		box.image.alt = box.caption.textContent;

		// The neighbour, quietly, so the next arrow press has nothing to wait
		// for. Never more than one in each direction: a set of forty images is
		// not forty downloads because somebody opened the first
		if( set.length > 1 )
			[ set[ ( at + 1 ) % set.length ], set[ ( at - 1 + set.length ) % set.length ] ].forEach( function( neighbour ) {
				var pre = new wn.Image();
				pre.src = neighbour.getAttribute('href') || '';
			} );
	}

	function go( by ) {

		if( box === null || set.length < 2 )
			return;

		box.classList.add('is-swapping');
		show( at + by );
	}

	function close() {

		if( box === null )
			return;

		var leaving = box;
		box = null;

		dc.removeEventListener( 'keydown', onKey, true );
		dc.documentElement.classList.remove('nino-lightbox-lock');
		leaving.classList.remove('is-open');

		// Taken out after the fade rather than on a timer that might outlive
		// the page: transitionend fires once, and the fallback is only there
		// for a browser that skipped the transition entirely
		var remove = function() {
			if( leaving.parentNode !== null )
				leaving.parentNode.removeChild( leaving );
		};
		leaving.addEventListener( 'transitionend', remove );
		wn.setTimeout( remove, 400 );

		if( opener !== null ) {
			opener.focus();
			opener = null;
		}
	}

	/**
	 *	Escape closes, the arrows move, and Tab stays inside: a dialog the
	 *	keyboard can walk out of is a dialog the page behind it can be
	 *	operated through
	 *
	 *	@param		{KeyboardEvent}	ev
	 *
	 *	@return		void
	 */
	function onKey( ev ) {

		if( box === null )
			return;

		if( ev.key === 'Escape' ) {
			ev.preventDefault();
			return close();
		}

		if( ev.key === 'ArrowRight' ) {
			ev.preventDefault();
			return go( 1 );
		}

		if( ev.key === 'ArrowLeft' ) {
			ev.preventDefault();
			return go( -1 );
		}

		if( ev.key !== 'Tab' )
			return;

		var focusable = box.querySelectorAll('button');
		if( focusable.length === 0 )
			return;

		var first = focusable[0];
		var last = focusable[ focusable.length - 1 ];

		if( ev.shiftKey === true && dc.activeElement === first ) {
			ev.preventDefault();
			last.focus();
		} else if( ev.shiftKey !== true && dc.activeElement === last ) {
			ev.preventDefault();
			first.focus();
		}
	}

	/**
	 *	Swipe left and right on a touch screen. Horizontal only, and only
	 *	past a distance a scroll would not travel sideways - otherwise
	 *	scrolling a tall picture flips to the next one
	 *
	 *	@param		{Element}	el
	 *
	 *	@return		void
	 */
	function bindSwipe( el ) {

		var startX = 0;
		var startY = 0;

		el.addEventListener( 'touchstart', function( ev ) {
			startX = ev.changedTouches[0].clientX;
			startY = ev.changedTouches[0].clientY;
		}, { passive : true } );

		el.addEventListener( 'touchend', function( ev ) {

			var byX = ev.changedTouches[0].clientX - startX;
			var byY = ev.changedTouches[0].clientY - startY;

			if( Math.abs( byX ) > 48 && Math.abs( byX ) > Math.abs( byY ) )
				go( byX < 0 ? 1 : -1 );
		}, { passive : true } );
	}

	// One listener for the whole document, for every lightbox link there is
	// now or will be. A modified click is the visitor asking for the image in
	// a tab of its own, which is exactly what the href already does
	dc.addEventListener( 'click', function( ev ) {

		if( ev.defaultPrevented === true || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey )
			return;

		var link = linkOf( ev.target );

		if( link === null || IMAGE.test( link.getAttribute('href') || '' ) === false )
			return;

		ev.preventDefault();
		open( link );
	} );

} )( window, document );
