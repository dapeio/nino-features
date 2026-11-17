( function( wn, dc ) {
	'use strict';
	try {
		var mode = wn.localStorage.getItem( 'nino-mode' );
		var root = dc.documentElement;
		var brand = wn.getComputedStyle( root ).getPropertyValue( '--brand-scheme' ).replace( /\s+/g, ' ' ).trim();
		if( ( mode === 'light' || mode === 'dark' ) && ( brand === 'light dark' || brand === 'dark light' ) )
			root.style.setProperty( '--brand-scheme', mode );
	}
	catch( e ) { /* storage refused: the visitor keeps the mode of the system */ }
} )( window, document );
