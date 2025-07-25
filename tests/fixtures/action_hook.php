<?php
/**
 * Test file with action hook
 */

function test_action() {
	$data = array('key' => 'value');
	do_action('test_action', $data);
}
