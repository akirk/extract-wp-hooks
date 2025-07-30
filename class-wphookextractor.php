<?php

class WpHookExtractor {

	private $config;

	public function __construct( $config = array() ) {
		$this->config = array_merge(
			array(
				'exclude_dirs'  => array( 'vendor' ),
				'ignore_filter' => array(),
				'ignore_regex'  => false,
				'section'       => 'file',
				'namespace'     => '',
				'example_style' => 'default',
			),
			$config
		);
	}

	public function extract_hooks_from_file( $file_path, $relative_dir = '' ) {
		$tokens = \token_get_all( file_get_contents( $file_path ) );
		$hooks = array();
		$main_dir = $relative_dir ? strtok( $relative_dir, '/' ) : basename( $file_path );

		foreach ( $tokens as $i => $token ) {
			if ( ! is_array( $token ) || ! isset( $token[1] ) ) {
				continue;
			}
			if ( ! in_array( ltrim( $token[1], '\\' ), array( 'apply_filters', 'do_action' ) ) ) {
				continue;
			}

			$comment = '';
			$hook = false;
			$l = max( 0, $i - 50 );
			$found_significant_token = false;
			for ( $j = $i; $j > $l; $j-- ) {
				if ( ! is_array( $tokens[ $j ] ) ) {
					continue;
				}

				// Check for significant tokens that would indicate the comment is not immediately before the hook.
				if ( in_array( $tokens[ $j ][0], array( T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT ), true ) ) {
					$found_significant_token = true;
					break;
				}

				if ( T_DOC_COMMENT === $tokens[ $j ][0] ) {
					// Only use the comment if we haven't found any significant tokens between it and the hook.
					if ( ! $found_significant_token ) {
						$comment = $tokens[ $j ][1];
					}
					break;
				}
			}

			for ( $j = $i + 1; $j < $i + 10; $j++ ) {
				if ( ! is_array( $tokens[ $j ] ) ) {
					continue;
				}

				if ( T_CONSTANT_ENCAPSED_STRING === $tokens[ $j ][0] ) {
					$hook = trim( $tokens[ $j ][1], '"\'' );
					break;
				}
			}

			if (
				$hook
				&& ! in_array( $hook, $this->config['ignore_filter'] )
				&& ( ! $this->config['ignore_regex'] || ! preg_match( $this->config['ignore_regex'], $hook ) )
			) {
				if ( ! isset( $hooks[ $hook ] ) ) {
					$hooks[ $hook ] = array(
						'files'   => array(),
						'section' => 'dir' === $this->config['section'] ? $main_dir : basename( $file_path ),
						'type'    => $token[1],
						'params'  => array(),
						'comment' => '',
					);
				}

				$ret = $this->extract_vars( $hooks[ $hook ]['params'], $tokens, $i );
				$file_key = $relative_dir ? $relative_dir . '/' . basename( $file_path ) . ':' . $token[2] : basename( $file_path ) . ':' . $token[2];
				$hooks[ $hook ]['files'][ $file_key ] = $ret[1];
				$hooks[ $hook ]['params'] = $ret[0];
				$hooks[ $hook ] = array_merge( $hooks[ $hook ], $this->parse_docblock( $comment, $hooks[ $hook ]['params'] ) );
			}
		}

		return $hooks;
	}

	public function extract_vars( $params, $tokens, $i ) {
		$parens = array();
		$var = 0;
		$vars = array( '' );
		$signature = $tokens[ $i ][1];
		$line = $tokens[ $i ][2];
		$search_window = 50;
		for ( $j = $i + 1; $j < $i + $search_window; $j++ ) {
			if ( ! isset( $tokens[ $j ] ) ) {
				break;
			}
			$token = $tokens[ $j ];
			if ( is_string( $token ) ) {
				$open_paren = false;
				$signature .= $token;
				switch ( $token ) {
					case '[':
					case '(':
					case '{':
						$vars[ $var ] .= $token;
						$parens[] = $token;

						break;
					case ')':
						$open_paren = '(';
						// Intentional fallthrough.
					case ']':
						if ( ! $open_paren ) {
							$open_paren = '[';
						}
						// Intentional fallthrough.
					case '}':
						if ( ! $open_paren ) {
							$open_paren = '{';
						}
						$vars[ $var ] .= $token;

						if ( end( $parens ) === $open_paren ) {
							array_pop( $parens );
						}
						if ( empty( $parens ) ) {
							$vars[ $var ] = substr( $vars[ $var ], 0, -1 );
							// all of the filter has been consumed.
							break 2;
						}
						break;
					case ',':
						if ( count( $parens ) === 1 ) {
							++$var;
							$vars[ $var ] = '';
						} else {
							$vars[ $var ] .= $token;
						}
						break;
					default:
						$vars[ $var ] .= $token;
						break;
				}
			} elseif ( is_array( $token ) ) {
				$signature .= $token[1];
				if ( T_WHITESPACE !== $token[0] ) {
					$vars[ $var ] .= $token[1];
				}
			}
		}
		if ( $j === $i + $search_window ) {
			$signature = rtrim( $signature ) . PHP_EOL . '// ...';
		}
		array_shift( $vars );
		foreach ( $vars as $k => $var ) {
			if ( isset( $params[ $k ] ) ) {
				if ( ! in_array( $var, $params[ $k ] ) ) {
					$params[ $k ][] = $var;
				}
			} else {
				$params[ $k ] = array( $var );
			}
		}
		return array( $params, $signature );
	}

	private function parse_docblock( $raw_comment, $params ) {
		if ( preg_match( '#^([ \t]*\*\s*|//\s*)?Documented (in|at) #m', $raw_comment ) ) {
			return array();
		}
		// Adapted from https://github.com/kamermans/docblock-reflection.
		$tags = array();
		$lines = array_filter( explode( PHP_EOL, trim( $raw_comment ) ) );
		$matches = null;
		$comment = '';

		switch ( count( $lines ) ) {
			case 1:
				// Handle single-line docblock.
				if ( ! preg_match( '#\\/\\*\\*([^*]*)\\*\\/#', $lines[0], $matches ) ) {
					return array(
						'comment' => trim( ltrim( $lines[0], "/ \t" ) ),
					);
				}
				$lines[0] = \substr( $lines[0], 3, -2 );
				break;

			case 0:
			case 2:
				return array();

			default:
				// Handle multi-line docblock.
				array_shift( $lines );
				array_pop( $lines );
				break;
		}
		$inside_code = false;
		$current_example = null;
		$example_content = '';
		$code_block_indent = null;

		foreach ( $lines as $line ) {
			// Check for code block markers.
			if ( false !== strpos( $line, '```' ) ) {
				$inside_code = ! $inside_code;
				if ( $inside_code ) {
					// Starting a code block - reset indentation tracking.
					$code_block_indent = null;
				} else {
					// Ending a code block - reset indentation tracking.
					$code_block_indent = null;
				}
			}

			if ( $inside_code ) {
				// For code blocks, preserve relative indentation.
				$line = preg_replace( '#^[ \t]*\*[ ]?#', '', $line );

				// Determine base indentation from first non-empty line in code block.
				if ( null === $code_block_indent && trim( $line ) !== '' ) {
					// Find the leading whitespace of this line.
					preg_match( '#^(\s*)#', $line, $matches );
					$code_block_indent = $matches[1];
				}

				// Remove the base indentation from all lines to normalize.
				if ( null !== $code_block_indent && strpos( $line, $code_block_indent ) === 0 ) {
					$line = substr( $line, strlen( $code_block_indent ) );
				}
			} else {
				// For non-code content, strip all leading whitespace as before.
				$line = preg_replace( '#^[ \t]*\*\s*#m', '', $line );
			}

			if ( preg_match( '#^Documented (in|at) #', $line ) ) {
				return array();
			}

			// Handle both @example tags and "Example:" patterns.
			if ( preg_match( '#^@example(.*)#', $line, $matches ) || preg_match( '#^Example:?(\s*)$#', $line, $matches ) ) {
				// If we were already collecting an example, save it first.
				if ( null !== $current_example ) {
					if ( ! isset( $tags['examples'] ) ) {
						$tags['examples'] = array();
					}
					$tags['examples'][] = array(
						'title'   => $current_example,
						'content' => trim( $example_content ),
					);
				}

				// Start collecting new example.
				$current_example = trim( $matches[1] );
				$example_content = '';
				continue;
			}

			// If we're currently collecting an example and hit another @ tag, finish the example.
			if ( null !== $current_example && preg_match( '#^@([^ ]+)#', $line ) ) {
				if ( ! isset( $tags['examples'] ) ) {
					$tags['examples'] = array();
				}
				$tags['examples'][] = array(
					'title'   => $current_example,
					'content' => trim( $example_content ),
				);
				$current_example = null;
				$example_content = '';
				// Continue processing this line as a regular tag.
			}

			// If we're collecting example content, add this line to it.
			if ( null !== $current_example ) {
				$example_content .= "$line\n";
				continue;
			}

			if ( preg_match( '#@(param)(.*)#', $line, $matches ) ) {
				$tag_value = \trim( $matches[2] );

				// If this tag was already parsed, make its value an array.
				if ( isset( $tags['params'] ) ) {
					$tags['params'][] = array( $tag_value );
				} else {
					$tags['params'] = array( array( $tag_value ) );
				}
				continue;
			}
			if ( preg_match( '#@([^ ]+)(.*)#', $line, $matches ) ) {
				$tag_name = $matches[1] . 's';
				$tag_value = \trim( $matches[2] );
				if ( ! $tag_value ) {
					continue;
				}
				// If this tag was already parsed, make its value an array.
				if ( isset( $tags[ $tag_name ] ) ) {
					$tags[ $tag_name ][] = array( $tag_value );
				} else {
					$tags[ $tag_name ] = $tag_value;
				}
				continue;
			}

			$comment .= "$line\n";
		}

		// Handle any remaining example at the end.
		if ( null !== $current_example ) {
			if ( ! isset( $tags['examples'] ) ) {
				$tags['examples'] = array();
			}
			$tags['examples'][] = array(
				'title'   => $current_example,
				'content' => trim( $example_content ),
			);
		}
		if ( ! isset( $tags['params'] ) ) {
			$tags['params'] = array();
		}
		foreach ( $params as $k => $param ) {
			if ( ! isset( $tags['params'][ $k ] ) ) {
				$tags['params'][ $k ] = $param;
			} elseif ( ! in_array( $tags['params'][ $k ], $param ) ) {
				$tags['params'][ $k ] = array_merge( $tags['params'][ $k ], $param );
			}
		}

		$ret = array_filter(
			array_merge(
				$tags,
				array(
					'comment' => trim( $comment ),
				)
			)
		);
		if ( empty( $ret ) ) {
			return array();
		}

		return $ret;
	}

	public function merge_file_hooks( $hooks, $file_hooks ) {
		// Merge results with existing hooks.
		foreach ( $file_hooks as $hook => $data ) {
			if ( ! isset( $hooks[ $hook ] ) ) {
				$hooks[ $hook ] = $data;
			} else {
				$hooks[ $hook ]['files'] = array_merge( $hooks[ $hook ]['files'], $data['files'] );

				// Merge parameters to show the maximum number across all files.
				$merged_params = $hooks[ $hook ]['params'];
				foreach ( $data['params'] as $index => $vars ) {
					if ( isset( $merged_params[ $index ] ) ) {
						// Merge variables for existing parameter index.
						$merged_params[ $index ] = array_merge( $merged_params[ $index ], $vars );
						$merged_params[ $index ] = array_unique( $merged_params[ $index ] );
					} else {
						// Add new parameter index.
						$merged_params[ $index ] = $vars;
					}
				}
				$hooks[ $hook ]['params'] = $merged_params;

				if ( ! empty( $data['comment'] ) ) {
					$hooks[ $hook ]['comment'] = $data['comment'];
				}
			}
		}

		return $hooks;
	}

	public function scan_directory( $base_path ) {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		$b = strlen( $base_path ) + 1;
		$hooks = array();
		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}
			$dir = substr( $file->getPath(), $b );
			$main_dir = strtok( $dir, '/' );
			if ( '.' === substr( $main_dir, 0, 1 ) || '.' === substr( $file->getFilename(), 0, 1 ) ) {
				continue;
			}
			if ( in_array( basename( $main_dir ), $this->config['exclude_dirs'], true ) ) {
				continue;
			}

			$file_hooks = $this->extract_hooks_from_file( $file->getPathname(), $dir );
			$hooks = $this->merge_file_hooks( $hooks, $file_hooks );
		}

		uksort(
			$hooks,
			function ( $a, $b ) use ( $hooks ) {
				if ( $hooks[ $a ]['section'] === $hooks[ $b ]['section'] ) {
					return $a < $b ? -1 : 1;
				}
				return $hooks[ $a ]['section'] < $hooks[ $b ]['section'] ? -1 : 1;
			}
		);

		return $hooks;
	}

	public function generate_documentation( $hooks, $docs_path, $github_blob_url ) {
		$documentation = $this->create_documentation_content( $hooks, $github_blob_url );
		$this->write_documentation( $documentation, $docs_path );
	}

	public function create_documentation_content( $hooks, $github_blob_url ) {
		$index = '';
		$section = '';
		$hook_docs = array();

		foreach ( $hooks as $hook => $data ) {
			if ( $section !== $data['section'] ) {
				$section = $data['section'];
				$index .= PHP_EOL . '## ' . $section . PHP_EOL . PHP_EOL;
			}
			$sections = array();
			$has_example = false;
			$index .= "- [`$hook`]($hook)";
			if ( ! empty( $data['comment'] ) ) {
				$index .= ' ' . strtok( $data['comment'], PHP_EOL );
				$sections['description'] = PHP_EOL . $data['comment'] . PHP_EOL . PHP_EOL;
			}

			// Handle examples (both @example tags and Example: patterns).
			if ( ! empty( $data['examples'] ) ) {
				$has_example = true;
				$example_content = '';
				foreach ( $data['examples'] as $example ) {
					$example_content .= '## Example' . PHP_EOL . PHP_EOL;
					if ( ! empty( $example['title'] ) ) {
						$example_content .= $example['title'] . PHP_EOL . PHP_EOL;
					}
					$example_content .= $example['content'] . PHP_EOL . PHP_EOL;
				}
				$sections['example'] = $example_content;
			}

			$index .= PHP_EOL;

			// Determine hook type regardless of parameters.
			if ( 'do_action' === $data['type'] ) {
				$hook_type = 'action';
				$hook_function = 'add_action';
			} else {
				$hook_type = 'filter';
				$hook_function = 'add_filter';
			}

			$count = 0;
			$signature_params = array();
			$consistent_param_count = 0;

			if ( ! empty( $data['params'] ) ) {
				$params = "## Parameters\n";

				$file_count = count( $data['files'] );
				foreach ( $data['params'] as $i => $vars ) {
					$usage_count = 0;
					foreach ( $data['files'] as $file_signature ) {
						$parts = explode( "'" . $hook . "'", $file_signature );
						if ( count( $parts ) > 1 ) {
							$param_part = $parts[1];
							$comma_count = substr_count( $param_part, ',' );
							$file_param_count = $comma_count;
							if ( $i < $file_param_count ) {
								++$usage_count;
							}
						}
					}

					if ( $usage_count === $file_count ) {
						$consistent_param_count = $i + 1;
					}
				}

				foreach ( $data['params'] as $i => $vars ) {
					$param = false;
					foreach ( $vars as $k => $var ) {
						if ( preg_match( '/^(_[ex_]|array)\(/', $var ) ) {
							continue;
						}

						if ( false !== strpos( $var, ' ' ) && false === strpos( $var, '\'' ) ) {
							$param = $var;
							$p = preg_split( '/ +/', $param, 3 );
							$vars[ $k ] = $p[1];
							continue;
						}

						if ( false !== strpos( $var, '(' ) ) {
							$var = preg_replace( '/(isset)?\([^)]*\)/', ' ', $var );
							$var = preg_replace( '/\b(\d+|true|false|array)\b/', ' ', $var );
							$var = preg_replace( '/^\w+::/', '', $var );
							$var = preg_replace( '/[^a-z0-9_>-]/i', ' ', $var );
							$var = strtolower( preg_replace( '/([a-z])\s*([A-Z])/', '$1_$2', $var ) );
							$var = preg_replace( '/^get_/', '', $var );
							$var = preg_replace( '/\s+/', '_', trim( $var ) );

							$vars[ $k ] = '$' . $var;
							continue;
						}
					}
					$type = 'unknown';

					// This was an extracted variable, so let's create a parameter definition.
					foreach ( $vars as $k => $var ) {
						if ( preg_match( '#array\(#', $var, $matches ) ) {
							$vars[ $k ] = '$array';
							if ( preg_match( '#[\'"]#', $var ) ) {
								$vars[ $k ] = '$string_list';
							}
							$type = 'array';
						} elseif ( preg_match( '#\$(?:[a-zA-Z0-9_]+)\[[\'"]([^\'"]+)[\'"]\]->([a-zA-Z0-9_]+)#', $var, $matches ) ) {
							$vars[ $k ] = '$' . $matches[1] . '_' . $matches[2];
							$vars[ $k ] = str_replace( 'post_post_', 'post_', $vars[ $k ] );
						} elseif ( preg_match( '#\$(?:[a-zA-Z0-9_]+)\[[\'"]([^\'"]+)[\'"]#', $var, $matches ) ) {
							$vars[ $k ] = '$' . $matches[1];
						} elseif ( preg_match( '#\$([a-zA-Z0-9_]+)\[\d#', $var, $matches ) ) {
							$vars[ $k ] = '$' . $matches[1];
						} elseif ( preg_match( '#([a-zA-Z0-9_]+)\(\)#', $var, $matches ) ) {
							$vars[ $k ] = '$' . str_replace( 'wp_get_', '', $matches[1] );
						} elseif ( preg_match( '#\$(?:[a-zA-Z0-9_]+)->(.+)$#', $var, $matches ) ) {
							$vars[ $k ] = '$' . $matches[1];
						} elseif ( preg_match( '#_[_exn]\(\s*([\'"][^\'"]+[\'"])#', $var, $matches ) ) {
							$type = 'string';
							$vars[ $k ] = '$' . preg_replace( '/[^a-z0-9]/', '_', strtolower( trim( $matches[1], '"\'' ) ) );
						} elseif ( strlen( $var ) - strlen( trim( $var, '"\'' ) ) === 2 ) {
							$type = 'string';
							$vars[ $k ] = '$' . preg_replace( '/[^a-z0-9]/', '_', strtolower( trim( $var, '"\'' ) ) );
						} elseif ( is_numeric( $var ) ) {
							$type = 'int';
							$vars[ $k ] = '$int';
						} elseif ( 'true' === $var || 'false' === $var ) {
							$type = 'bool';
							$vars[ $k ] = '$' . $var;
						} elseif ( 'null' === $var ) {
							$vars[ $k ] = '$ret';
						} elseif ( in_array( $var, array( '$url' ), true ) ) {
							$type = 'string';
						} elseif ( '$array' === $var ) {
							$type = 'array';
							$vars[ $k ] = '$array';
						}
					}

					if ( ! $param ) {
						$var = reset( $vars );
						$param = $type . ' ' . $var;
						$other = array_unique( array_diff( $vars, array( $param, $var, 'null' ) ) );
						if ( $other ) {
							$param .= ' Other variable names: `' . implode( '`, `', $other ) . '`';
						}
					}

					++$count;
					$p = preg_split( '/ +/', $param, 3 );
					if ( '\\' === substr( $p[0], 0, 1 ) ) {
						$p[0] = substr( $p[0], 1 );
					} elseif ( $this->config['namespace'] && ! in_array( strtok( $p[0], '|' ), array( 'int', 'string', 'bool', 'array', 'object', 'unknown' ) ) && substr( $p[0], 0, 3 ) !== 'WP_' ) {
						$p[0] = $this->config['namespace'] . '\\' . $p[0];
					}
					// Determine if this parameter should be optional (not used consistently across all files).
					$is_optional = $i >= $consistent_param_count;

					if ( 'unknown' === $p[0] ) {
						$params .= "\n- `{$p[1]}`";
						if ( $is_optional ) {
							$signature_params[] = $p[1] . ' = null';
						} else {
							$signature_params[] = $p[1];
						}
						if ( isset( $p[2] ) ) {
							$params .= ' ' . $p[2];
						}
					} else {
						$params .= "\n- *`{$p[0]}`* `{$p[1]}`";
						if ( isset( $p[2] ) ) {
							$params .= ' ' . $p[2];
						}
						if ( substr( $p[0], -5 ) === '|null' || $is_optional ) {
							if ( substr( $p[0], -5 ) === '|null' ) {
								$signature_params[] = substr( $p[0], 0, -5 ) . ' ' . $p[1] . ' = null';
							} else {
								$signature_params[] = "{$p[0]} {$p[1]} = null";
							}
						} else {
							$signature_params[] = "{$p[0]} {$p[1]}";
						}
					}
				}

				// Generate signature based on format.
				switch ( $this->config['example_style'] ) {
					case 'prefixed':
						$callback_name = 'my_' . $hook . '_callback';
						$function_signature = "function {$callback_name}(";
						if ( count( $signature_params ) === 0 ) {
							$function_signature .= ') {';
						} elseif ( count( $signature_params ) === 1 ) {
							$function_signature .= ' ' . $signature_params[0] . ' ) {';
						} else {
							$function_signature .= ' ' . implode( ', ', $signature_params ) . ' ) {';
						}
						$function_signature .= "\n    // Your code here.";
						if ( 'action' !== $hook_type && ! empty( $signature_params[0] ) ) {
							$first_param = explode( ' ', $signature_params[0] );
							$function_signature .= "\n    return " . end( $first_param ) . ';';
						}
						$function_signature .= "\n}";

						// Add hook registration on a single line below the function.
						$hook_registration = $hook_function . "( '{$hook}', '{$callback_name}'";
						if ( $consistent_param_count > 1 ) {
							$hook_registration .= ", 10, {$consistent_param_count}";
						}
						$hook_registration .= ' );';

						$signature = "{$function_signature}\n{$hook_registration}";
						break;
					default:
						$signature = $hook_function . '(' . PHP_EOL . '   \'' . $hook . '\',' . PHP_EOL . '    function(';
						if ( count( $signature_params ) === 1 ) {
							$signature .= ' ' . $signature_params[0] . ' ) {';
						} elseif ( count( $signature_params ) > 1 ) {
							$signature .= PHP_EOL . '        ';
							$signature .= implode( ',' . PHP_EOL . '        ', $signature_params ) . PHP_EOL . '    ) {';
						} else {
							$signature .= ') {';
						}
						$signature .= PHP_EOL . '        // Your code here.';
						if ( 'action' !== $hook_type && ! empty( $signature_params[0] ) ) {
							$first_param = explode( ' ', $signature_params[0] );
							$signature .= PHP_EOL . '        return ' . end( $first_param ) . ';';
						}
						$signature .= PHP_EOL . '    }';

						if ( $consistent_param_count > 1 ) {
							$signature .= ',' . PHP_EOL . '    10,' . PHP_EOL . '    ' . $consistent_param_count . PHP_EOL;
						} else {
							$signature .= PHP_EOL;
						}
						$signature .= ');';
						break;
				}
				$sections['parameters'] = $params . PHP_EOL . PHP_EOL;
			}

			// Generate example even for hooks without parameters.
			if ( ! $has_example ) {
				// Generate signature based on format.
				switch ( $this->config['example_style'] ) {
					case 'prefixed':
						$callback_name = 'my_' . $hook . '_callback';
						$function_signature = "function {$callback_name}(";
						if ( count( $signature_params ) === 0 ) {
							$function_signature .= ') {';
						} elseif ( count( $signature_params ) === 1 ) {
							$function_signature .= ' ' . $signature_params[0] . ' ) {';
						} else {
							$function_signature .= ' ' . implode( ', ', $signature_params ) . ' ) {';
						}
						$function_signature .= "\n    // Your code here.";
						if ( 'action' !== $hook_type && ! empty( $signature_params[0] ) ) {
							$first_param = explode( ' ', $signature_params[0] );
							$function_signature .= "\n    return " . end( $first_param ) . ';';
						}
						$function_signature .= "\n}";

						// Add hook registration on a single line below the function.
						$hook_registration = $hook_function . "( '{$hook}', '{$callback_name}'";
						if ( $consistent_param_count > 1 ) {
							$hook_registration .= ", 10, {$consistent_param_count}";
						}
						$hook_registration .= ' );';

						// Extract parameter information for documentation.
						$param_docs = array();
						foreach ( $signature_params as $param ) {
							$param_docs[] = $param;
						}

						// Generate the function documentation.
						$function_docs = $this->generate_function_docs(
							$hook,
							$hook_type,
							$param_docs,
							$data['comment'] ?? '',
							$data['returns'] ?? '',
							$callback_name
						);

						$signature = "{$function_docs}\n{$function_signature}\n{$hook_registration}";
						break;
					default:
						$signature = $hook_function . '(' . PHP_EOL . '   \'' . $hook . '\',' . PHP_EOL . '    function(';
						if ( count( $signature_params ) === 1 ) {
							$signature .= ' ' . $signature_params[0] . ' ) {';
						} elseif ( count( $signature_params ) > 1 ) {
							$signature .= PHP_EOL . '        ';
							$signature .= implode( ',' . PHP_EOL . '        ', $signature_params ) . PHP_EOL . '    ) {';
						} else {
							$signature .= ') {';
						}
						$signature .= PHP_EOL . '        // Your code here.';
						if ( 'action' !== $hook_type && ! empty( $signature_params[0] ) ) {
							$first_param = explode( ' ', $signature_params[0] );
							$signature .= PHP_EOL . '        return ' . end( $first_param ) . ';';
						}
						$signature .= PHP_EOL . '    }';

						if ( $consistent_param_count > 1 ) {
							$signature .= ',' . PHP_EOL . '    10,' . PHP_EOL . '    ' . $consistent_param_count . PHP_EOL;
						} else {
							$signature .= PHP_EOL;
						}
						$signature .= ');';
						break;
				}
				$sections['example'] = '## Auto-generated Example' . PHP_EOL . PHP_EOL . '```php' . PHP_EOL . $signature . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
			}

			if ( ! empty( $data['returns'] ) ) {
				$returns_content = "## Returns\n";
				$p = preg_split( '/ +/', $data['returns'], 2 );
				if ( '\\' === substr( $p[0], 0, 1 ) ) {
					$p[0] = substr( $p[0], 1 );
				} elseif ( $this->config['namespace'] && ! in_array( strtok( $p[0], '|' ), array( 'int', 'string', 'bool', 'array', 'unknown' ) ) && substr( $p[0], 0, 3 ) !== 'WP_' ) {
					$p[0] = $this->config['namespace'] . '\\' . $p[0];
				}
				if ( ! isset( $p[1] ) ) {
					$p[1] = '';
				}
				$returns_content .= "\n`{$p[0]}` {$p[1]}";
				$returns_content .= PHP_EOL . PHP_EOL;
				$sections['returns'] = $returns_content;
			}

			$files_content = "## Files\n\n";
			foreach ( $data['files'] as $file => $signature ) {
				$files_content .= "- [$file](" . $github_blob_url . str_replace( ':', '#L', $file ) . ")\n";
				$files_content .= '```php' . PHP_EOL . $signature . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
			}
			$files_content .= "\n\n[← All Hooks](Hooks)\n";
			$sections['files'] = $files_content;

			$hook_docs[ $hook ] = $sections;
		}

		return array(
			'index' => $index,
			'hooks' => $hook_docs,
		);
	}

	public function write_documentation( $documentation, $docs_path ) {
		if ( ! file_exists( $docs_path ) ) {
			mkdir( $docs_path, 0777, true );
		}

		$section_order = array( 'description', 'example', 'parameters', 'returns', 'files' );

		foreach ( $documentation['hooks'] as $hook => $sections ) {
			$doc = '';
			foreach ( $section_order as $section_key ) {
				if ( isset( $sections[ $section_key ] ) ) {
					$doc .= $sections[ $section_key ];
				}
			}
			file_put_contents( $docs_path . "/$hook.md", $doc );
		}

		file_put_contents(
			$docs_path . '/Hooks.md',
			$documentation['index']
		);
	}

	public static function sample_config() {
		return file_get_contents( __DIR__ . '/../.extract-wp-hooks.json' );
	}

	/**
	 * Generates PHPDoc documentation for a hook callback function.
	 *
	 * @param string $hook_name     The name of the hook.
	 * @param string $hook_type     The type of hook ('filter' or 'action').
	 * @param array  $params        Array of parameter information.
	 * @param string $description   The hook description.
	 * @param string $return_type   The return type for filters.
	 * @param string $callback_name The name of the callback function.
	 * @return string               The generated function documentation.
	 */
	public function generate_function_docs( $hook_name, $hook_type, $params = array(), $description = '', $return_type = '', $callback_name = '' ) {
		if ( empty( $callback_name ) ) {
			$callback_name = 'my_' . $hook_name . '_callback';
		}

		$doc = "/**\n";

		// Add function description.
		if ( ! empty( $description ) ) {
			// Format the description for PHPDoc.
			$description = trim( $description );
			$lines = explode( "\n", $description );
			foreach ( $lines as $index => $line ) {
				$line = trim( $line );

				// Add period to the last line if it doesn't have one.
				if ( count( $lines ) - 1 === $index && ! empty( $line ) && ! in_array( substr( $line, -1 ), array( '.', '!', '?' ) ) ) {
					$line .= '.';
				}

				$doc .= ' * ' . $line . "\n";
			}
		} else {
			$doc .= " * Callback function for the '{$hook_name}' " . $hook_type . ".\n";
		}

		// Only add the empty line separator if we have parameters or a return type.
		if ( ! empty( $params ) || ( 'filter' === $hook_type && ! empty( $return_type ) ) ) {
			$doc .= " *\n";
		}

		// Add parameters.
		if ( ! empty( $params ) ) {
			// First, find the longest type to align parameters properly.
			$max_type_length = 0;
			foreach ( $params as $param_info ) {
				$parts = explode( ' ', $param_info, 2 );
				if ( count( $parts ) === 2 ) {
					$max_type_length = max( $max_type_length, strlen( $parts[0] ) );
				}
			}

			foreach ( $params as $i => $param_info ) {
				// Parse parameter information.
				$parts = explode( ' ', $param_info, 2 );

				if ( count( $parts ) === 2 ) {
					// We have both type and variable name.
					$param_type = $parts[0];
					$param_var = $parts[1];

					// Extract just the variable name without default value.
					if ( strpos( $param_var, '=' ) !== false ) {
						$param_var = trim( explode( '=', $param_var )[0] );
					}

					// Remove $ if present.
					$param_name = ltrim( $param_var, '$' );

					// Get parameter description if available.
					$param_desc = '';
					if ( isset( $this->hooks[ $hook_name ]['param_descriptions'][ $i ] ) ) {
						$param_desc = $this->hooks[ $hook_name ]['param_descriptions'][ $i ];
					}

					// Pad the type to align variables.
					$padded_type = str_pad( $param_type, $max_type_length, ' ', STR_PAD_RIGHT );
					$doc .= " * @param {$padded_type} \${$param_name} {$param_desc}\n";
				} else {
					// Just a variable name without type.
					$param_var = $parts[0];
					$param_name = ltrim( $param_var, '$' );

					// Get parameter description if available.
					$param_desc = '';
					if ( isset( $this->hooks[ $hook_name ]['param_descriptions'][ $i ] ) ) {
						$param_desc = $this->hooks[ $hook_name ]['param_descriptions'][ $i ];
					}

					$doc .= " * @param mixed \${$param_name} {$param_desc}\n";
				}
			}
		}

		// Add return type for filters.
		if ( 'filter' === $hook_type ) {
			if ( ! empty( $return_type ) ) {
				$return_parts = preg_split( '/ +/', $return_type, 2 );
				$return_type = $return_parts[0];
				$return_desc = $return_parts[1] ?? '';
				$doc .= " * @return {$return_type} {$return_desc}\n";
			} elseif ( ! empty( $params ) ) {
				// If we have parameters, assume we return the first parameter's type.
				$first_param = explode( ' ', $params[0], 2 );
				if ( count( $first_param ) === 2 ) {
					$doc .= " * @return {$first_param[0]} The filtered value.\n";
				} else {
					$doc .= " * @return mixed The filtered value.\n";
				}
			} else {
				$doc .= " * @return mixed The filtered value.\n";
			}
		}

		$doc .= ' */';

		return $doc;
	}
}
