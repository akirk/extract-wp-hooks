<?php
/**
 * Test file with one parameter using do_action
 */
function test_one_param() {
	do_action( 'one_param_hook', $param1 );
}