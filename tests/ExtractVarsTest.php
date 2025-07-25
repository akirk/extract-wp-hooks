<?php

use PHPUnit\Framework\TestCase;

class ExtractVarsTest extends TestCase {

	private function find_hook_token_index( $tokens, $hook_function ) {
		foreach ( $tokens as $i => $token ) {
			if ( is_array( $token ) && $token[1] === $hook_function ) {
				return $i;
			}
		}
		return null;
	}

	public function test_extract_vars_with_simple_filter() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$tokens = token_get_all( file_get_contents( $file_path ) );

		$filter_index = $this->find_hook_token_index( $tokens, 'apply_filters' );
		$this->assertNotNull( $filter_index, 'apply_filters token not found' );

		$extractor = new WpHookExtractor();
		$result = $extractor->extract_vars( array(), $tokens, $filter_index );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertIsArray( $result[0] );
		$this->assertIsString( $result[1] );
		$this->assertStringContainsString( 'apply_filters', $result[1] );
		$this->assertStringContainsString( 'simple_hook', $result[1] );
	}

	public function test_extract_vars_with_multiple_params() {
		$file_path = __DIR__ . '/fixtures/multiple_params.php';
		$tokens = token_get_all( file_get_contents( $file_path ) );

		$filter_index = $this->find_hook_token_index( $tokens, 'apply_filters' );
		$this->assertNotNull( $filter_index, 'apply_filters token not found' );

		$extractor = new WpHookExtractor();
		$result = $extractor->extract_vars( array(), $tokens, $filter_index );

		$this->assertIsArray( $result[0] );
		$this->assertArrayHasKey( 0, $result[0] );
		$this->assertArrayHasKey( 1, $result[0] );
		$this->assertArrayHasKey( 2, $result[0] );
	}

	public function testextract_vars_with_do_action() {
		$file_path = __DIR__ . '/fixtures/action_hook.php';
		$tokens = token_get_all( file_get_contents( $file_path ) );

		$action_index = $this->find_hook_token_index( $tokens, 'do_action' );
		$this->assertNotNull( $action_index, 'do_action token not found' );

		$extractor = new WpHookExtractor();
		$result = $extractor->extract_vars( array(), $tokens, $action_index );

		$this->assertStringContainsString( 'do_action', $result[1] );
		$this->assertStringContainsString( 'test_action', $result[1] );
	}

	public function testextract_vars_with_complex_parameters() {
		$file_path = __DIR__ . '/fixtures/complex_params.php';
		$tokens = token_get_all( file_get_contents( $file_path ) );

		$filter_index = $this->find_hook_token_index( $tokens, 'apply_filters' );
		$this->assertNotNull( $filter_index, 'apply_filters token not found' );

		$extractor = new WpHookExtractor();
		$result = $extractor->extract_vars( array(), $tokens, $filter_index );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertStringContainsString( 'apply_filters', $result[1] );
		$this->assertStringContainsString( 'complex_hook', $result[1] );
	}
}
