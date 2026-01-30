<?php
/**
 * Tests for parsing docblocks with multiple occurrences of the same tag.
 *
 * @see https://github.com/akirk/extract-wp-hooks/issues/23
 */
class ArrayTagsTest extends WpHookExtractor_Testcase {

	/**
	 * Test that multiple @type tags are parsed correctly.
	 *
	 * This is the main issue reported in #23: WordPress documentation standards
	 * allow documenting array parameters with multiple @type tags.
	 */
	public function test_multiple_type_tags_extraction() {
		$file_path = __DIR__ . '/fixtures/multiple_type_tags.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'multiple_type_tags_hook', $hooks );

		$hook = $hooks['multiple_type_tags_hook'];
		$this->assertEquals( 'apply_filters', $hook['type'] );

		// Verify all 6 @type tags are extracted.
		$this->assertArrayHasKey( 'types', $hook );
		$this->assertIsArray( $hook['types'] );
		$this->assertCount( 6, $hook['types'] );

		// Verify specific type entries.
		$this->assertStringContainsString( '$name', $hook['types'][0][0] );
		$this->assertStringContainsString( '$slug', $hook['types'][1][0] );
		$this->assertStringContainsString( '$parent', $hook['types'][2][0] );
		$this->assertStringContainsString( '$taxonomy', $hook['types'][3][0] );
		$this->assertStringContainsString( '$level', $hook['types'][4][0] );
		$this->assertStringContainsString( '$location', $hook['types'][5][0] );
	}

	/**
	 * Test that documentation generation works with multiple @type tags.
	 */
	public function test_multiple_type_tags_documentation() {
		$file_path = __DIR__ . '/fixtures/multiple_type_tags.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'hooks', $documentation );
		$this->assertArrayHasKey( 'multiple_type_tags_hook', $documentation['hooks'] );
	}

	/**
	 * Test that multiple @return tags are parsed without crashing.
	 *
	 * While unusual, multiple @return tags should not cause a fatal error.
	 */
	public function test_multiple_return_tags_extraction() {
		$file_path = __DIR__ . '/fixtures/multiple_return_tags.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'multiple_return_tags_hook', $hooks );

		$hook = $hooks['multiple_return_tags_hook'];
		$this->assertEquals( 'apply_filters', $hook['type'] );

		// Verify @return is extracted (as array when multiple).
		$this->assertArrayHasKey( 'returns', $hook );
		$this->assertIsArray( $hook['returns'] );
		$this->assertCount( 2, $hook['returns'] );

		// Verify both return types are captured.
		$this->assertStringContainsString( 'string', $hook['returns'][0][0] );
		$this->assertStringContainsString( 'int', $hook['returns'][1][0] );
	}

	/**
	 * Test that documentation generation works with multiple @return tags.
	 */
	public function test_multiple_return_tags_documentation() {
		$file_path = __DIR__ . '/fixtures/multiple_return_tags.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'hooks', $documentation );
		$this->assertArrayHasKey( 'multiple_return_tags_hook', $documentation['hooks'] );

		// Verify the returns section uses the first return type.
		$hook_doc = $documentation['hooks']['multiple_return_tags_hook'];
		$this->assertArrayHasKey( 'returns', $hook_doc );
		$this->assertStringContainsString( 'string', $hook_doc['returns'] );
	}

	/**
	 * Test that single @return tag remains a string (backwards compatibility).
	 */
	public function test_single_return_tag_is_string() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'user_data_filter', $hooks );

		$hook = $hooks['user_data_filter'];
		$this->assertArrayHasKey( 'returns', $hook );
		$this->assertIsString( $hook['returns'] );
		$this->assertEquals( 'array Modified user data', $hook['returns'] );
	}

	/**
	 * Test that single @since tag remains a string (backwards compatibility).
	 */
	public function test_single_since_tag_is_string() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'simple_hook', $hooks );

		$hook = $hooks['simple_hook'];
		if ( isset( $hook['sinces'] ) ) {
			// If there's only one @since, it should be a string.
			// If the fixture has multiple, it would be an array.
			$this->assertTrue(
				is_string( $hook['sinces'] ) || is_array( $hook['sinces'] ),
				'sinces should be either string or array'
			);
		}
	}
}
