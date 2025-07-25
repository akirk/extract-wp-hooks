<?php

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {

	public function test_example_code_extraction() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'user_data_filter', $hooks );

		$hook = $hooks['user_data_filter'];
		$this->assertEquals( 'apply_filters', $hook['type'] );
		$this->assertArrayHasKey( 'comment', $hook );

		// Verify example is now extracted to examples array.
		$this->assertArrayHasKey( 'examples', $hook );
		$this->assertCount( 1, $hook['examples'] );
		$this->assertStringContainsString( 'add_filter', $hook['examples'][0]['content'] );
		$this->assertStringContainsString( 'user_data_filter', $hook['examples'][0]['content'] );

		// Verify parameters are extracted.
		$this->assertArrayHasKey( 'params', $hook );
		$this->assertCount( 2, $hook['params'] );

		// Verify return tag is extracted.
		$this->assertArrayHasKey( 'returns', $hook );
		$this->assertEquals( 'array Modified user data', $hook['returns'] );
	}

	public function test_example_word_only_detection() {
		$file_path = __DIR__ . '/fixtures/docblock_example_word_only.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'post_content_filter', $hooks );

		$hook = $hooks['post_content_filter'];
		$this->assertArrayHasKey( 'comment', $hook );

		// Verify example is now extracted to examples array.
		$this->assertArrayHasKey( 'examples', $hook );
		$this->assertCount( 1, $hook['examples'] );
		$this->assertStringContainsString( 'add_filter', $hook['examples'][0]['content'] );
		$this->assertStringContainsString( 'my_filter_function', $hook['examples'][0]['content'] );
	}

	public function test_complex_parameter_extraction() {
		$file_path = __DIR__ . '/fixtures/complex_params.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'complex_hook', $hooks );

		$hook = $hooks['complex_hook'];
		$this->assertEquals( 'apply_filters', $hook['type'] );
		$this->assertArrayHasKey( 'params', $hook );
		$this->assertCount( 3, $hook['params'] ); // Three parameters after hook name.
	}

	public function test_extract_hooks_with_default_config() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$this->assertArrayHasKey( 'simple_hook', $hooks );
		$this->assertEquals( 'apply_filters', $hooks['simple_hook']['type'] );
	}

	public function test_extract_hooks_with_custom_config() {
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

	public function test_create_documentation_content() {
		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'index', $documentation );
		$this->assertArrayHasKey( 'hooks', $documentation );
		$this->assertArrayHasKey( 'simple_hook', $documentation['hooks'] );

		$index = $documentation['index'];
		$this->assertStringContainsString( '## simple_filter.php', $index );
		$this->assertStringContainsString( '- [`simple_hook`](simple_hook)', $index );

		$hook_content = $documentation['hooks']['simple_hook'];
		$this->assertStringContainsString( '## Parameters', $hook_content );
		$this->assertStringContainsString( 'add_filter(', $hook_content );
		$this->assertStringContainsString( '## Files', $hook_content );
		$this->assertStringContainsString( '[simple_filter.php:7](' . $github_blob_url . 'simple_filter.php#L7)', $hook_content );
	}

	public function test_create_documentation_content_with_example() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$hook_content = $documentation['hooks']['user_data_filter'];
		$this->assertStringContainsString( '## Example', $hook_content );
		$this->assertStringContainsString( 'add_filter', $hook_content );
		$this->assertStringContainsString( 'last_modified', $hook_content );
		$this->assertStringNotContainsString( '## Auto-generated Example', $hook_content );
	}

	public function test_create_documentation_content_action_hook() {
		$file_path = __DIR__ . '/fixtures/action_hook.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$hook_content = $documentation['hooks']['test_action'];
		$this->assertStringContainsString( 'add_action(', $hook_content );
		$this->assertStringNotContainsString( 'return $data;', $hook_content );
	}

	public function test_create_documentation_content_with_example_tag() {
		$file_path = __DIR__ . '/fixtures/gatherpress1.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'gatherpress_pseudopostmetas', $documentation['hooks'] );
		
		$hook_content = $documentation['hooks']['gatherpress_pseudopostmetas'];
		$this->assertStringContainsString( '## Example', $hook_content );
		$this->assertStringContainsString( 'event-organiser', $hook_content );
		$this->assertStringContainsString( 'add_filter', $hook_content );
		$this->assertStringContainsString( 'export_callback', $hook_content );
		$this->assertStringContainsString( 'import_callback', $hook_content );
		$this->assertStringNotContainsString( '## Auto-generated Example', $hook_content );
	}
}
