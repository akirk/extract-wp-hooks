<?php
/**
 * Test file with multiple parameters
 */
function test_multiple_params() {
	$param1 = 'first';
	$param2 = 'second';
	return apply_filters( 'multi_param_hook', $param1, $param2, $extra_data );
}
