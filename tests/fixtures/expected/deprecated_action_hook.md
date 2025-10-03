> **DEPRECATED**
> This hook was deprecated in version 7.5.0.
> Use `activitypub_handled_follow` instead.


Deprecated action hook.

## Auto-generated Example

```php
add_action(
   'activitypub_followers_post_follow',
    function(
        string $actor,
        array $activity,
        int $user_id
    ) {
        // Your code here.
    },
    10,
    3
);
```

## Parameters

- *`string`* `$actor` The URL of the actor.
- *`array`* `$activity` The activity data.
- *`int`* `$user_id` The user ID.

## Files

- [deprecated_hooks.php:16](https://github.com/test/repo/blob/main/deprecated_hooks.php#L16)
```php
\do_action_deprecated( 'activitypub_followers_post_follow', array( $activity['actor'], $activity, $user_id ), '7.5.0', 'activitypub_handled_follow' )
```



[← All Hooks](Hooks)
