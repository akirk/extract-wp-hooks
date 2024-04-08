# extract-hooks

This script is intended for WordPress plugins that themselves provide hooks that can be used by other plugins and create a documentation in a Github wiki for them.

Typically, you'd first create a [`extract-hooks.ini`](https://github.com/akirk/extract-hooks/blob/main/extract-hooks.ini), and check out the Github wiki in a folder above the repo. Modify the `extract-hooks.ini` accordingly and execute extract-hooks.php. This will create markdown files in the wiki folder. You can then `git commit` and `git push` the changes.

## Examples
- [https://github.com/akirk/friends/wiki/Hooks](https://github.com/akirk/friends/wiki/Hooks)
- [https://github.com/akirk/enable-mastodon-apps/wiki/Hooks](https://github.com/akirk/enable-mastodon-apps/wiki/Hooks)

## How it works

The PHP script doesn't have any dependencies. It uses PHP's internal parser (using []`token_get_all`](https://www.php.net/manual/en/function.token-get-all.php)) to identify PHP function calls to `apply_filters()` or `do_action()`.

For each filter, it looks at the comment preceeding the filter, so that you can document it, for example:

```php
/*
 * This is an example filter.
 *
 * @param string $text The text to modify.
 * @param string $mode Extra information that might be useful.
 * @return Return the modified text.
 */
$result = apply_filters( 'example_filter', $text, $mode );
```

This will generate an `example_filter.md` that contains the text `This is an example filter` and a list of parameters and return value:

> ### example_filter
>
> This is an example filter.
>
> #### Parameters
> - `string` `$text` The text to modify.
> - `string` `$mode` Extra information that might be useful.
>
> #### Returns
> `Return the modified text`

But not only that, it will contain an auto-generated example:

```php
add_filter(
    'example_filter',
    function(
        string $text,
        string $mode
    ) {
        // Your code
        return $text;
    },
    10,
    2
);
```
You can also provide your own example in the comment, that will override the auto-generated example:

```php
/*
 * This is an example filter.
 *
 * Example:
 * ```php
 * add_filter( 'example_filter', function ( $text ) {
 *     return strtolower( $text );
 * } );
 * ```
 *
 * @param string $text The text to modify.
 * @param string $mode Extra information that might be useful.
 * @return Return the modified text.
 */
$result = apply_filters( 'example_filter', $text, $mode );
```
