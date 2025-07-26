<?php
/**
 * Bootstrap file for PHPUnit tests
 */

require_once __DIR__ . '/../class-wphookextractor.php';

class WpHookExtractor_Testcase extends PHPUnit\Framework\TestCase {
	protected function assertStringEqualsFileOrWrite( $expected_file_path, $actual_content ) {
		if ( ! file_exists( $expected_file_path ) ) {
			$dir = dirname( $expected_file_path );
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir, 0755, true );
			}
			file_put_contents( $expected_file_path, $actual_content );
			file_put_contents( 'php://stderr', 'Updated fixture: ' . basename( $expected_file_path ) . "\n" );
		}
		return $this->assertStringEqualsFile( $expected_file_path, $actual_content );
	}

}
