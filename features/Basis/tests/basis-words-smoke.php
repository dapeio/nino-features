<?php
declare(strict_types=1);

/**
 *	Nino features
 *	basis-words-smoke.php	Where the words of the menus are read from: the names a screen reader reads for the header, the foot and the button of a menu. They are read below
 *												/feature/basis/ and, where the feature has no text of its own for one, below /module/basis/, where a project that was set up while an earlier
 *												kernel carried Basis keeps them. So such a project draws its menus as it did, and the words of the feature win where it has them.
 *
 *	Usage: php features/Basis/tests/basis-words-smoke.php
 *				 NINO_ROOT=../nino php features/Basis/tests/basis-words-smoke.php
 */

require __DIR__. '/prepare.php';

require $root. '/tests/harness.php';
require __DIR__. '/support.php';

$ignored = ninoWarnings();
basisUnderTest();

/**
 *	A project with Basis on, one entry in each menu, and the words given
 *
 *	@param		array			$words				key => text, in en_US
 *
 *	@return 	array										$appData
 */
function wordsSite( array $words ): array {

	$appData = ninoSandbox( 'basis-words' );
	$appData['./nino/filesystem/path'] = $GLOBALS['root'];
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis' ];
	$appData['/nino/html/navs'] = [ 'main', 'footer' ];
	\Nino\Html::addFills( $appData, $words, 'en_US' );
	\Nino\Callbacks::registerCallback( $appData, '/nino/navigation/lines', static function( array &$appData, array $args ): array {
		return [ 'nav' => $args['nav'], 'lines' => [ '/about:About' ] ];
	} );
	\Nino\Features::registerComponents( $appData );
	\Nino\Html::init( $appData );
	\Nino\Locales::useLocale( $appData, 'en_US' );

	return $appData;
}

$labels = static function( array &$appData ): array {

	$html = \Nino\Html::renderSource( $appData, '[header-wrap][header-nav][/header-wrap][footer-wrap][footer-col][footer-nav][/footer-col][/footer-wrap]', \Nino\Html::context( $appData ) );
	preg_match_all( '/<(?:nav|summary) class="(?:header-nav|footer-nav)[^"]*"[^>]*aria-label="([^"]*)"/', $html, $found );

	return $found[1];
};

echo "Words below /module/basis/ only\n";

$module = wordsSite( [ '/module/basis/navigation/primary' => 'Mod main', '/module/basis/navigation/menu' => 'Mod menu', '/module/basis/navigation/footer' => 'Mod foot' ] );
$drawn = $labels( $module );
ninoWarnings();

check( 'a project that keeps its words of the menus below /module/basis/ - as one set up while an earlier kernel carried Basis does - draws the name of the header, the name of the button and the name of the foot from them',
	in_array( 'Mod main', $drawn, true ) === true && in_array( 'Mod menu', $drawn, true ) === true && in_array( 'Mod foot', $drawn, true ) === true );

echo "\nWords of the feature as well\n";

{

	$both = wordsSite( [ '/module/basis/navigation/primary' => 'Mod main', '/module/basis/navigation/menu' => 'Mod menu', '/module/basis/navigation/footer' => 'Mod foot',
		'/feature/basis/navigation/primary' => 'Feature main', '/feature/basis/navigation/footer' => 'Feature foot' ] );
	$drawn = $labels( $both );
	ninoWarnings();

	check( 'where the feature has a word of its own it wins, and a word it has not is read below /module/basis/: the header and the foot are named by the feature, the button by the word of the module',
		in_array( 'Feature main', $drawn, true ) === true && in_array( 'Feature foot', $drawn, true ) === true && in_array( 'Mod menu', $drawn, true ) === true
		&& in_array( 'Mod main', $drawn, true ) === false && in_array( 'Mod foot', $drawn, true ) === false );
}

$empty = wordsSite( [] );
$drawn = $labels( $empty );
ninoWarnings();

check( 'with no word anywhere the names are empty and nothing is thrown', array_filter( $drawn, static fn( string $label ): bool => $label !== '' ) === [] );

ninoDone( $module );
