<?php
function my_plugin_register_unnamespaced_ability() {
	wp_register_ability(
		'analyze-text',
		array(
			'label'               => 'Analyze Text',
			'description'         => 'Analyzes text.',
			'category'            => 'text-processing',
			'execute_callback'    => 'my_plugin_analyze_text',
			'permission_callback' => '__return_true',
		)
	);
}
