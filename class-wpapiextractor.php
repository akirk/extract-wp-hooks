<?php
/**
 * WordPress API Extractor
 *
 * @package WpHookExtractor
 */

require_once __DIR__ . '/class-wphookextractor.php';
require_once __DIR__ . '/class-wpabilityextractor.php';

/**
 * Extracts WordPress extension APIs from PHP files and generates documentation.
 */
class WpApiExtractor {

	/**
	 * Hook extractor.
	 *
	 * @var WpHookExtractor
	 */
	private $hook_extractor;

	/**
	 * Ability extractor.
	 *
	 * @var WpAbilityExtractor
	 */
	private $ability_extractor;

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 */
	public function __construct( $config = array() ) {
		$this->hook_extractor    = new WpHookExtractor( $config );
		$this->ability_extractor = new WpAbilityExtractor( $config );
	}

	/**
	 * Extracts APIs from a PHP file.
	 *
	 * @param string $file_path    Path to the PHP file.
	 * @param string $relative_dir Relative directory path.
	 * @return array Extracted APIs.
	 */
	public function extract_hooks_from_file( $file_path, $relative_dir = '' ) {
		return $this->merge_file_hooks(
			$this->hook_extractor->extract_hooks_from_file( $file_path, $relative_dir ),
			$this->ability_extractor->extract_hooks_from_file( $file_path, $relative_dir )
		);
	}

	/**
	 * Merges APIs from a file into the main API array.
	 *
	 * @param array $hooks      Existing APIs.
	 * @param array $file_hooks APIs from the current file.
	 * @return array Merged APIs.
	 */
	public function merge_file_hooks( $hooks, $file_hooks ) {
		return $this->hook_extractor->merge_file_hooks( $hooks, $file_hooks );
	}

	/**
	 * Returns warnings collected during extraction.
	 *
	 * @return array List of warning strings.
	 */
	public function get_warnings() {
		return array_merge(
			$this->hook_extractor->get_warnings(),
			$this->ability_extractor->get_warnings()
		);
	}

	/**
	 * Scans a directory recursively for PHP files and extracts APIs.
	 *
	 * @param string $base_path Base directory path to scan.
	 * @return array All extracted APIs.
	 */
	public function scan_directory( $base_path ) {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		$b     = strlen( $base_path ) + 1;
		$hooks = array();
		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}
			$dir      = substr( $file->getPath(), $b );
			$main_dir = strtok( $dir, '/' );
			if ( '.' === substr( $main_dir, 0, 1 ) || '.' === substr( $file->getFilename(), 0, 1 ) ) {
				continue;
			}
			if ( in_array( basename( $main_dir ), $this->hook_extractor->get_config_value( 'exclude_dirs' ), true ) ) {
				continue;
			}

			$file_hooks = $this->extract_hooks_from_file( $file->getPathname(), $dir );
			$hooks      = $this->merge_file_hooks( $hooks, $file_hooks );
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

	/**
	 * Generates documentation files from extracted APIs.
	 *
	 * @param array  $hooks           Extracted APIs.
	 * @param string $docs_path       Path to write documentation.
	 * @param string $github_blob_url Base URL for GitHub blob links.
	 */
	public function generate_documentation( $hooks, $docs_path, $github_blob_url ) {
		$this->hook_extractor->generate_documentation( $hooks, $docs_path, $github_blob_url );
	}

	/**
	 * Creates documentation content from extracted APIs.
	 *
	 * @param array  $hooks           Extracted APIs.
	 * @param string $github_blob_url Base URL for GitHub blob links.
	 * @return array Documentation content with index and API pages.
	 */
	public function create_documentation_content( $hooks, $github_blob_url ) {
		return $this->hook_extractor->create_documentation_content( $hooks, $github_blob_url );
	}

	/**
	 * Writes documentation files to disk.
	 *
	 * @param array  $documentation Documentation content.
	 * @param string $docs_path     Path to write documentation.
	 */
	public function write_documentation( $documentation, $docs_path ) {
		$this->hook_extractor->write_documentation( $documentation, $docs_path );
	}

	/**
	 * Returns a sample configuration file.
	 *
	 * @return string Sample configuration JSON.
	 */
	public static function sample_config() {
		return WpHookExtractor::sample_config();
	}
}
