<?php
/**
 * Complex parameter test with docblock - this comment should be ignored
 * because it's attached to the function, not the filter
 *
 * @param string $setting The setting value
 * @param int    $user_id The user ID
 * @param array  $options Additional options
 */
function test_complex() {
	// This comment should also be ignored - not directly before apply_filters
	$setting = get_option( 'setting' );
	
	return apply_filters(
		'complex_hook',
		$setting,
		$user->ID,
		array( 'key' => 'value' )
	);
}
