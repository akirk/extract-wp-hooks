<?php

use PHPUnit\Framework\TestCase;

class ParseDocblockTest extends TestCase {

	private function extractCommentFromFile( $file_path ) {
		$tokens = token_get_all( file_get_contents( $file_path ) );

		foreach ( $tokens as $token ) {
			if ( is_array( $token ) && ( T_DOC_COMMENT === $token[0] || T_COMMENT === $token[0] ) ) {
				return $token[1];
			}
		}

		return '';
	}

	public function test_parse_docblock_with_empty_comment() {
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( '', array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_parse_docblock_with_simple_comment() {
		$file_path = __DIR__ . '/fixtures/docblock_simple.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 'Simple comment', $result['comment'] );
	}

	public function test_parse_docblock_with_single_line_docblock() {
		$file_path = __DIR__ . '/fixtures/docblock_single_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 'Single line docblock', $result['comment'] );
	}

	public function test_parse_docblock_with_multi_line_docblock() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertStringContainsString( 'Multi line docblock', $result['comment'] );
	}

	public function test_parse_docblock_with_param_tag() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'params', $result );
		$this->assertIsArray( $result['params'] );
	}

	public function test_parse_docblock_ignores_documented_in() {
		$file_path = __DIR__ . '/fixtures/docblock_documented_in.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_parse_docblock_with_return_tag() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'returns', $result );
		$this->assertEquals( 'bool The result', $result['returns'] );
	}

	public function test_parse_docblock_with_example_code() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );

		// Example should now be extracted to examples array, not in comment.
		$this->assertArrayHasKey( 'examples', $result );
		$this->assertCount( 1, $result['examples'] );
		$this->assertStringContainsString( 'add_filter', $result['examples'][0]['content'] );
		$this->assertStringContainsString( 'user_data_filter', $result['examples'][0]['content'] );

		// Comment should not contain the example anymore.
		$this->assertStringNotContainsString( 'Example:', $result['comment'] );
	}

	public function test_parse_docblock_with_example_word_only() {
		$file_path = __DIR__ . '/fixtures/docblock_example_word_only.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );

		// Example should now be extracted to examples array, not in comment.
		$this->assertArrayHasKey( 'examples', $result );
		$this->assertCount( 1, $result['examples'] );
		$this->assertStringContainsString( 'add_filter', $result['examples'][0]['content'] );
		$this->assertStringContainsString( 'my_filter_function', $result['examples'][0]['content'] );

		// Comment should not contain the standalone "Example" line anymore.
		$this->assertStringNotContainsString( "Example\n", $result['comment'] );
	}
}
