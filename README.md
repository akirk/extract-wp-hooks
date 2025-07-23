# extract-hooks

This script is intended for WordPress plugins that provide hooks that can be used by other plugins. By parsing its source code, it creates a documentation in a Github wiki.

You can configure this tool either through a JSON configuration file or directly through GitHub Action inputs. For local usage, you'll need a configuration file, but for GitHub Actions, you can specify all configuration directly in your workflow file.

## Examples
- [https://github.com/akirk/extract-hooks/wiki/Hooks](https://github.com/akirk/extract-hooks/wiki/Hooks) (extracted from [example.php](https://github.com/akirk/extract-hooks/blob/main/example.php))
- [https://github.com/akirk/friends/wiki/Hooks](https://github.com/akirk/friends/wiki/Hooks)
- [https://github.com/akirk/enable-mastodon-apps/wiki/Hooks](https://github.com/akirk/enable-mastodon-apps/wiki/Hooks)

## How it works

The PHP script doesn't have any dependencies. It uses PHP's internal parser (using [`token_get_all`](https://www.php.net/manual/en/function.token-get-all.php)) to identify PHP function calls to `apply_filters()` or `do_action()`.

It generates a markdown file for each filter which is suitable for a Github wiki. The page contains potentially provided documentation (via a comment in the source code), an (auto-generated) example, parameters, return value, references to the source code (including extracted source snippet).

### Example: Provide Documentation Via a Comment
For each filter, it looks at the comment preceeding the filter, so that you can document it, for example:

```php
/*
 * This is example filter 1.
 *
 * @param string $text The text to modify.
 * @param string $mode Extra information that might be useful.
 * @return string The modified text.
 */
$result = apply_filters( 'example_filter1', $text, $mode );
```

This will generate an [example_filter1.md](https://github.com/akirk/extract-hooks/wiki/example_filter1) that contains the text `This is an example filter` and a list of parameters and return value:

> # example_filter1
>
> This is an example filter.
>
> ## Parameters
> - `string` `$text` The text to modify.
> - `string` `$mode` Extra information that might be useful.
>
> ## Returns
> `string` The modified text.

But not only that, it will contain an auto-generated example:

> ## Auto-generated Example
> ```php
> add_filter(
>     'example_filter1',
>     function(
>         string $text,
>         string $mode
>     ) {
>         // Your code
>         return $text;
>     },
>     10,
>     2
> );
> ```

### Provide an Example
You can also provide your own example in the comment, that will override the auto-generated example:

```php
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
 * @return string The modified text.
 */
$result = apply_filters( 'example_filter2', $text, $mode );
```

It generates this output: [example_filter2](https://github.com/akirk/extract-hooks/wiki/example_filter2)
> ## Example
> ```php
> add_filter( 'example_filter2', function ( $text ) {
>     return strtolower( $text );
> } );
> ```

### No Documentation

Finally, if you have an filter without any documentation, the script attempts to create a useful auto-generated example. So suppose you have code

```
$result = apply_filters( 'example_filter3', $text, $mode );
```

It generates this output: [example_filter3](https://github.com/akirk/extract-hooks/wiki/example_filter3)
> ## Auto-generated Example
>
> ```php
> add_filter(
>     'example_filter3',
>     function (
>         $text,
>         $mode
>     ) {
>         // Your code here
>         return $text;
>     },
>     10,
>     2
> );
> ```
>
> ## Parameters
>
> - `$text`
> - `$mode`

## Install

### Option 1: GitHub Action (Recommended)

Add the following workflow file to your WordPress plugin repository at `.github/workflows/extract-hooks.yml`:

```yaml
name: Extract WordPress Hooks

on:
  push:
    branches: [ main, master ]
    paths: [ '**.php' ]
  workflow_dispatch:

jobs:
  extract-hooks:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v4
    - uses: akirk/extract-wp-hooks@main
```

This will automatically extract hooks from your PHP files and update your GitHub wiki whenever you push changes.

#### Configuration

You can configure the action in two ways:

1. **Using action inputs (recommended):**

```yaml
- uses: akirk/extract-wp-hooks@main
  with:
    namespace: "My_Plugin"
    base-dir: "."
    wiki-directory: "wiki"
    exclude-dirs: "vendor,tests,node_modules"
    ignore-filters: "debug_hook,internal_filter"
    section: "file"
```

2. **Using a configuration file:**

Create an `.extract-wp-hooks.json` file in your repository root:

```json
{
    "namespace": "My_Plugin", // PHP Namespace used in the project.
    "base_dir": ".", // The directory to analyse for php files.
    "wiki_directory": "wiki", // Where the files will be written to.
    "github_blob_url": "https://github.com/username/my-plugin/blob/main/", // This is the base url for the links to the source files.
    "exclude_dirs": ["vendor", "tests"],
    "ignore_filter": ["debug_hook", "internal_filter"]
}
```

#### Available Action Inputs

| Input | Description | Default |
|-------|-------------|---------|
| `namespace` | PHP Namespace that's used | |
| `base-dir` | Base directory to scan for hooks | `.` |
| `wiki-directory` | Directory to store wiki files | `wiki` |
| `github-blob-url` | GitHub blob URL for source links | Auto-generated from repository |
| `exclude-dirs` | Comma-separated list of directories to exclude | `vendor,node_modules` |
| `ignore-filters` | Comma-separated list of filter names to ignore | |
| `ignore-regex` | Regex pattern to ignore filter names | |
| `section` | How to group hooks in documentation: 'file' or 'dir' | `file` |
| `wiki-repo` | Wiki repository URL (e.g., username/repo.wiki.git) | Auto-generated from repository |
| `config-file` | Path to config file (optional if other inputs are provided) | `.extract-wp-hooks.json` |

### Option 2: Composer

Via composer:
```
composer require --dev akirk/extract-wp-hooks
```

You will then be able to run `extract-wp-hooks.php` from the vendor bin directory:

```
./vendor/bin/extract-wp-hooks.php
```

Place a `.extract-wp-hooks.json` or `extract-wp-hooks.json` in your project directory to use it.

Alternatively, you can use environment variables to configure the script:

```bash
EXTRACT_WP_HOOKS_NAMESPACE="My_Plugin" \
EXTRACT_WP_HOOKS_WIKI_DIRECTORY="wiki" \
EXTRACT_WP_HOOKS_GITHUB_BLOB_URL="https://github.com/username/my-plugin/blob/main/" \
EXTRACT_WP_HOOKS_EXCLUDE_DIRS="vendor,tests" \
EXTRACT_WP_HOOKS_IGNORE_FILTER="debug_hook,internal_filter" \
./vendor/bin/extract-wp-hooks.php
