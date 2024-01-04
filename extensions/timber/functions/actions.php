<?php 

function create_timber_action($name) {
    do_action($name);
}


add_filter('timber/twig/functions', function ($functions) {
    // Your existing functions
    // ...

    // Add your custom function using an associative array
    $functions[] = [
        'name' => 'action',
        'callable' => 'create_timber_action',
    ];

    return $functions;
});