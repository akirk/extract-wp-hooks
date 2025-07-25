<?php

use PHPUnit\Framework\TestCase;

class ExtractVarsTest extends TestCase
{
	private function findHookTokenIndex($tokens, $hookFunction)
	{
		foreach ($tokens as $i => $token) {
			if (is_array($token) && $token[1] === $hookFunction) {
				return $i;
			}
		}
		return null;
	}

	public function testExtractVarsWithSimpleFilter()
	{
		$filePath = __DIR__ . '/fixtures/simple_filter.php';
		$tokens = token_get_all(file_get_contents($filePath));

		$filterIndex = $this->findHookTokenIndex($tokens, 'apply_filters');
		$this->assertNotNull($filterIndex, 'apply_filters token not found');

		$result = extract_vars([], $tokens, $filterIndex);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertIsArray($result[0]); // params
		$this->assertIsString($result[1]); // signature
		$this->assertStringContainsString('apply_filters', $result[1]);
		$this->assertStringContainsString('simple_hook', $result[1]);
	}

	public function testExtractVarsWithMultipleParams()
	{
		$filePath = __DIR__ . '/fixtures/multiple_params.php';
		$tokens = token_get_all(file_get_contents($filePath));

		$filterIndex = $this->findHookTokenIndex($tokens, 'apply_filters');
		$this->assertNotNull($filterIndex, 'apply_filters token not found');

		$result = extract_vars([], $tokens, $filterIndex);

		$this->assertIsArray($result[0]);
		$this->assertArrayHasKey(0, $result[0]); // First parameter after hook name
		$this->assertArrayHasKey(1, $result[0]); // Second parameter
		$this->assertArrayHasKey(2, $result[0]); // Third parameter
	}

	public function testExtractVarsWithDoAction()
	{
		$filePath = __DIR__ . '/fixtures/action_hook.php';
		$tokens = token_get_all(file_get_contents($filePath));

		$actionIndex = $this->findHookTokenIndex($tokens, 'do_action');
		$this->assertNotNull($actionIndex, 'do_action token not found');

		$result = extract_vars([], $tokens, $actionIndex);

		$this->assertStringContainsString('do_action', $result[1]);
		$this->assertStringContainsString('test_action', $result[1]);
	}

	public function testExtractVarsWithComplexParameters()
	{
		$filePath = __DIR__ . '/fixtures/complex_params.php';
		$tokens = token_get_all(file_get_contents($filePath));

		$filterIndex = $this->findHookTokenIndex($tokens, 'apply_filters');
		$this->assertNotNull($filterIndex, 'apply_filters token not found');

		$result = extract_vars([], $tokens, $filterIndex);

		$this->assertIsArray($result[0]);
		$this->assertCount(3, $result[0]); // Three parameters after hook name
		$this->assertStringContainsString('apply_filters', $result[1]);
		$this->assertStringContainsString('complex_hook', $result[1]);
	}
}
