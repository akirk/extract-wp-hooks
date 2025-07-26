<?php

class ExampleStyleTest extends WpHookExtractor_Testcase {
	public function test_default_example_style_0_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/zero_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_0_params.md', $documentation['hooks']['zero_param_hook']['example'] );
	}

	public function test_prefixed_example_style_0_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/zero_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_0_params.md', $documentation['hooks']['zero_param_hook']['example'] );
	}

	public function test_default_example_style_1_param() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_style.md', $documentation['hooks']['simple_hook']['example'] );
	}

	public function test_prefixed_example_style_1_param() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_style.md', $documentation['hooks']['simple_hook']['example'] );
	}

	public function test_default_example_style_2_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/two_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_2_params.md', $documentation['hooks']['two_param_hook']['example'] );
	}

	public function test_prefixed_example_style_2_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/two_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_2_params.md', $documentation['hooks']['two_param_hook']['example'] );
	}

	public function test_default_example_style_3_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/multiple_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_3_params.md', $documentation['hooks']['multi_param_hook']['example'] );
	}

	public function test_prefixed_example_style_3_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/multiple_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_3_params.md', $documentation['hooks']['multi_param_hook']['example'] );
	}

	public function test_example_style_does_not_affect_existing_examples() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$example_section = $documentation['hooks']['user_data_filter']['example'];

		// Should contain the original example from the docblock.
		$this->assertStringContainsString( '## Example', $example_section );

		// Should NOT contain auto-generated example when docblock example exists.
		$this->assertStringNotContainsString( '## Auto-generated Example', $example_section );
	}

	public function test_invalid_example_style_falls_back_to_default() {
		$config = array( 'example_style' => 'invalid_style' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/simple_filter.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		// Should fall back to default style.
		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_style.md', $documentation['hooks']['simple_hook']['example'] );
	}

	public function test_default_example_style_action_0_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/zero_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_action_0_params.md', $documentation['hooks']['zero_param_hook']['example'] );
	}

	public function test_default_example_style_action_1_param() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/one_param_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_action_1_param.md', $documentation['hooks']['one_param_hook']['example'] );
	}

	public function test_default_example_style_action_2_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/two_params_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_action_2_params.md', $documentation['hooks']['two_param_action_hook']['example'] );
	}

	public function test_default_example_style_action_3_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/three_params_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_action_3_params.md', $documentation['hooks']['three_param_hook']['example'] );
	}

	public function test_prefixed_example_style_action_0_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/zero_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_action_0_params.md', $documentation['hooks']['zero_param_hook']['example'] );
	}

	public function test_prefixed_example_style_action_1_param() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/one_param_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_action_1_param.md', $documentation['hooks']['one_param_hook']['example'] );
	}

	public function test_prefixed_example_style_action_2_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/two_params_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_action_2_params.md', $documentation['hooks']['two_param_action_hook']['example'] );
	}

	public function test_prefixed_example_style_action_3_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/three_params_action.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_action_3_params.md', $documentation['hooks']['three_param_hook']['example'] );
	}

	public function test_default_example_style_complex_params() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/complex_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_complex_params.md', $documentation['hooks']['complex_hook']['example'] );
	}

	public function test_prefixed_example_style_complex_params() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/complex_params.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_complex_params.md', $documentation['hooks']['complex_hook']['example'] . PHP_EOL );
	}

	public function test_default_example_style_complex_params_invalid_comment_position() {
		$config = array( 'example_style' => 'default' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/complex_params_invalid_comment_position.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_default_complex_params_invalid_comment_position.md', $documentation['hooks']['complex_hook_invalid_comment']['example'] );
	}

	public function test_prefixed_example_style_complex_params_invalid_comment_position() {
		$config = array( 'example_style' => 'prefixed' );
		$extractor = new WpHookExtractor( $config );

		$file_path = __DIR__ . '/fixtures/complex_params_invalid_comment_position.php';
		$hooks = $extractor->extract_hooks_from_file( $file_path );

		$github_blob_url = 'https://github.com/test/repo/blob/main/';
		$documentation = $extractor->create_documentation_content( $hooks, $github_blob_url );

		$this->assertStringEqualsFileOrWrite( __DIR__ . '/fixtures/expected/example_prefixed_complex_params_invalid_comment_position.md', $documentation['hooks']['complex_hook_invalid_comment']['example'] );
	}
}
