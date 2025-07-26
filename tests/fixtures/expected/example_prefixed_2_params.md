## Auto-generated Example

```php
add_filter(
   'two_param_hook',
    'my_two_param_hook_callback',
    10,
    2
);

function my_two_param_hook_callback(
    $first,
    $second
) {
    // Your code here.
    return $first;
}
```

