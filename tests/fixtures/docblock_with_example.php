<?php
/**
 * Test hook with example code
 * 
 * This hook allows filtering the user data before saving.
 * 
 * Example:
 * ```php
 * add_filter('user_data_filter', function($data, $user_id) {
 *     $data['last_modified'] = current_time('mysql');
 *     return $data;
 * }, 10, 2);
 * ```
 * 
 * @param array $data The user data array
 * @param int $user_id The user ID
 * @return array Modified user data
 */
return apply_filters('user_data_filter', $data, $user_id);
