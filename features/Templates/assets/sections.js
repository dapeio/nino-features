/**
 *	Nino Template Builder — section canvas, inspector and HTML+ editor.
 */

( function(wn,dc) {

	'use strict';

	/*	The shortcode this panel is about, spelled out here rather than inside a
		fill. A fill value is substituted into the page before \Nino\Html's
		shortcode pass runs, and the [jstext] payload carries the same stored
		value, so a literal '[template]' in a locale file is executed as the
		shortcode and replaced with nothing - in both languages, on both paths.
		A .js file is a static asset and is never rendered, so the token is
		safe here and the fills carry a %s.	*/
	const SHORTCODE = '[template]';

	const pd = Nino.admin.templates;

	function element( tag, className, text ) {
		const node = dc.createElement( tag );
		if( className )
			node.className = className;
		if( text !== undefined )
			node.textContent = text;
		return node;
	}

	function button( label, title, callback, className ) {
		const node = element( 'button', className || '', label );
		node.type = 'button';
		node.title = title;
		node.setAttribute( 'aria-label', title );
		node.addEventListener( 'click', function( event ) {
			event.preventDefault();
			event.stopPropagation();
			callback();
		} );
		return node;
	}

	/*	The preset a stored section is managed by: the library's preset of
		that key, for a spec the composer writes - version 3, named areas.
		A marker that says anything else is not one the builder can open,
		so the section is what the builder does not recognise: its source,
		left byte for byte, edited as HTML+	*/
	function presetFor( spec ) {
		if( isAreaSpec( spec ) === false )
			return null;
		return pd._library.presets.find( function( preset ) { return preset.key === spec.preset } ) || null;
	}

	function humanize( value ) {
		return String( value || '' ).replace( /[-_]+/g, ' ' ).replace( /\b\w/g, function( char ) { return char.toUpperCase() } );
	}

	function sectionLabel( section, index ) {
		if( section.type === 'template' )
			return section.template || 'template-section-'+ ( index + 1 );
		return section.htmlId || ( section.spec && section.spec.id ) || 'section-'+ ( index + 1 );
	}

	function isAreaSpec( spec ) {
		return !!spec && Number( spec.version ) === 3 && spec.areas && typeof spec.areas === 'object';
	}

	function effectiveFrameValue( spec, preset, key ) {
		if( spec.frame && spec.frame[key] && spec.frame[key] !== 'auto' )
			return spec.frame[key];
		const layoutKey = spec.layout && spec.layout !== 'auto' && preset.layouts[spec.layout] ? spec.layout : preset.recommend.layout;
		const layout = preset.layouts[layoutKey] || {};
		if( layout.frame && layout.frame[key] && layout.frame[key] !== 'auto' )
			return layout.frame[key];
		if( preset.recommend.frame && preset.recommend.frame[key] && preset.recommend.frame[key] !== 'auto' )
			return preset.recommend.frame[key];
		return ( pd._library.fallbacks || {} )[key] || 'auto';
	}

	function areaStyle( specArea, area ) {
		const key = specArea && specArea.style && specArea.style !== 'auto' ? specArea.style : area.recommend.style;
		return area.styles[key] || {};
	}

	function areaColumns( specArea, area ) {
		const style = areaStyle( specArea, area );
		const value = String( style.class || '' )+ ' '+ String( specArea && specArea.style || '' );
		if( /(?:nino-grid-m-25|four)/.test( value ) ) return 4;
		if( /(?:nino-grid-m-33|three)/.test( value ) ) return 3;
		if( /(?:nino-grid-m-50|two)/.test( value ) ) return 2;
		return 1;
	}

	function appendComponentPreview( wrap, type ) {
		if( type === 'image' )
			wrap.appendChild( element( 'span', 'pd-preview-media' ) );
		else if( type === 'title' )
			wrap.appendChild( element( 'span', 'pd-preview-title' ) );
		else if( type === 'subtitle' || type === 'description' || type === 'text' || type === 'price' || type === 'number' )
			wrap.appendChild( element( 'span', 'pd-preview-line' ) );
		else if( type === 'button' )
			wrap.appendChild( element( 'span', 'pd-preview-button' ) );
		else if( type === 'template' )
			wrap.appendChild( element( 'span', 'pd-preview-line pd-preview-template' ) );
	}

	function areaPreview( spec, preset ) {
		const wrap = element('div');
		Object.keys( preset.areas || {} ).forEach( function( areaKey ) {
			const area = preset.areas[areaKey];
			const specArea = spec.areas[areaKey] || {};
			const components = specArea.components || [];
			if( area.source === 'elements' ) {
				const items = element( 'div', 'pd-preview-items' );
				const columns = areaColumns( specArea, area );
				items.style.setProperty( '--pd-items', String( columns ) );
				for( let i = 0; i < columns; i++ )
					items.appendChild( element( 'span', 'pd-preview-item'+ ( components.some( function( component ) { return component.type === 'image' } ) ? ' has-image' : '' ) ) );
				wrap.appendChild( items );
				return;
			}
			components.forEach( function( component ) { appendComponentPreview( wrap, component.type ) } );
		} );
		if( wrap.childNodes.length === 0 )
			wrap.appendChild( element( 'span', 'pd-preview-line' ) );
		return Array.from( wrap.childNodes );
	}

	/**
	 *	The card's drawing of a section: its areas where a preset manages it,
	 *	and otherwise a title over three lines - what the canvas can say
	 *	about source it does not read
	 *
	 *	@param		{Object|null}	spec
	 *
	 *	@return		{Array}
	 */
	function preview( spec ) {
		const areaPreset = presetFor( spec );
		if( areaPreset )
			return areaPreview( spec, areaPreset );
		return [ element( 'span', 'pd-preview-title' ), element( 'span', 'pd-preview-line' ), element( 'span', 'pd-preview-line' ), element( 'span', 'pd-preview-line' ) ];
	}

	function rawLabel( source, position ) {
		if( source.trim() === '' )
			return Nino.content.getText('/_admin/templates/label/raw-spacing');
		return Nino.content.getText('/_admin/templates/label/raw-frame').replace( '%d', String( position + 1 ) );
	}

	function detachMetadata( source ) {
		return String( source || '' ).replace( /[\t ]*<!--\s*nino:section\s+\{[^\r\n]*\}\s*-->[\t ]*(?:\r?\n)?/, '' );
	}

	function createCard( section, sectionIndex ) {
		const templateSection = section.type === 'template';
		const spec = section.spec;
		const preset = presetFor( spec );
		const managed = preset !== null;
		const card = element( 'article', 'pd-section-card'+ ( pd._selectedId === section._clientId ? ' is-selected' : '' ) );
		card.dataset.kind = templateSection ? 'template' : ( managed ? 'managed' : 'custom' );
		card.tabIndex = 0;
		card.setAttribute( 'aria-label', Nino.content.getText('/_admin/templates/label/section-aria').replace( '%s', sectionLabel( section, sectionIndex ) ) );
		card.addEventListener( 'click', function() { pd.select( section._clientId ) } );
		card.addEventListener( 'keydown', function( event ) {
			if( event.key === 'Enter' || event.key === ' ' ) {
				event.preventDefault();
				pd.select( section._clientId );
			}
		} );

		card.appendChild( element( 'span', 'pd-section-accent' ) );
		const main = element( 'div', 'pd-section-main' );
		const copy = element( 'div', 'pd-section-copy' );
		const meta = element( 'div', 'pd-section-meta' );
		meta.appendChild( element( 'span', 'pd-badge', templateSection ? Nino.content.getText('/_admin/templates/label/badge-template') : ( managed ? Nino.adminUi.text( preset.category ) : Nino.content.getText('/_admin/templates/label/badge-custom') ) ) );
		meta.appendChild( element( 'span', 'pd-badge is-neutral', templateSection ? '[template]' : ( managed ? humanize( effectiveFrameValue( spec, preset, 'background' ) ) : '<section>' ) ) );
		copy.appendChild( meta );
		copy.appendChild( element( 'h3', '', sectionLabel( section, sectionIndex ) ) );
		copy.appendChild( element( 'p', '', templateSection ? ( section.path || '/templates/'+ section.template )+ '.tpl' : ( managed ? Nino.adminUi.text( preset.name ) : Nino.content.getText('/_admin/templates/hint/code-authored') ) ) );

		const bindings = element( 'div', 'pd-binding-row' );
		if( templateSection ) {
			const binding = element( 'span', 'pd-binding' );
			binding.append( element( 'b', '', '⌘' ), element( 'span', '', Nino.content.getText('/_admin/templates/label/binding-include') ) );
			bindings.appendChild( binding );
		}
		if( section.fills.length ) {
			const binding = element( 'span', 'pd-binding' );
			binding.append( element( 'b', '', 'T' ), element( 'span', '', ( section.fills.length === 1 ? Nino.content.getText('/_admin/templates/label/binding-fill') : Nino.content.getText('/_admin/templates/label/binding-fills') ).replace( '%d', String( section.fills.length ) ) ) );
			bindings.appendChild( binding );
		}
		if( section.elementTypes.length ) {
			const binding = element( 'span', 'pd-binding' );
			binding.append( element( 'b', '', 'E' ), element( 'span', '', section.elementTypes.join(', ') ) );
			bindings.appendChild( binding );
		}
		if( section.imageSlots.length ) {
			const binding = element( 'span', 'pd-binding' );
			binding.append( element( 'b', '', 'I' ), element( 'span', '', ( section.imageSlots.length === 1 ? Nino.content.getText('/_admin/templates/label/binding-slot') : Nino.content.getText('/_admin/templates/label/binding-slots') ).replace( '%d', String( section.imageSlots.length ) ) ) );
			bindings.appendChild( binding );
		}
		if( bindings.childNodes.length === 0 )
			bindings.appendChild( element( 'span', 'pd-binding', Nino.content.getText('/_admin/templates/label/binding-none') ) );
		copy.appendChild( bindings );

		const visual = element( 'div', 'pd-card-preview' );
		visual.dataset.surface = managed ? effectiveFrameValue( spec, preset, 'background' ) : 'default';
		if( templateSection ) {
			const templatePreview = element( 'div', 'pd-template-preview' );
			const icon = element( 'span', 'pd-template-preview-icon', section.template === 'html-header' ? 'HEAD' : ( section.template === 'html-footer' ? 'FOOT' : 'TPL' ) );
			const lines = element( 'span', 'pd-template-preview-lines' );
			lines.append( element( 'span', 'pd-preview-title' ), element( 'span', 'pd-preview-line' ) );
			templatePreview.append( icon, lines );
			visual.appendChild( templatePreview );
		} else
			preview( spec ).forEach( function( node ) { visual.appendChild( node ) } );
		main.append( copy, visual );

		const actions = element( 'div', 'pd-section-actions' );
		actions.append(
			button( '↑', Nino.content.getText('/_admin/templates/label/move-up'), function() { Nino.admin.templates.sectionsUI.move( section._clientId, -1 ) } ),
			button( '↓', Nino.content.getText('/_admin/templates/label/move-down'), function() { Nino.admin.templates.sectionsUI.move( section._clientId, 1 ) } ),
			button( '✎', templateSection ? Nino.content.getText('/_admin/templates/label/include-replace') : ( managed ? Nino.content.getText('/_admin/templates/label/edit-settings') : Nino.content.getText('/_admin/templates/label/edit-source') ), function() { Nino.admin.templates.sectionsUI.edit( section._clientId ) } ),
			button( '⎘', Nino.content.getText('/_admin/templates/label/duplicate-item'), function() { Nino.admin.templates.sectionsUI.duplicate( section._clientId ) } ),
			button( '×', Nino.content.getText('/_admin/templates/label/remove-item'), function() { Nino.admin.templates.sectionsUI.remove( section._clientId ) }, 'is-danger' )
		);

		card.append( main, actions );
		return card;
	}

	Object.assign( pd, { sectionsUI : {
		effectiveFrameValue : effectiveFrameValue,

		_inspectorToken : 0,
		_types : null,
		_images : null,
		_codeContext : null,

		humanize : humanize,
		detachMetadata : detachMetadata,

		renderCanvas : function() {
			const canvas = dc.getElementById('pd-canvas');
			if( !canvas || pd._current === null )
				return;
			canvas.innerHTML = '';
			const sectionCount = pd.model.sectionIndices( pd._current.segments ).length;
			let sectionIndex = 0;

			pd._current.segments.forEach( function( segment, segmentIndex ) {
				if( segment.type === 'slot' )
					return;
				if( segment.type === 'raw' ) {
					if( segment.source.trim() !== '' )
						canvas.appendChild( element( 'div', 'pd-raw-boundary', rawLabel( segment.source, segmentIndex ) ) );
					return;
				}

				canvas.appendChild( createCard( segment, sectionIndex++ ) );
				if( sectionIndex < sectionCount ) {
					const between = element( 'div', 'pd-add-between' );
					between.appendChild( button( '+', Nino.content.getText('/_admin/templates/label/add-here'), function() { pd.composer.open( { afterId : segment._clientId } ) } ) );
					canvas.appendChild( between );
				}
			} );

			if( sectionIndex === 0 ) {
				const empty = element( 'div', 'pd-empty-state' );
				empty.style.minHeight = '24rem';
				empty.append( element( 'h1', '', Nino.content.getText('/_admin/templates/empty/sections') ), element( 'p', '', Nino.content.getText('/_admin/templates/empty/sections-detail').replace( '%s', SHORTCODE ) ) );
				const actions = element( 'div', 'pd-toolbar-actions' );
				actions.style.marginTop = '1rem';
				actions.appendChild( button( Nino.content.getText('/_admin/templates/label/add-first'), Nino.content.getText('/_admin/templates/label/add-first-title'), function() { pd.composer.open( { afterId : null } ) }, 'nino-admin-btn-primary' ) );
				empty.appendChild( actions );
				canvas.appendChild( empty );
			}
		},

		move : function( clientId, direction ) {
			if( pd._current && pd.model.moveSection( pd._current.segments, clientId, direction ) ) {
				pd.setDirty( true );
				pd.sectionsUI.renderCanvas();
			}
		},

		remove : function( clientId ) {
			const section = pd.section( clientId );
			const label = section && section.type === 'template' ? section.template : ( section && ( section.htmlId || Nino.content.getText('/_admin/templates/label/without-id') ) );
			if( !section || !wn.confirm( Nino.content.getText('/_admin/templates/confirm/remove-section').replace( '%s', label ) ) )
				return;
			if( pd.model.removeSection( pd._current.segments, clientId ) ) {
				if( pd._selectedId === clientId )
					pd._selectedId = null;
				pd.setDirty( true );
				pd.sectionsUI.renderCanvas();
				pd.sectionsUI.renderInspector();
			}
		},

		edit : function( clientId ) {
			const section = pd.section( clientId );
			if( !section )
				return;
			if( section.type === 'template' )
				return pd.openInclude( { mode : 'replace', targetId : clientId } );
			if( presetFor( section.spec ) )
				return pd.composer.open( { mode : 'replace', targetId : clientId, spec : section.spec } );
			pd.sectionsUI.openCode( { mode : 'replace', targetId : clientId, source : section.source } );
		},

		duplicate : function( clientId ) {
			const section = pd.section( clientId );
			if( !section )
				return;
			if( section.type === 'template' ) {
				const copy = Object.assign( {}, section, { _clientId : 'pd-component-'+ (++pd._clientCounter) } );
				pd.model.insertSection( pd._current.segments, copy, clientId );
				pd._selectedId = copy._clientId;
				pd.setDirty( true );
				pd.renderDocument();
				return;
			}
			const suggested = pd.model.nextId( pd._current.segments, ( section.htmlId || 'section' )+ '-copy' );
			if( presetFor( section.spec ) )
				return pd.composer.open( { mode : 'insert', afterId : clientId, spec : Object.assign( {}, section.spec, { id : suggested } ) } );

			let source = section.source;
			if( section.htmlId )
				source = source.replace( /(\bid\s*=\s*["'])[^"']*(["'])/i, '$1'+ suggested+ '$2' );
			pd.sectionsUI.openCode( { mode : 'insert', afterId : clientId, source : source, title : Nino.content.getText('/_admin/templates/label/duplicate-source') } );
		},

		insertResult : function( result, context ) {
			const segment = Object.assign( {}, result.segment );
			segment._clientId = 'pd-component-'+ (++pd._clientCounter);

			if( context.mode === 'replace' ) {
				const current = pd.section( context.targetId );
				if( !current )
					return false;
				segment._clientId = current._clientId;
				const index = pd._current.segments.indexOf( current );
				pd._current.segments[index] = segment;
				pd._selectedId = segment._clientId;
			} else {
				pd.model.insertSection( pd._current.segments, segment, context.afterId || null );
				pd._selectedId = segment._clientId;
			}

			pd.setDirty( true );
			pd.sectionsUI.renderCanvas();
			pd.sectionsUI.renderInspector();
			return true;
		},

		openCode : function( context ) {
			const dialog = dc.getElementById('pd-code-dialog');
			pd.sectionsUI._codeContext = context;
			dc.getElementById('pd-code-title').textContent = context.title || ( context.mode === 'replace' ? Nino.content.getText('/_admin/templates/label/edit-source') : Nino.content.getText('/_admin/templates/label/insert-source') );
			const note = dc.getElementById('pd-code-note');
			note.textContent = context.detachManaged === true
				? Nino.content.getText('/_admin/templates/hint/detach')
				: Nino.content.getText('/_admin/templates/hint/one-section');
			const source = context.detachManaged === true ? detachMetadata( context.source ) : context.source;
			/*	A component's source is a part of a section rather than one, so
				it neither gets the <section> skeleton as a starting point nor the
				note about detaching - nothing detaches, which is the whole reason
				this mode exists	*/
			if( context.mode === 'component' )
				note.textContent = Nino.content.getText('/_admin/templates/hint/one-component');
			dc.getElementById('pd-code-source').value = context.mode === 'component'
				? ( source || '' )
				: ( source || '<section id="section-id" class="nino-section">\n\t<div class="nino-grid-row">\n\t</div>\n</section>\n' );
			// A collection's HTML+ is the item, filled per record: the fields it can
			// name, written as the fills they are. The sentence carries a %s and
			// this fills it - a fill value holding [[...]] would be resolved
			// before it ever got here (see SHORTCODE above)
			const fields = dc.getElementById('pd-code-fields');
			if( fields ) {
				const names = context.mode === 'component' && Array.isArray( context.fields ) ? context.fields.concat( [ '.id' ] ) : [];
				fields.hidden = names.length === 0;
				fields.textContent = names.length === 0 ? '' : Nino.content.getText('/_admin/templates/hint/loop-fields').replace( '%s', names.map( function( name ) { return '[['+ name+ ']]' } ).join(', ') );
			}
			dc.getElementById('pd-code-error').textContent = '';
			dialog.showModal();
			dc.getElementById('pd-code-source').focus();
		},

		submitCode : function() {
			const context = pd.sectionsUI._codeContext;
			const source = dc.getElementById('pd-code-source').value;
			const message = dc.getElementById('pd-code-error');
			message.textContent = Nino.content.getText('/_admin/templates/msg/checking-section');

			/*	One component's source goes back into the draft and is checked by
				composing it - the same composer that will write the section, so
				what it refuses here it would have refused on save, and the reason
				is the one the editor reads	*/
			if( context.mode === 'component' ) {
				pd.areaComposer.applyComponentSource( context.component, source ).then( function() {
					dc.getElementById('pd-code-dialog').close();
					pd.toast( Nino.content.getText('/_admin/templates/msg/source-updated'), false );
				} ).catch( function( error ) { message.textContent = error.message } );
				return;
			}

			pd.api( 'documents/inspect', { name : pd._current.name, source : source } ).then( function( response ) {
				const duplicate = pd.sections().find( function( section ) {
					return section.htmlId && section.htmlId === response.segment.htmlId && section._clientId !== context.targetId;
				} );
				if( duplicate )
					throw new Error( Nino.content.getText('/_admin/templates/error/duplicate-id').replace( '%s', response.segment.htmlId ) );
				pd.sectionsUI.insertResult( response, context );
				dc.getElementById('pd-code-dialog').close();
				pd.toast( context.mode === 'replace' ? Nino.content.getText('/_admin/templates/msg/source-updated') : Nino.content.getText('/_admin/templates/msg/source-inserted'), false );
			} ).catch( function( error ) {
				message.textContent = error.message;
			} );
		},

		renderInspector : function() {
			const empty = dc.getElementById('pd-inspector-empty');
			const content = dc.getElementById('pd-inspector-content');
			const section = pd.selectedSection();
			const token = ++pd.sectionsUI._inspectorToken;
			content.innerHTML = '';

			if( !section ) {
				empty.classList.remove('pd-hidden');
				content.classList.add('pd-hidden');
				return;
			}

			empty.classList.add('pd-hidden');
			content.classList.remove('pd-hidden');
			const spec = section.spec;
			const preset = presetFor( spec );
			const templateSection = section.type === 'template';
			const title = element( 'div', 'pd-inspector-title' );
			const titleCopy = element('div');
			titleCopy.append(
				element( 'span', 'pd-eyebrow', templateSection ? Nino.content.getText('/_admin/templates/label/badge-template') : ( preset ? Nino.adminUi.text( preset.name ) : Nino.content.getText('/_admin/templates/label/badge-custom') ) ),
				element( 'h2', '', templateSection ? section.template : ( section.htmlId || Nino.content.getText('/_admin/templates/label/section-noid') ) )
			);
			title.append( titleCopy, button( '✎', Nino.content.getText('/_admin/templates/label/edit-section'), function() { pd.sectionsUI.edit( section._clientId ) }, 'pd-icon-button' ) );
			content.appendChild( title );

			if( templateSection ) {
				const include = element( 'section', 'pd-inspector-section' );
				include.append( element( 'h3', '', Nino.content.getText('/_admin/templates/label/include-heading').replace( '%s', SHORTCODE ) ), element( 'code', 'pd-template-code', section.source.trim() ) );
				const includeActions = element( 'div', 'pd-inspector-actions' );
				includeActions.style.marginTop = '.7rem';
				includeActions.append(
					button( Nino.content.getText('/_admin/templates/label/replace'), Nino.content.getText('/_admin/templates/label/replace-title'), function() { pd.sectionsUI.edit( section._clientId ) } ),
					button( Nino.content.getText('/_admin/templates/label/duplicate'), Nino.content.getText('/_admin/templates/label/duplicate-include'), function() { pd.sectionsUI.duplicate( section._clientId ) } )
				);
				include.appendChild( includeActions );
				content.appendChild( include );

				const templateLifecycle = element( 'section', 'pd-inspector-section' );
				templateLifecycle.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/include-actions') ) );
				const templateActions = element( 'div', 'pd-inspector-actions' );
				templateActions.append(
					button( Nino.content.getText('/_admin/templates/label/moveup'), Nino.content.getText('/_admin/templates/label/moveup-include'), function() { pd.sectionsUI.move( section._clientId, -1 ) } ),
					button( Nino.content.getText('/_admin/templates/label/remove'), Nino.content.getText('/_admin/templates/label/remove-include'), function() { pd.sectionsUI.remove( section._clientId ) }, 'is-danger' )
				);
				templateLifecycle.appendChild( templateActions );
				content.appendChild( templateLifecycle );
				return;
			}

			if( preset ) {
				const structure = element( 'section', 'pd-inspector-section' );
				structure.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/structure') ) );
				const grid = element( 'div', 'pd-spec-grid' );
				const details = [
					[ Nino.content.getText('/_admin/templates/label/background'), effectiveFrameValue( spec, preset, 'background' ) ],
					[ Nino.content.getText('/_admin/templates/label/layout'), spec.layout === 'auto' ? preset.recommend.layout : spec.layout ],
					[ Nino.content.getText('/_admin/templates/label/areas'), Object.keys( spec.areas ).length ],
					[ Nino.content.getText('/_admin/templates/label/components'), Object.keys( spec.areas ).reduce( function( count, key ) { return count + ( spec.areas[key].components || [] ).length }, 0 ) ],
					[ Nino.content.getText('/_admin/templates/label/collections'), Object.keys( preset.areas ).filter( function( key ) { return preset.areas[key].source === 'elements' } ).length ],
					[ Nino.content.getText('/_admin/templates/label/motion'), spec.pageMotion ],
				];
				details.forEach( function( item ) {
					const cell = element( 'div', 'pd-spec-item' );
					cell.append( element( 'small', '', item[0] ), element( 'strong', '', humanize( item[1] ) ) );
					grid.appendChild( cell );
				} );
				structure.appendChild( grid );
				const actions = element( 'div', 'pd-inspector-actions' );
				actions.style.marginTop = '.7rem';
				actions.append(
					button( Nino.content.getText('/_admin/templates/label/settings'), Nino.content.getText('/_admin/templates/label/edit-settings'), function() { pd.sectionsUI.edit( section._clientId ) } ),
					button( 'HTML+', Nino.content.getText('/_admin/templates/label/detach'), function() { pd.sectionsUI.openCode( { mode : 'replace', targetId : section._clientId, source : section.source, detachManaged : true } ) } )
				);
				structure.appendChild( actions );
				content.appendChild( structure );
			}

			pd.sectionsUI.renderNativeContent( content, section, token );
			pd.sectionsUI.renderResources( content, section, token );

			const lifecycle = element( 'section', 'pd-inspector-section' );
			lifecycle.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/section-actions') ) );
			const actions = element( 'div', 'pd-inspector-actions' );
			actions.append(
				button( Nino.content.getText('/_admin/templates/label/duplicate'), Nino.content.getText('/_admin/templates/label/duplicate-section'), function() { pd.sectionsUI.duplicate( section._clientId ) } ),
				button( Nino.content.getText('/_admin/templates/label/remove'), Nino.content.getText('/_admin/templates/label/remove-section'), function() { pd.sectionsUI.remove( section._clientId ) }, 'is-danger' )
			);
			lifecycle.appendChild( actions );
			content.appendChild( lifecycle );
		},

		renderNativeContent : function( container, section, token ) {
			if( section.fills.length === 0 )
				return;
			const panel = element( 'section', 'pd-inspector-section' );
			panel.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/native-content') ) );
			const status = element( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/templates/msg/loading-fills') );
			panel.appendChild( status );
			container.appendChild( panel );

			pd.api( 'content/fields', { name : pd._current.name, keys : section.fills } ).then( function( response ) {
				if( token !== pd.sectionsUI._inspectorToken )
					return;
				status.remove();
				const fields = element( 'div', 'pd-content-fields' );
				response.fields.forEach( function( entry ) {
					const field = element( 'label', 'pd-content-field' );
					const label = element('span');
					const suffix = entry.key.split('/').pop();
					label.append( element( 'b', '', humanize( suffix ) ), element( 'small', '', entry.global ? Nino.content.getText('/_admin/templates/label/fill-global') : ( entry.exists ? response.nativeLocale : Nino.content.getText('/_admin/templates/label/fill-new').replace( '%s', response.nativeLocale ) ) ) );
					const long = [ 'description', 'content', 'subtitle', 'quote', 'address' ].includes( suffix );
					const input = element( long ? 'textarea' : 'input' );
					input.value = entry.value;
					// Only the page template's own keys are saved from here; a word of
					// another template, of the project or of the system is shown as it
					// is, and edited in the Text panel
					if( entry.writable === false ) {
						input.readOnly = true;
						input.title = Nino.content.getText('/_admin/templates/hint/fill-readonly');
					} else {
						input.dataset.key = entry.key;
						input.dataset.create = entry.exists ? 'false' : 'true';
					}
					field.append( label, input );
					fields.appendChild( field );
				} );
				panel.appendChild( fields );
				const footer = element( 'div', 'pd-content-footer' );
				const message = element( 'span', '', Nino.content.getText('/_admin/templates/hint/native-locale') );
				const save = button( Nino.content.getText('/_admin/templates/label/save-content'), Nino.content.getText('/_admin/templates/label/save-content-title'), function() {
					save.disabled = true;
					message.textContent = Nino.content.getText('/_admin/templates/msg/saving-content');
					const items = Array.from( fields.querySelectorAll('[data-key]') ).map( function( input ) { return { key : input.dataset.key, value : input.value, create : input.dataset.create === 'true' } } );
					pd.api( 'content/save', { name : pd._current.name, items : items } ).then( function() {
						save.disabled = false;
						message.textContent = Nino.content.getText('/_admin/templates/msg/content-saved');
						pd.toast( Nino.content.getText('/_admin/templates/msg/content-saved-toast'), false );
					} ).catch( function( error ) {
						save.disabled = false;
						message.textContent = error.message;
					} );
				}, 'nino-admin-btn-primary' );
				footer.append( message, save );
				panel.appendChild( footer );
			} ).catch( function( error ) {
				if( token === pd.sectionsUI._inspectorToken ) {
					status.className = 'nino-admin-error';
					status.textContent = error.message;
				}
			} );
		},

		ensureTypes : function() {
			if( pd.sectionsUI._types !== null )
				return Promise.resolve( pd.sectionsUI._types );
			return pd.api( 'content/types', {} ).then( function( response ) {
				pd.sectionsUI._types = response.types || [];
				return pd.sectionsUI._types;
			} );
		},

		ensureImages : function() {
			if( pd.sectionsUI._images !== null )
				return Promise.resolve( pd.sectionsUI._images );
			return pd.api( 'content/images', {} ).then( function( response ) {
				pd.sectionsUI._images = response.slots || [];
				return pd.sectionsUI._images;
			} );
		},

		renderResources : function( container, section, token ) {
			const preset = presetFor( section.spec );
			if( section.imageSlots.length ) {
				const images = element( 'section', 'pd-inspector-section' );
				images.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/image-slots') ) );
				const imageList = element( 'div', 'pd-resource-list' );
				imageList.appendChild( element( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/templates/msg/checking-slots') ) );
				images.appendChild( imageList );
				container.appendChild( images );

				pd.sectionsUI.ensureImages().then( function( slots ) {
					if( token !== pd.sectionsUI._inspectorToken )
						return;
					imageList.innerHTML = '';
					section.imageSlots.forEach( function( uri ) {
						const existing = slots.find( function( slot ) { return slot.uri === uri } );
						const row = element( 'div', 'pd-resource' );
						row.appendChild( element( 'code', '', uri ) );
						if( existing ) {
							const link = element( 'a', '', existing.hasImage ? Nino.content.getText('/_admin/templates/label/edit-image') : Nino.content.getText('/_admin/templates/label/upload-image') );
							// The workbench addresses a screen by hash and reads no
							// query at all, so a '?tab=' link only ever landed on
							// whichever panel the rail lists first. The Images panel
							// groups its slots by the first segment of their uri and
							// restores that group from the hash, so the row opens the
							// group this slot is in
							link.href = pd.assetUrl( '/_admin/#images/'+ encodeURIComponent( uri.split('/').filter( Boolean )[0] || '' ) );
							row.appendChild( link );
						} else {
							const request = preset ? pd.sectionsUI.areaImageRequest( section.spec, preset, uri ) : null;
							if( request ) row.appendChild( button( Nino.content.getText('/_admin/templates/label/create-slot'), Nino.content.getText('/_admin/templates/label/create-slot-title').replace( '%s', uri ), function() {
								pd.api( 'content/image-create', Object.assign( { name : pd._current.name }, request ) ).then( function() {
									pd.sectionsUI._images.push( { uri : uri, hasImage : false } );
									pd.toast( Nino.content.getText('/_admin/templates/msg/slot-created'), false );
									pd.sectionsUI.renderInspector();
								} ).catch( function( error ) { pd.toast( error.message, true ) } );
							} ) );
							else {
								// A slot that does not exist yet is defined on the
								// Slots tab, not among the uploads
								const link = element( 'a', '', Nino.content.getText('/_admin/templates/label/create-in-admin') );
								link.href = pd.assetUrl( '/_admin/#slots' );
								row.appendChild( link );
							}
						}
						imageList.appendChild( row );
					} );
				} ).catch( function( error ) {
					imageList.innerHTML = '';
					imageList.appendChild( element( 'p', 'nino-admin-error', error.message ) );
				} );
			}

			if( section.elementTypes.length === 0 )
				return;
			const elements = element( 'section', 'pd-inspector-section' );
			elements.appendChild( element( 'h3', '', Nino.content.getText('/_admin/templates/label/collections-heading') ) );
			const list = element( 'div', 'pd-resource-list' );
			list.appendChild( element( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/templates/msg/checking-types') ) );
			elements.appendChild( list );
			container.appendChild( elements );

			pd.sectionsUI.ensureTypes().then( function( types ) {
				if( token !== pd.sectionsUI._inspectorToken )
					return;
				list.innerHTML = '';
				section.elementTypes.forEach( function( uri ) {
					const existing = types.find( function( entry ) { return entry.type === uri } );
					const row = element( 'div', 'pd-resource' );
					row.appendChild( element( 'code', '', uri ) );
					if( existing ) {
						const link = element( 'a', '', Nino.content.getText('/_admin/templates/label/edit-elements') );
						link.href = pd.assetUrl( '/_admin/#elements/'+ encodeURIComponent( uri ) );
						row.appendChild( link );
					} else if( preset ) {
						const area = Object.keys( preset.areas ).find( function( key ) {
							return preset.areas[key].source === 'elements' && section.spec.areas[key] && section.spec.areas[key].source.elementType === uri;
						} );
						if( area ) row.appendChild( button( Nino.content.getText('/_admin/templates/label/create-type'), Nino.content.getText('/_admin/templates/label/create-type-title').replace( '%s', uri ), function() {
							pd.api( 'content/type-create', { preset : section.spec.preset, area : area, uri : uri, title : humanize( uri ) } ).then( function( response ) {
								pd.sectionsUI._types.push( { type : response.uri, title : response.title, model : response.model } );
								pd.toast( Nino.content.getText('/_admin/templates/msg/type-created').replace( '%s', uri ), false );
								pd.sectionsUI.renderInspector();
							} ).catch( function( error ) { pd.toast( error.message, true ) } );
						} ) );
						else {
							const link = element( 'a', '', Nino.content.getText('/_admin/templates/label/create-in-admin') );
							link.href = pd.assetUrl( '/_admin/#types' );
							row.appendChild( link );
						}
					} else {
						const link = element( 'a', '', Nino.content.getText('/_admin/templates/label/create-in-admin') );
						link.href = pd.assetUrl( '/_admin/#types' );
						row.appendChild( link );
					}
					list.appendChild( row );
				} );
			} ).catch( function( error ) {
				list.innerHTML = '';
				list.appendChild( element( 'p', 'nino-admin-error', error.message ) );
			} );
		},

		areaImageRequest : function( spec, preset, uri ) {
			const generatedPrefix = '/template/'+ spec.pageId+ '/'+ spec.id+ '/';
			if( uri === generatedPrefix+ 'background' )
				return { preset : spec.preset, slot : 'background', uri : uri, label : Nino.content.getText('/_admin/templates/label/background-image') };
			for( const areaKey of Object.keys( preset.areas || {} ) ) {
				const area = preset.areas[areaKey];
				if( area.source !== 'single' || !spec.areas[areaKey] ) continue;
				for( const component of spec.areas[areaKey].components || [] ) {
					if( component.type === 'image' && component.bindings && component.bindings.src === uri && uri === generatedPrefix+ component.id )
						// area.label, not its fill key: this caption is stored with the
						// image slot and outlives the interface language that made it
						return { preset : spec.preset, slot : areaKey+ '.'+ component.id+ '.src', area : areaKey, component : component.id, property : 'src', uri : uri, label : Nino.adminUi.text( area.label )+ ' · '+ Nino.content.getText('/_admin/templates/label/image') };
				}
			}
			return null;
		},

		init : function() {
			const form = dc.getElementById('pd-code-form');
			if( !form )
				return;
			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				pd.sectionsUI.submitCode();
			} );
			dc.querySelectorAll('.pd-code-close').forEach( function( close ) {
				close.addEventListener( 'click', function() { dc.getElementById('pd-code-dialog').close() } );
			} );
			const source = dc.getElementById('pd-code-source');
			source.addEventListener( 'keydown', function( event ) {
				if( event.key !== 'Tab' )
					return;
				event.preventDefault();
				const start = source.selectionStart;
				source.value = source.value.slice( 0, start )+ '\t'+ source.value.slice( source.selectionEnd );
				source.selectionStart = source.selectionEnd = start + 1;
			} );
		},
	} } );

	Nino.events.bindCallback( 'ready', pd.sectionsUI.init );

})(window, document);
