## Auto-generated Example

```php
function my_multi_param_hook_callback( $param1, $param2, $extra_data ) {
    // Your code here.
    return $param1;
}
add_filter( 'multi_param_hook', 'my_multi_param_hook_callback', 10, 3 );
```

