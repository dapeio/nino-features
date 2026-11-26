<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Mail		The mail of the core, for every part of it that writes to people: the packages that hang their mails in the band of the core. It makes the mail - the markup of the
	 *							project, /templates/mail/<name>.tpl (template(), body()), the words of a package, a text of its text files below the root it entered with addWords() - and
	 *							hands it to the kernel's outbox in one call (deliver()), which keeps a mail until it is out, whatever becomes of the request; a mail whose answer the owner waits
	 *							for in the request - the test of the wordmark - goes through send(). The words are read in the language the recipient reads (speak(), resume(), wordsLocale()).
	 *							A mail whose template or word the project lacks is not sent: attempt() and part() make it a warning that names the mail and what is missing, and the caller is told
	 *							what it is told of a mail that was not delivered. A value enters the template after it was rendered, escaped, and in one pass, so that nothing in it is read as a fill
	 *							or a shortcode. What a package says in its mails is the package's: its words are its own below /feature/<its key>/.
	 *
	 *							The band at the head of every mail that has the frame is the shortcode [mail-head], the place the mails of the packages leave for it: the logo of the slot
	 *							/logo/mail or - where the setting brand_word has a text - the wordmark as text in a table, with the picture of the slot to the right of it. The shortcode runs the
	 *							callbacks registered for it in turn, each given the answer of the one before, and a module that has no band of its own registers one that hands on what it was
	 *							given: this one takes an answer that is nothing as nobody's and draws the band, so the band is the same whichever registers first. brandTest() is the test mail
	 *							of the wordmark. The markup of the band is the project's, add-only like every template of the mails, and the test mail's the core's own.
	 *
	 *	@package						Dape/Nino
	 */
	class Mail {

		/**	The image slot of the logo in the mails	*/
		public const string LOGO_SLOT = '/logo/mail';

		/**	The wordmark at the head of a mail: the colour of its first line where the setting is empty, the colour of its second line, the grey the second line takes where its colour would not read on white, and the least contrast (WCAG, text) a colour needs on the white ground of the band	*/
		public const string BRAND_COLOR = '#54BCD6';
		public const string BRAND_INK = '#444647';
		public const string BRAND_GREY = '#6B6E70';
		public const float BRAND_CONTRAST = 4.5;

		/**	The height of the sign beside the wordmark: the picture of the slot /logo/mail, in pixels	*/
		public const int SIGN_HEIGHT = 44;

		/**	The widest shape of that picture - its width over its height - the band shows beside the wordmark: 2 to 1	*/
		public const float SIGN_RATIO = 2.0;

		/**	The folder of the markup in the project (the install unit brings the band there), and the folder of the markup that is the core's own	*/
		public const string TEMPLATES = '/templates/mail';
		public const string OWN = '/features/Crm/templates';

		/**	What the module keeps of the mail for the request: the roots of the words, the words by locale as the text files have them - read once for as long as a mail is being made (attempt()), so that a mail of thirty words reads each file once -, the locale the words are read in ('' for the native language of the website), how deep the calls of attempt() are and whether the band is registered	*/
		private const string ROOTS = './crm/mail/roots';
		private const string WORDS = './crm/mail/words';
		private const string LOCALE = './crm/mail/locale';
		private const string DEPTH = './crm/mail/depth';
		private const string HEAD = './crm/mail/head';

		/**	The shape of the name of a template, and of a root of words	*/
		private const string NAME = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D';
		private const string ROOT = '~^/feature/[a-z][a-z0-9]*(?:-[a-z0-9]+)*/$~D';

		/**
		 *	Say that a package has words in its mails: the root they stand below, /feature/<its key>/. A word of a root that was not entered is no word of a mail (text() refuses it),
		 *	and wordsLocale() looks for the words of the mails below the roots. The same root again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$root					/feature/<key>/, with the slash at its end; the key is the package's own
		 *	@param		array 		$opts					package (required): the key of the package, see Register::package()
		 *
		 *	@return 	bool											false, with a warning, for a root that is none or is not the package's
		 */
		public static function addWords( array &$appData, string $root, array $opts = [] ): bool {

			return self::enter( $appData, $root, Register::package( $appData, $opts['package'] ?? null ) !== null && array_diff_key( $opts, [ 'package' => 1 ] ) === [] ? (string) $opts['package'] : null );
		}

		/**
		 *	The words of the mails of the core: below /feature/crm/
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function init( array &$appData ): bool {

			return self::enter( $appData, '/feature/'. Api::CORE. '/', Api::CORE );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$root
		 *	@param		string|null	$package			The package the root is entered for; null for none that is one
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, string $root, ?string $package ): bool {

			// A root is the one of the package - /feature/<its key>/ - and of no other: a package has the words of its own mails
			if( $package === null || preg_match( self::ROOT, $root ) !== 1 || $root !== '/feature/'. $package. '/' ) {
				trigger_error( 'Crm: the root of the words of the mails of a package was refused', E_USER_WARNING );

				return false;
			}

			$appData[self::ROOTS][$root] = true;

			return true;
		}

		/**
		 *	A word of the mail, from the text file of the project in the language the mail is written in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the text without its brackets, below a root entered with addWords()
		 *	@param		string		...$params		Values for the places of the word; a word the mail gives none is checked all the same
		 *
		 *	@return 	string
		 *
		 *	@throws		\OutOfBoundsException			The key is below no root, the project has no such word in that language (nor, for an empty one, in the native language), or one that does not fit its values: a mail cannot say a key or an empty line
		 */
		public static function text( array &$appData, string $key, string ...$params ): string {

			if( self::rooted( $appData, $key ) === false )
				throw new \OutOfBoundsException( 'the key of a word of a mail is below no root that a package entered' );

			$native = \Nino\Locales::getNativeLocale( $appData );
			$locale = ( $appData[self::LOCALE] ?? '' ) !== '' ? (string) $appData[self::LOCALE] : $native;
			$text = self::words( $appData, $locale )['[['. $key. ']]'] ?? null;

			// A language that has the word with no text - nothing, or blanks, which the kernel counts as no text - is a language that has not translated it yet: the word of the native language stands in, as the kernel shows it for an empty one
			if( is_string( $text ) === true && trim( $text ) === '' && $locale !== $native ) {
				$locale = $native;
				$text = self::words( $appData, $native )['[['. $key. ']]'] ?? null;
			}

			// A word with no text is no word: a mail with an empty heading or an empty sentence is no better than one that says a key
			if( is_string( $text ) === false || trim( $text ) === '' )
				throw new \OutOfBoundsException( 'the word '. $key. ' is missing or empty in /text/'. $locale. '.php' );

			// A word the project changed may carry any percent sign: only %s, %n$s and %% are places of the mail (a %d would take a value in silence), and the places must not be more than the values. A place fewer leaves a value unused, which sprintf allows and which harms nothing
			if( preg_match( '/%(?!(?:\d+\$)?s)/', str_replace( '%%', '', $text ) ) === 1 )
				throw new \OutOfBoundsException( 'the word '. $key. ' in /text/'. $locale. '.php does not fit its values: it has a place that is none of %s, %n$s and %%' );

			try {
				return vsprintf( $text, $params );
			}
			catch( \ValueError ) {
				throw new \OutOfBoundsException( 'the word '. $key. ' in /text/'. $locale. '.php does not fit its values: it has more places than the mail has values' );
			}
		}

		/**
		 *	Whether a key stands below a root a package entered
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	bool
		 */
		private static function rooted( array &$appData, string $key ): bool {

			foreach( array_keys( (array) ( $appData[self::ROOTS] ?? [] ) ) as $root )
				if( str_starts_with( $key, (string) $root ) === true )
					return true;

			return false;
		}

		/**
		 *	The language the words of a mail are read in: the language itself where it is one of the website and its text file has the words of the mails, the native language of the website where it is not
		 *	or has none (the words of the native language are the ones a project has to have)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale				A locale; '' for none
		 *
		 *	@return 	string
		 */
		public static function wordsLocale( array &$appData, string $locale ): string {

			$native = \Nino\Locales::getNativeLocale( $appData );

			if( $locale === $native || preg_match( \Nino\Locales::PATTERN, $locale ) !== 1 || in_array( $locale, \Nino\Locales::getAvailableLocales( $appData ), true ) === false )
				return $native;

			// A language the unit has no words of is given the keys with empty texts: a file that has words is one with a text under a key of the mails
			foreach( self::words( $appData, $locale ) as $key => $text )
				if( is_string( $text ) === true && $text !== '' && self::rooted( $appData, trim( (string) $key, '[]' ) ) === true )
					return $locale;

			return $native;
		}

		/**
		 *	The words of one language: the text file of the project
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale				A locale of the website
		 *
		 *	@return 	array											'[[key]]' => text
		 */
		private static function words( array &$appData, string $locale ): array {

			if( (int) ( $appData[self::DEPTH] ?? 0 ) > 0 && isset( $appData[self::WORDS][$locale] ) === true )
				return $appData[self::WORDS][$locale];

			$texts = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
			$texts = is_array( $texts ) === true ? $texts : [];

			if( (int) ( $appData[self::DEPTH] ?? 0 ) > 0 )
				$appData[self::WORDS][$locale] = $texts;

			return $texts;
		}

		/**
		 *	Read the words of a mail in another language from now on
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale				As wordsLocale() has it
		 *
		 *	@return 	string										The language the words were read in before: what to put back with resume(), in a finally
		 */
		public static function speak( array &$appData, string $locale ): string {

			$before = (string) ( $appData[self::LOCALE] ?? '' );
			$appData[self::LOCALE] = self::wordsLocale( $appData, $locale );

			return $before;
		}

		/**
		 *	Read the words of a mail in the language they were read in before speak()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$before				What speak() answered
		 *
		 *	@return 	void
		 */
		public static function resume( array &$appData, string $before ): void {

			$appData[self::LOCALE] = $before;
		}

		/**
		 *	Make a mail, or say why it was not made: a template or a word the project lacks (an \OutOfBoundsException of body() or text()) is a warning that names the mail and what is missing, nothing is sent, and the answer is $failed - what a mail
		 *	that was not delivered is answered with. The text files are read once for as long as the mail is being made
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$about				What the mail is about, for the warning
		 *	@param		int|array	$failed				The answer for a mail that was not made
		 *	@param		\Closure	$make
		 *
		 *	@return 	mixed											What $make answers; $failed for a mail that could not be made
		 */
		public static function attempt( array &$appData, string $about, int|array $failed, \Closure $make ): mixed {

			$appData[self::DEPTH] = (int) ( $appData[self::DEPTH] ?? 0 ) + 1;

			try {
				return $make();
			}
			catch( \OutOfBoundsException $e ) {
				trigger_error( 'Crm: the mail about '. $about. ' was not sent: '. $e->getMessage(), E_USER_WARNING );

				return $failed;
			}
			finally {

				if( --$appData[self::DEPTH] === 0 )
					unset( $appData[self::WORDS], $appData[self::LOCALE] );
			}
		}

		/**
		 *	One mail of a call that makes more than one, or the mails of one language: made, or - where the project lacks a template or a word of it - left out with the warning of attempt(), so that the other mails of the call go out. Called inside attempt()
		 *
		 *	@param		string		$about				What the mail is about, for the warning
		 *	@param		\Closure	$make
		 *
		 *	@return 	array|null								What $make answers; null for a mail that could not be made
		 */
		public static function part( string $about, \Closure $make ): ?array {

			try {
				return $make();
			}
			catch( \OutOfBoundsException $e ) {
				trigger_error( 'Crm: the mail about '. $about. ' was not sent: '. $e->getMessage(), E_USER_WARNING );

				return null;
			}
		}

		/**
		 *	Hand mails to the outbox of the kernel, in one call: each is kept until it is out - delivered after the response, by this request or a later one, tried again after a failure -
		 *	and counts one try of the cap of mails an hour of the client that set it off (CLIENT) or of the cap of the whole site (SYSTEM). The warning for a cap that refused them
		 *	and for a mail the outbox did not take, and the number that were taken
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$mails				[ { to, subject, body, replyTo }, ... ], the body rendered: the outbox keeps the finished mail
		 *	@param		string		$about				What the warnings name, "the mail about ..." or "the mails about ..."
		 *	@param		bool			$many					Whether $about is in the plural
		 *	@param		string		$key					'<key of the feature>/<what>' to make a second call with the same mails the same mails (\Nino\Mail::queue()); with several mails each gets -<n> after it; '' for none
		 *	@param		string		$kind					\Nino\Mail::CLIENT, SYSTEM or TEST: whose cap of the hour the mails count against
		 *
		 *	@return 	int|string								How many mails the outbox took; 'limit' where the cap refused one
		 */
		public static function deliver( array &$appData, array $mails, string $about, bool $many = false, string $key = '', string $kind = \Nino\Mail::CLIENT ): int|string {

			$taken = 0;

			foreach( array_values( $mails ) as $at => $mail ) {

				$id = \Nino\Mail::queue( $appData, is_array( $mail ) === true ? $mail : [], $kind, $key === '' ? '' : ( count( $mails ) > 1 ? $key. '-'. $at : $key ) );

				if( $id !== '' ) {
					$taken++;
					continue;
				}

				if( \Nino\Mail::rateLimited( $appData ) === true || \Nino\Mail::queueRefused( $appData ) === 'ratelimited' ) {
					trigger_error( 'Crm: '. $about. ( $many === true ? ' were' : ' was' ). ' refused by the cap of mails an hour', E_USER_WARNING );

					return 'limit';
				}

				trigger_error( 'Crm: '. $about. ( $many === true ? ' were' : ' was' ). ' not taken by the outbox ('. \Nino\Mail::queueRefused( $appData ). ')', E_USER_WARNING );
			}

			return $taken;
		}

		/**
		 *	The shortcode [mail-head], the first line of every mail that has the frame: the band at the head of the mail. Where the setting brand_word is empty it is the band of the logo - the block
		 *	[imagevalues /logo/mail] of crm-head.tpl, which draws nothing while the slot is empty. Where the setting holds a wordmark it is a table (crm-head-word.tpl): the first line in the colour of the
		 *	brand, the second in the ink, both bold and in capitals, and - where the slot /logo/mail holds a picture - the picture, 44 pixels high, to the right of them and without an alternative text (the words carry the name). The words are no link, a client would show them blue and underlined: the only link of the band is on the picture. The band has a ground of its own, white, and the
		 *	line under it. Nothing of it is a style a mail client of the old kind cannot read: tables and inline styles, no position, no transform, no rounded border, no web font. A value of the settings enters the
		 *	markup escaped and in one pass, after the parts are drawn, so that nothing in it is read as a fill or a shortcode, a colour only as #rrggbb.
		 *	The parts of the band are templates of the project: one it lacks is a warning that names its path and a band without that part - the band is a frame and no body, and the mail it stands in, which may be one of a package, goes out all the same.
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$args					The arguments of the shortcode, or the answer of a callback of [mail-head] that ran before this one
		 *
		 *	@return 	string										Finished markup: what a callback before this one answered where it answered something, else the band
		 */
		public static function doShortcodeHead( array &$appData, mixed $args ): string {

			// A band another module drew stands; nothing - '' - is nobody's answer
			if( is_string( $args ) === true && $args !== '' )
				return $args;

			$brand = self::brand( $appData );

			if( $brand['word'] === '' )
				return self::drawn( $appData, 'crm-head' );

			$slot = \Nino\Images::getSlot( $appData, self::LOGO_SLOT );
			$ratio = is_array( $slot ) === true && (int) ( $slot['height'] ?? 0 ) > 0 ? (int) ( $slot['width'] ?? 0 ) / (int) $slot['height'] : 1.0;
			$domain = $brand['domain'] === '' ? '' : strtr( self::drawn( $appData, 'crm-head-domain' ), [ '[[brand-ink]]' => $brand['ink'], '[[domain]]' => self::escape( mb_strtoupper( $brand['domain'], 'UTF-8' ) ) ] );
			// A picture wider than twice its height is a logo, not a sign: beside the wordmark it would push the words off a phone, so the wordmark stands alone
			$sign = $ratio > self::SIGN_RATIO ? '' : strtr( self::drawn( $appData, 'crm-head-sign' ), [ '[[sign-width]]' => (string) max( 1, (int) round( self::SIGN_HEIGHT * $ratio ) ) ] );

			return strtr( self::drawn( $appData, 'crm-head-word' ), [
				'[[sign]]' => $sign,
				'[[domain]]' => $domain,
				'[[word]]' => self::escape( mb_strtoupper( $brand['word'], 'UTF-8' ) ),
				'[[brand-color]]' => $brand['color'],
			] );
		}

		/**
		 *	A part of the band drawn: the template of the project, read once as html+ and drawn with the context of the call
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					crm-head, crm-head-word, crm-head-domain or crm-head-sign
		 *
		 *	@return 	string										'' for a template that is missing or empty (the warning is Feature::template()'s)
		 */
		private static function drawn( array &$appData, string $name ): string {

			$source = self::template( $appData, $name );

			return $source === '' ? '' : trim( \Nino\Html::renderSource( $appData, $source, \Nino\Html::context( $appData ) ) );
		}

		/**
		 *	A text as html text and as the value of an attribute
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function escape( string $value ): string {

			return \Nino\Components::escape( $value );
		}

		/**
		 *	Register the shortcode [mail-head], once for the request. The packages whose mails have the frame write [mail-head] and register an answer that hands on what it was given, so
		 *	that their mails carry the band where the core is on and nothing where it is not, and never the shortcode as text; the answer of the core stands, whichever of them registers first
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function registerHead( array &$appData ): void {

			if( ( $appData[self::HEAD] ?? false ) === true )
				return;

			$appData[self::HEAD] = true;
			\Nino\Feature::shortcode( $appData, 'mail-head', [ self::class, 'doShortcodeHead' ], false );
		}

		/**
		 *	The wordmark of the settings as the band draws it: the two lines as typed, the colour of the first (the colour of the brand where the setting is empty or none) and the ink of the second - the
		 *	setting where it reads on the white ground of the band, the grey where it does not (see ink()). A site without the database of the core, or one that is not the core's, has no wordmark
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											word, domain, color and ink; word is '' for none
		 */
		private static function brand( array &$appData ): array {

			$none = [ 'word' => '', 'domain' => '', 'color' => self::BRAND_COLOR, 'ink' => self::BRAND_INK ];

			// Drawing a band reads the settings and never makes the database: a file that is not the core's yet is the band of no wordmark
			if( Db::kindFor( $appData, \Nino\Filesystem::path( $appData, Db::FILE ) ) !== 'ours' )
				return $none;

			try {
				$settings = Settings::all( $appData );
			}
			catch( \PDOException $e ) {
				return $none;
			}

			$color = $settings['brand_color'];

			return [
				'word'   => $settings['brand_word'],
				'domain' => $settings['brand_domain'],
				'color'  => preg_match( Settings::COLOR_SHAPE, $color ) === 1 ? strtoupper( $color ) : self::BRAND_COLOR,
				'ink'    => self::ink( $settings['brand_ink'] ),
			];
		}

		/**
		 *	The ink of the second line: the colour of the setting where it reads on white, the grey where it does not. The band keeps a ground of its own, white, so a colour that has a contrast of 4.5 to 1 on
		 *	it reads wherever the client leaves the ground alone; the grey (#6B6E70, 5.1 to 1 on white, 4.1 to 1 on black) is the middle that stays readable where a client turns the ground dark and
		 *	the ink with it. An empty setting is the ink of the brand, #444647
		 *
		 *	@param		string		$ink					'#rrggbb' or '' - what Settings kept
		 *
		 *	@return 	string										'#RRGGBB'
		 */
		public static function ink( string $ink ): string {

			if( preg_match( Settings::COLOR_SHAPE, $ink ) !== 1 )
				return self::BRAND_INK;

			return self::contrast( $ink, '#FFFFFF' ) >= self::BRAND_CONTRAST ? strtoupper( $ink ) : self::BRAND_GREY;
		}

		/**
		 *	The contrast of two colours as WCAG 2 has it: (the lighter luminance + .05) / (the darker + .05)
		 *
		 *	@param		string		$one					'#rrggbb'
		 *	@param		string		$other				'#rrggbb'
		 *
		 *	@return 	float											1.0 to 21.0
		 */
		public static function contrast( string $one, string $other ): float {

			$lights = [ self::luminance( $one ), self::luminance( $other ) ];

			return ( max( $lights ) + .05 ) / ( min( $lights ) + .05 );
		}

		/**
		 *	The relative luminance of a colour (WCAG 2)
		 *
		 *	@param		string		$colour				'#rrggbb'
		 *
		 *	@return 	float											0.0 to 1.0
		 */
		private static function luminance( string $colour ): float {

			$parts = array_map( static function( string $hex ): float {
				$value = hexdec( $hex ) / 255;

				return $value <= .03928 ? $value / 12.92 : ( ( $value + .055 ) / 1.055 ) ** 2.4;
			}, str_split( substr( $colour, 1 ), 2 ) );

			return .2126 * $parts[0] + .7152 * $parts[1] + .0722 * $parts[2];
		}

		/**
		 *	The test mail: one mail to the contact address of the company, with the band at the head as the settings make it and one sentence, so that the owner sees the wordmark in the mail programs of his
		 *	own. Nothing of a client is in it, and nothing is written to the log but that it was sent or was not. The owner waits for the answer, so it is sent in the request and not kept in the outbox
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int|string								1 for the mail the transport took, 0 where it did not; 'brand_empty' where the settings hold no first line, 'contact' where the company has no contact address that is one, 'limit' where the cap of mails an hour refused it
		 */
		public static function brandTest( array &$appData ): int|string {

			return self::attempt( $appData, 'the test of the wordmark', 0, static function() use ( &$appData ): int|string {
				return self::madeBrandTest( $appData );
			} );
		}

		/**
		 *	brandTest() for a project that has the template and the words
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int|string
		 */
		private static function madeBrandTest( array &$appData ): int|string {

			// With no first line the band is the logo, and the owner has nothing of the wordmark to look at
			if( self::brand( $appData )['word'] === '' )
				return 'brand_empty';

			$locale = \Nino\Locales::getCurrentLocale( $appData );

			\Nino\Locales::useLocale( $appData, \Nino\Locales::getNativeLocale( $appData ) );

			try {
				$markup = \Nino\Html::renderHtml( $appData, self::body( $appData, 'mail-brand-test', true ) );
				$owner = trim( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/contact/email' ) );
			}
			finally {
				\Nino\Locales::useLocale( $appData, $locale );
			}

			if( filter_var( $owner, FILTER_VALIDATE_EMAIL ) === false )
				return 'contact';

			$values = [
				'[[heading]]' => self::escape( self::text( $appData, '/feature/crm/mail-brand/title' ) ),
				'[[intro]]'   => self::escape( self::text( $appData, '/feature/crm/mail-brand/intro' ) ),
				'[[notice]]'  => self::escape( self::text( $appData, '/feature/crm/mail-brand/notice' ) ),
			];

			$sent = \Nino\Mail::send( $appData, $owner, self::text( $appData, '/feature/crm/mail-brand/subject' ), strtr( $markup, $values ), '' );

			if( $sent === true )
				return 1;

			if( \Nino\Mail::rateLimited( $appData ) === true ) {
				trigger_error( 'Crm: the test mail of the wordmark was refused by the cap of mails an hour', E_USER_WARNING );

				return 'limit';
			}

			trigger_error( 'Crm: the test mail of the wordmark was not delivered', E_USER_WARNING );

			return 0;
		}

		/**
		 *	The markup of a mail: the template of the project, /templates/mail/<name>.tpl, read through \Nino\Feature::template() - the install unit brings the templates add-only and nothing falls back to it -,
		 *	or - for the markup the core keeps as its own - the template beside the class. A template that is missing or empty is a warning that names the path, and the markup is ''
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					A file of the folder without .tpl: lower case letters, digits and hyphens
		 *	@param		bool			$own					Read the core's own template and not the project's
		 *
		 *	@return 	string										Without the newlines at its end; '' for a template that is missing or empty
		 *
		 *	@throws		\InvalidArgumentException	A name that is none
		 */
		public static function template( array &$appData, string $name, bool $own = false ): string {

			if( preg_match( self::NAME, $name ) !== 1 )
				throw new \InvalidArgumentException( 'There is no mail template "'. $name. '"' );

			return \Nino\Feature::template( $appData, $own === true ? self::OWN : self::TEMPLATES, $name );
		}

		/**
		 *	The markup of a mail that cannot go without it: template(), and a mail with no body is no mail
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					As template() has it
		 *	@param		bool			$own
		 *
		 *	@return 	string
		 *
		 *	@throws		\OutOfBoundsException			The project has no such template, or an empty one
		 */
		public static function body( array &$appData, string $name, bool $own = false ): string {

			$markup = self::template( $appData, $name, $own );

			if( $markup === '' )
				throw new \OutOfBoundsException( 'the template '. ( $own === true ? self::OWN : self::TEMPLATES ). '/'. $name. '.tpl is missing or empty' );

			return $markup;
		}
	}
}
