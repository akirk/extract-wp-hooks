<?php

class FunctionDocsTest extends PHPUnit\Framework\TestCase {
	private $extractor;

	public function setUp(): void {
		$this->extractor = new WpHookExtractor(
			array(
				'include_function_docs' => true,
				'example_style'         => 'prefixed',
			)
		);
	}

	public function test_generate_function_docs_filter() {
		$hook_name = 'test_filter';
		$hook_type = 'filter';
		$params = array( 'string $content', 'int $post_id' );
		$description = "Filters the content of a post.\nAllows modification of post content before display.";
		$return_type = 'string The filtered content';
		$callback_name = 'my_test_filter_callback';

		$expected = <<<'EOD'
/**
 * Filters the content of a post.
 * Allows modification of post content before display.
 *
 * @param string $content 
 * @param int    $post_id 
 * @return string The filtered content
 */
EOD;

		$result = $this->extractor->generate_function_docs(
			$hook_name,
			$hook_type,
			$params,
			$description,
			$return_type,
			$callback_name
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_generate_function_docs_action() {
		$hook_name = 'test_action';
		$hook_type = 'action';
		$params = array( 'int $post_id', 'WP_Post $post' );
		$description = 'Fires after a post is saved.';
		$callback_name = 'my_test_action_callback';

		$expected = <<<'EOD'
/**
 * Fires after a post is saved.
 *
 * @param int     $post_id 
 * @param WP_Post $post 
 */
EOD;

		$result = $this->extractor->generate_function_docs(
			$hook_name,
			$hook_type,
			$params,
			$description,
			'',
			$callback_name
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_generate_function_docs_no_params() {
		$hook_name = 'init';
		$hook_type = 'action';
		$params = array();
		$description = 'Fires after WordPress has finished loading but before any headers are sent.';
		$callback_name = 'my_init_callback';

		$expected = <<<'EOD'
/**
 * Fires after WordPress has finished loading but before any headers are sent.
 */
EOD;

		$result = $this->extractor->generate_function_docs(
			$hook_name,
			$hook_type,
			$params,
			$description,
			'',
			$callback_name
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_generate_function_docs_no_description() {
		$hook_name = 'test_hook';
		$hook_type = 'filter';
		$params = array( 'mixed $value' );
		$description = '';
		$callback_name = 'my_test_hook_callback';

		$expected = <<<'EOD'
/**
 * Callback function for the 'test_hook' filter.
 *
 * @param mixed $value 
 * @return mixed The filtered value.
 */
EOD;

		$result = $this->extractor->generate_function_docs(
			$hook_name,
			$hook_type,
			$params,
			$description,
			'',
			$callback_name
		);

		$this->assertEquals( $expected, $result );
	}
}
