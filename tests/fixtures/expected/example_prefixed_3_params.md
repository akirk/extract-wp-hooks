## Auto-generated Example

```php
/**
 * Callback function for the 'multi_param_hook' filter.
 *
 * @param mixed $param1 
 * @param mixed $param2 
 * @param mixed $extra_data 
 * @return mixed The filtered value.
 */
function my_multi_param_hook_callback( $param1, $param2, $extra_data ) {
    // Your code here.
    return $param1;
}
add_filter( 'multi_param_hook', 'my_multi_param_hook_callback', 10, 3 );
```

