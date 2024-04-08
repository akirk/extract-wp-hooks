<?php

namespace Extract_Hooks;

/*
 * This is example filter 1.
 *
 * @param string $text The text to modify.
 * @param string $mode Extra information that might be useful.
 * @returns Return the modified text.
 */
$result = apply_filters( 'example_filter1', $text, $mode );

/*
 * This is example filter 2.
 *
 * Example:
 * ```php
 * add_filter( 'example_filter2', function ( $text ) {
 *     return strtolower( $text );
 * } );
 * ```
 *
 * @param string $text The text to modify.
 * @param string $mode Extra information that might be useful.
 * @returns Return the modified text.
 */
$result = apply_filters( 'example_filter2', $text, $mode );

$result = apply_filters( 'example_filter3', $text, $mode );
