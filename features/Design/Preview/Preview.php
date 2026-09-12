<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Preview		see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Preview						What a setup looks like, before it is the site's.
	 *
	 *										One page that uses every class a part set can reach, in
	 *										the markup a real page produces, with the chosen header
	 *										and footer around it and the compiled stylesheet over it.
	 *										Nothing here writes: a preview is a question, and the
	 *										answer to it must not already be the answer on disk.
	 *
	 *										Two things show it. The panel, against the project it
	 *										runs in - its menu, its logo, its socialmedia block - and
	 *										design-library/preview.php, against a throwaway project
	 *										it builds per request, which is what writing a set needs
	 *										and what no installed project can offer. Both assemble the
	 *										same markup out of the same specimen and compile it with
	 *										the same compiler; what differs is only where the render
	 *										context comes from.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Preview {

		/*	The one picture the specimen shows, as a data uri rather than a file.
			An article set is judged on the space around an image, not on the
			image - and shipping one would mean choosing a photograph, which is a
			design decision this page must not make for whoever is looking at it.
			A uri also needs no route: the panel renders inside /_admin, where
			/images/… is not the site's, and the harness serves from its own root */
		public const string PLACEHOLDER = 'data:image/svg+xml;charset=utf-8,'
			. '%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 640 420%22 role=%22img%22 aria-label=%22Platzhalter%22%3E'
			. '%3Crect width=%22640%22 height=%22420%22 fill=%22%23d8dee6%22/%3E'
			. '%3Cpath d=%22M0 300l170-130 130 100 110-80 230 170z%22 fill=%22%23b9c3cf%22/%3E'
			. '%3Ccircle cx=%22480%22 cy=%22110%22 r=%2246%22 fill=%22%23c9d2dc%22/%3E%3C/svg%3E';

		/**
		 *	The specimen with the chosen frames around it, as html+ for the
		 *	kernel to render.
		 *
		 *	The frames are inlined rather than included as [template
		 *	/templates/theme.header]: a preview shows a header nobody has applied
		 *	yet, and the file that include names is the one currently on disk.
		 *	Reading the library directly is the difference between "what this
		 *	would look like" and "what it looks like"
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$setup				A normalised setup (see Setup::normalize())
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Html+
		 */
		public static function markup( string $libraryDir, array $setup, array &$notes = [] ): string {

			return self::frame( $libraryDir, $setup, 'header', $notes )
				. "\n". self::specimen()
				. "\n". self::frame( $libraryDir, $setup, 'footer', $notes );
		}

		/**
		 *	The markup half of a frame, straight out of the library
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					'header' or 'footer'
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Html+, or nothing at all
		 */
		public static function frame( string $libraryDir, array $setup, string $part, array &$notes = [] ): string {

			$set 			= (string) ( $setup['parts'][$part]['set'] ?? '' );
			$template = Setup::file( $libraryDir, $part, $set, 'template' );

			if( $template === '' ) {
				$notes[] = 'no template for "'. $part. '" set "'. $set. '" - the preview shows the page without it';
				return '';
			}

			return (string) file_get_contents( $template );
		}

		/**
		 *	The stylesheet the specimen is shown under - the feature's own
		 *	compiler, not a second assembly beside it. What is previewed is byte
		 *	for byte what applying would write, and the compiler is exercised
		 *	every time somebody looks at a design rather than only when its test
		 *	runs
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$public				What [[/nino/public]] resolves to here
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								The compiled css
		 */
		public static function css( array $setup, string $libraryDir, string $public, array &$notes = [] ): string {

			/*	base.css's @font-face urls carry the fill every stylesheet in a
				project carries, and a preview is not written through the asset
				bundler that would resolve it (see Modules\Assets). Unresolved, the
				three webfaces silently do not load - and a design shown in the
				wrong typeface is worse than no preview at all */
			return str_replace( '[[/nino/public]]', $public, Compiler::compile( $setup, $libraryDir, $notes ) );
		}

		/**
		 *	The page around the render. Both callers need the same shell and a
		 *	different head: the harness links its stylesheets, because it serves
		 *	them itself and a reload should pick them up; the panel carries them
		 *	inline, because an iframe's srcdoc is the whole document it has
		 *
		 *	@param		string		$locale				The render's locale - its first two letters
		 *																		become the lang attribute
		 *	@param		string		$head					Stylesheets, as tags
		 *	@param		string		$body					Everything inside <body>
		 *	@param		string		$tail					Scripts, as tags
		 *
		 *	@return 	string								A complete html document
		 */
		public static function document( string $locale, string $head, string $body, string $tail ): string {

			/*	A design preview is full of headings a crawler would happily take
				for a site, and the harness answers on a public dev domain. The
				header, the robots.txt and this are the three places that say no */
			return '<!doctype html>
<html lang="'. htmlspecialchars( substr( $locale, 0, 2 ), ENT_QUOTES ). '">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Design preview</title>
'. $head. '
</head>
<body>
'. $body. '
'. $tail. '
</body>
</html>';
		}


		/**
		 *	The specimen, without its frames. Every class a part set can reach, once,
		 *	in the markup a real page produces - the article grid is the shape the
		 *	articles-grid preset emits, the form is the contact page's. Section ids are
		 *	the part names, so a picker can jump to one
		 *
		 *	@return 	string								Html+, rendered through the kernel by the caller
		 */
		public static function specimen(): string {

			$lorem 	= 'Die Entscheidung, die ein Set trifft, sieht man erst an echtem Text: wo der Titel steht, wie weit er vom Untertitel absteht, und ob die Zeile noch ruhig bleibt, wenn sie lang wird.';
			$short 	= 'Kurz genug, um die Ausrichtung zu zeigen.';
			$out 		= [];

			// --- ATF: the hero, its three loudnesses, the arrow ---
			$out[] = '<section id="atf" class="nino-section nino-section--dark nino-cover nino-atf" data-cover-height="70" aria-labelledby="atf-title">
				<div class="nino-cover-content">
					<div class="nino-grid-row nino-grid-middle">
						<div class="nino-grid-100 nino-text-center">
							<h2 class="nino-atf-title" id="atf-title">ATF</h2>
							<p class="nino-atf-subtitle">'. $short. '</p>
							<p><a class="nino-btn nino-btn--primary" href="#section">Weiter</a> <a class="nino-btn nino-btn--outline" href="#article">Artikel</a></p>
						</div>
					</div>
				</div>
				<button class="nino-atf-arrowdown" data-arrow-target="#section" aria-label="Weiter"></button>
			</section>';

			$out[] = '<section class="nino-section" aria-label="ATF-Lautstärken">
				<div class="nino-grid-row">
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title nino-atf-title--quiet">quiet</h3><p class="nino-atf-subtitle nino-atf-subtitle--quiet">'. $short. '</p></div>
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title">default</h3><p class="nino-atf-subtitle">'. $short. '</p></div>
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title nino-atf-title--loud">loud</h3><p class="nino-atf-subtitle nino-atf-subtitle--loud">'. $short. '</p></div>
				</div>
			</section>';

			// --- Section: every surface, every loudness, the border steps ---
			$surfaces = [ '' => 'default', '--alt' => 'alt', '--tint' => 'tint', '--primary' => 'primary', '--brand-alt' => 'brand-alt', '--dark' => 'dark', '--black' => 'black' ];
			$first = true;

			foreach( $surfaces as $modifier => $label ) {
				$out[] = '<section'. ( $first ? ' id="section"' : '' ). ' class="nino-section'. ( $modifier !== '' ? ' nino-section'. $modifier : '' ). '" aria-label="Section '. $label. '">
					<div class="nino-grid-row">
						<div class="nino-grid-100">
							<h2 class="nino-section-title">Section &mdash; '. $label. '</h2>
							<p class="nino-section-subtitle">'. $short. '</p>
							<p class="nino-section-text">'. $lorem. '</p>
							<p><a class="nino-btn nino-btn--primary" href="#">Primär</a> <a class="nino-btn nino-btn--outline" href="#">Outline</a></p>
						</div>
					</div>
				</section>';
				$first = false;
			}

			$out[] = '<section class="nino-section nino-section--border-1" aria-label="Section-Lautstärken und Rahmen">
				<div class="nino-grid-row">
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title nino-section-title--quiet">quiet</h3><p class="nino-section-subtitle nino-section-subtitle--quiet">'. $short. '</p><p class="nino-section-text nino-section-text--quiet">'. $short. '</p></div>
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title">default</h3><p class="nino-section-subtitle">'. $short. '</p><p class="nino-section-text">'. $short. '</p></div>
					<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title nino-section-title--loud">loud</h3><p class="nino-section-subtitle nino-section-subtitle--loud">'. $short. '</p><p class="nino-section-text nino-section-text--loud">'. $short. '</p></div>
				</div>
			</section>';

			$out[] = '<section class="nino-section nino-section--border-2" aria-label="Rahmenstufe 2"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-2</p></div></div></section>';
			$out[] = '<section class="nino-section nino-section--border-3" aria-label="Rahmenstufe 3"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-3</p></div></div></section>';
			$out[] = '<section class="nino-section nino-section--border-primary" aria-label="Rahmen in der Markenfarbe"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-primary</p></div></div></section>';

			return implode( "\n", $out ). self::_specimenRest( $lorem, $short );
		}


		/**
		 *	The second half of the specimen - articles, buttons, forms, lists and
		 *	tables, the building blocks. Split off so neither function is a wall
		 *
		 *	@param		string		$lorem				A paragraph long enough to wrap
		 *	@param		string		$short				A line short enough to read at a glance
		 *
		 *	@return 	string								Html+
		 */
		private static function _specimenRest( string $lorem, string $short ): string {

			$out = [];

			// --- Article: the grid an articles-grid section emits, then the variants ---
			$card = static function( string $title, string $extra = '', string $inner = '' ): string {
				return '<div class="nino-grid-100 nino-grid-m-33">
					<article class="nino-article'. ( $extra !== '' ? ' '. $extra : '' ). '">
						<img class="nino-article-img" src="'. self::PLACEHOLDER. '" alt="" width="640" height="420">
						<div class="nino-article-content">
							<h3 class="nino-article-title">'. $title. '</h3>
							<p class="nino-article-subtitle">Untertitel</p>
							<p class="nino-article-descr">Eine Beschreibung, lang genug, dass sie in die zweite Zeile läuft und der Abstand darunter sichtbar wird.</p>
							'. $inner. '
						</div>
					</article>
				</div>';
			};

			$out[] = '<section id="article" class="nino-section" aria-labelledby="article-title">
				<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="article-title">Article</h2></div></div>
				<div class="nino-grid-row">
					'. $card( 'Mit Preis', '', '<p class="nino-article-price">49 €</p>' ). '
					'. $card( 'Mit Badge', '', '<p><span class="nino-badge nino-badge--primary">Neu</span> <span class="nino-badge nino-badge--success">Auf Lager</span></p>' ). '
					'. $card( 'Mit Aktion', '', '<p><a class="nino-btn nino-btn--small nino-btn--outline" href="#">Mehr</a></p>' ). '
				</div>
				<div class="nino-grid-row">
					'. $card( 'alt', 'nino-article--alt' ). '
					'. $card( 'borderless', 'nino-article--borderless' ). '
					'. $card( 'grid', 'nino-article--grid' ). '
				</div>
				<div class="nino-grid-row"><div class="nino-grid-100">
					<article class="nino-article nino-article--fullwidth nino-article-cols">
						<img class="nino-article-img nino-article-img--maxheight" src="'. self::PLACEHOLDER. '" alt="" width="640" height="420">
						<div class="nino-article-content">
							<h3 class="nino-article-title nino-article-title--loud">fullwidth, cols, loud</h3>
							<p class="nino-article-descr nino-article-descr--quiet">'. $lorem. '</p>
						</div>
					</article>
				</div></div>
			</section>';

			// --- Buttons: all eight, on a plain and on a dark surface ---
			$buttons = static function(): string {
				$html = '';
				foreach( [ '' => 'btn', '--primary' => 'primary', '--outline' => 'outline', '--brand-alt' => 'brand-alt', '--light' => 'light', '--dark' => 'dark' ] as $modifier => $label )
					$html .= '<a class="nino-btn'. ( $modifier !== '' ? ' nino-btn'. $modifier : '' ). '" href="#">'. $label. '</a> ';
				return $html. '<a class="nino-btn nino-btn--primary nino-btn--big" href="#">big</a> <a class="nino-btn nino-btn--primary nino-btn--small" href="#">small</a>';
			};

			$out[] = '<section id="buttons" class="nino-section" aria-labelledby="buttons-title">
				<div class="nino-grid-row"><div class="nino-grid-100">
					<h2 class="nino-section-title" id="buttons-title">Buttons</h2>
					<p>'. $buttons(). '</p>
				</div></div>
			</section>';
			$out[] = '<section class="nino-section nino-section--dark" aria-label="Buttons auf dunkler Fläche">
				<div class="nino-grid-row"><div class="nino-grid-100"><p>'. $buttons(). '</p></div></div>
			</section>';

			// --- Forms: the contact page's shape, plus the states ---
			$out[] = '<section id="forms" class="nino-section nino-section--alt" aria-labelledby="forms-title">
				<div class="nino-grid-row">
					<div class="nino-grid-100 nino-grid-m-50">
						<h2 class="nino-section-title" id="forms-title">Forms</h2>
						<form class="nino-form" action="#" method="post" onsubmit="return false">
							<label for="p-name">Name *</label>
							<input type="text" id="p-name" name="name" class="nino-form-input" value="Ada Lovelace" required>
							<label for="p-mail">E-Mail *</label>
							<input type="email" id="p-mail" name="email" class="nino-form-input" placeholder="ada@example.org" required>
							<label for="p-topic">Thema</label>
							<select id="p-topic" name="topic" class="nino-form-select"><option>Anfrage</option><option>Angebot</option></select>
							<label for="p-message">Nachricht *</label>
							<textarea id="p-message" name="message" class="nino-form-textarea" required>Zwei Zeilen, damit die Höhe und der Innenabstand sichtbar sind.</textarea>
							<p class="nino-form-message" aria-live="polite"></p>
							<p><small>* Pflichtfeld</small></p>
							<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Senden</button>
						</form>
					</div>
					<div class="nino-grid-100 nino-grid-m-50">
						<h3 class="nino-section-subtitle">Zustände</h3>
						<form class="nino-form nino-is-error" action="#" onsubmit="return false"><p class="nino-form-message">Da fehlt noch etwas.</p></form>
						<form class="nino-form nino-is-success" action="#" onsubmit="return false"><p class="nino-form-message">Danke, ist angekommen.</p></form>
						<form class="nino-form nino-form--inline" action="#" onsubmit="return false">
							<input type="email" class="nino-form-input" placeholder="E-Mail" aria-label="E-Mail">
							<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Anmelden</button>
						</form>
					</div>
				</div>
			</section>';

			return implode( "\n", $out ). self::_specimenTail( $lorem, $short );
		}


		/**
		 *	The last third - lists, tables, badges, and the two building blocks that
		 *	carry their own vocabulary
		 *
		 *	@param		string		$lorem				A paragraph long enough to wrap
		 *	@param		string		$short				A line short enough to read at a glance
		 *
		 *	@return 	string								Html+
		 */
		private static function _specimenTail( string $lorem, string $short ): string {

			$items = '<li>Erster Punkt</li><li>Ein zweiter, der lang genug ist, um umzubrechen und den Zeilenabstand zu zeigen</li><li>Dritter</li>';
			$out 		= [];

			$out[] = '<section id="lists" class="nino-section" aria-labelledby="lists-title">
				<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="lists-title">Listen &amp; Tabellen</h2></div></div>
				<div class="nino-grid-row">
					<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">list</p><ul class="nino-list">'. $items. '</ul></div>
					<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">check</p><ul class="nino-list nino-list--check">'. $items. '</ul></div>
					<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">numbered</p><ol class="nino-list nino-list--numbered">'. $items. '</ol></div>
				</div>
				<div class="nino-grid-row">
					<div class="nino-grid-100 nino-grid-m-50"><p class="nino-section-subtitle">columns</p><ul class="nino-list nino-list--columns">'. $items. $items. '</ul></div>
					<div class="nino-grid-100 nino-grid-m-50"><p class="nino-section-subtitle">content</p><ul class="nino-list nino-list--content">'. $items. '</ul></div>
				</div>
				<div class="nino-grid-row"><div class="nino-grid-100">
					<p class="nino-section-subtitle">striped, bordered</p>
					<div class="nino-table-wrap"><table class="nino-table nino-table--striped nino-table--bordered">
						<thead><tr><th>Bauteil</th><th>Set</th><th>Stufe</th></tr></thead>
						<tbody><tr><td>Section</td><td>v1</td><td>default</td></tr><tr><td>Article</td><td>v1</td><td>less</td></tr><tr><td>Buttons</td><td>v2</td><td>more</td></tr></tbody>
					</table></div>
				</div></div>
				<div class="nino-grid-row"><div class="nino-grid-100">
					<p class="nino-section-subtitle">badges</p>
					<p class="nino-badge-cloud"><span class="nino-badge">plain</span> <span class="nino-badge nino-badge--primary">primary</span> <span class="nino-badge nino-badge--success">success</span> <span class="nino-badge nino-badge--error">error</span> <span class="nino-badge nino-badge--pill">pill</span></p>
				</div></div>
			</section>';

			// --- Building blocks: pricing and timeline, the two with their own words ---
			// A plan is a direct child of .nino-pricing-row, which is the flex container
			// itself - no grid column around it, or the row lays out its wrappers
			$plan = static function( string $title, string $price, bool $featured = false ): string {
				return '<div class="nino-pricing-item'. ( $featured === true ? ' nino-pricing-item--featured' : '' ). '">
					<h3 class="nino-pricing-title">'. $title. '</h3>
					<p class="nino-pricing-price">'. $price. '</p>
					<ul class="nino-list nino-list--check nino-pricing-features"><li>Ein Merkmal</li><li>Noch eines</li><li>Und ein drittes</li></ul>
					<p><a class="nino-btn nino-btn--primary" href="#">Wählen</a></p>
				</div>';
			};

			$out[] = '<section id="blocks" class="nino-section nino-section--tint" aria-labelledby="blocks-title">
				<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="blocks-title">Bausteine</h2><p class="nino-section-subtitle">Preispläne und Abläufe &mdash; eigenes Vokabular, eigenes Set</p></div></div>
				<div class="nino-grid-row"><div class="nino-grid-100">
					<div class="nino-pricing-row">
						'. $plan( 'Klein', '9 €' ). '
						'. $plan( 'Mittel', '29 €', true ). '
						'. $plan( 'Groß', '79 €' ). '
					</div>
				</div></div>
				<div class="nino-grid-row"><div class="nino-grid-100">
					<p class="nino-section-subtitle">timeline &mdash; counted (die Nummer erzeugt ein CSS-Zähler)</p>
					<ol class="nino-timeline nino-timeline--counted">
						<li class="nino-timeline-step"><h3 class="nino-article-title">Auswählen</h3><p class="nino-article-descr">'. $short. '</p></li>
						<li class="nino-timeline-step"><h3 class="nino-article-title">Anpassen</h3><p class="nino-article-descr">'. $lorem. '</p></li>
						<li class="nino-timeline-step"><h3 class="nino-article-title">Kompilieren</h3><p class="nino-article-descr">'. $short. '</p></li>
					</ol>
				</div></div>
				<div class="nino-grid-row"><div class="nino-grid-100 nino-grid-m-66">
					<p class="nino-section-subtitle">timeline &mdash; stacked, mit eigener Nummer</p>
					<ol class="nino-timeline nino-timeline--stacked">
						<li class="nino-timeline-step"><span class="nino-timeline-number">01</span><div><h3 class="nino-article-title">Auswählen</h3><p class="nino-article-descr">'. $short. '</p></div></li>
						<li class="nino-timeline-step"><span class="nino-timeline-number">02</span><div><h3 class="nino-article-title">Anpassen</h3><p class="nino-article-descr">'. $short. '</p></div></li>
					</ol>
				</div></div>
			</section>';

			return "\n". implode( "\n", $out );
		}
	}

}
