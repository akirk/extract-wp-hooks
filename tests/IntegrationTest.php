<?php

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {

	public function testExampleCodeExtraction() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'user_data_filter', $hooks );

		$hook = $hooks['user_data_filter'];
		$this->assertEquals( 'apply_filters', $hook['type'] );
		$this->assertArrayHasKey( 'comment', $hook );

		// Verify raw example code is preserved (conversion happens during doc generation).
		$this->assertStringContainsString( 'Example:', $hook['comment'] );
		$this->assertStringContainsString( 'add_filter', $hook['comment'] );
		$this->assertStringContainsString( 'user_data_filter', $hook['comment'] );

		// Test example detection pattern.
		$has_example = preg_match( '/^Example:?$/m', $hook['comment'] );
		$this->assertEquals( 1, $has_example );

		// Verify parameters are extracted.
		$this->assertArrayHasKey( 'params', $hook );
		$this->assertCount( 2, $hook['params'] );

		// Verify return tag is extracted.
		$this->assertArrayHasKey( 'returns', $hook );
		$this->assertEquals( 'array Modified user data', $hook['returns'] );
	}

	public function testExampleWordOnlyDetection() {
		$file_path = __DIR__ . '/fixtures/docblock_example_word_only.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'post_content_filter', $hooks );

		$hook = $hooks['post_content_filter'];
		$this->assertArrayHasKey( 'comment', $hook );

		// Verify raw "Example" word is preserved.
		$this->assertStringContainsString( 'Example', $hook['comment'] );
		$this->assertStringContainsString( 'add_filter', $hook['comment'] );

		// Test example detection pattern.
		$has_example = preg_match( '/^Example:?$/m', $hook['comment'] );
		$this->assertEquals( 1, $has_example );
	}

	public function testComplexParameterExtraction() {
		$file_path = __DIR__ . '/fixtures/complex_params.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'complex_hook', $hooks );

		$hook = $hooks['complex_hook'];
		$this->assertEquals( 'apply_filters', $hook['type'] );
		$this->assertArrayHasKey( 'params', $hook );
		$this->assertCount( 3, $hook['params'] ); // Three parameters after hook name.
	}

	public function testExtractHooksWithDefaultConfig() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'simple_hook', $hooks );
		$this->assertEquals( 'apply_filters', $hooks['simple_hook']['type'] );
	}

	public function testExtractHooksWithCustomConfig() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$config = array(
			'section'   => 'dir',
			'namespace' => 'MyPlugin',
		);
		$extractor = new WpHookExtractor( $config );
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'simple_hook', $hooks );
		$this->assertEquals( 'simple_filter.php', $hooks['simple_hook']['section'] );
	}
}
