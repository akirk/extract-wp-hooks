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
				'example_format' => 'default', // or prefixed
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
			$l = max( 0, $i - 10 );
			for ( $j = $i; $j > $l; $j-- ) {
				if ( ! is_array( $tokens[ $j ] ) ) {
					continue;
				}

				if ( T_DOC_COMMENT === $tokens[ $j ][0] ) {
					$comment = $tokens[ $j ][1];
					break;
				}

				if ( T_COMMENT === $tokens[ $j ][0] ) {
					$comment = $tokens[ $j ][1];
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

	public function parse_docblock( $raw_comment, $params ) {
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

			// Merge results with existing hooks.
			foreach ( $file_hooks as $hook => $data ) {
				if ( ! isset( $hooks[ $hook ] ) ) {
					$hooks[ $hook ] = $data;
				} else {
					$hooks[ $hook ]['files'] = array_merge( $hooks[ $hook ]['files'], $data['files'] );
					$hooks[ $hook ]['params'] = array_merge_recursive( $hooks[ $hook ]['params'], $data['params'] );
					if ( ! empty( $data['comment'] ) ) {
						$hooks[ $hook ]['comment'] = $data['comment'];
					}
				}
			}
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
			$doc = '';
			$has_example = false;
			$index .= "- [`$hook`]($hook)";
			if ( ! empty( $data['comment'] ) ) {
				$index .= ' ' . strtok( $data['comment'], PHP_EOL );
				$doc .= PHP_EOL . $data['comment'] . PHP_EOL . PHP_EOL;
			}

			// Handle examples (both @example tags and Example: patterns).
			if ( ! empty( $data['examples'] ) ) {
				$has_example = true;
				foreach ( $data['examples'] as $example ) {
					$doc .= '## Example' . PHP_EOL . PHP_EOL;
					if ( ! empty( $example['title'] ) ) {
						$doc .= $example['title'] . PHP_EOL . PHP_EOL;
					}
					$doc .= $example['content'] . PHP_EOL . PHP_EOL;
				}
			}

			$index .= PHP_EOL;

			if ( ! empty( $data['params'] ) ) {
				if ( 'do_action' === $data['type'] ) {
					$hook_type = 'action';
					$hook_function = 'add_action';
				} else {
					$hook_type = 'filter';
					$hook_function = 'add_filter';
				}

				$params = "## Parameters\n";
				$first = false;
				$count = 0;
				$signature_params = array();
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
					if ( ! $first ) {
						$first = $p[1];
					}
					if ( 'unknown' === $p[0] ) {
						$params .= "\n- `{$p[1]}`";
						$signature_params[] = $p[1];
						if ( isset( $p[2] ) ) {
							$params .= ' ' . $p[2];
						}
					} else {
						$params .= "\n- *`{$p[0]}`* `{$p[1]}`";
						if ( isset( $p[2] ) ) {
							$params .= ' ' . $p[2];
						}
						if ( substr( $p[0], -5 ) === '|null' ) { // Remove this if, if you don't want to support PHP 7.4 or below.
							$signature_params[] = substr( $p[0], 0, -5 ) . ' ' . $p[1] . ' = null';
						} else {
							$signature_params[] = "{$p[0]} {$p[1]}";
						}
					}
				}

				// Generate signature based on format.
				switch ( $this->config['example_format'] ) {
					case 'prefixed':
						$signature = "function prefixed_{$hook_type}_callback( ";
						$signature .= implode( ', ', $signature_params ) . ' ) {';
						$signature .= PHP_EOL . '    // Your code here.';
						if ( 'action' !== $hook_type ) {
							$signature .= PHP_EOL . '    return ' . $first . ';';
						}
						$signature .= PHP_EOL . '}';
						$signature .= PHP_EOL . $hook_function . "( '{$hook}', 'prefixed_{$hook_type}_callback'";
						if ( $count > 1 ) {
							$signature .= ', 10, ' . $count;
						}
						$signature .= ' );';
						break;
					default:
						$signature = $hook_function . "( '{$hook}', function( ";
						$signature .= implode( ', ', $signature_params ) . ' ) {';
						$signature .= PHP_EOL . '    // Your code here.';
						if ( 'action' !== $hook_type ) {
							$signature .= PHP_EOL . '    return ' . $first . ';';
						}
						$signature .= PHP_EOL . '}';

						if ( $count > 1 ) {
							$signature .= ', 10, ' . $count;
						}
						$signature .= ' );';
						break;
				}
				if ( ! $has_example ) {
					$doc .= '## Auto-generated Example' . PHP_EOL . PHP_EOL . '```php' . PHP_EOL . $signature . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
				}
				$doc .= $params . PHP_EOL . PHP_EOL;
			}

			if ( ! empty( $data['returns'] ) ) {
				$doc .= "## Returns\n";
				$p = preg_split( '/ +/', $data['returns'], 2 );
				if ( '\\' === substr( $p[0], 0, 1 ) ) {
					$p[0] = substr( $p[0], 1 );
				} elseif ( $this->config['namespace'] && ! in_array( strtok( $p[0], '|' ), array( 'int', 'string', 'bool', 'array', 'unknown' ) ) && substr( $p[0], 0, 3 ) !== 'WP_' ) {
					$p[0] = $this->config['namespace'] . '\\' . $p[0];
				}
				if ( ! isset( $p[1] ) ) {
					$p[1] = '';
				}
				$doc .= "\n`{$p[0]}` {$p[1]}";

				$doc .= PHP_EOL . PHP_EOL;
			}

			$doc .= "## Files\n\n";
			foreach ( $data['files'] as $file => $signature ) {
				$doc .= "- [$file](" . $github_blob_url . str_replace( ':', '#L', $file ) . ")\n";
				$doc .= '```php' . PHP_EOL . $signature . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
			}
			$doc .= "\n\n[← All Hooks](Hooks)\n";

			$hook_docs[ $hook ] = $doc;
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

		foreach ( $documentation['hooks'] as $hook => $content ) {
			file_put_contents(
				$docs_path . "/$hook.md",
				$content
			);
		}

		file_put_contents(
			$docs_path . '/Hooks.md',
			$documentation['index']
		);
	}

	public static function sample_config() {
		return file_get_contents( __DIR__ . '/../.extract-wp-hooks.json' );
	}
}
