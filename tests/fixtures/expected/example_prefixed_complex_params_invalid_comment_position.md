## Auto-generated Example

```php
add_filter(
   'complex_hook_invalid_comment',
    'my_complex_hook_invalid_comment_callback',
    10,
    3
);

function my_complex_hook_invalid_comment_callback(
    $setting,
    $ID,
    array $string_list
) {
    // Your code here.
    return $setting;
}
```

