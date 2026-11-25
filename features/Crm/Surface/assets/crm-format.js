/**
 *	Crm		How the CRM writes money, days and times in the language of the page: Nino.crm.format. The file stands alone - it reads nothing of the surface and is read by nothing but the page that loads it - so that a page of
 *				a package below /crm that draws with a style sheet of its own can load it without the rest of the script. The language is the one of the page (the lang of the document), or the one a page tells it with locale().
 *
 *				  money( cents, currency? )		1.234,56 EUR in German, 1,234.56 EUR in English: the cents with the group and the decimal sign of the language, the currency behind a no-break space; none for the number alone
 *				  parseMoney( text )					What money( cents, '' ) writes, and the plain 1234.56 and 1234,56, back to cents; null for any other text and for one past the highest price
 *				  rate( hundredths )					A rate of hundredths of a percent: 19 % or 5,5 % (5.5 % in English)
 *				  day( iso )								A day Y-m-d, or a time that begins with it, as the language writes a day: 24.11.2026 in German, 11/24/2026 in English; '' for any other text
 *				  time( iso )								The time of day of a time of the server in the time of the browser, HH:MM; '' for none
 *				  stamp( iso )							The day, and the time of day unless the time is midnight
 *				  locale( code? )						The language: de_DE or en_US; with a code it is set (anything but de_DE is en_US)
 */

( function( wn, dc ) {

	'use strict';

	wn.Nino = wn.Nino || {};

	// Nino.crm is one object that stays: it cannot be assigned again, and the names under it are set once (see the points of the core)
	if( Object.prototype.hasOwnProperty.call( wn.Nino, 'crm' ) === false )
		Object.defineProperty( wn.Nino, 'crm', { value : {}, enumerable : true } );

	const NBSP = ' ';

	// The highest price, in cents: a thousand million
	const MAX_PRICE = 1000000000;

	let current = '';

	/**
	 *	@param		{string}	[code]
	 *
	 *	@return		{string}									de_DE or en_US
	 */
	const locale = function( code ) {

		if( typeof code === 'string' && code !== '' )
			current = code.toLowerCase().startsWith( 'de' ) === true ? 'de_DE' : 'en_US';

		if( current === '' ) {

			const lang = dc !== undefined && dc.documentElement !== undefined && dc.documentElement !== null ? String( dc.documentElement.lang ?? '' ) : '';

			return lang.toLowerCase().startsWith( 'de' ) === true ? 'de_DE' : 'en_US';
		}

		return current;
	};

	/**
	 *	@param		{number}	number
	 *	@param		{string}	sign						The sign between the groups of thousands
	 *
	 *	@return		{string}
	 */
	const group = function( number, sign ) {
		return String( number ).replace( /\B(?=(\d{3})+(?!\d))/g, sign );
	};

	const pad = function( number ) {
		return String( number ).padStart( 2, '0' );
	};

	/**
	 *	@param		{number}	cents
	 *	@param		{string}	[currency]			'' for the number alone
	 *
	 *	@return		{string}
	 */
	const money = function( cents, currency ) {

		const german = locale() === 'de_DE';
		const magnitude = Math.abs( cents );
		const text = ( cents < 0 ? '-' : '' )+ group( Math.floor( magnitude / 100 ), german ? '.' : ',' )+ ( german ? ',' : '.' )+ pad( magnitude % 100 );

		return currency === undefined || currency === '' ? text : text+ NBSP+ currency;
	};

	/**
	 *	A number of at most two decimals as a whole number of cents. A group of thousands is the one of the language; a number of one to three digits, a dot and three more (1.500) is neither a thousand nor one and a half, and no price
	 *
	 *	@param		{string}	text					1.234,56 and 1234,56 and 1234.56 and -12,5
	 *
	 *	@return		{number|null}						Cents; null for any other text and for one past the highest price
	 */
	const parseMoney = function( text ) {

		const typed = String( text ).trim();

		const grouped = locale() === 'de_DE' ? /^(-?)(\d{1,3}(?:\.\d{3})+),(\d{1,2})$/ : /^(-?)(\d{1,3}(?:,\d{3})+)\.(\d{1,2})$/;
		const plain = /^(-?)(\d{1,15})(?:[.,](\d{1,2}))?$/;
		let found = grouped.exec( typed );

		if( found !== null )
			found[2] = found[2].replace( /[.,]/g, '' );
		else {
			found = plain.exec( typed );

			if( found === null )
				return null;
		}

		const cents = Number( found[2]+ ( found[3] ?? '' ).padEnd( 2, '0' ) );

		return Number.isSafeInteger( cents ) === false || cents > MAX_PRICE ? null : ( found[1] === '-' ? -cents : cents );
	};

	/**
	 *	@param		{number}	hundredths				Hundredths of a percent
	 *
	 *	@return		{string}									19 %, 7 % or 5,5 %
	 */
	const rate = function( hundredths ) {

		const fraction = String( hundredths % 100 ).padStart( 2, '0' ).replace( /0+$/, '' );

		return Math.floor( hundredths / 100 )+ ( fraction === '' ? '' : ( locale() === 'de_DE' ? ',' : '.' )+ fraction )+ ' %';
	};

	/**
	 *	@param		{string}	iso							Y-m-d, or a time that begins with it
	 *
	 *	@return		{string}									The day as the language writes it; '' for none
	 */
	const day = function( iso ) {

		if( typeof iso !== 'string' || /^\d{4}-\d{2}-\d{2}/.test( iso ) === false )
			return '';

		return locale() === 'de_DE' ? iso.slice( 8, 10 )+ '.'+ iso.slice( 5, 7 )+ '.'+ iso.slice( 0, 4 ) : iso.slice( 5, 7 )+ '/'+ iso.slice( 8, 10 )+ '/'+ iso.slice( 0, 4 );
	};

	/**
	 *	@param		{string}	iso							A time of the server, Y-m-dTH:i:sZ
	 *
	 *	@return		{string}									The time of day in the time of the browser, HH:MM; '' for none
	 */
	const time = function( iso ) {

		const at = typeof iso === 'string' ? new Date( iso ) : null;

		return at === null || isNaN( at.getTime() ) === true ? '' : pad( at.getHours() )+ ':'+ pad( at.getMinutes() );
	};

	/**
	 *	@param		{string}	iso							A time of the server
	 *
	 *	@return		{string}									The day, and the time of day unless the time is midnight (a day somebody named)
	 */
	const stamp = function( iso ) {

		if( typeof iso !== 'string' || iso === '' )
			return '';

		return /T00:00:00Z$/.test( iso ) === true ? day( iso ) : day( iso )+ ' '+ time( iso );
	};

	Object.defineProperty( wn.Nino.crm, 'format', { value : Object.freeze( { locale : locale, money : money, parseMoney : parseMoney, rate : rate, day : day, time : time, stamp : stamp } ), enumerable : true } );

} )( window, document );
