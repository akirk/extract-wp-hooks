<?php
/**
 * Test file with two parameters using do_action
 */
function test_two_params() {
	do_action( 'two_param_hook', $param1, $param2 );
}