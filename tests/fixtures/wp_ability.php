<?php
/**
 * Registers text analysis abilities.
 */
function my_plugin_register_abilities() {
	wp_register_ability(
		'my-plugin/analyze-text',
		array(
			'label'               => __( 'Analyze Text', 'my-plugin' ),
			'description'         => __( 'Analyzes text and returns a sentiment result.', 'my-plugin' ),
			'category'            => 'text-processing',
			'execute_callback'    => 'my_plugin_analyze_text',
			'permission_callback' => array( 'My_Plugin', 'can_analyze_text' ),
			'input_schema'        => array(
				'type'        => 'string',
				'description' => __( 'The text to analyze.', 'my-plugin' ),
				'minLength'   => 10,
			),
			'output_schema'       => array(
				'type' => 'string',
				'enum' => array( 'positive', 'negative', 'neutral' ),
			),
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly' => true,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'my_plugin_register_abilities' );
