<?php
/**
 * Filters the AT Tags emitted in the page head.
 *
 * @param array<string, string[]> $tags Tag name => list of AT-URIs.
 * @return array<string, string[]> The filtered tags.
 */
$tags = apply_filters( 'generic_type_with_space_hook', $tags );
