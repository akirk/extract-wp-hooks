<?php
$setting = get_option( 'setting' );

/**
 * Filter the complex hook with proper parameter documentation.
 *
 * @param string $setting The setting value
 * @param int    $user_id The user ID
 * @param array  $options Additional options
 */
return apply_filters(
	'complex_hook',
	$setting,
	$user->ID,
	array( 'key' => 'value' )
);
