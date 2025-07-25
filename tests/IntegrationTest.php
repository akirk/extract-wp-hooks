<?php

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
	public function testExampleCodeExtraction()
	{
		$filePath = __DIR__ . '/fixtures/docblock_with_example.php';
		$hooks = extract_hooks_from_file($filePath);

		$this->assertArrayHasKey('user_data_filter', $hooks);

		$hook = $hooks['user_data_filter'];
		$this->assertEquals('apply_filters', $hook['type']);
		$this->assertArrayHasKey('comment', $hook);


		// Verify raw example code is preserved (conversion happens during doc generation)
		$this->assertStringContainsString('Example:', $hook['comment']);
		$this->assertStringContainsString('add_filter', $hook['comment']);
		$this->assertStringContainsString('user_data_filter', $hook['comment']);

		// Test example detection pattern
		$hasExample = preg_match('/^Example:?$/m', $hook['comment']);
		$this->assertEquals(1, $hasExample);

		// Verify parameters are extracted
		$this->assertArrayHasKey('params', $hook);
		$this->assertCount(2, $hook['params']); // $data and $user_id

		// Verify return tag is extracted
		$this->assertArrayHasKey('returns', $hook);
		$this->assertEquals('array Modified user data', $hook['returns']);
	}

	public function testExampleWordOnlyDetection()
	{
		$filePath = __DIR__ . '/fixtures/docblock_example_word_only.php';
		$hooks = extract_hooks_from_file($filePath);

		$this->assertArrayHasKey('post_content_filter', $hooks);

		$hook = $hooks['post_content_filter'];
		$this->assertArrayHasKey('comment', $hook);

		// Verify raw "Example" word is preserved
		$this->assertStringContainsString('Example', $hook['comment']);
		$this->assertStringContainsString('add_filter', $hook['comment']);

		// Test example detection pattern
		$hasExample = preg_match('/^Example:?$/m', $hook['comment']);
		$this->assertEquals(1, $hasExample);
	}

	public function testComplexParameterExtraction()
	{
		$filePath = __DIR__ . '/fixtures/complex_params.php';
		$hooks = extract_hooks_from_file($filePath);

		$this->assertArrayHasKey('complex_hook', $hooks);

		$hook = $hooks['complex_hook'];
		$this->assertEquals('apply_filters', $hook['type']);
		$this->assertArrayHasKey('params', $hook);
		$this->assertCount(3, $hook['params']); // Three parameters after hook name
	}

	public function testExtractHooksWithDefaultConfig()
	{
		$filePath = __DIR__ . '/fixtures/simple_filter.php';
		$hooks = extract_hooks_from_file($filePath);

		$this->assertArrayHasKey('simple_hook', $hooks);
		$this->assertEquals('apply_filters', $hooks['simple_hook']['type']);
	}

	public function testExtractHooksWithCustomConfig()
	{
		$filePath = __DIR__ . '/fixtures/simple_filter.php';
		$config = [
			'section' => 'dir',
			'namespace' => 'MyPlugin'
		];
		$hooks = extract_hooks_from_file($filePath, $config);

		$this->assertArrayHasKey('simple_hook', $hooks);
		$this->assertEquals('simple_filter.php', $hooks['simple_hook']['section']);
	}
}
