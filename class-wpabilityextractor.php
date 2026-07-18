<?php
/**
 * WordPress Ability Extractor
 *
 * @package WpHookExtractor
 */

require_once __DIR__ . '/class-wphookextractor.php';

/**
 * Extracts WordPress abilities from PHP files.
 */
class WpAbilityExtractor extends WpHookExtractor {

	/**
	 * Extracts abilities from a PHP file.
	 *
	 * @param string $file_path    Path to the PHP file.
	 * @param string $relative_dir Relative directory path.
	 * @return array Extracted abilities.
	 */
	public function extract_hooks_from_file( $file_path, $relative_dir = '' ) {
		$tokens    = \token_get_all( file_get_contents( $file_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$abilities = array();
		$main_dir  = $relative_dir ? strtok( $relative_dir, '/' ) : basename( $file_path );

		foreach ( $tokens as $i => $token ) {
			if ( ! is_array( $token ) || ! isset( $token[1] ) ) {
				continue;
			}

			$function_name = ltrim( $token[1], '\\' );
			if ( 'wp_register_ability' !== $function_name ) {
				continue;
			}

			$comment = $this->find_preceding_docblock( $tokens, $i );
			$ability = $this->extract_ability_from_call( $tokens, $i );

			if ( empty( $ability['name'] ) ) {
				continue;
			}

			$name = $ability['name'];
			if (
				in_array( $name, $this->config['ignore_filter'], true )
				|| ( $this->config['ignore_regex'] && preg_match( $this->config['ignore_regex'], $name ) )
			) {
				continue;
			}
			if ( empty( $ability['namespace'] ) ) {
				$this->warnings[] = sprintf(
					'%s:%d: Ability %s is missing a namespace. Ability names should use namespace/name.',
					$file_path,
					$token[2],
					$name
				);
			}

			if ( ! isset( $abilities[ $name ] ) ) {
				$section = 'dir' === $this->config['section'] ? $main_dir : basename( $file_path );
				if ( ! empty( $ability['namespace'] ) ) {
					$section = 'Abilities: ' . $ability['namespace'];
				}
				$abilities[ $name ] = array(
					'files'             => array(),
					'section'           => $section,
					'type'              => 'wp_register_ability',
					'params'            => array(),
					'comment'           => '',
					'ability_namespace' => $ability['namespace'],
					'ability_slug'      => $ability['slug'],
					'ability'           => $ability['args'],
					'ability_raw'       => $ability['raw_args'],
				);
			}

			$file_key                                = $relative_dir ? $relative_dir . '/' . basename( $file_path ) . ':' . $token[2] : basename( $file_path ) . ':' . $token[2];
			$abilities[ $name ]['files'][ $file_key ] = $ability['signature'];
			$abilities[ $name ]                       = array_merge( $abilities[ $name ], $this->parse_docblock( $comment, array() ) );

			if ( empty( $abilities[ $name ]['comment'] ) && ! empty( $ability['args']['description'] ) ) {
				$abilities[ $name ]['comment'] = $ability['args']['description'];
			}
		}

		return $abilities;
	}

	/**
	 * Extracts a wp_register_ability() call.
	 *
	 * @param array $tokens All tokens from the file.
	 * @param int   $i      Current token index.
	 * @return array Ability details.
	 */
	private function extract_ability_from_call( $tokens, $i ) {
		$call = $this->extract_call_arguments( $tokens, $i );
		$name = isset( $call['args'][0] ) ? trim( $call['args'][0], '"\'' ) : '';
		$args = isset( $call['args'][1] ) ? $this->parse_static_array_expression( $call['args'][1] ) : array(
			'values' => array(),
			'raw'    => array(),
		);

		$namespace = '';
		$slug      = $name;
		if ( false !== strpos( $name, '/' ) ) {
			list( $namespace, $slug ) = explode( '/', $name, 2 );
		}

		return array(
			'name'      => $name,
			'namespace' => $namespace,
			'slug'      => $slug,
			'args'      => $args['values'],
			'raw_args'  => $args['raw'],
			'signature' => $call['signature'],
		);
	}

	/**
	 * Finds the PHPDoc comment immediately preceding a token.
	 *
	 * @param array $tokens All tokens from the file.
	 * @param int   $i      Current token index.
	 * @return string PHPDoc comment.
	 */
	private function find_preceding_docblock( $tokens, $i ) {
		$l                       = max( 0, $i - 50 );
		$found_significant_token = false;

		for ( $j = $i; $j > $l; $j-- ) {
			if ( ! is_array( $tokens[ $j ] ) ) {
				continue;
			}

			if ( in_array( $tokens[ $j ][0], array( T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT ), true ) ) {
				$found_significant_token = true;
				break;
			}

			if ( T_DOC_COMMENT === $tokens[ $j ][0] ) {
				return $found_significant_token ? '' : $tokens[ $j ][1];
			}
		}

		return '';
	}
}
