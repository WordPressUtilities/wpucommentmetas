# WPU Comment metas

Add new fields to your WordPress comments

## How to

Add this code sample to a mu-plugin or your theme's functions.php

```php
add_filter('wpucommentmetas_fields', 'wpucommentmetasexample_wpucommentmetas_fields', 10, 1);
function wpucommentmetasexample_wpucommentmetas_fields($fields) {

    /*
     * Basic field :
     * Label will be taken from the field id,
     * Will be displayed at the end of the form
     **/
    $fields['myfield'] = array();

    /*
     * Advanced field :
     **/
    $fields['advanced'] = array(
        /* Custom label */
        'label' => 'My advanced field',
        /* Destination hooks */
        'display_hooks' => array(
            /* After fields, logged */
            'comment_form_after_fields',
            'comment_form_logged_in_after'
        )
    );
    return $fields;
}
```


## Roadmap

* [ ] Visibility in admin for each field ( list OR meta box ).
* [ ] Hook for visibility in admin.
* [ ] Visibility in front for each field.
* [ ] Hook for visibility in front.
* [ ] Public or private field.
* [ ] Comment for a specific post type.
* [ ] Mobile view in admin.
* [ ] Required fields (check in backend).

