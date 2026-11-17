<?php
declare(strict_types=1);

// The repository of fonts, as a test makes it: a key pair of its own, the archive of a family - plain ustar under gzip, written here and
// not by tar - the entry of the list that names it and the list with its detached signature. Nothing of it comes from the network; the
// repository the feature reads by default is never asked

/**
 *	@param		int				$length				The length of the file
 *	@param		string		$seed					What the bytes after the header are made of
 *
 *	@return 	string											A string that has the header of a woff2 file - the signature, a flavor, its own length - and is no font
 */
function fontsWoff2( int $length = 96, string $seed = 'x' ): string {
	return 'wOF2'. 'true'. pack( 'N', $length ). substr( str_repeat( $seed, $length ), 0, $length - 12 );
}

/**
 *	@param		array			$entries			A list of [ name, content, type, link ] - the type '0' for a file, '5' for a directory, '2' for a symbolic link, '1' for a hard link
 *
 *	@return 	string											A tar: one header and the padded data for each entry and the two blocks of nothing that end it
 */
function fontsTar( array $entries ): string {

	$tar = '';

	foreach( $entries as $entry ) {

		[ $name, $content ] = $entry;
		$type = $entry[2] ?? '0';
		$link = $entry[3] ?? '';

		$head = str_pad( substr( $name, 0, 100 ), 100, "\0" ). sprintf( '%07o', $type === '5' ? 0755 : 0644 ). "\0". sprintf( '%07o', 0 ). "\0". sprintf( '%07o', 0 ). "\0"
			. sprintf( '%011o', strlen( $content ) ). "\0". sprintf( '%011o', 0 ). "\0". '        '. $type. str_pad( $link, 100, "\0" ). "ustar\0". '00'
			. str_pad( '', 32, "\0" ). str_pad( '', 32, "\0" ). str_pad( '', 16, "\0" ). str_pad( '', 155, "\0" );
		$head = str_pad( $head, 512, "\0" );
		$sum = array_sum( unpack( 'C*', $head ) );

		$head = substr( $head, 0, 148 ). sprintf( '%06o', $sum ). "\0 ". substr( $head, 156 );
		$tar .= $head. $content. str_repeat( "\0", ( 512 - strlen( $content ) % 512 ) % 512 );
	}

	return $tar. str_repeat( "\0", 1024 );
}

/**
 *	The name a file of a font has for its weight and its style: 400.woff2, 700-italic.woff2, 100-900.woff2
 *
 *	@param		int|string	$weight
 *	@param		string			$style
 *
 *	@return 	string
 */
function fontsFile( int|string $weight, string $style ): string {
	return str_replace( ' ', '-', (string) $weight ). ( $style === 'italic' ? '-italic' : '' ). '.woff2';
}

/**
 *	A family as a repository publishes it
 *
 *	@param		string		$slug
 *	@param		string		$family
 *	@param		array			$faces				A list of [ weight, style ]
 *	@param		array			$set					'category', 'version', 'license', 'host' (where the archive is), 'files' (name => content, replaces the woff2 files),
 *																			'more' (name => content, files the archive holds besides them), 'tar' (the entries of the tar, replaces the archive's own), 'manifest' (fields of the font.json that say another thing),
 *																			'raw' (the archive, replacing all), 'entry' (fields of the entry that say another thing)
 *
 *	@return 	array											[ 'entry' => the entry of the list, 'archive' => the bytes, 'files' => name => content ]
 */
function fontsFamily( string $slug, string $family, array $faces, array $set = [] ): array {

	$files = [];
	$list = [];

	foreach( $faces as [ $weight, $style ] ) {

		$file = fontsFile( $weight, $style );
		$files[$file] = fontsWoff2( 96 + strlen( $file ), $weight. $style );
		$list[] = [ 'weight' => $weight, 'style' => $style, 'file' => $file ];
	}

	$manifest = [
		'slug'			=> $slug,
		'family'		=> $family,
		'category'	=> $set['category'] ?? 'sans',
		'version'		=> $set['version'] ?? '1.0',
		'license'		=> $set['license'] ?? 'OFL-1.1',
		'source'		=> 'https://github.com/example/'. $slug,
		'weights'		=> array_values( array_unique( array_column( $list, 'weight' ), SORT_REGULAR ) ),
		'styles'		=> array_values( array_unique( array_column( $list, 'style' ) ) ),
		'files'			=> $list,
	];

	$files = ( $set['files'] ?? $files );
	$tarEntries = [ [ $slug. '/', '', '5' ], [ $slug. '/font.json', (string) json_encode( array_replace( $manifest, $set['manifest'] ?? [] ) ) ], [ $slug. '/LICENSE', "SIL Open Font License, Version 1.1\nCopyright (c) example\n" ] ];

	foreach( $files + ( $set['more'] ?? [] ) as $name => $content )
		$tarEntries[] = [ $slug. '/'. $name, $content ];

	$archive = $set['raw'] ?? gzencode( fontsTar( $set['tar'] ?? $tarEntries ), 6 );
	$host = $set['host'] ?? 'fonts.example.test';

	return [
		'entry'		=> array_replace( $manifest + [ 'archive' => 'https://'. $host. '/'. $slug. '-'. $manifest['version']. '.tar.gz', 'sha256' => hash( 'sha256', $archive ), 'size' => strlen( $archive ) ], $set['entry'] ?? [] ),
		'archive'	=> $archive,
		'files'		=> $files,
	];
}

/**
 *	@param		array			$entries
 *	@param		array			$extra				Fields of the document that say another thing
 *
 *	@return 	string											The list, webfonts.json
 */
function fontsList( array $entries, array $extra = [] ): string {
	return (string) json_encode( array_replace( [ 'format' => 1, 'generated' => '2026-11-16T09:00:00Z', 'families' => $entries ], $extra ), JSON_UNESCAPED_SLASHES );
}

/**
 *	@return 	array											[ private key (resource), the private key as PEM, the public key as PEM ] - an ECDSA key of the curve the scheme uses
 */
function fontsKeys(): array {

	$key = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );

	openssl_pkey_export( $key, $private );

	return [ $key, $private, (string) openssl_pkey_get_details( $key )['key'] ];
}

/**
 *	@param		string		$data
 *	@param		mixed			$key					A private key
 *
 *	@return 	string											The detached signature, base64, as `openssl dgst -sha256 -sign key.pem webfonts.json | base64` writes it
 */
function fontsSign( string $data, mixed $key ): string {

	openssl_sign( $data, $signature, $key, OPENSSL_ALGO_SHA256 );

	return base64_encode( $signature );
}
