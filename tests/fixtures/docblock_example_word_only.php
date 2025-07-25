<?php
/**
 * Test hook with Example word only
 *
 * This hook allows filtering the post title.
 *
 * Example
 *
 * You can use this hook like this:
 * add_filter('post_title_filter', 'my_filter_function');
 *
 * @param string $title The post title
 * @return string Modified title
 */
return apply_filters( 'post_title_filter', $title );
