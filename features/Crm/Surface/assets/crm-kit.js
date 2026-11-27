/**
 *	Crm		The surface of the CRM in the browser, the first of its four files: the shell. The page at /crm draws its screens into the main region from the data of one JSON block (the words, the token, the user, the links) and the
 *				answers of its endpoint; it is not part of the workbench and loads none of its scripts. Every string that comes from the server or from a person enters the page as textContent or as a property of an element;
 *				nothing here builds HTML.
 *
 *				  the state		what the page remembers (state) and starts again at boot (reset)
 *				  the model		pure functions: the days and times (Nino.crm.format), the links of a mail address and a phone number, the label of a project, the title of a client, the sentences of errors
 *				  the shell		the element helper (every element is made from data, no markup is written), the toast, the menus, the dialogs (<dialog>), the post to the endpoint (the session that ended, the token that is
 *								stale), the forms and the lists the screens share, the picker of a client, the search of the overview, the card of the device and the hint for the home screen
 *
 *				The files of the core load in this order: crm-format.js (Nino.crm.format, which stands alone), this file, crm-points.js (the registration points a package uses), crm-screens.js (the screens of the core, registered through
 *				the points) and crm-boot.js (the router, the keys and the start). What they share while the page loads is Nino.crm.core, which the last of them takes away.
 */

( function( wn, dc ) {

	'use strict';

	wn.Nino = wn.Nino || {};

	if( Object.prototype.hasOwnProperty.call( wn.Nino, 'crm' ) === false )
		Object.defineProperty( wn.Nino, 'crm', { value : {}, enumerable : true } );

	// What the files of the core share with one another while the page loads; the last file takes it away
	const core = {};

	wn.Nino.crm.core = core;

	// A name under Nino.crm: set once, frozen with what it holds, never assigned again
	core.expose = function( name, api ) {

		Object.defineProperty( wn.Nino.crm, name, { value : Object.freeze( api ), enumerable : true } );

		return wn.Nino.crm[name];
	};

	const PAGE_SIZE = 50;
	// The width under which the lists are cards and an entry of the draft stands in a stack; the stylesheet has the same number
	const PHONE = '(max-width: 759px)';
	// How many hits of each kind the search of the overview lists
	const SEARCH_MAX = 5;
	const PICK_MAX = 10;
	// An address a package may give: a hash of the page or a path of this site, never a second host
	const ADDRESS = /^(?:#|\/(?!\/))[^\s\\\u0000-\u001f\u007f]*$/;
	const CLIENT_FIELDS = [ 'company', 'name', 'street', 'zip', 'city', 'country', 'email', 'phone', 'vat_id', 'buyer_reference' ];
	const RELOAD_KEY = 'crm-reloaded';
	const HOME_KEY = 'crm-surface-homescreen';
	// The properties the element helper sets as properties; every other key is an attribute
	const PROPERTIES = [ 'value', 'checked', 'disabled', 'hidden', 'selected', 'title', 'placeholder', 'type', 'id', 'name', 'rows', 'src', 'tabIndex', 'max', 'min' ];
	const BOOLEANS = [ 'checked', 'disabled', 'hidden', 'selected' ];
	const state = {};
	/**
	 *	Everything the page remembers; boot() starts it again
	 *
	 *	@return		{void}
	 */
	const reset = function() {

		Object.keys( state ).forEach( function( key ) { delete state[key] } );

		Object.assign( state, {
			base : '', day : '', csrf : '', perm : '', locale : 'en_US', perms : { crm : true, elements : false, text : false }, user : '', texts : {}, links : {}, packages : {},
			settings : {}, clients : [], summary : null, people : [], erasing : [], wiping : false,
			cfilter : { q : '', sort : 'number', dir : 1, page : 1 },
			row : null, chord : false,
			wfilter : { q : '', page : 1 }, wbody : null, phases : [], jclients : [], jpick : null, jfilter : { q : '', all : false, client : '', phase : '', page : 1 }, jbody : null, busy : null, saver : {}, company : null,
			app : '', device : { remembered : false, count : 0 }, follow : null, view : 0, entered : false, seen : null, tab : '', at : '', leaving : '', draft : null, saving : false, queued : false, pending : null, mailnotes : null, settingsRedraw : null, settingsInput : null, tabFocus : false, timer : null, main : null, toast : null, dialogs : null, booted : false, lit : '',
		} );
	};
	reset();
	// --- the element helper ------------------------------------------------------------------------------------------

	/**
	 *	Put children into an element: strings and numbers as text, arrays flattened, nothing for null, undefined and false
	 *
	 *	@param		{Object}	node
	 *	@param		{Array}		kids
	 *
	 *	@return		{void}
	 */
	const append = function( node, kids ) {

		kids.forEach( function( kid ) {

			if( kid === null || kid === undefined || kid === false )
				return;

			if( Array.isArray( kid ) === true )
				append( node, kid );
			else if( typeof kid === 'string' || typeof kid === 'number' )
				node.appendChild( dc.createTextNode( String( kid ) ) );
			else
				node.appendChild( kid );
		} );
	};
	/**
	 *	An element: the tag, its attributes and properties as an object (class, text, dataset, on for the listeners, the
	 *	names of PROPERTIES as properties, the rest - href among them - as attributes) and its children
	 *
	 *	@param		{string}	tag
	 *	@param		{Object}	[attrs]
	 *	@param		{...*}		kids
	 *
	 *	@return		{Object}
	 */
	const el = function( tag, attrs, ...kids ) {

		const node = dc.createElement( tag );
		const later = [];

		Object.keys( attrs || {} ).forEach( function( key ) {

			const value = attrs[key];

			if( value === null || value === undefined || ( value === false && BOOLEANS.includes( key ) === true ) )
				return;

			if( key === 'class' )
				node.className = value;
			else if( key === 'text' )
				node.textContent = String( value );
			else if( key === 'dataset' )
				Object.keys( value ).forEach( function( name ) { node.dataset[name] = String( value[name] ) } );
			else if( key === 'on' )
				Object.keys( value ).forEach( function( name ) { node.addEventListener( name, value[name] ) } );
			else if( key === 'value' )
				later.push( [ key, String( value ) ] );
			else if( PROPERTIES.includes( key ) === true )
				node[key] = BOOLEANS.includes( key ) === true || typeof value === 'number' ? value : String( value );
			else
				node.setAttribute( key, String( value ) );
		} );

		append( node, kids );

		// A select takes its value when its options are there
		later.forEach( function( pair ) { node[pair[0]] = pair[1] } );

		return node;
	};
	/**
	 *	@param		{Object}	node
	 *
	 *	@return		{void}										Nothing left in it
	 */
	const clear = function( node ) {

		node.textContent = '';

		while( node.firstChild !== null && node.firstChild !== undefined )
			node.removeChild( node.firstChild );
	};
	/**
	 *	@param		{string}	key
	 *	@param		{...*}		params			Values for the %s of the word
	 *
	 *	@return		{string}									The word; its key where there is none, which a test would catch
	 */
	const t = function( key, ...params ) {

		const word = state.texts[key];

		if( typeof word !== 'string' )
			return key;

		let at = 0;

		return word.replace( /%s/g, function() {
			const value = params[at++];
			return value === undefined ? '' : String( value );
		} );
	};
	// --- the model ---------------------------------------------------------------------------------------------------

	const pad = function( number ) { return String( number ).padStart( 2, '0' ) };
	/**
	 *	@param		{Date}		[date]
	 *
	 *	@return		{string}									Y-m-d; without a date the day of the server that made the page (a clock that is ahead or behind does not make a day the server refuses), else the browser's own time
	 */
	const today = function( date ) {

		if( date === undefined && typeof state.day === 'string' && state.day !== '' )
			return state.day;

		const at = date || new Date();
		return at.getFullYear()+ '-'+ pad( at.getMonth()+ 1 )+ '-'+ pad( at.getDate() );
	};
	/**
	 *	@param		{string}	iso							Y-m-d, or a time that begins with it
	 *
	 *	@return		{string}									d.m.Y; '' for none
	 */
	const dmy = function( iso ) {
		return wn.Nino.crm.format.day( iso );
	};
	/**
	 *	@param		{string}	iso							A time of the server, Y-m-dTH:i:sZ
	 *
	 *	@return		{string}									The time of day in the browser's time, HH:MM; '' for none
	 */
	const hm = function( iso ) {
		return wn.Nino.crm.format.time( iso );
	};
	/**
	 *	@param		{string}	iso							A time of the server
	 *
	 *	@return		{string}									The day, and the time of day unless the time is midnight (a day somebody named)
	 */
	const stamp = function( iso ) {
		return wn.Nino.crm.format.stamp( iso );
	};
	/**
	 *	The link of an address: only an address of the plain shape is one - letters, digits and . _ + - before the @ and a name with a dot after it - so that nothing a person typed can carry
	 *	a second part of a link (a query, a second address) into the href
	 *
	 *	@param		{*}				email
	 *
	 *	@return		{string}									mailto: and the address; '' for a text that is none
	 */
	const mailtoOf = function( email ) {
		return typeof email === 'string' && /^[A-Za-z0-9._+-]{1,64}@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/.test( email.trim() ) === true ? 'mailto:'+ email.trim() : '';
	};
	/**
	 *	The link of a phone number: the number as the clients keep it - digits, blanks and + ( ) / . -, a + only first - cut down to its digits and the +
	 *
	 *	@param		{*}				phone
	 *
	 *	@return		{string}									tel: and the number; '' for a text that is none
	 */
	const telOf = function( phone ) {

		if( typeof phone !== 'string' || /^[0-9+][0-9 +()\/.-]*$/.test( phone.trim() ) === false )
			return '';

		// The (0) that is written after a country code is no part of the number
		const number = phone.trim().charAt( 0 ) === '+' ? phone.replace( /\(0\)/, '' ).replace( /[^0-9+]/g, '' ) : phone.replace( /[^0-9+]/g, '' );

		return /^\+?[0-9]{3,20}$/.test( number ) === true ? 'tel:'+ number : '';
	};
	/**
	 *	The name a project is shown by: P- and its number in four digits (P-0001, five from 10000 on), as K-0001 is a client's. The id stays what the address and the log hold; an id that is no number is shown as it is
	 *
	 *	@param		{string}	id							The id of a project, '' for none
	 *
	 *	@return		{string}									P-0001; the id as it is where it is no number from 1 to 999999999
	 */
	const projectLabel = function( id ) {

		const text = String( id ?? '' );

		return /^[0-9]{1,9}$/.test( text ) === true && parseInt( text, 10 ) > 0 ? 'P-'+ String( parseInt( text, 10 ) ).padStart( 4, '0' ) : text;
	};
	/**
	 *	A project in a line of text: its name (P-0001) and its title, the name alone where the title is empty
	 *
	 *	@param		{string}	id
	 *	@param		{string}	title
	 *
	 *	@return		{string}
	 */
	const projectText = function( id, title ) {
		return projectLabel( id )+ ( title === undefined || title === null || title === '' ? '' : ' '+ title );
	};
	// --- the shell ---------------------------------------------------------------------------------------------------

	/**
	 *	Say something in the toast
	 *
	 *	@param		{string}	message
	 *	@param		{boolean}	[bad]						A refusal stays longer
	 *
	 *	@return		{void}
	 */
	const toast = function( message, bad ) {

		const node = state.toast;

		node.textContent = message;
		node.classList.add( 'show' );
		node.classList.toggle( 'bad', bad === true );
		wn.clearTimeout( state.timer );
		state.timer = wn.setTimeout( function() { node.classList.remove( 'show' ) }, bad === true ? 6000 : 2600 );
	};
	/**
	 *	The sentence for an answer that failed: the one of its code, with its params; else the word for what was being done
	 *
	 *	@param		{Object}	answer
	 *	@param		{string}	[fallback]			A key of the words
	 *
	 *	@return		{string}
	 */
	const errorText = function( answer, fallback ) {

		const body = answer.body !== null && typeof answer.body === 'object' ? answer.body : {};

		if( answer.status === 0 || body.code === 'offline' )
			return t( 'error.offline' );

		if( typeof body.code === 'string' && /^[a-z][a-z0-9_]*$/.test( body.code ) === true && typeof state.texts['error.'+ body.code] === 'string' ) {

			const said = t( 'error.'+ body.code, ...( Array.isArray( body.params ) === true ? body.params : [] ) );

			// A veto is the sentence of the one who holds the element back, as it wrote it: it is text, here and wherever the answer is shown
			return body.code === 'crm_elements_vetoed' && typeof body.error === 'string' && body.error !== '' ? said+ ' '+ body.error : said;
		}

		return answer.status === 403 ? t( 'error.forbidden' ) : t( fallback || 'error.request' );
	};
	/**
	 *	Tell a person a request failed, unless the page has said already why (the session)
	 *
	 *	@param		{Object}	answer
	 *	@param		{string}	[fallback]
	 *
	 *	@return		{string}									The sentence
	 */
	const failed = function( answer, fallback ) {

		const text = errorText( answer, fallback );

		if( answer.handled !== true )
			toast( text, true );

		return text;
	};
	/**
	 *	The block of a link that is shown once: a field that cannot be typed in, the button that copies it (where the browser has a clipboard; else the field is
	 *	selected for the person to copy) and the sentence that it is shown now and kept nowhere
	 *
	 *	@param		{Object}	spec						label, value, note
	 *
	 *	@return		{Object}									node and field
	 */
	const shownLink = function( spec ) {

		const field = el( 'input', { id: 'dlg-link', type: 'text', readonly: 'readonly', 'aria-label': spec.label, value: spec.value } );
		const line = el( 'span', { class: 'state-note', id: 'dlg-link-state', role: 'status' } );
		const copy = el( 'button', { type: 'button', class: 'btn sm', id: 'dlg-link-copy', text: t( 'shown.copy' ), on: { click: function() {

			field.focus();
			field.select();

			if( wn.navigator !== undefined && wn.navigator.clipboard !== undefined && typeof wn.navigator.clipboard.writeText === 'function' )
				wn.navigator.clipboard.writeText( field.value ).then( function() { line.textContent = t( 'shown.copied' ) }, function() { line.textContent = t( 'shown.selected' ) } );
			else
				line.textContent = t( 'shown.selected' );
		} } } );

		field.readOnly = true;

		return { field : field, node : el( 'div', { class: 'secret' }, el( 'label', { class: 'field' }, el( 'span', { text: spec.label } ), field ), el( 'div', { class: 'actions' }, copy, line ), el( 'p', { class: 'state-note', text: spec.note } ) ) };
	};
	/**
	 *	A dialog with one question: the text, optionally fields, and two buttons. Escape and the second button are the no
	 *
	 *	@param		{Object}	spec						title, text, facts (pairs of a label and a value, shown as text beneath the text), pre, yes, no, danger, fields ([ { name, label, type, value, options, maxlength, rows } ]; the type checkbox is a box, ticked where the value is true, and its value is a boolean; the type textarea is a field of several lines), shortcut (true: Ctrl+S, Cmd+S says yes - for a dialog that is a form to save), link ({ href, text } instead of the buttons),
	 *														secret ({ label, value, note }: a link shown once, in a field nobody can type in, with a button that copies it - and the dialog closes with a button),
	 *														run (a function of the values that answers a promise, for a dialog that does its step before it closes: a string is the sentence of a refusal, said in the dialog, which stays open; { secret, note }
	 *														is the link of the step, shown once in the place of the form, with the sentence note beneath it; anything else closes the dialog with the values). A field of the type radio has options ([ { value, label } ]) and its value is the one that is chosen; a field of the type client has the same options and is a picker of a client;
	 *														a field with needs (the name of a box of the dialog) is off while that box is not ticked; follow ({ field, on, off }: the name of a box and the words of the yes while it is ticked and while it is not) lets the button say what the box makes of it;
	 *														setup (a function of the controls and of put(), which puts a node of its own in the form above the buttons) lets a dialog tie its fields together - the folder that depends on the client -, and a field has maxlength for the text it takes and autocomplete for the browser's help, hint (a sentence under it) and count (the longest text it takes: the field says how many characters it holds of them), and a field with needs and hide is gone, not only off, while that box is not ticked;
	 *														body (a node or a list of nodes between the text and the fields); unlock ({ field, word }: the yes is off until the field holds the word exactly, and while a step runs neither the no nor Escape closes the dialog)
	 *
	 *	@return		{Promise}									The values of the fields (an empty object without fields) for the yes; null for the no
	 */
	const dialog = function( spec ) {

		return new Promise( function( resolve ) {

			let answer = null;
			let closed = false;
			let decided = false;
			let running = false;
			let yes = null;
			let no = null;
			let actions = null;
			const controls = {};
			const labels = {};
			const problem = el( 'p', { class: 'state-note bad', id: 'dlg-problem', role: 'alert', hidden: true } );

			const box = el( 'dialog', { class: spec.wide === true ? 'dlg wide' : 'dlg', 'aria-label': spec.title || spec.text } );
			const finish = function( value ) {

				if( decided === true )
					return;

				decided = true;
				answer = value;
				box.close();
			};

			// A dialog that asks for a word is not answered with another: the yes is off, and Enter in the field does not press it
			const locked = function() {
				return spec.unlock !== undefined && controls[spec.unlock.field] !== undefined && controls[spec.unlock.field].value !== spec.unlock.word;
			};

			const accept = function() {

				if( locked() === true )
					return;

				const values = {};

				Object.keys( controls ).forEach( function( name ) { values[name] = controls[name].type === 'checkbox' ? controls[name].checked === true : controls[name].value } );

				if( spec.run === undefined )
					finish( values );
				else
					step( values );
			};

			// A dialog with a step of its own: the yes is off while the step runs, a refusal is said in the dialog and the dialog stays
			const step = function( values ) {

				if( running === true )
					return;

				running = true;
				yes.disabled = true;
				problem.hidden = true;

				if( spec.unlock !== undefined )
					no.disabled = true;

				spec.run( values ).then( function( result ) {

					running = false;
					yes.disabled = locked();

					if( spec.unlock !== undefined )
						no.disabled = false;

					if( typeof result === 'string' ) {
						problem.textContent = result;
						problem.hidden = false;
						return;
					}

					if( result === null || typeof result !== 'object' || result.secret === undefined ) {
						finish( values );
						return;
					}

					// The link of the step takes the place of the form: it is shown once, and the dialog takes it along when it closes
					secret = shownLink( result.secret );
					clear( form );
					form.appendChild( el( 'h2', { text: spec.title } ) );
					form.appendChild( secret.node );
					form.appendChild( el( 'p', { class: 'state-note', id: 'dlg-note', text: result.note } ) );
					form.appendChild( el( 'div', { class: 'actions end' }, el( 'button', { type: 'button', class: 'btn primary', text: t( 'dialog.close' ), on: { click: function() { finish( values ) } } } ) ) );
				} );
			};

			const fields = ( spec.fields || [] ).map( function( field ) {

				if( field.type === 'checkbox' ) {

					const box = el( 'input', { id: 'dlg-'+ field.name, type: 'checkbox', checked: field.value === true } );

					controls[field.name] = box;

					return el( 'label', { class: 'check' }, box, ' '+ field.label );
				}

				if( field.type === 'radio' ) {

					const inputs = field.options.map( function( option, index ) {
						return el( 'input', { type: 'radio', name: 'dlg-'+ field.name, value: option.value, checked: field.value === undefined ? index === 0 : option.value === field.value } );
					} );
					const group = el( 'fieldset', { class: 'choice', id: 'dlg-'+ field.name }, el( 'legend', { text: field.label } ), field.options.map( function( option, index ) {
						return el( 'label', { class: 'check' }, inputs[index], ' '+ option.label );
					} ) );

					// The group answers as one control: its value is the one that is chosen
					controls[field.name] = { type : 'radio', get value() { const chosen = inputs.find( function( input ) { return input.checked === true } ); return chosen === undefined ? '' : chosen.value } };

					return group;
				}

				// A client is chosen in a picker, the list is drawn in the flow of the dialog; the field answers with the value of the client that is chosen, and its change is a choice or a let go
				if( field.type === 'client' ) {

					const picker = clientField( { id: 'dlg-'+ field.name }, field.options.map( function( option ) { return { value : option.value, text : option.label, label : option.label } } ), field.value );

					controls[field.name] = {
						type : 'client',
						get value() { return picker.value() },
						set value( value ) { picker.set( value ) },
						addEventListener : function( name, fn ) { if( name === 'change' ) picker.listen( fn ) },
					};

					return labels[field.name] = el( 'div', { class: 'field' }, el( 'span', {}, el( 'label', { for: 'dlg-'+ field.name, text: field.label } ) ), picker.node );
				}

				const control = field.options !== undefined
					? el( 'select', { id: 'dlg-'+ field.name }, field.options.map( function( option ) { return el( 'option', { value: option.value, selected: option.value === field.value, text: option.label } ) } ) )
					: ( field.type === 'textarea'
						? el( 'textarea', { id: 'dlg-'+ field.name, rows: field.rows ?? 6, maxlength: field.maxlength ?? null, value: field.value ?? '' } )
						: el( 'input', { id: 'dlg-'+ field.name, type: field.type || 'text', value: field.value ?? '', min: field.min, max: field.max, maxlength: field.maxlength ?? null, autocomplete: field.autocomplete ?? null } ) );

				controls[field.name] = control;

				if( field.hint !== undefined )
					control.setAttribute( 'aria-describedby', 'dlg-'+ field.name+ '-hint' );

				return labels[field.name] = el( 'label', { class: 'field' }, el( 'span', { text: field.label } ), control, field.hint === undefined ? null : el( 'span', { class: 'state-note', id: 'dlg-'+ field.name+ '-hint', text: field.hint } ), field.count === undefined ? null : charCount( control, field.count, 'dlg-'+ field.name+ '-count' ) );
			} );

			let secret = spec.secret === undefined ? null : shownLink( spec.secret );

			box.addEventListener( 'close', function() {

				// A close request that came without a cancel (a second Escape in the browser) does not end a step that runs: the dialog is shown again, with the answer still to come
				if( running === true && decided === false && spec.unlock !== undefined && closed === false ) {
					box.showModal();
					return;
				}

				if( closed === true )
					return;

				closed = true;

				// A link that was shown is gone with the dialog: not the field, nor the text of it
				if( secret !== null )
					secret.field.value = '';

				if( box.parentNode !== null )
					box.parentNode.removeChild( box );

				resolve( answer );
			} );

			const form = el( 'form', { on: { submit: function( event ) { event.preventDefault(); accept() } } },
				spec.title ? el( 'h2', { text: spec.title } ) : null,
				spec.text === undefined ? null : el( 'p', { text: spec.text } ),
				spec.body === undefined ? null : spec.body,
				spec.facts === undefined ? null : kv( spec.facts ),
				spec.pre === undefined ? null : el( 'pre', { class: 'sheet', text: spec.pre } ),
				secret === null ? null : secret.node,
				fields,
				spec.run === undefined ? null : problem,
				actions = el( 'div', { class: 'actions end' },
					spec.link !== undefined
						? el( 'a', { class: 'btn primary', href: spec.link.href, target: spec.link.blank === true ? '_blank' : null, rel: spec.link.blank === true ? 'noopener' : null, text: spec.link.text, on: { click: function( event ) {
							if( spec.link.reload === true ) {
								event.preventDefault();
								wn.location.reload();
							}
						} } } )
						: spec.close === true ? el( 'button', { type: 'button', class: 'btn primary', text: t( 'dialog.close' ), on: { click: function() { finish( null ) } } } ) : [
							no = el( 'button', { type: 'button', class: 'btn', text: spec.no || t( 'dialog.no' ), on: { click: function() { finish( null ) } } } ),
							yes = el( 'button', { type: 'button', class: 'btn '+ ( spec.danger === true ? 'danger' : 'primary' ), text: spec.yes || t( 'dialog.yes' ), on: { click: accept } } ),
						] ) );

			// The word: the yes waits for it, and a step that runs is not given up by Escape
			if( spec.unlock !== undefined && controls[spec.unlock.field] !== undefined && yes !== null ) {

				const gate = function() { yes.disabled = running === true || locked() };

				controls[spec.unlock.field].addEventListener( 'input', gate );
				gate();
				box.addEventListener( 'cancel', function( event ) {
					if( running === true )
						event.preventDefault();
				} );
			}

			// A field that needs a box is off while the box is not ticked, and the yes says what the box makes of it
			( spec.fields || [] ).filter( function( field ) { return field.needs !== undefined && controls[field.needs] !== undefined && controls[field.name] !== undefined } ).forEach( function( field ) {
				const sync = function() {
					controls[field.name].disabled = controls[field.needs].checked !== true;

					if( field.hide === true && labels[field.name] !== undefined )
						labels[field.name].hidden = controls[field.needs].checked !== true;
				};

				controls[field.needs].addEventListener( 'change', sync );
				sync();
			} );

			if( typeof spec.setup === 'function' )
				spec.setup( controls, function( node ) { form.insertBefore( node, actions ) } );

			if( spec.follow !== undefined && controls[spec.follow.field] !== undefined && yes !== null ) {

				const word = function() { yes.textContent = controls[spec.follow.field].checked === true ? spec.follow.on : spec.follow.off };

				controls[spec.follow.field].addEventListener( 'change', word );
				word();
			}

			// A dialog that is a form to save takes Ctrl+S (Cmd+S) as its yes; the browser's own save of the page is kept off
			if( spec.shortcut === true )
				box.addEventListener( 'keydown', function( event ) {
					if( ( event.ctrlKey === true || event.metaKey === true ) && typeof event.key === 'string' && event.key.toLowerCase() === 's' ) {
						event.preventDefault();
						accept();
					}
				} );

			box.appendChild( form );
			state.dialogs.appendChild( box );
			box.showModal();
		} );
	};
	/**
	 *	@param		{Object}	spec						See dialog()
	 *
	 *	@return		{Promise}									true for the yes
	 */
	const ask = function( spec ) {
		return dialog( spec ).then( function( values ) { return values !== null } );
	};
	/**
	 *	The box of a link that may be shown instead of mailed: only where the account holds the permission of the package that makes the link (state.perms[kind]). A link without the mail acts as the
	 *	customer's account, so the permission of the CRM alone does not offer it
	 *
	 *	@param		{string}	kind
	 *	@param		{string}	label					The key of the words of the box
	 *
	 *	@return		{Array}										The fields of a dialog: the box, or none
	 */
	const quietField = function( kind, label ) {
		return state.perms[kind] === true ? [ { name : 'notify', label : t( label ), type : 'checkbox', value : true } ] : [];
	};
	/**
	 *	The count under a text field that has a longest text: how many characters it holds of the most it takes, kept as the field is typed in, in the red where it holds more. A character is a character of
	 *	the text, not a unit of the string, as the server counts it; the field has no maxlength of its own, which counts units, so the server's limit is the one that refuses
	 *
	 *	@param		{Object}	control				The field
	 *	@param		{number}	max
	 *	@param		{string}	id
	 *
	 *	@return		{Object}									The note
	 */
	const charCount = function( control, max, id ) {

		const note = el( 'span', { class: 'state-note msg-count', id: id } );
		const show = function() {
			const length = Array.from( control.value ).length;

			note.textContent = t( 'form.count', length, max );
			note.classList.toggle( 'bad', length > max );
		};

		control.addEventListener( 'input', show );
		show();

		return note;
	};
	/**
	 *	The sentence of a dialog: its own where there is a box, and where there is none the sentence without the word of the box and the one that says why the link
	 *	goes by mail only
	 *
	 *	@param		{string}	kind
	 *	@param		{string}	key						The key of the sentence; the one without the box is the key with -plain
	 *
	 *	@return		{string}
	 */
	const quietText = function( kind, key ) {
		return state.perms[kind] === true ? t( key ) : t( key+ '-plain' )+ ' '+ t( 'notify.noperm-'+ kind );
	};
	/**
	 *	Show a link the server made without a mail, once, in a dialog of its own: it is in no screen and in no list, and the dialog takes it along when it closes
	 *
	 *	@param		{string}	kind						What the link is for: the words of the dialog are shown.title-<kind> and shown.text-<kind>, which a package brings
	 *	@param		{string}	link
	 *
	 *	@return		{Promise}
	 */
	const showLink = function( kind, link ) {

		// A link that is not there is no dialog
		if( typeof link !== 'string' || link === '' )
			return Promise.resolve( null );

		return dialog( { title: t( 'shown.title-'+ kind ), text: t( 'shown.text-'+ kind ), secret: { label: t( 'shown.field' ), value: link, note: t( 'shown.once' ) }, close: true } );
	};
	/**
	 *	The page tells a session that ended: a dialog with the link to this page, which shows its login where nobody is signed in (the link keeps the hash,
	 *	and a click reads the page again, since a link to the address it is at would only move to the hash)
	 *
	 *	@return		{void}
	 */
	const sessionLost = function() {

		if( state.lost === true )
			return;

		state.lost = true;
		dialog( { title: t( 'session.title' ), text: t( 'session.text' ), link: { href: state.base+ ( core.appOf() === null ? '/crm' : core.appOf().route )+ wn.location.hash, text: t( 'session.link' ), reload : true } } ).then( function() { state.lost = false } );
	};
	/**
	 *	Post to the endpoint and read the answer: the session that ended and the token that is stale are said by the page, not by the screen
	 *
	 *	@param		{Object}	init						headers and body of the request
	 *
	 *	@return		{Promise}									{ ok, status, body, handled }; a request that did not arrive has status 0
	 */
	const post = function( init, path ) {

		return wn.fetch( state.base+ ( path || '/crm/api' ), Object.assign( { method : 'POST', credentials : 'same-origin' }, init ) ).then( function( response ) {

			return response.json().then( function( body ) { return body }, function() { return null } ).then( function( body ) {

				const answer = { ok : response.status >= 200 && response.status < 300, status : response.status, body : body, handled : false };
				const code = body !== null && typeof body === 'object' ? body.code : '';

				if( answer.status === 401 && code === 'session' ) {
					answer.handled = true;
					sessionLost();
				}

				// A token that is no longer this session's: the page is read again, once; what it still gets then is the person's to read
				if( answer.status === 403 && code === 'csrf' ) {

					try {
						if( wn.sessionStorage.getItem( RELOAD_KEY ) !== '1' ) {
							wn.sessionStorage.setItem( RELOAD_KEY, '1' );
							wn.location.reload();
							answer.handled = true;
						}
					}
					catch( e ) {
						answer.handled = false;
					}
				}

				if( answer.ok === true ) {
					try {
						wn.sessionStorage.removeItem( RELOAD_KEY );
					}
					catch( e ) {
						// Storage is not there: no flag was set, none needs to go
					}
				}

				return answer;
			} );
		}, function() {
			return { ok : false, status : 0, body : { code : 'offline' }, handled : false };
		} );
	};
	/**
	 *	Post an action to the endpoint
	 *
	 *	@param		{string}	action					eg. crm-clients/list
	 *	@param		{Object}	[data]
	 *
	 *	@return		{Promise}									See post()
	 */
	const api = function( action, data ) {
		return post( { headers : { 'Content-Type' : 'application/json', 'X-CSRF-Token' : state.csrf }, body : JSON.stringify( { action : action, data : data || {} } ) } );
	};
	const closeMenus = function( except ) {

		dc.querySelectorAll( '.menu' ).forEach( function( menu ) {
			if( menu !== except ) {
				menu.classList.remove( 'open' );
				const button = menu.querySelector( 'button' );
				if( button !== null )
					button.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	};
	/**
	 *	A menu: a button and the list under it
	 *
	 *	@param		{Object}	spec						label, aria (what a screen reader calls the button), button (the class of the button), align, tall (a long list that scrolls), items ([ { label, run, href, saved, danger } | { heading } | 'sep' ])
	 *
	 *	@return		{Object}
	 */
	const menu = function( spec ) {

		const root = el( 'div', { class: 'menu' } );
		// The items that can be chosen now: the keys move among them
		const choices = function() {
			return Array.from( root.querySelectorAll( '[role="menuitem"]' ) ).filter( function( item ) { return item.disabled !== true } );
		};
		const show = function( open, focus ) {
			closeMenus( root );
			root.classList.toggle( 'open', open );
			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

			if( open === true && focus !== undefined && choices().length > 0 )
				choices().at( focus ).focus();
		};
		const button = el( 'button', { type: 'button', class: 'btn '+ ( spec.button || '' ), 'aria-haspopup': 'true', 'aria-expanded': 'false', 'aria-label': spec.aria, text: spec.label, on: { click: function( event ) {
			const open = root.classList.contains( 'open' ) === false;
			// A click of the keyboard (Enter, Space) has no position: the focus goes into the list, as it does for a menu
			show( open, open === true && event.detail === 0 ? 0 : undefined );
		} } } );
		root.addEventListener( 'keydown', function( event ) {

			const open = root.classList.contains( 'open' );
			const items = choices();
			const at = items.indexOf( event.target );

			if( event.key === 'Escape' && open === true ) {
				show( false );
				button.focus();
			}
			else if( [ 'ArrowDown', 'ArrowUp', 'Home', 'End' ].includes( event.key ) === true && event.altKey !== true ) {
				event.preventDefault();

				if( open === false )
					show( true, event.key === 'ArrowUp' || event.key === 'End' ? -1 : 0 );
				else if( items.length > 0 )
					items[ event.key === 'Home' ? 0 : ( event.key === 'End' ? items.length - 1 : ( at + ( event.key === 'ArrowDown' ? 1 : -1 ) + items.length ) % items.length ) ].focus();
			}
		} );
		// The focus that leaves the menu closes it
		root.addEventListener( 'focusout', function( event ) {
			if( root.classList.contains( 'open' ) === true && event.relatedTarget !== null && root.contains( event.relatedTarget ) === false )
				show( false );
		} );

		root.appendChild( button );
		root.appendChild( el( 'div', { class: spec.tall === true ? 'list tall' : 'list', role: 'menu' }, spec.items.map( function( item ) {

			if( item === 'sep' )
				return el( 'hr' );

			if( item.heading !== undefined )
				return el( 'div', { class: 'heading', role: 'presentation', text: item.heading } );

			// An address that is none of a page of this site (a script, another host) is no link: the item is a line of text
			if( item.href !== undefined && ( typeof item.href !== 'string' || ADDRESS.test( item.href ) === false ) )
				return el( 'span', { role: 'menuitem', 'aria-disabled': 'true', class: 'quiet', text: item.label } );

			if( item.href !== undefined )
				return el( 'a', { role: 'menuitem', href: item.href, target: '_blank', rel: 'noopener', text: item.label, on: { click: function() { closeMenus() } } } );

			return el( 'button', { type: 'button', role: 'menuitem', class: item.danger === true ? 'danger' : '', text: item.label, on: { click: function() { closeMenus(); button.focus(); item.run() } } } );
		} ) ) );

		return root;
	};
	/**
	 *	The head of a screen
	 *
	 *	@param		{string|Object}	title				The words, or the h1 itself where the screen changes them
	 *	@param		{Array}		parts						After the title: badges, the sub line, the actions
	 *
	 *	@return		{Object}
	 */
	const head = function( title, ...parts ) {
		return el( 'div', { class: 'head' }, typeof title === 'string' ? el( 'h1', { text: title } ) : title, parts );
	};
	/**
	 *	The search field of a head
	 *
	 *	@param		{string}	placeholder
	 *	@param		{string}	value
	 *	@param		{Object}	on							The listeners of the field
	 *
	 *	@return		{Object}
	 */
	const search = function( placeholder, value, on ) {

		return el( 'div', { class: 'search' },
			el( 'input', { id: 'q', type: 'search', placeholder: placeholder, value: value, 'aria-label': placeholder, autocomplete: 'off', on: on } ),
			el( 'kbd', { text: '/', 'aria-hidden': 'true' } ) );
	};
	const badge = function( kind, text, title ) {
		return el( 'span', { class: 'badge b-'+ kind, text: text, title: title } );
	};
	const link = function( href, text, attrs ) {
		return el( 'a', Object.assign( { href : href, text : text }, attrs || {} ) );
	};
	const note = function( kind, ...kids ) {
		return el( 'div', { class: 'note'+ ( kind === '' ? '' : ' '+ kind ), role: kind === 'bad' ? 'alert' : null }, kids );
	};
	const card = function( title, ...kids ) {
		return el( 'div', { class: 'card pad' }, title === '' ? null : el( 'h3', { text: title } ), kids );
	};
	/**
	 *	A definition list of the pairs that have a value
	 *
	 *	@param		{Array}		pairs						[ [ term, node or text ], ... ]; a pair with an empty value is left out
	 *
	 *	@return		{Object}
	 */
	const kv = function( pairs ) {

		return el( 'dl', { class: 'kv' }, pairs.filter( function( pair ) { return pair[1] !== '' && pair[1] !== null && pair[1] !== false } ).map( function( pair ) {
			return [ el( 'dt', { text: pair[0] } ), el( 'dd', {}, pair[1] ) ];
		} ) );
	};
	const loading = function() {

		clear( state.main );
		state.main.setAttribute( 'aria-busy', 'true' );
		state.main.appendChild( el( 'p', { class: 'state-note', role: 'status', text: t( 'page.loading' ) } ) );
	};
	const reveal = function() {
		clear( state.main );
		state.main.setAttribute( 'aria-busy', 'false' );
	};
	// --- Ctrl+S / Cmd+S -----------------------------------------------------------------------------------------------

	/**
	 *	Make a save of the screen known to the shell: what Ctrl+S (Cmd+S) runs. A screen has one for each form it holds, by a name of its own; a form that is
	 *	drawn again takes its name again, and a screen that is left has none (route() forgets them all)
	 *
	 *	@param		{string}		slot						The name of the form on the screen
	 *	@param		{Object}		node						The form or the card; the form that holds the focus is the one that is saved
	 *	@param		{Function}	run							Saves it
	 *	@param		{Function}	[dirty]					Whether it holds input that was not stored; without it the form is always the one to save - on a screen with one form
	 *	@param		{Function}	[busy]					Whether a save of it is running; without it the shell cannot tell
	 *	@param		{boolean}		[strict]				A form with nothing unstored is nothing to save even where it holds the focus
	 *
	 *	@return		{void}
	 */
	const saver = function( slot, node, run, dirty, busy, strict ) {
		state.saver[slot] = { node : node, run : run, dirty : dirty, busy : busy, strict : strict === true };
	};
	const unsaver = function( slot ) {
		delete state.saver[slot];
	};
	/**
	 *	Whether the screen holds input that was not stored: a form the guard of the page knows (state.draft) or a place that saves itself by its own save (a note, a text of a task)
	 *
	 *	@return		{string|null}						'form' for a form, 'text' for a text, null for nothing
	 */
	const unsaved = function() {

		if( state.draft !== null && state.draft.dirty === true )
			return state.draft.form === true ? 'form' : 'text';

		return Object.keys( state.saver ).some( function( slot ) { return typeof state.saver[slot].dirty === 'function' && state.saver[slot].dirty() === true } ) === true ? 'text' : null;
	};
	/**
	 *	The one place the page asks before it lets go of what was not stored: a link, a key, the rail, the address typed and the back button of the browser all go through it. Nothing unstored: it goes on
	 *	at once; else the question is asked, and a yes forgets what was typed and goes on
	 *
	 *	@param		{Function}	go							What to do when the screen may be left
	 *
	 *	@return		{void}
	 */
	const leave = function( go ) {

		const what = unsaved();

		if( what === null ) {
			go();
			return;
		}

		ask( { title : t( 'leave.title' ), text : t( what === 'form' ? 'leave.text-form' : 'leave.text' ), yes : t( 'leave.yes' ), danger : true } ).then( function( yes ) {

			if( yes !== true )
				return;

			if( state.draft !== null )
				state.draft.dirty = false;

			state.saver = {};
			go();
		} );
	};
	/**
	 *	The save of a form that has a Save: the form is submitted as its button would, and a button that is disabled is a save that is running
	 *
	 *	@param		{string}		slot
	 *	@param		{Object}		form
	 *	@param		{Object}		button
	 *	@param		{Function}	[dirty]
	 *
	 *	@return		{void}
	 */
	const submitter = function( slot, form, button, dirty ) {
		saver( slot, form, function() { form.requestSubmit() }, dirty, function() { return button.disabled === true } );
	};
	/**
	 *	Whether the node holds the one that has the focus (or is it)
	 *
	 *	@param		{Object}	node
	 *	@param		{Object}	inner
	 *
	 *	@return		{boolean}
	 */
	const holds = function( node, inner ) {

		for( let at = inner; at !== null && at !== undefined; at = at.parentNode )
			if( at === node )
				return true;

		return false;
	};
	/**
	 *	The form Ctrl+S saves: of the forms of the screen the one that holds the focused field, else the first that holds input that was not stored (a form that cannot
	 *	say is always one), else none
	 *
	 *	@return		{Object|null}
	 */
	const chosenSaver = function() {

		const all = Object.keys( state.saver ).map( function( slot ) { return state.saver[slot] } );
		const focused = all.find( function( one ) { return holds( one.node, dc.activeElement ) && ( one.strict !== true || one.dirty() === true ) } );

		if( focused !== undefined )
			return focused;

		return all.find( function( one ) { return typeof one.dirty !== 'function' || one.dirty() === true } ) ?? null;
	};
	/**
	 *	Ctrl+S or Cmd+S: the save of the form on screen. The browser's own save of the page is kept off only where a form took the key; a save that is running is not
	 *	started a second time; a question that stands over the page is not what the key is for
	 *
	 *	@param		{Event}		event
	 *
	 *	@return		{void}
	 */
	const shortcut = function( event ) {

		if( Array.from( state.dialogs.children ).some( function( box ) { return box.open === true } ) === true )
			return;

		const one = chosenSaver();

		if( one === null )
			return;

		event.preventDefault();

		if( typeof one.busy === 'function' && one.busy() === true )
			return;

		one.run();
	};
	/**
	 *	A screen that could not load: the sentence and a button to try again
	 *
	 *	@param		{Object}	answer
	 *	@param		{string}	fallback
	 *	@param		{Function}	again
	 *
	 *	@return		{void}
	 */
	const loadFailed = function( answer, fallback, again ) {

		reveal();
		state.main.appendChild( note( 'bad', el( 'span', { text: failed( answer, fallback ) } ), el( 'button', { type: 'button', class: 'btn sm', text: t( 'page.retry' ), on: { click: again } } ) ) );
	};
	/**
	 *	Take over what the overview answers: the count of the clients in the rail, and what the packages added
	 *
	 *	@param		{Object}	body						As crm/overview has it: summary, needs, packages
	 *
	 *	@return		{void}
	 */
	const countOpen = function( body ) {

		state.summary = body.summary;
		core.menuCounts();
	};
	/**
	 *	Read the overview again: the count of the rail follows what an action changed
	 *
	 *	@return		{Promise}
	 */
	const refresh = function() {

		return api( 'crm/overview' ).then( function( answer ) {

			if( answer.ok === true )
				countOpen( answer.body );

			return answer;
		} );
	};
	/**
	 *	Post one step of a package; the count in the rail is read again when it went through
	 *
	 *	@param		{string}	action
	 *	@param		{Object}	data
	 *	@param		{string}	fallback				The word for what failed
	 *
	 *	@return		{Promise}									The answer; its ok says whether to go on
	 */
	const step = function( action, data, fallback ) {

		return api( action, data ).then( function( answer ) {

			if( answer.ok === true )
				refresh();
			else
				failed( answer, fallback );

			return answer;
		} );
	};
	// --- overview ---------------------------------------------------------------------------------------------------

	const tile = function( label, value, hint, tone ) {

		return el( 'div', { class: 'card tile' },
			el( 'div', { class: 'l', text: label } ),
			el( 'div', { class: 'v'+ ( tone === '' ? '' : ' '+ tone ), text: value } ),
			el( 'div', { class: 'h', text: hint } ) );
	};
	/**
	 *	The keyboard and the ARIA of a field with a list under it, as the search of the overview and the picker of a client have them: the field is the combobox, the list its
	 *	listbox, the options have the ids prefix-0, prefix-1 ...; ArrowDown and ArrowUp move the mark through the options and round, the mark is aria-activedescendant. The caller
	 *	draws the options; the helper keeps the number of them, the mark and the state of the list
	 *
	 *	@param		{Object}	input							The field
	 *	@param		{Object}	list								The element that holds the options and is shown and hidden
	 *	@param		{string}	prefix							The start of the ids of the options
	 *
	 *	@return		{Object}									{ at, count, show( count, at ), hide(), mark(), key( event ) }
	 */
	const combo = function( input, list, prefix ) {

		const box = {
			at : -1,
			count : 0,
			mark : function() {

				Array.from( list.querySelectorAll( '[role="option"]' ) ).forEach( function( option, index ) {
					option.setAttribute( 'aria-selected', index === box.at ? 'true' : 'false' );
					option.classList.toggle( 'on', index === box.at );
				} );

				if( box.at >= 0 )
					input.setAttribute( 'aria-activedescendant', prefix+ '-'+ box.at );
				else
					input.removeAttribute( 'aria-activedescendant' );
			},
			show : function( count, at ) {
				box.count = count;
				box.at = at;
				list.hidden = false;
				input.setAttribute( 'aria-expanded', 'true' );
				box.mark();
			},
			hide : function() {
				box.count = 0;
				box.at = -1;
				list.hidden = true;
				input.setAttribute( 'aria-expanded', 'false' );
				input.removeAttribute( 'aria-activedescendant' );
			},
			/**
			 *	@return		{boolean}							Whether the key was an arrow that moved the mark
			 */
			key : function( event ) {

				if( event.key !== 'ArrowDown' && event.key !== 'ArrowUp' )
					return false;

				if( box.count === 0 )
					return true;

				event.preventDefault();
				box.at = ( box.at + ( event.key === 'ArrowDown' ? 1 : box.count - 1 ) ) % box.count;
				box.mark();

				return true;
			},
		};

		return box;
	};
	/**
	 *	The search of the overview: the field, and under it the hits in what the search sources hold - clients and projects of the core, what a package registers - for the text, at most
	 *	SEARCH_MAX of each source. The sources are asked once, when the field is first used; typing asks nothing of the server. The arrows move through the list, Enter opens the
	 *	hit that is marked (the first at first), Escape closes the list
	 *
	 *	@param		{number}	mine
	 *
	 *	@return		{Object}									The element of the field and its list
	 */
	const searchBox = function( mine ) {

		const results = el( 'div', { class: 'hits', id: 'search-results', role: 'listbox', 'aria-label': t( 'search.results' ), hidden: true } );
		let loaded = null;
		let hits = [];

		const field = search( t( 'search.all' ), '', {} );
		const input = field.querySelector( 'input' );

		input.setAttribute( 'role', 'combobox' );
		input.setAttribute( 'aria-controls', 'search-results' );
		input.setAttribute( 'aria-expanded', 'false' );
		input.setAttribute( 'aria-autocomplete', 'list' );

		const keys = combo( input, results, 'hit' );

		/**
		 *	The lists, once: a list that cannot be read is an empty one
		 *
		 *	@return		{Promise}
		 */
		const load = function() {

			if( loaded === null ) {

				const kit = core.kitOf( mine );
				const sources = core.settingsOrdered( core.searchList.list );

				loaded = Promise.all( sources.map( function( source ) {

					const made = core.guarded( function() { return source.load( kit ) } );

					return Promise.resolve( made.ok === true ? made.value : [] ).then( function( rows ) { return { source : source, rows : rows } }, function() { return { source : source, rows : [] } } );
				} ) );
			}

			return loaded;
		};

		const close = function() {
			hits = [];
			clear( results );
			keys.hide();
		};

		const draw = function() {

			clear( results );

			if( hits.length === 0 ) {
				close();
				return;
			}

			const kinds = [];

			hits.forEach( function( hit ) {
				if( kinds.includes( hit.kind ) === false )
					kinds.push( hit.kind );
			} );

			kinds.forEach( function( kind ) {

				const group = hits.map( function( hit, index ) { return [ hit, index ] } ).filter( function( pair ) { return pair[0].kind === kind } );
				const heading = core.wordOf( group[0][0].word, t( 'search.group-'+ kind ) );

				results.appendChild( el( 'div', { role: 'group', 'aria-label': heading, dataset: { kind: kind } }, el( 'div', { class: 'heading', 'aria-hidden': 'true', text: heading } ), group.map( function( pair ) {
					return el( 'a', { role: 'option', id: 'hit-'+ pair[1], href: pair[0].href, class: 'hit', 'aria-selected': 'false' }, el( 'b', { text: pair[0].label } ), el( 'span', { class: 'muted', text: pair[0].hint } ) );
				} ) ) );
			} );

			keys.show( hits.length, 0 );
		};

		input.addEventListener( 'input', function() {

			const typed = input.value;

			if( typed.trim() === '' ) {
				close();
				return;
			}

			load().then( function( data ) {

				// The text has moved on, or the screen has: this answer is for nobody
				if( state.view !== mine || input.value !== typed )
					return;

				hits = core.hitsOf( data, typed, core.kitOf( mine ) );
				draw();
			} );
		} );

		input.addEventListener( 'keydown', function( event ) {

			if( keys.key( event ) === true )
				return;

			if( event.key === 'Escape' ) {
				close();
				return;
			}

			if( event.key !== 'Enter' )
				return;

			event.preventDefault();

			if( hits.length > 0 && keys.at >= 0 )
				wn.location.hash = hits[keys.at].href;
		} );

		// A click elsewhere closes the list; the screen that is left takes the listener along
		const outside = function( event ) {

			if( state.view !== mine ) {
				dc.removeEventListener( 'click', outside );
				return;
			}

			if( holds( box, event.target ) === false )
				close();
		};

		const box = el( 'div', { class: 'search-box', id: 'search-box' }, field, results );

		dc.addEventListener( 'click', outside );

		return box;
	};
	/**
	 *	The picker of a client: a field with the list of the clients under it - the list goes in, the choice comes out. An empty field with the focus shows the first PICK_MAX
	 *	in the order the list has; a text narrows the list to the clients whose text holds it, in any case; more than PICK_MAX are said with a line that gives the number of
	 *	the others. The keys, the roles and the aria attributes are the ones of the search of the overview (combo): the field is the combobox, the list its listbox, the
	 *	clients are the options. Typing and the arrows choose nothing: Enter, a click or a tap on an option does, the choice stands in the field and typing in the field
	 *	lets it go. The list is the one the caller gives; the picker asks the server for nothing.
	 *
	 *	Where the picker stands in for a select it has more: a choice that was there when the screen opened (set), a name for the field (label, placeholder), the choice
	 *	let go by an entry of its own that the list starts with while no text is typed (empty: the entry says what no choice means, "all clients"), and the list opened
	 *	once more over a choice that stands (reopen), with that choice marked. The entry is no client and is not counted among the PICK_MAX. The field of such a picker is a text
	 *	field, no search field: Escape in a search field empties it, and Escape in a dialog or a form is to leave it
	 *
	 *	@param		{Object}	setup
	 *	@param		{string}	setup.id					The id of the field; the ids of the list and of the options start with it
	 *	@param		{string}	setup.list				The name of the list for a screen reader
	 *	@param		{Function}	setup.more				Gives the line for the number of the clients that are not shown
	 *	@param		{string}	setup.none				The line for a text no client holds
	 *	@param		{Function}	[setup.picked]			Called with the client that was chosen, and with null where the choice was let go
	 *	@param		{string}	[setup.label]				The name of the field for a screen reader, where no label element names it
	 *	@param		{string}	[setup.placeholder]	What the empty field says; the entry of empty by default
	 *	@param		{string}	[setup.empty]				The entry that lets the choice go
	 *	@param		{boolean}	[setup.restore]			Leaving the field with a text that chose nothing gives back the choice that stood before it was typed in, instead of emptying the field (with reopen); the entry of empty is then the only way to let the choice go
	 *	@param		{boolean}	[setup.reopen]			A click or an arrow opens the list over a choice that stands
	 *
	 *	@return		{Object}									{ node, input, items( list ), set( value ), value(), reset(), focus(), enable( on ), listen( fn ), keep( step ) }; a client of the list is { value, text, label }: the text is
	 *															what the field searches, the label what the option and the field show; set() chooses by value and says nothing of it, listen() is called after each choice and each let go,
	 *															keep() runs a step that draws the screen anew with the picker in it and gives the focus and the caret back
	 */
	const clientPicker = function( setup ) {

		const input = el( 'input', { type: setup.reopen === true ? 'text' : 'search', id: setup.id, class: 'picker-field', autocomplete: 'off', role: 'combobox', 'aria-controls': setup.id+ '-list', 'aria-expanded': 'false', 'aria-autocomplete': 'list', 'aria-label': setup.label ?? null, placeholder: setup.placeholder ?? setup.empty ?? null } );
		const listbox = el( 'div', { class: 'picker-options', id: setup.id+ '-list', role: 'listbox', 'aria-label': setup.list } );
		const note = el( 'p', { class: 'picker-note', role: 'status', hidden: true } );
		const pop = el( 'div', { class: 'picker-pop', hidden: true }, listbox, note );
		const node = el( 'div', { class: 'picker' }, input, pop );
		const keys = combo( input, pop, setup.id+ '-o' );
		let items = [];
		let shown = [];
		let chosen = null;
		let quiet = false;
		let held = false;
		// The choice that stood when the field was typed in, kept where setup.restore asks for it: leaving the field with a text that chose nothing gives it back
		let before = null;
		const heard = [];
		const letgo = { value: '', text: '', label: '', none: true };

		const close = function() {
			shown = [];
			clear( listbox );
			note.hidden = true;
			keys.hide();
		};

		// What was chosen or let go is told to the caller and to those that listen
		const told = function( one ) {

			if( typeof setup.picked === 'function' )
				setup.picked( one );

			heard.forEach( function( fn ) { fn( one ) } );
		};

		const pick = function( one ) {

			if( one.none === true ) {
				const was = chosen;

				chosen = null;
				before = null;
				input.value = '';
				close();

				if( was !== null )
					told( null );

				return;
			}

			chosen = one;
			before = null;
			input.value = one.label;
			close();
			told( one );
		};

		const draw = function() {

			// A choice that stands is shown with the whole list around it, not narrowed to itself
			const word = chosen !== null && input.value === chosen.label ? '' : input.value.trim().toLowerCase();
			const found = word === '' ? items : items.filter( function( one ) { return one.text.toLowerCase().includes( word ) } );
			const rows = ( setup.empty !== undefined && word === '' ? [ letgo ] : [] ).concat( found.slice( 0, PICK_MAX ) );

			shown = rows;
			clear( listbox );
			rows.forEach( function( one, index ) {
				listbox.appendChild( el( 'div', { role: 'option', id: setup.id+ '-o-'+ index, class: one.none === true ? 'picker-option picker-empty' : 'picker-option', 'aria-selected': 'false', dataset: { value: one.value }, text: one.none === true ? setup.empty : one.label, on: {
					mousedown: function( event ) { event.preventDefault() },
					click: function() { pick( one ); input.focus() },
				} } ) );
			} );

			const clients = rows.filter( function( one ) { return one.none !== true } ).length;

			note.hidden = items.length === 0 || ( found.length === clients && found.length > 0 );
			note.textContent = note.hidden === true ? '' : found.length === 0 ? setup.none : setup.more( found.length - clients );

			if( items.length === 0 ) {
				close();
				return;
			}

			// The first match is marked while a text is typed; over a choice that stands, that choice
			const standing = chosen === null ? -1 : rows.findIndex( function( one ) { return one.value === chosen.value } );

			keys.show( rows.length, word !== '' && rows.length > 0 ? 0 : standing );
		};

		input.addEventListener( 'input', function() {

			if( chosen !== null ) {
				before = setup.restore === true ? chosen : null;
				chosen = null;
				told( null );
			}

			draw();
		} );
		input.addEventListener( 'focus', function() { if( chosen === null && quiet === false ) draw() } );
		input.addEventListener( 'click', function() { if( pop.hidden === true && ( chosen === null || setup.reopen === true ) ) draw() } );
		input.addEventListener( 'keydown', function( event ) {

			if( ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) && pop.hidden === true ) {
				event.preventDefault();
				draw();
				return;
			}

			if( keys.key( event ) === true ) {

				// A mark that moves out of the window is followed
				if( keys.at >= 0 && listbox.children[keys.at] !== undefined )
					listbox.children[keys.at].scrollIntoView( { block: 'nearest' } );

				return;
			}

			if( event.key === 'Escape' && pop.hidden === false ) {
				event.preventDefault();
				event.stopPropagation();
				close();
				return;
			}

			// Enter with the list open and an option marked chooses it; with the list shut it is the Enter of the form
			if( event.key === 'Enter' && pop.hidden === false && keys.at >= 0 ) {
				event.preventDefault();
				pick( shown[keys.at] );
			}
		} );
		node.addEventListener( 'focusout', function( event ) {

			if( held === true || ( event.relatedTarget !== null && holds( node, event.relatedTarget ) === true ) )
				return;

			close();

			// Where the picker stands in for a select the field says what is chosen: a text that chose nothing is not left in it
			if( setup.reopen === true && chosen === null ) {

				// Where the choice is not to be lost by a text, the choice that stood comes back; only the entry that lets it go lets it go
				if( setup.restore === true && before !== null ) {
					chosen = before;
					before = null;
					input.value = chosen.label;
					told( chosen );
				}
				else
					input.value = '';
			}
		} );

		return {
			node : node,
			input : input,
			items : function( list ) {
				items = list;

				if( chosen !== null && list.some( function( one ) { return one.value === chosen.value } ) === false )
					chosen = null;

				if( pop.hidden === false )
					draw();
			},
			// A choice that was there before the person came: it stands in the field and nobody is told; no client of the list by this value is no choice
			set : function( value ) {
				chosen = items.find( function( one ) { return one.value === value } ) ?? null;
				input.value = chosen === null ? '' : chosen.label;
			},
			listen : function( fn ) { heard.push( fn ) },
			// A step that draws the screen anew with this picker in it: the focus and the caret are given back, and the field is not taken for left
			keep : function( step ) {

				const had = dc.activeElement === input;
				const at = input.selectionStart;

				held = true;
				step();
				held = false;

				if( had === true ) {
					quiet = true;
					input.focus();
					quiet = false;
					input.setSelectionRange( at, at );
				}
			},
			value : function() { return chosen === null ? '' : chosen.value },
			reset : function() {
				chosen = null;
				input.value = '';
				close();
			},
			// The focus to the field with the list shut: after a choice was used, the next one is begun by a key
			focus : function() { quiet = true; input.focus(); quiet = false },
			enable : function( on ) { input.disabled = on !== true; if( on !== true ) close() },
		};
	};
	/**
	 *	The clients a picker offers, as it takes them: what it searches and what it shows is the number and the title, as the option of the select had them
	 *
	 *	@param		{Array}		clients					As crm-clients/list has them
	 *	@param		{Function}	[value]					The value of a client; its id as a string by default
	 *	@param		{Function}	[title]					The title of a client; clientTitle() by default
	 *
	 *	@return		{Array}
	 */
	const pickerClients = function( clients, value, title ) {
		return clients.map( function( one ) {
			const name = one.number+ ' · '+ ( title === undefined ? clientTitle( one ) : title( one ) );

			return { value : value === undefined ? String( one.id ) : value( one ), text : name, label : name };
		} );
	};
	/**
	 *	The picker of a client where it stands in for a select over the clients: the words are the ones of the screens, the choice that stands is opened once more and, where the
	 *	choice may be none, the entry that lets it go says what none means
	 *
	 *	@param		{Object}	setup					id, label, placeholder, empty and picked as clientPicker() has them
	 *	@param		{Array}		clients					As pickerClients() takes them
	 *	@param		{string}	[preset]				The value of the choice that stands when the screen opens
	 *
	 *	@return		{Object}								The picker
	 */
	const clientField = function( setup, clients, preset ) {

		const picker = clientPicker( Object.assign( { list: t( 'field.client-list' ), none: t( 'shares.no-match' ), more: function( rest ) { return t( 'shares.more', rest ) }, reopen: true }, setup ) );

		picker.items( clients );
		picker.set( preset ?? '' );

		return picker;
	};
	// --- Keep this device signed in -------------------------------------------------------------------------------

	/**
	 *	Ask the server to keep this device signed in or to forget every device, and draw what it answers: the card, and the buttons of the hint on the home screen
	 *
	 *	@param		{string}	action
	 *	@param		{string}	done							The word of the toast
	 *
	 *	@return		{void}
	 */
	const deviceRun = function( action, done ) {

		const buttons = dc.querySelectorAll( '[data-device]' );

		buttons.forEach( function( button ) { button.disabled = true } );

		api( action ).then( function( answer ) {

			buttons.forEach( function( button ) { button.disabled = false } );

			if( answer.ok !== true ) {
				failed( answer );
				return;
			}

			// A sign-out of the other devices ends their sessions and gives this one a new session: the token that goes with it is the one the next request carries
			if( answer.body !== null && typeof answer.body === 'object' && typeof answer.body.csrf === 'string' && answer.body.csrf !== '' )
				state.csrf = answer.body.csrf;

			state.device = Object.assign( { remembered : false, count : 0 }, answer.body );
			delete state.device.csrf;
			toast( t( done ) );

			dc.querySelectorAll( '#device' ).forEach( function( old ) { old.replaceWith( deviceCard() ) } );
			dc.querySelectorAll( '[data-remember]' ).forEach( function( button ) { button.hidden = state.device.remembered } );
		} );
	};
	/**
	 *	The card that keeps this device signed in - or says that it stays and offers to sign every device out. What the device is and how many there are comes from the page and from the answers of
	 *	the two actions; the words are the surface's. The cookie is made by the server on the owner's word (the button) and by nothing else
	 *
	 *	@return		{Object}
	 */
	const deviceCard = function() {

		const device = state.device;

		return el( 'div', { class: 'card pad', id: 'device' }, el( 'h3', { text: t( 'device.title' ) } ),
			el( 'p', { class: 'state-note', text: t( device.remembered === true ? 'device.on' : 'device.off' ) } ),
			device.count > 0 ? el( 'p', { class: 'state-note', dataset: { count : device.count }, text: t( 'device.others', device.count ) } ) : null,
			el( 'p', { class: 'device-actions' },
				device.remembered === true ? null : el( 'button', { type: 'button', class: 'btn', dataset: { device: 'remember' }, text: t( 'device.remember' ), on: { click: function() { deviceRun( 'crm/device-remember', 'device.remembered' ) } } } ),
				device.count > 0 ? el( 'button', { type: 'button', class: 'btn danger', dataset: { device: 'forget' }, text: t( 'device.forget' ), on: { click: function() {
				ask( { title : t( 'device.forget' ), text : t( 'device.forget-text' ), yes : t( 'device.forget-yes' ) } ).then( function( yes ) {
					if( yes === true )
						deviceRun( 'crm/devices-forget', 'device.forgotten' );
				} );
			} } } ) : null ) );
	};
	// --- Put it on the home screen -----------------------------------------------------------------------------------------

	// The mark of the device that has put the hint away, and the offer of the browser to install the page
	const home = { offer : null, card : null };
	/**
	 *	Whether the page runs as an app already: the display mode standalone, or the flag iOS sets
	 *
	 *	@return		{boolean}
	 */
	const installed = function() {

		try {
			return ( typeof wn.matchMedia === 'function' && wn.matchMedia( '(display-mode: standalone)' ).matches === true ) || ( wn.navigator !== undefined && wn.navigator.standalone === true );
		}
		catch( e ) {
			return false;
		}
	};
	/**
	 *	Whether the device has put the hint away; with true it does so for good. Kept in the browser of the device where the browser lets a page keep anything, and sent nowhere
	 *
	 *	@param		{boolean}	[write]
	 *
	 *	@return		{boolean}
	 */
	const homeAway = function( write ) {

		try {
			const key = state.app === '' ? HOME_KEY : HOME_KEY+ '-'+ state.app;

			if( write === true )
				wn.localStorage.setItem( key, '1' );

			return wn.localStorage.getItem( key ) !== null;
		}
		catch( e ) {
			return false;
		}
	};
	/**
	 *	Put the hint away for good on this device
	 *
	 *	@return		{void}
	 */
	const homePut = function() {

		homeAway( true );

		if( home.card !== null )
			home.card.hidden = true;
	};
	/**
	 *	Whether the hint is wanted: on a phone, where the page is no app yet and the device has not put the hint away
	 *
	 *	@return		{boolean}
	 */
	const homeWanted = function() {
		return phone() === true && installed() === false && homeAway() === false;
	};
	/**
	 *	The key of the words of the hint: an app that has words of its own says them (apps.add, home)
	 *
	 *	@param		{string}	part						title, ios or android
	 *
	 *	@return		{string}
	 */
	const homeWord = function( part ) {
		return ( core.appOf() === null || core.appOf().home === null ? 'home.' : core.appOf().home )+ part;
	};
	/**
	 *	The hint that tells how to put the surface on the home screen: one sentence for iOS and one for Android, the button that puts it away and - where the browser offers the install itself - the button
	 *	Installieren in place of the sentence for Android. Nothing where the hint is not wanted
	 *
	 *	@return		{Object|null}
	 */
	const homeHint = function() {

		if( homeWanted() === false ) {
			home.card = null;

			return null;
		}

		const android = el( 'p', { class: 'home-system', dataset: { system: 'android' }, hidden: home.offer !== null }, el( 'b', { text: t( 'home.android-name' ) } ), ' ', t( homeWord( 'android' ) ) );
		const install = el( 'button', { type: 'button', class: 'btn primary', dataset: { install: '1' }, hidden: home.offer === null, text: t( 'home.install' ), on: { click: function() {

			const offer = home.offer;

			if( offer === null )
				return;

			home.offer = null;
			offer.prompt();

			Promise.resolve( offer.userChoice ).then( function( choice ) {
				if( choice !== undefined && choice !== null && choice.outcome === 'accepted' )
					homePut();
			}, function() {} );
		} } } );
		const card = el( 'div', { class: 'home', role: 'note', id: 'home-hint' },
			el( 'p', { class: 'home-title', text: t( homeWord( 'title' ) ) } ),
			el( 'p', { class: 'home-system', dataset: { system: 'ios' } }, el( 'b', { text: t( 'home.ios-name' ) } ), ' ', t( homeWord( 'ios' ) ) ),
			android,
			el( 'p', { class: 'home-actions' }, el( 'button', { type: 'button', class: 'btn', dataset: { remember: '1', device: 'remember' }, hidden: state.device.remembered === true, text: t( 'device.remember' ), on: { click: function() { deviceRun( 'crm/device-remember', 'device.remembered' ) } } } ), install, el( 'button', { type: 'button', class: 'btn', dataset: { dismiss: '1' }, text: t( 'home.dismiss' ), on: { click: homePut } } ) ) );

		home.card = card;

		return card;
	};
	/**
	 *	The browser offers to install the page: the offer is taken over where the hint is wanted (the browser's own bar stays where it is not), and shown on the hint that is drawn. The install by another way
	 *	puts the hint away for good
	 *
	 *	@param		{Object}	event
	 *
	 *	@return		{void}
	 */
	const homeOffer = function( event ) {

		if( homeWanted() === false )
			return;

		event.preventDefault();
		home.offer = event;

		if( home.card !== null ) {
			const button = home.card.querySelector( '[data-install]' );
			const android = home.card.querySelector( '[data-system="android"]' );

			if( button !== null )
				button.hidden = false;

			if( android !== null )
				android.hidden = true;
		}
	};
	// --- The lists of the tables -------------------------------------------------------------------------------------

	/**
	 *	The head of a sortable column: a button that sorts by it, and again to turn the order
	 *
	 *	@param		{Object}	filter					sort and dir of the table
	 *	@param		{string}	key
	 *	@param		{string}	label
	 *	@param		{boolean}	right					A column of numbers
	 *	@param		{Function}	again				Draws the table again
	 *	@param		{number}	first					The order a column is sorted in the first time: 1 up, -1 down
	 *
	 *	@return		{Object}
	 */
	const sortHead = function( filter, key, label, right, again, first ) {

		const on = filter.sort === key;

		return el( 'th', { class: ( right === true ? 'r ' : '' )+ 'sortable', scope: 'col', 'aria-sort': on ? ( filter.dir > 0 ? 'ascending' : 'descending' ) : 'none' },
			el( 'button', { type: 'button', class: 'th', dataset: { sort: key, label: label, first: first }, text: label+ ( on ? ( filter.dir > 0 ? ' ↑' : ' ↓' ) : '' ), on: { click: function() {
				if( filter.sort === key )
					filter.dir = -filter.dir;
				else {
					filter.sort = key;
					filter.dir = first;
				}
				again();
			} } } ) );
	};
	/**
	 *	The sort of a list for the phone: the head of the table is hidden there, and a select with the same sorts takes its place. Each sortable
	 *	column is offered in both orders, the one its head starts with first; a choice sets what the head sets and draws the list again
	 *
	 *	@param		{Object}	box						The card of the table
	 *	@param		{Object}	filter					sort and dir of the table
	 *	@param		{Function}	again				Draws the table again
	 *
	 *	@return		{Object|null}						Nothing for a table that has no sortable column
	 */
	const sortBox = function( box, filter, again ) {

		const heads = box.querySelectorAll( 'button.th' );

		if( heads.length === 0 )
			return null;

		const options = [];

		heads.forEach( function( head ) {

			const first = Number( head.dataset.first );

			[ first, -first ].forEach( function( dir ) { options.push( el( 'option', { value: head.dataset.sort+ ':'+ dir, text: head.dataset.label+ ( dir > 0 ? ' ↑' : ' ↓' ) } ) ) } );
		} );

		return el( 'div', { class: 'sortbox' },
			el( 'label', { for: 'sort-by', text: t( 'list.sort' ) } ),
			el( 'select', { id: 'sort-by', value: filter.sort+ ':'+ filter.dir, on: { change: function( event ) {

				const chosen = event.target.value.split( ':' );

				filter.sort = chosen[0];
				filter.dir = Number( chosen[1] );
				again();

				// The list is drawn again with a new select: the focus goes to it, so that the arrow that was pressed can go on
				const next = dc.getElementById( 'sort-by' );

				if( next !== null )
					next.focus();
			} } }, options ) );
	};
	/**
	 *	Say the roles of a table in its markup, for the stylesheet that lays its rows out as a grid on the phone: a screen reader takes the table
	 *	from a grid otherwise
	 *
	 *	@param		{Object}	table
	 *
	 *	@return		{void}
	 */
	const keepRoles = function( table ) {

		table.setAttribute( 'role', 'table' );
		table.querySelectorAll( 'thead, tbody, tfoot' ).forEach( function( group ) { group.setAttribute( 'role', 'rowgroup' ) } );
		table.querySelectorAll( 'tr' ).forEach( function( row ) { row.setAttribute( 'role', 'row' ) } );
		table.querySelectorAll( 'th' ).forEach( function( head ) { head.setAttribute( 'role', 'columnheader' ) } );
		table.querySelectorAll( 'td' ).forEach( function( cell ) { cell.setAttribute( 'role', 'cell' ) } );
	};
	/**
	 *	Whether the window is a phone: the width under which the lists are cards
	 *
	 *	@return		{boolean}
	 */
	const phone = function() {
		return typeof wn.matchMedia === 'function' && wn.matchMedia( PHONE ).matches === true;
	};
	/**
	 *	A table that is a list becomes cards on the phone by its stylesheet alone: the same rows and cells, each cell named for the card (data-col)
	 *	and carrying the word of its column (data-label), the cells that hold nothing marked (data-empty). The table keeps its roles, since a
	 *	grid in place of its rows takes them from a screen reader otherwise. A row of another shape - the one that says there is nothing, the
	 *	line over several versions - is left as it is
	 *
	 *	@param		{Object}	box						The card of the table
	 *	@param		{string}	kind					Names the layout of the card in the stylesheet
	 *	@param		{string[]}	columns				The name of each column; with :l the word of the column stands before the cell
	 *	@param		{Object}	[filter]			With the sort of the list: a select for it goes before the card
	 *	@param		{Function}	[again]
	 *
	 *	@return		{Object|Array}						The card; with a sort the select for it too (null for a table that sorts by no column), before the card
	 */
	const cardList = function( box, kind, columns, filter, again ) {

		const table = box.querySelector( 'table' );
		const heads = table.querySelectorAll( 'th' );

		table.className = table.className+ ' cards';
		table.dataset.cards = kind;
		keepRoles( table );

		Array.from( table.querySelectorAll( 'tr' ) ).filter( function( row ) { return row.parentNode.tagName === 'TBODY' } ).forEach( function( row ) {

			const cells = Array.from( row.children ).filter( function( kid ) { return kid.tagName === 'TD' } );

			if( cells.length !== columns.length )
				return;

			cells.forEach( function( cell, at ) {

				const named = columns[at].split( ':' );
				const head = heads[at];
				const button = head === undefined ? null : head.querySelector( 'button.th' );

				cell.dataset.col = named[0];

				if( cell.dataset.label === undefined )
					cell.dataset.label = head === undefined ? '' : ( button === null ? head.textContent : button.dataset.label );

				if( named[1] === 'l' )
					cell.dataset.lab = '1';

				if( cell.textContent.trim() in { '' : 1, '–' : 1 } && Array.from( cell.children ).every( function( kid ) { return kid.textContent.trim() in { '' : 1, '–' : 1 } } ) && cell.querySelector( 'input, button, a, select' ) === null )
					cell.dataset.empty = '1';
			} );
		} );

		return filter === undefined ? box : [ sortBox( box, filter, again ), box ];
	};
	/**
	 *	The pages of a table: Back, where it is, Next; nothing for a single page
	 *
	 *	@param		{Object}	filter					page of the table
	 *	@param		{number}	pages
	 *	@param		{Function}	again				Draws the table again
	 *
	 *	@return		{Object|null}
	 */
	const pagerOf = function( filter, pages, again ) {

		if( pages === 1 )
			return null;

		return el( 'div', { class: 'pager' },
			el( 'button', { type: 'button', class: 'btn sm', id: 'prev', text: t( 'list.prev' ), disabled: filter.page === 1, on: { click: function() { filter.page--; again() } } } ),
			el( 'span', { class: 'state-note', text: t( 'list.page', filter.page, pages ) } ),
			el( 'button', { type: 'button', class: 'btn sm', id: 'next', text: t( 'list.next' ), disabled: filter.page === pages, on: { click: function() { filter.page++; again() } } } ) );
	};
	/**
	 *	The sentence for what a run was refused for: the first three by name and sentence, then how many more
	 *
	 *	@param		{Array}		refused					[ name, sentence ]
	 *
	 *	@return		{string}									'' where none was refused
	 */
	const refusedText = function( refused ) {

		if( refused.length === 0 )
			return '';

		const named = refused.slice( 0, 3 ).map( function( one ) { return t( 'select.failed-line', one[0], one[1] ) } ).join( ', ' );

		return t( 'select.failed', refused.length > 3 ? t( 'select.failed-more', named, refused.length - 3 ) : named );
	};
	/**
	 *	Draw a list again with the search field kept as it is, the cursor in it
	 *
	 *	@param		{Function}	draw						Draws the list
	 *	@param		{boolean}	[typing]				The search is what changed
	 *
	 *	@return		{void}
	 */
	const keepSearch = function( draw, typing ) {

		const field = typing === true ? dc.getElementById( 'q' ) : null;
		const at = field === null ? 0 : field.selectionStart;

		draw();

		if( field !== null ) {
			const next = dc.getElementById( 'q' );
			next.focus();
			next.setSelectionRange( at, at );
		}
	};
	// --- forms -------------------------------------------------------------------------------------------------------

	/**
	 *	Post an action, and say the refusal in a toast: the shape of a step that changes nothing the rail counts
	 *
	 *	@param		{string}	action
	 *	@param		{Object}	data
	 *	@param		{string}	fallback				The word for what failed
	 *
	 *	@return		{Promise}									The answer; its ok says whether to go on
	 */
	const call = function( action, data, fallback ) {

		return api( action, data ).then( function( answer ) {

			if( answer.ok !== true )
				failed( answer, fallback );

			return answer;
		} );
	};
	/**
	 *	A form of a screen: rows of a label, a control and the place where a refusal is said beside the control. What is typed marks the
	 *	form as changed, which the guard of the page asks before it lets go of the screen
	 *
	 *	@param		{Array}		rows						[ { name, label, control, hint, extra, node, picker } ]: extra is a node of its own under the control and its hint; node is what stands in the place where
	 *															the control is not the whole of it (the field of a picker of a client is in the node of the picker, which is given as picker, and its choice is the change of the control)
	 *	@param		{Object}	form						Gets controls (name => control) and notes (name => the element of the refusal)
	 *
	 *	@return		{Object}									The definition list
	 */
	const formList = function( rows, form ) {

		return el( 'dl', { class: 'kv' }, rows.map( function( row ) {

			const note = el( 'span', { class: 'state-note bad', role: 'alert' } );
			const clear = function() {
				row.control.classList.remove( 'err' );
				row.control.removeAttribute( 'aria-invalid' );
				note.textContent = '';
				form.dirty = true;
			};

			form.controls[row.name] = row.control;
			form.notes[row.name] = note;
			row.control.addEventListener( 'input', clear );
			row.control.addEventListener( 'change', clear );

			if( row.picker !== undefined )
				row.picker.listen( clear );

			// A hint is what the control is described by, for a reader of the screen: the control names it
			if( row.hint !== undefined && row.control.id !== undefined && row.control.id !== '' )
				row.control.setAttribute( 'aria-describedby', 'hint-'+ row.control.id );

			return [ el( 'dt', { class: row.control.tagName === 'TEXTAREA' ? 'top' : null }, el( 'label', { for : row.control.id, text : row.label } ) ), el( 'dd', {}, row.node ?? row.control, row.hint === undefined ? null : el( 'span', { class: 'state-note', id: 'hint-'+ row.control.id, text: row.hint } ), row.extra === undefined ? null : row.extra, note ) ];
		} ) );
	};
	/**
	 *	A form that the guard of the page knows: a fresh one holds nothing unsaved
	 *
	 *	@return		{Object}
	 */
	const formOf = function() {

		const form = { controls : {}, notes : {}, dirty : false, form : true };

		state.draft = form;

		return form;
	};
	/**
	 *	@param		{Object}	form
	 *
	 *	@return		{Object}									name => the text of each control
	 */
	const valuesOfForm = function( form ) {

		const values = {};

		Object.keys( form.controls ).forEach( function( name ) { values[name] = form.controls[name].type === 'checkbox' ? form.controls[name].checked : form.controls[name].value } );

		return values;
	};
	/**
	 *	A refusal of the server beside the field it names: the sentence in the toast and under the control, the control marked and focused
	 *
	 *	@param		{Object}	form
	 *	@param		{Object}	answer
	 *	@param		{string}	fallback
	 *
	 *	@return		{string}									The sentence
	 */
	const refuseAt = function( form, answer, fallback ) {

		const sentence = failed( answer, fallback );
		const field = answer.body !== null && typeof answer.body === 'object' && typeof answer.body.field === 'string' ? answer.body.field : '';

		if( field !== '' && form.controls[field] !== undefined ) {
			form.controls[field].classList.add( 'err' );
			form.controls[field].setAttribute( 'aria-invalid', 'true' );
			form.notes[field].textContent = sentence;
			form.controls[field].focus();
		}

		return sentence;
	};
	// --- Clients ------------------------------------------------------------------------------------------------------

	/**
	 *	@param		{Object}	client					As the server has it (with its title)
	 *
	 *	@return		{string}									How it is called: its company, else its name, else its address
	 */
	const clientTitle = function( client ) {
		return typeof client.title === 'string' && client.title !== '' ? client.title : ( client.company || client.name || client.email || '' );
	};
	/**
	 *	The name a project is shown by (P-0001) as the small number that stands before its title in a list
	 *
	 *	@param		{string}	id
	 *
	 *	@return		{Object}
	 */
	const projectNo = function( id ) {
		return el( 'span', { class: 'pno num', text: projectLabel( id ) } );
	};

	Object.assign( core, { ADDRESS, CLIENT_FIELDS, PAGE_SIZE, PHONE, SEARCH_MAX, api, append, ask, badge, call, card, cardList, charCount, clear, clientField, clientPicker, clientTitle, closeMenus, countOpen, deviceCard, dialog, dmy, el, errorText, failed, formList, formOf, head, hm, holds, home, homeHint, homeOffer, homePut, keepRoles, keepSearch, kv, leave, link, loadFailed, loading, mailtoOf, menu, note, pagerOf, phone, pickerClients, post, projectLabel, projectNo, projectText, quietField, quietText, refresh, refuseAt, refusedText, reset, reveal, saver, search, searchBox, unsaved, shortcut, showLink, sortHead, stamp, state, step, submitter, t, telOf, tile, toast, today, unsaver, valuesOfForm } );

} )( window, document );
