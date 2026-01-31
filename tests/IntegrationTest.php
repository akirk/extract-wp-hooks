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


	public function test_github_wiki_disabled() {
		$config = array(
			'example_style' => 'default',
			'github_wiki'   => false,
		);
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/zero_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		// Should have top headline when not in wiki mode.
		$this->assertArrayHasKey( 'headline', $documentation['hooks']['zero_param_hook'] );

		// Links should have .md extension when not in wiki mode.
		$this->assertStringContainsString( '(zero_param_hook.md)', $documentation['index'] );
		$this->assertStringContainsString( '(Hooks.md)', $documentation['hooks']['zero_param_hook']['files'] );
	}

	public function test_null_param_without_namespace_prefix() {
		$config = array(
			'namespace' => 'ActivityPub',
		);
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/null_param.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'activitypub_pre_get_by_username', $documentation['hooks'] );
		$params_section = $documentation['hooks']['activitypub_pre_get_by_username']['parameters'];

		// Verify that null type is not prefixed with namespace.
		$this->assertStringContainsString( '*`null`*', $params_section );
		$this->assertStringNotContainsString( 'ActivityPub\\null', $params_section );

		// Verify string type is also not prefixed.
		$this->assertStringContainsString( '*`string`*', $params_section );
		$this->assertStringNotContainsString( 'ActivityPub\\string', $params_section );
	}

	public function test_dynamic_hook_extraction() {
		$file_path = __DIR__ . '/fixtures/dynamic_hook.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		// Verify the hook name includes the dynamic part with {$var} syntax.
		$this->assertArrayHasKey( 'activitypub_inbox_{$type}', $hooks );

		$hook = $hooks['activitypub_inbox_{$type}'];
		$this->assertEquals( 'do_action', $hook['type'] );
		$this->assertArrayHasKey( 'comment', $hook );
		$this->assertEquals( 'ActivityPub inbox action for specific activity types.', $hook['comment'] );

		// Verify parameters are extracted.
		$this->assertArrayHasKey( 'params', $hook );
		$this->assertCount( 3, $hook['params'] );
	}

	public function test_dynamic_hook_documentation_uses_wildcard() {
		$file_path = __DIR__ . '/fixtures/dynamic_hook.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertArrayHasKey( 'activitypub_inbox_{$type}', $documentation['hooks'] );
		$example = $documentation['hooks']['activitypub_inbox_{$type}']['example'];

		// Verify the example uses * instead of {$type}.
		$this->assertStringContainsString( 'activitypub_inbox_*', $example );
		$this->assertStringNotContainsString( 'activitypub_inbox_{$type}', $example );
		$this->assertStringNotContainsString( '{$type}', $example );
	}

	public function test_deprecated_hook_detection() {
		$file_path = __DIR__ . '/fixtures/deprecated_hooks.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		// Test deprecated action hook.
		$this->assertArrayHasKey( 'activitypub_followers_post_follow', $hooks );
		$deprecated_action = $hooks['activitypub_followers_post_follow'];
		$this->assertTrue( $deprecated_action['deprecated'] );
		$this->assertEquals( '7.5.0', $deprecated_action['deprecated_version'] );
		$this->assertEquals( 'activitypub_handled_follow', $deprecated_action['replacement'] );
		$this->assertEquals( 'do_action', $deprecated_action['type'] );

		// Test deprecated filter hook.
		$this->assertArrayHasKey( 'activitypub_rest_following', $hooks );
		$deprecated_filter = $hooks['activitypub_rest_following'];
		$this->assertTrue( $deprecated_filter['deprecated'] );
		$this->assertEquals( '7.1.0', $deprecated_filter['deprecated_version'] );
		$this->assertEquals( 'Please migrate your Followings to the new internal Following structure.', $deprecated_filter['replacement'] );
		$this->assertEquals( 'apply_filters', $deprecated_filter['type'] );

		// Test deprecated hook without replacement.
		$this->assertArrayHasKey( 'old_legacy_hook', $hooks );
		$no_replacement_hook = $hooks['old_legacy_hook'];
		$this->assertTrue( $no_replacement_hook['deprecated'] );
		$this->assertEquals( '8.0.0', $no_replacement_hook['deprecated_version'] );
		$this->assertArrayNotHasKey( 'replacement', $no_replacement_hook );
		$this->assertEquals( 'do_action', $no_replacement_hook['type'] );

		// Test deprecated hook without version or replacement.
		$this->assertArrayHasKey( 'very_old_hook', $hooks );
		$no_version_hook = $hooks['very_old_hook'];
		$this->assertTrue( $no_version_hook['deprecated'] );
		$this->assertArrayNotHasKey( 'deprecated_version', $no_version_hook );
		$this->assertArrayNotHasKey( 'replacement', $no_version_hook );
		$this->assertEquals( 'do_action', $no_version_hook['type'] );

		// Verify we have four deprecated hooks.
		$this->assertCount( 4, $hooks );
	}

	public function test_deprecated_hook_documentation() {
		$file_path = __DIR__ . '/fixtures/deprecated_hooks.php';
		$extractor = new WpHookExtractor();
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		// Test deprecated action documentation.
		$this->assertArrayHasKey( 'activitypub_followers_post_follow', $documentation['hooks'] );
		$deprecated_action_sections = $documentation['hooks']['activitypub_followers_post_follow'];

		// Build complete documentation for the action hook.
		$section_order = array( 'headline', 'deprecation', 'description', 'example', 'parameters', 'returns', 'files' );
		$action_doc = '';
		foreach ( $section_order as $section_key ) {
			if ( isset( $deprecated_action_sections[ $section_key ] ) ) {
				$action_doc .= $deprecated_action_sections[ $section_key ];
			}
		}
		$this->assertStringEqualsFile( __DIR__ . '/fixtures/expected/deprecated_action_hook.md', $action_doc );

		// Test deprecated filter documentation.
		$this->assertArrayHasKey( 'activitypub_rest_following', $documentation['hooks'] );
		$deprecated_filter_sections = $documentation['hooks']['activitypub_rest_following'];

		// Build complete documentation for the filter hook.
		$filter_doc = '';
		foreach ( $section_order as $section_key ) {
			if ( isset( $deprecated_filter_sections[ $section_key ] ) ) {
				$filter_doc .= $deprecated_filter_sections[ $section_key ];
			}
		}
		$this->assertStringEqualsFile( __DIR__ . '/fixtures/expected/deprecated_filter_hook.md', $filter_doc );

		// Test deprecated hook without replacement.
		$this->assertArrayHasKey( 'old_legacy_hook', $documentation['hooks'] );
		$no_replacement_sections = $documentation['hooks']['old_legacy_hook'];

		// Build complete documentation for the hook without replacement.
		$no_replacement_doc = '';
		foreach ( $section_order as $section_key ) {
			if ( isset( $no_replacement_sections[ $section_key ] ) ) {
				$no_replacement_doc .= $no_replacement_sections[ $section_key ];
			}
		}
		$this->assertStringEqualsFile( __DIR__ . '/fixtures/expected/deprecated_hook_no_replacement.md', $no_replacement_doc );

		// Test deprecated hook without version or replacement.
		$this->assertArrayHasKey( 'very_old_hook', $documentation['hooks'] );
		$no_version_sections = $documentation['hooks']['very_old_hook'];

		// Build complete documentation for the hook without version or replacement.
		$no_version_doc = '';
		foreach ( $section_order as $section_key ) {
			if ( isset( $no_version_sections[ $section_key ] ) ) {
				$no_version_doc .= $no_version_sections[ $section_key ];
			}
		}
		$this->assertStringEqualsFile( __DIR__ . '/fixtures/expected/deprecated_hook_no_version.md', $no_version_doc );

		// Test index shows deprecated marker.
		$this->assertStringEqualsFile( __DIR__ . '/fixtures/expected/deprecated_hooks_index.md', $documentation['index'] );
	}
}
