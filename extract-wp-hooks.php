<?php
/**
 * Extract WordPress hooks from a codebase.
 *
 * @author Alex Kirk
 */

require_once __DIR__ . '/class-wphookextractor.php';

$config_files = array( 'extract-wp-hooks.json', '.extract-wp-hooks.json' );
$base = getcwd();
foreach ( $config_files as $config_file ) {
	$config_file = $base . '/' . $config_file;
	if ( file_exists( $config_file ) ) {
		break;
	}
}

if ( ! file_exists( $config_file ) ) {
	echo 'Please provide an extract-wp-hooks.json file in the current directory or the same directory as this script. Example: ', PHP_EOL, WpHookExtractor::sample_config(), PHP_EOL;
	exit( 1 );
}
echo 'Loading ', realpath( $config_file ), PHP_EOL;
$config = json_decode( file_get_contents( $config_file ), true );

foreach ( array( 'wiki_directory', 'github_blob_url' ) as $key ) {
	if ( ! isset( $config[ $key ] ) ) {
		echo 'Missing config entry ', $key, '. Example: ', PHP_EOL, WpHookExtractor::sample_config(), PHP_EOL;
		exit( 1 );
	}
}

if ( empty( $config['exclude_dirs'] ) ) {
	$config['exclude_dirs'] = array();
}
$config['exclude_dirs'][] = 'vendor';
if ( empty( $config['ignore_filter'] ) ) {
	$config['ignore_filter'] = array();
}
if ( empty( $config['ignore_regex'] ) ) {
	$config['ignore_regex'] = false;
}
if ( empty( $config['section'] ) ) {
	$config['section'] = 'file';
}
if ( empty( $config['namespace'] ) ) {
	$config['namespace'] = '';
}

$base = isset( $config['base_dir'] ) ? $config['base_dir'] : getcwd();
if ( '.' === $base ) {
	$base = getcwd();
}

echo 'Scanning ', $base, PHP_EOL;
$extractor = new WpHookExtractor( $config );
$hooks = $extractor->scan_directory( $base );

$extractor->generate_documentation( $hooks, $base . '/' . $config['wiki_directory'], $config['github_blob_url'] );

echo 'Generated ' . count( $hooks ) . ' hooks documentation files in ' . realpath( $base . '/' . $config['wiki_directory'] ) . PHP_EOL;
