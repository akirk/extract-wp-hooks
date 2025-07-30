## Auto-generated Example

```php
/**
 * Callback function for the 'two_param_hook' filter.
 *
 * @param mixed $first 
 * @param mixed $second 
 * @return mixed The filtered value.
 */
function my_two_param_hook_callback( $first, $second ) {
    // Your code here.
    return $first;
}
add_filter( 'two_param_hook', 'my_two_param_hook_callback', 10, 2 );
```

