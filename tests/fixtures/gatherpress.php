<?php
function get_pseudopostmetas(): array {
	/**
	 * Filters the list of data-names and their respective export- and import-callbacks.
	 *
	 * The filter allows to hook into WordPress' native import & export processes,
	 * when post types of the GatherPress plugin are being migrated.
	 * That can be helpful, if you want to import event- or venue-data from another plugin.
	 *
	 * @example
	 *   The filter in use, to import data from the "Event Organiser" plugin.
	 *   https://github.com/carstingaxion/gatherpress-export-import/blob/main/import-events-from--event-organiser.php
	 *
	 * @example
	 *   Example use of the filter to illustrate function signatures for the callbacks.
	 *   ```
	 *   \add_filter(
	 *       'gatherpress_pseudopostmetas',
	 *       function ( array $pseudopostmetas ): array {
	 *           $pseudopostmetas['my_gatherpress_extension_data_name'] = [
	 *               'export_callback' => function ( WP_Post $post ): string {
	 *                   // Do something with $post.
	 *                   // Query & prepare custom data
	 *                   // to exported with the current post.
	 *                   return 'my_gatherpress_extension_data';
	 *               },
	 *               'import_callback' => function (int $post_id, $meta_value ): void {
	 *                   // Save data for given post_id to a custom location,
	 *                   // when data should not end up in the postmeta table.
	 *                   return;
	 *               },
	 *           ];
	 *           return $pseudopostmetas;
	 *       }
	 *   );
	 *   ```
	 * @since 1.0.0
	 *
	 * @param  array $pseudopostmetas List of data-names and their respective export- and import-callbacks.
	 * @return array
	 */
	return (array) apply_filters( 'gatherpress_pseudopostmetas', $this->pseudopostmetas );
}
