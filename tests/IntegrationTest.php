<?php

class IntegrationTest extends WpHookExtractor_Testcase {
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
		$this->assertStringEqualsFileOrWrite(
			__DIR__ . '/fixtures/expected/example_docblock_example_word_only.md',
			'## Example' . PHP_EOL . PHP_EOL . $hook['examples'][0]['content'] . PHP_EOL
		);
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

		$this->assertArrayHasKey( 'example', $documentation['hooks']['simple_hook'] );
		$this->assertArrayHasKey( 'parameters', $documentation['hooks']['simple_hook'] );
		$this->assertArrayHasKey( 'files', $documentation['hooks']['simple_hook'] );
		$this->assertStringContainsString( 'add_filter(', $documentation['hooks']['simple_hook']['example'] );
		$this->assertStringContainsString( '[simple_filter.php:7](' . $github_blob_url . 'simple_filter.php#L7)', $documentation['hooks']['simple_hook']['files'] );
	}

	public function test_create_documentation_content_with_example() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'user_data_filter', $documentation['hooks'] );
		$this->assertArrayHasKey( 'example', $documentation['hooks']['user_data_filter'] );
		$this->assertArrayHasKey( 'parameters', $documentation['hooks']['user_data_filter'] );
		$this->assertArrayHasKey( 'files', $documentation['hooks']['user_data_filter'] );
		$this->assertStringContainsString( 'add_filter', $documentation['hooks']['user_data_filter']['example'] );
		$this->assertStringContainsString( 'last_modified', $documentation['hooks']['user_data_filter']['example'] );
		$this->assertStringNotContainsString( '## Auto-generated Example', $documentation['hooks']['user_data_filter']['example'] );
	}

	public function test_create_documentation_content_action_hook() {
		$file_path = __DIR__ . '/fixtures/action_hook.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'test_action', $documentation['hooks'] );
		$this->assertStringContainsString( 'add_action(', $documentation['hooks']['test_action']['example'] );
		$this->assertStringNotContainsString( 'return $data;', $documentation['hooks']['test_action']['example'] );
	}

	public function test_create_documentation_content_with_example_tag() {
		$file_path = __DIR__ . '/fixtures/gatherpress.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'gatherpress_pseudopostmetas', $documentation['hooks'] );

		$this->assertArrayHasKey( 'gatherpress_pseudopostmetas', $documentation['hooks'] );
		$this->assertArrayHasKey( 'example', $documentation['hooks']['gatherpress_pseudopostmetas'] );
		$this->assertStringContainsString( 'event-organiser', $documentation['hooks']['gatherpress_pseudopostmetas']['example'] );
		$this->assertStringContainsString( 'add_filter', $documentation['hooks']['gatherpress_pseudopostmetas']['example'] );
		$this->assertStringContainsString( 'export_callback', $documentation['hooks']['gatherpress_pseudopostmetas']['example'] );
		$this->assertStringContainsString( 'import_callback', $documentation['hooks']['gatherpress_pseudopostmetas']['example'] );
		$this->assertStringNotContainsString( '## Auto-generated Example', $documentation['hooks']['gatherpress_pseudopostmetas']['example'] );
	}

	public function test_action_multiple_files() {
		$file_path = __DIR__ . '/fixtures/two_params_action.php';
		$file_path2 = __DIR__ . '/fixtures/two_params_action_second_file.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );
		$hooks = $extractor->merge_file_hooks( $hooks, $extractor->extract_hooks_from_file( $file_path2 ) );

		$this->assertArrayHasKey( 'two_param_action_hook', $hooks );
		$this->assertEquals( 'do_action', $hooks['two_param_action_hook']['type'] );

		$this->assertCount( 2, $hooks['two_param_action_hook']['files'] );
		$this->assertCount( 3, $hooks['two_param_action_hook']['params'] );
	}
}
