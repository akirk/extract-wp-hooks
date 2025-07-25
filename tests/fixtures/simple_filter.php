<?php
/**
 * Test file with simple filter
 */
function test_function() {
	$value = 'test';
	return apply_filters( 'simple_hook', $value );
}
