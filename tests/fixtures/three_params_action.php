<?php
/**
 * Test file with three parameters using do_action
 */
function test_three_params() {
	do_action( 'three_param_hook', $param1, $param2, $param3 );
}