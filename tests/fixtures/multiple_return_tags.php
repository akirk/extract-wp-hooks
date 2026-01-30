<?php
/**
 * Filter with multiple return tags (unusual but should not crash).
 *
 * @param mixed $value The value to filter.
 * @return string When the value is a string.
 * @return int When the value is numeric.
 */
$result = apply_filters( 'multiple_return_tags_hook', $value );
