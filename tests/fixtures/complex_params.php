<?php
/**
 * Complex parameter test with docblock
 *
 * @param string $setting The setting value
 * @param int    $user_id The user ID
 * @param array  $options Additional options
 */
function test_complex() {
	return apply_filters(
		'complex_hook',
		get_option( 'setting' ),
		$user->ID,
		array( 'key' => 'value' )
	);
}
