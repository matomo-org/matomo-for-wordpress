<?php
/**
 * phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.DB.RestrictedClasses.mysql__PDO
 *
 * @package matomo
 */

$host = getenv( 'WP_DB_HOST' ); // defined in .env.default or .env
if ( empty( $host ) ) {
	echo json_encode( [ 'error' => 'host is empty' ] );
	exit;
}

$dbname = getenv( 'WP_DB_NAME' ); // defined in local-dev-entrypoint.sh
if ( empty( $dbname ) ) {
	echo json_encode( [ 'error' => 'dbname is empty' ] );
	exit;
}

$pdo   = new \PDO( "mysql:host=$host;dbname={$dbname}_test", 'root', 'pass' );
$query = $pdo->prepare( "SHOW TABLES LIKE '%matomo%'" );
$query->execute();

$tables = $query->fetchAll( \PDO::FETCH_COLUMN );
echo json_encode( $tables );
