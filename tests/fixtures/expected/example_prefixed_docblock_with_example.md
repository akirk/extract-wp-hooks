## Example

```php
add_filter('user_data_filter', function($data, $user_id) {
    $data['last_modified'] = current_time('mysql');
    return $data;
}, 10, 2);
```