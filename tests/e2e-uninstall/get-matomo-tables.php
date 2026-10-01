<?php
/**
 * phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.DB.RestrictedClasses.mysql__PDO
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput -- validated by regex, WordPress is not loaded
 *
 * @package matomo
 */

$host = getenv( 'WP_DB_HOST' ); // defined in .env.default or .env
if ( empty( $host ) ) {
	echo json_encode( [ 'error' => 'host is empty' ] );
	exit;
}

// the webserver's environment only has the DB name of the install it was started for
$folder = isset( $_GET['folder'] ) ? (string) $_GET['folder'] : '';
if ( preg_match( '/^[A-Za-z0-9._-]+$/', $folder ) ) {
	$dbname = 'wp_matomo_' . str_replace( [ '.', '-' ], '_', $folder ); // same as local-dev-entrypoint.sh
} else {
	$dbname = getenv( 'WP_DB_NAME' ); // defined in local-dev-entrypoint.sh
}

if ( empty( $dbname ) ) {
	echo json_encode( [ 'error' => 'dbname is empty' ] );
	exit;
}

$multi = isset( $_GET['multi'] ) ? ( (int) $_GET['multi'] ) : 0;

$dbname = preg_replace( '/_multi$/', '', $dbname );
if ( $multi ) {
	$dbname .= '_multi';
}

$pdo   = new \PDO( "mysql:host=$host;dbname={$dbname}", 'root', 'pass' );
$query = $pdo->prepare( 'SHOW TABLES' );
$query->execute();

$tables = $query->fetchAll( \PDO::FETCH_COLUMN );
echo json_encode(
	[
		'dbname' => $dbname,
		'tables' => $tables,
	]
);
