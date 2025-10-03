> **DEPRECATED**
> This hook was deprecated in version 8.0.0.


Deprecated hook without replacement.

## Auto-generated Example

```php
add_action(
   'old_legacy_hook',
    function(
        string $data,
        string $8_0_0
    ) {
        // Your code here.
    },
    10,
    2
);
```

## Parameters

- *`string`* `$data` Some data.
- *`string`* `$8_0_0`

## Files

- [deprecated_hooks.php:35](https://github.com/test/repo/blob/main/deprecated_hooks.php#L35)
```php
\do_action_deprecated( 'old_legacy_hook', array( $data ), '8.0.0' )
```



[← All Hooks](Hooks)
