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

	public function testParseDocblockWithEmptyComment() {
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( '', array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function testParseDocblockWithSimpleComment() {
		$file_path = __DIR__ . '/fixtures/docblock_simple.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 'Simple comment', $result['comment'] );
	}

	public function testParseDocblockWithSingleLineDocblock() {
		$file_path = __DIR__ . '/fixtures/docblock_single_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertEquals( 'Single line docblock', $result['comment'] );
	}

	public function testParseDocblockWithMultiLineDocblock() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );
		$this->assertStringContainsString( 'Multi line docblock', $result['comment'] );
	}

	public function testParseDocblockWithParamTag() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'params', $result );
		$this->assertIsArray( $result['params'] );
	}

	public function testParseDocblockIgnoresDocumentedIn() {
		$file_path = __DIR__ . '/fixtures/docblock_documented_in.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function testParseDocblockWithReturnTag() {
		$file_path = __DIR__ . '/fixtures/docblock_multi_line.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'returns', $result );
		$this->assertEquals( 'bool The result', $result['returns'] );
	}

	public function testParseDocblockWithExampleCode() {
		$file_path = __DIR__ . '/fixtures/docblock_with_example.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );

		// Should contain the raw example code (not yet converted to markdown).
		$this->assertStringContainsString( 'Example:', $result['comment'] );
		$this->assertStringContainsString( 'add_filter', $result['comment'] );
		$this->assertStringContainsString( 'user_data_filter', $result['comment'] );

		// Test that it detects the example pattern for later conversion.
		$this->assertTrue( preg_match( '/^Example:?$/m', $result['comment'] ) === 1 );
	}

	public function testParseDocblockWithExampleWordOnly() {
		$file_path = __DIR__ . '/fixtures/docblock_example_word_only.php';
		$comment = $this->extractCommentFromFile( $file_path );
		$extractor = new WpHookExtractor();
		$result = $extractor->parse_docblock( $comment, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'comment', $result );

		// Should contain raw "Example" (not yet converted to markdown).
		$this->assertStringContainsString( 'Example', $result['comment'] );
		$this->assertStringContainsString( 'add_filter', $result['comment'] );

		// Test that it detects the example pattern for later conversion.
		$this->assertTrue( preg_match( '/^Example:?$/m', $result['comment'] ) === 1 );
	}
}
