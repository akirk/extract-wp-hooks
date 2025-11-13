> **DEPRECATED**
> This hook was deprecated in version 7.1.0.
> Please migrate your Followings to the new internal Following structure.


Deprecated filter hook.

## Auto-generated Example

```php
add_filter(
   'activitypub_rest_following',
    function(
        array $items,
        object $user,
        string $please_migrate_your_followings_to_the_new_internal_following_structure_
    ) {
        // Your code here.
        return $items;
    },
    10,
    3
);
```

## Parameters

- *`array`* `$items` The array of following urls.
- *`object`* `$user` The user object.
- *`string`* `$please_migrate_your_followings_to_the_new_internal_following_structure_`

## Files

- [deprecated_hooks.php:26](https://github.com/test/repo/blob/main/deprecated_hooks.php#L26)
```php
\apply_filters_deprecated( 'activitypub_rest_following', array( array(), $user ), '7.1.0', 'Please migrate your Followings to the new internal Following structure.' )
```



[← All Hooks](Hooks)
