## Auto-generated Example

```php
add_filter(
   'complex_hook',
    'my_complex_hook_callback',
    10,
    3
);

function my_complex_hook_callback(
    string $setting,
    int $user_id,
    array $options
) {
    // Your code here.
    return $setting;
}
```

