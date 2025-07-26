<?php
/**
 * Test file with two parameters
 */
function test_two_params() {
	$first = 'value1';
	$second = 'value2';
	return apply_filters( 'two_param_hook', $first, $second );
}