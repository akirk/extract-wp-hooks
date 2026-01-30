<?php
/**
 * Filter term arguments before creating the term.
 *
 * @since 0.1.0
 *
 * @param array $args {
 *     Term arguments.
 *
 *     @type string                $name      Term name.
 *     @type string                $slug      Term slug.
 *     @type int                   $parent    Parent term ID.
 *     @type string                $taxonomy  Taxonomy name.
 *     @type int                   $level     Hierarchy level (1-6).
 *     @type array<string, string> $location  Full location data array.
 * }
 */
$term_args = apply_filters(
	'multiple_type_tags_hook',
	array(
		'name'     => $name,
		'slug'     => $slug,
		'parent'   => $parent_id,
		'taxonomy' => $taxonomy,
		'level'    => $level,
		'location' => $location,
	)
);
