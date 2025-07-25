<?php
/**
 * Test hook with Example word only
 *
 * This hook allows filtering the post content.
 *
 * Example
 *
 * You can use this hook like this:
 * add_filter('post_content_filter', 'my_filter_function');
 *
 * @param string $content The post content
 * @return string Modified content
 */
return apply_filters( 'post_content_filter', $content );
