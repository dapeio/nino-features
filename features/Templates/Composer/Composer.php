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
		 * alt="[[/page-…/…-alt]]" - reading up to the first "]" ended the match
		 * inside that fill and left its tail (]"]) standing in the preview.
		 */
		private const string SHORTCODE_ARGUMENTS = '(?:"[^"]*"|\'[^\']*\'|[^\]"\'])*';

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
		 */
		public static function preview( array $input ): ?string {

			$input['pageId'] = (string) ( $input['pageId'] ?? 'preview' );
			$input['id'] = (string) ( $input['id'] ?? 'preview-section' );
			$input['elementType'] = (string) ( $input['elementType'] ?? 'preview-items' );

			try {
				$result = self::compose( $input, true );
			} catch( \Throwable ) {
				return null;
			}

			return self::_previewHtml( $result['source'] );
		}

		private static function _previewHtml( string $source ): string {

			$source = preg_replace( '/[\t ]*<!--\s*nino:section\s+\{[^\r\n]*\}\s*-->[\t ]*(?:\r?\n)?/', '', $source ) ?? $source;
			$source = preg_replace( '#<script\b[^>]*>.*?</script\s*>#is', '', $source ) ?? $source;
			$source = preg_replace( '#<script\b[^>]*>.*$#is', '', $source ) ?? $source;
			$source = preg_replace( '#</?script\b[^>]*>#is', '', $source ) ?? $source;
			$source = preg_replace( '#\s+on[a-z0-9:_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $source ) ?? $source;
			$source = preg_replace_callback(
				'#\s+(href|src|action|formaction|xlink:href)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i',
				fn( array $match ): string => ' '. $match[1]. '="#"',
				$source
			) ?? $source;
			$source = preg_replace( '#\s+(href|src|action|formaction|xlink:href)\s*=\s*javascript:[^\s>]*#i', ' $1="#"', $source ) ?? $source;
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

			$source = preg_replace_callback( '#\[elements\s+('. self::SHORTCODE_ARGUMENTS. ')\](.*?)\[/elements\]#is', function( array $match ): string {
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
						fn( array $fill ): string => self::_previewFill( $fill[1], $index ),
						$item
					) ?? $item;
					$out .= $item;
				}
				return $out;
			}, $source ) ?? $source;

			// [elementvalues] loops one field's distinct values, not records - a
			// fixed, realistic set of sample category buttons stands in, the same
			// way [elements] above stands in with sample cards.
			$source = preg_replace_callback( '#\[elementvalues\s+('. self::SHORTCODE_ARGUMENTS. ')\](.*?)\[/elementvalues\]#is', function( array $match ): string {
				$sampleValues = [ 'Consulting' => 2, 'Design' => 3, 'Development' => 1 ];
				// Honour limit the same way the [elements] fixture above does, so
				// a preset that bounds its button row previews what it ships
				$limit = [];
				preg_match( '/\blimit="(\d+)"/i', $match[1], $limit );
				$count = min( count( $sampleValues ), max( 1, (int) ( $limit[1] ?? count( $sampleValues ) ) ) );
				$out = '';
				$index = 0;
				foreach( array_slice( $sampleValues, 0, $count, true ) as $value => $usage ) {
					$out .= str_replace( [ '[[.id]]', '[[.value]]', '[[.count]]' ], [ (string) $index, $value, (string) $usage ], $match[2] );
					$index++;
				}
				return $out;
			}, $source ) ?? $source;

			$source = preg_replace_callback(
				'#\[\[([^\]]+)\]\]#',
				fn( array $fill ): string => self::_previewFill( $fill[1], null ),
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

			return $source;
		}

		private static function _previewFill( string $token, ?int $index ): string {

			if( $token === '/nino/dir' )
				return '';

			$key = strtolower( basename( str_replace( '\\', '/', $token ) ) );
			$number = ( $index ?? 0 ) + 1;
			$absolute = str_starts_with( $token, '/' );
			$values = [
				'title' => $absolute ? 'A clear headline for this section' : 'Thoughtful item '. $number,
				'subtitle' => 'A concise supporting line makes the purpose immediately clear.',
				'description' => $absolute ? 'Use this space to explain the most important idea in a calm, readable way.' : 'Useful supporting copy that gives this item enough context.',
				'content' => 'Realistic sample content shows spacing, rhythm and hierarchy before anything is inserted.',
				'quote' => '“The result feels focused, considered and remarkably easy to use.”',
				'author' => 'Alex Morgan',
				'role' => 'Product lead',
				'number' => (string) ( 24 + ( $number * 17 ) ),
				'step' => (string) $number,
				'suffix' => $number % 2 === 0 ? '%' : '+',
				'price' => (string) ( 49 + ( $number * 50 ) ),
				'badge' => $number === 2 ? 'Recommended' : 'Popular',
				'features' => 'Strategy · Design · Delivery · Support',
				'linklabel' => 'Learn more',
				'link' => '#',
				'cta-label' => 'Get started',
				'cta-uri' => '#',
				'secondary-cta-label' => 'See details',
				'secondary-cta-uri' => '#',
				'column-a' => 'Service',
				'column-b' => 'Duration',
				'column-c' => 'Investment',
				'columna' => 'Service '. $number,
				'columnb' => ( 30 + $number * 15 ). ' min',
				'columnc' => ( 90 + $number * 40 ). ' €',
				'optiona' => $number % 2 === 0 ? 'Included' : 'Optional',
				'optionb' => $number % 2 === 0 ? 'Advanced' : 'Standard',
				'video-uri' => 'about:blank',
				'email' => 'hello@example.com',
				'phone' => '+49 123 456789',
				'address' => 'Example Street 12<br>12345 Example City',
				'name' => 'Your name',
				'message' => 'Your message',
				'submit' => 'Send message',
				'required' => 'Required fields',
				'image' => 'preview-image.jpg',
			];

			return $values[$key] ?? ucwords( str_replace( [ '-', '_' ], ' ', $key ) );
		}

		private static function _previewImage( string $label ): string {

			$hue = abs( crc32( $label ) ) % 360;
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="hsl('. $hue.',55%,72%)"/><stop offset="1" stop-color="hsl('. ( ( $hue + 65 ) % 360 ). ',48%,38%)"/></linearGradient></defs><rect width="1200" height="800" fill="url(#g)"/><circle cx="900" cy="190" r="220" fill="rgba(255,255,255,.16)"/><path d="M0 650L340 390l190 150 190-190 480 360v90H0z" fill="rgba(255,255,255,.2)"/></svg>';
			return 'data:image/svg+xml,'. rawurlencode( $svg );
		}

	}

}
