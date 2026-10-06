<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Templates\Composer	Composes one managed section out of a preset and its bindings
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Templates {

	/**
	 *	Validated SectionSpec -> ordinary HTML+ source. The output is copied
	 *	into the page and remains independent of the library at runtime.
	 *
	 *	What a section can be is the library's to say, preset by preset, in
	 *	library/<key>/manifest.php - this class resolves a key there and
	 *	composes, and knows no kind of section of its own. It used to: a
	 *	modules() catalogue of 28 section types beside the manifests, with
	 *	names, layouts, fields and Elements models, left from the composer
	 *	before named areas. No preset named one, and the library hands the
	 *	panel version-3 manifests only, so it answered for nothing but itself.
	 */
	class Composer {

		/*	The one class of feature that writes markup in php on purpose, and
			the reason it may: what this composes is not a view of anything - it
			is the source of a template, which is the product the Template Builder
			exists to make. A .tpl of its own would be a template that writes a
			template, and every section it assembles already comes out of the
			library's own .tpl files (see Library::template()). What is here is the
			scaffolding between them and the indentation that makes the result
			readable to whoever opens it afterwards.

			See AGENTS.md, "Markup belongs in a template", which names this as the
			exception it is	*/

		/**
		 * One shortcode's argument list, for the inert preview's own matching.
		 * Quoted values are consumed whole because a bound alt text compiles to
		 * alt="[[/template/page-…/…-alt]]" - reading up to the first "]" ended the match
		 * inside that fill and left its tail (]"]) standing in the preview.
		 */
		private const string SHORTCODE_ARGUMENTS = '(?:"[^"]*"|\'[^\']*\'|[^\]"\'])*';

		/**
		 * What the preview takes of the texts the panel holds: how many, and
		 * how long each one may be
		 */
		private const int PREVIEW_MAX_TEXTS = 100;
		private const int PREVIEW_MAX_TEXT_BYTES = 4000;

		/**
		 *	Compose one section from a preset.
		 *
		 *	Every preset in the library is an Area preset - Library::presets()
		 *	skips a manifest that does not declare version 3 - so this resolves
		 *	the key and hands straight over. It stays a method of its own
		 *	because the key lookup and the "unknown preset" answer belong to the
		 *	caller's vocabulary, not to the Area composer's.
		 *
		 *	@param		array			$input				Preset key plus the browser's own values
		 *	@param		bool			$preview			Mark every area for the panel's preview frame
		 *
		 *	@return 	array								{ source, spec, fields, imageSlots, elementSchema, segment }
		 */
		public static function compose( array $input, bool $preview = false ): array {

			$preset = Library::preset( (string) ( $input['preset'] ?? 'blank' ) );

			if( $preset === null )
				throw new \InvalidArgumentException( 'unknown section preset' );

			return AreaComposer::compose( $input, $preset, $preview );
		}

		/**
		 * Render the real generated section with deterministic fixture content.
		 * The preview is isolated in a sandboxed iframe by the client; no project
		 * content is read and no Elements collection has to exist yet.
		 *
		 * Every area carries a data-pd-area marker here and nowhere else, so the
		 * panel can dim the ones the operator is not editing.
		 *
		 * @param		array			$input				The draft, plus 'texts' where the panel holds typed values - see previewTexts()
		 * @param		?callable	$text					Answers a fill key with its text in the workbench's language, for the samples of an empty field
		 */
		public static function preview( array $input, ?callable $text = null ): ?string {

			$input['pageId'] = (string) ( $input['pageId'] ?? 'preview' );
			$input['id'] = (string) ( $input['id'] ?? 'preview-section' );
			$input['elementType'] = (string) ( $input['elementType'] ?? 'preview-items' );

			try {
				$result = self::compose( $input, true );
			} catch( \Throwable ) {
				return null;
			}

			$preset = Library::preset( (string) ( $input['preset'] ?? '' ) );

			return self::_previewHtml( $result['source'], self::previewSamples( $preset ?? [], $result, self::previewTexts( $input['texts'] ?? null, $result ), $text ) );
		}

		/**
		 *	What the panel typed into the section's text fields, as far as the
		 *	preview takes it: the first 100 entries of a fill key and a text, each
		 *	text cut to 4000 bytes, and only the keys the composed section
		 *	has a field for. Anything else - another shape, the entries after
		 *	the hundredth, a key the section does not own - is left out rather
		 *	than refused,
		 *	since the preview is a courtesy and has to render whatever it is
		 *	sent. A typed value goes through \Nino\Text::sanitizeValue()
		 *	later, in previewSamples(), the way saving it would
		 *
		 *	@param		mixed			$texts				$input['texts']
		 *	@param		array			$result				What compose() answered
		 *
		 *	@return 	array								[ fill key => text ]
		 */
		private static function previewTexts( mixed $texts, array $result ): array {

			if( is_array( $texts ) === false )
				return [];

			// The first ones are kept: a section with more fields than the cap
			// shows the typed text of those, where an answer of nothing at all
			// would show the sample over what has been typed
			$texts = array_slice( $texts, 0, self::PREVIEW_MAX_TEXTS, true );

			$keys = array_column( (array) ( $result['fields'] ?? [] ), 'key' );
			$typed = [];

			foreach( $texts as $key => $value )
				if( is_string( $value ) === true && in_array( (string) $key, $keys, true ) === true )
					$typed[(string) $key] = mb_strcut( $value, 0, self::PREVIEW_MAX_TEXT_BYTES, 'UTF-8' );

			return $typed;
		}

		/**
		 *	What the preview shows for the fills of one composed section, and
		 *	where it comes from - nowhere in this class. A textfill the section
		 *	creates shows what the panel has typed into it, made as safe as
		 *	saving it would make it; where nothing is typed - and a new
		 *	section starts empty - it shows the field's sample, the workbench's
		 *	own words for what such a field is for (the component catalogue's
		 *	'sample', a fill key), so the preview reads as a section with text
		 *	in it although none of that is stored. Every other fill - the
		 *	fields a collection loops over, the project texts a layout writes
		 *	in - shows what the preset's manifest names under 'samples'. A fill
		 *	neither answers is shown as its own name.
		 *
		 *	This used to be a table of thirty-six sample values here, keyed by
		 *	field name, that knew the presets from the outside: that a table's
		 *	columns are a service and a duration, what a price is, what the
		 *	contact form's company fields say - and, over its keys, the section
		 *	types of the composer before named areas, which nineteen of them
		 *	were for and nothing asked any more
		 *
		 *	@param		array			$preset				Library::preset(), [] for none
		 *	@param		array			$result				What compose() answered
		 *	@param		array			$texts				What the panel typed, by fill key - see previewTexts()
		 *	@param		?callable	$text					Resolves a sample's fill key; without one the field's own name stands in for it
		 *
		 *	@return 	array								[ fill => text or list of texts ]
		 */
		public static function previewSamples( array $preset, array $result, array $texts = [], ?callable $text = null ): array {

			$samples = (array) ( $preset['samples'] ?? [] );

			foreach( (array) ( $result['fields'] ?? [] ) as $field ) {

				$key = (string) $field['key'];
				$typed = (string) ( $texts[$key] ?? '' );

				if( $typed !== '' ) {
					$samples[$key] = \Nino\Text::sanitizeValue( $typed, false );
					continue;
				}

				$sample = $text !== null && (string) ( $field['sample'] ?? '' ) !== '' ? (string) $text( (string) $field['sample'] ) : '';
				$samples[$key] = $sample !== '' ? $sample : self::_previewName( $key );
			}

			return $samples;
		}

		/**
		 *	One fill's preview value, null where neither the section nor its
		 *	manifest answers it. The two fills of the preview's own mechanics
		 *	answer '' - the frame is inert and loads nothing from a project
		 *	path. A list gives each item its own entry, round again after the
		 *	last; %n in a text is the item's number
		 *
		 *	@param		string			$token				The fill, without its brackets
		 *	@param		?int			$index				The item's position inside a loop, null outside one
		 *	@param		array			$samples			See previewSamples()
		 *
		 *	@return 	?string
		 */
		public static function previewSample( string $token, ?int $index, array $samples ): ?string {

			if( $token === '/nino/dir' || $token === '/nino/public' )
				return '';

			if( isset( $samples[$token] ) === false )
				return null;

			$position = $index ?? 0;
			$sample = $samples[$token];

			if( is_array( $sample ) === true )
				$sample = (string) $sample[ $position % count( $sample ) ];

			return str_replace( '%n', (string) ( $position + 1 ), (string) $sample );
		}

		private static function _previewHtml( string $source, array $samples ): string {

			$source = preg_replace( '/[\t ]*<!--\s*nino:section\s+\{[^\r\n]*\}\s*-->[\t ]*(?:\r?\n)?/', '', $source ) ?? $source;
			$source = preg_replace( '#<script\b[^>]*>.*?</script\s*>#is', '', $source ) ?? $source;
			$source = preg_replace( '#<script\b[^>]*>.*$#is', '', $source ) ?? $source;
			$source = preg_replace( '#</?script\b[^>]*>#is', '', $source ) ?? $source;
			$source = preg_replace( '#\s+on[a-z0-9:_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $source ) ?? $source;
			$source = preg_replace( '#<\?.*?\?>#s', '', $source ) ?? $source;
			// Preview frames deliberately run without Nino.ui.js. Remove VPA's
			// hidden initial state (and its variants) instead of leaving motion-
			// enabled sections permanently transparent inside the iframe.
			$source = preg_replace_callback( '#(\bclass\s*=\s*)(["\'])(.*?)\2#is', function( array $match ): string {
				$classes = preg_split( '/\s+/', trim( $match[3] ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
				$previewClasses = array_values( array_filter(
					$classes,
					fn( string $class ): bool => $class !== 'nino-vpa' && str_starts_with( $class, 'nino-vpa--' ) === false
				) );
				if( $previewClasses === $classes )
					return $match[0];
				return $match[1]. $match[2]. implode( ' ', $previewClasses ). $match[2];
			}, $source ) ?? $source;
			$source = preg_replace_callback( '#\[image\s+'. self::SHORTCODE_ARGUMENTS. '\]#i', fn(): string => '<img src="'. self::_previewImage( 'Section image' ). '" alt="">', $source ) ?? $source;

			$source = preg_replace_callback( '#\[elements\s+('. self::SHORTCODE_ARGUMENTS. ')\](.*?)\[/elements\]#is', function( array $match ) use ( $samples ): string {
				$limit = [];
				preg_match( '/\blimit="(\d+)"/i', $match[1], $limit );
				$columns = match( true ) {
					preg_match( '/(?:^|\s)nino-grid-[a-z]+-25(?:\s|["\'])/i', $match[2] ) === 1 => 4,
					preg_match( '/(?:^|\s)nino-grid-[a-z]+-33(?:\s|["\'])/i', $match[2] ) === 1 => 3,
					preg_match( '/(?:^|\s)nino-grid-[a-z]+-50(?:\s|["\'])/i', $match[2] ) === 1 => 2,
					preg_match( '/(?:^|\s)nino-grid-[a-z]+-100(?:\s|["\'])/i', $match[2] ) === 1 => 1,
					default => 3,
				};
				$count = min( $columns, max( 1, (int) ( $limit[1] ?? $columns ) ) );
				$out = '';
				for( $index = 0; $index < $count; $index++ ) {
					$item = $match[2];
					$item = str_replace( '[[.id]]', (string) $index, $item );
					$item = preg_replace_callback(
						'#src=(["\'])[^"\']*/images/\[\[image\]\]\1#i',
						fn( array $imageMatch ): string => 'src='. $imageMatch[1]. self::_previewImage( 'Preview item '. ( $index + 1 ) ). $imageMatch[1],
						$item
					) ?? $item;
					$item = preg_replace_callback(
						'#\[\[([^\]]+)\]\]#',
						fn( array $fill ): string => self::_previewFill( $fill[1], $index, $samples ),
						$item
					) ?? $item;
					$out .= $item;
				}
				return $out;
			}, $source ) ?? $source;

			// [elementvalues] loops one field's distinct values, not records - the
			// field's own sample for the first three items stands in, so the
			// buttons name what the sample cards above carry
			$source = preg_replace_callback( '#\[elementvalues\s+('. self::SHORTCODE_ARGUMENTS. ')\](.*?)\[/elementvalues\]#is', function( array $match ) use ( $samples ): string {
				$field = [];
				preg_match( '/\bkey="([^"]+)"/i', $match[1], $field );
				// Honour limit the same way the [elements] fixture above does, so
				// a preset that bounds its button row previews what it ships
				$limit = [];
				preg_match( '/\blimit="(\d+)"/i', $match[1], $limit );
				$count = min( 3, max( 1, (int) ( $limit[1] ?? 3 ) ) );
				$values = [];
				for( $index = 0; $index < $count; $index++ )
					$values[] = self::_previewFill( (string) ( $field[1] ?? '' ), $index, $samples );
				$out = '';
				foreach( array_values( array_unique( $values ) ) as $index => $value )
					$out .= str_replace( [ '[[.id]]', '[[.value]]', '[[.count]]' ], [ (string) $index, $value, '1' ], $match[2] );
				return $out;
			}, $source ) ?? $source;

			$source = preg_replace_callback(
				'#\[\[([^\]]+)\]\]#',
				fn( array $fill ): string => self::_previewFill( $fill[1], null, $samples ),
				$source
			) ?? $source;
			$source = str_replace( '[csrf]', '', $source );
			// Each looping shortcode is paired with its own closer via a
			// backreference: a shared (?:elements|elementvalues) alternation let a
			// leftover [elements] swallow everything up to a stray
			// [/elementvalues] and vice versa.
			$source = preg_replace( '#\[(elements|elementvalues)\b\s*'. self::SHORTCODE_ARGUMENTS. '\](?:.*?\[/\1\])?#is', '', $source ) ?? $source;
			$source = preg_replace( '#\[(?:template|image)\b\s*'. self::SHORTCODE_ARGUMENTS. '\]#is', '', $source ) ?? $source;
			$source = preg_replace( '#(<iframe\b[^>]*\bsrc=)(["\'])[^"\']*\2#i', '$1$2about:blank$2', $source ) ?? $source;
			$source = preg_replace( '#(<form\b[^>]*\baction=)(["\'])[^"\']*\2#i', '$1$2#$2', $source ) ?? $source;
			// Last, after every fill is in: a text the panel typed is substituted
			// by then, and an address of it that reads javascript: would be one
			// the pass over the source could not have seen
			$source = preg_replace_callback(
				'#\s+(href|src|action|formaction|xlink:href)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i',
				fn( array $match ): string => ' '. $match[1]. '="#"',
				$source
			) ?? $source;
			$source = preg_replace( '#\s+(href|src|action|formaction|xlink:href)\s*=\s*javascript:[^\s>]*#i', ' $1="#"', $source ) ?? $source;

			return $source;
		}

		/**
		 *	A fill as the preview shows it: its sample, or its own name where
		 *	nothing answers it - a preset whose manifest leaves a fill out
		 *	shows the gap rather than borrowing another preset's words
		 *
		 *	@param		string			$token
		 *	@param		?int			$index
		 *	@param		array			$samples
		 *
		 *	@return 	string
		 */
		private static function _previewFill( string $token, ?int $index, array $samples ): string {
			return self::previewSample( $token, $index, $samples ) ?? self::_previewName( $token );
		}

		/**
		 *	A fill's own name as a phrase: its last segment, words apart and
		 *	capitalised
		 *
		 *	@param		string		$token
		 *
		 *	@return 	string
		 */
		private static function _previewName( string $token ): string {
			return ucwords( str_replace( [ '-', '_' ], ' ', strtolower( basename( str_replace( '\\', '/', $token ) ) ) ) );
		}

		private static function _previewImage( string $label ): string {

			$hue = abs( crc32( $label ) ) % 360;
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="hsl('. $hue.',55%,72%)"/><stop offset="1" stop-color="hsl('. ( ( $hue + 65 ) % 360 ). ',48%,38%)"/></linearGradient></defs><rect width="1200" height="800" fill="url(#g)"/><circle cx="900" cy="190" r="220" fill="rgba(255,255,255,.16)"/><path d="M0 650L340 390l190 150 190-190 480 360v90H0z" fill="rgba(255,255,255,.2)"/></svg>';
			return 'data:image/svg+xml,'. rawurlencode( $svg );
		}

	}

}
