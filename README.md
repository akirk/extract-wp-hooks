# extract-hooks

This script is intended for WordPress plugins that provide hooks that can be used by other plugins. By parsing its source code, it creates a documentation in a Github wiki.

Typically, you'd first create a [`extract-hooks.ini`](https://github.com/akirk/extract-hooks/blob/main/extract-hooks.ini), and check out the Github wiki in a folder above the repo. Modify the `extract-hooks.ini` accordingly and execute `extract-hooks.php`. This will create markdown files in the wiki folder. You can then `git commit` and `git push` the changes.

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

> ## example_filter1
>
> This is an example filter.
>
> ### Parameters
> - `string` `$text` The text to modify.
> - `string` `$mode` Extra information that might be useful.
>
> ### Returns
> `string` The modified text.

But not only that, it will contain an auto-generated example:

> ### Auto-generated Example
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
> ### Example
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
> ### Auto-generated Example
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

