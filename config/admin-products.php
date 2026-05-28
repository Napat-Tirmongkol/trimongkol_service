<?php

/**
 * Registry of admin-managed products. Adding a new product means
 * registering it here plus building the controller + views under
 * the matching namespace — the admin nav picks it up automatically.
 */

return [
    'scanner' => [
        'label_key' => 'app.admin.products.scanner.label',
        'desc_key' => 'app.admin.products.scanner.desc',
        'route' => 'admin.scanner.dashboard',
        'pattern' => 'admin.scanner.*',
        'tabs' => [
            ['route' => 'admin.scanner.dashboard', 'label_key' => 'app.admin.products.scanner.tab_overview', 'pattern' => 'admin.scanner.dashboard'],
            ['route' => 'admin.scanner.classrooms', 'label_key' => 'app.admin.products.scanner.tab_classrooms', 'pattern' => 'admin.scanner.classrooms*'],
        ],
    ],
];
