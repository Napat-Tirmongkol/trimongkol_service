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

    'queue' => [
        'label_key' => 'app.admin.products.queue.label',
        'desc_key' => 'app.admin.products.queue.desc',
        'route' => 'admin.queue.dashboard',
        'pattern' => 'admin.queue.*',
        'tabs' => [
            ['route' => 'admin.queue.dashboard', 'label_key' => 'app.admin.products.queue.tab_overview', 'pattern' => 'admin.queue.dashboard'],
            ['route' => 'admin.queue.index', 'label_key' => 'app.admin.products.queue.tab_queues', 'pattern' => 'admin.queue.index*'],
            ['route' => 'admin.queue.payments', 'label_key' => 'app.admin.products.queue.tab_payments', 'pattern' => 'admin.queue.payments*'],
        ],
    ],

    'accounting' => [
        'label_key' => 'app.admin.products.accounting.label',
        'desc_key' => 'app.admin.products.accounting.desc',
        'route' => 'admin.accounting.dashboard',
        'pattern' => 'admin.accounting.*',
        'tabs' => [
            ['route' => 'admin.accounting.dashboard', 'label_key' => 'app.admin.products.accounting.tab_overview', 'pattern' => 'admin.accounting.dashboard'],
        ],
    ],

    'social' => [
        'label_key' => 'app.admin.products.social.label',
        'desc_key'  => 'app.admin.products.social.desc',
        'route'     => 'admin.social.dashboard',
        'pattern'   => 'admin.social.*',
        'tabs' => [
            ['route' => 'admin.social.dashboard', 'label_key' => 'app.admin.products.social.tab_overview', 'pattern' => 'admin.social.dashboard'],
            ['route' => 'admin.social.feeds',     'label_key' => 'app.admin.products.social.tab_feeds',    'pattern' => 'admin.social.feeds*'],
            ['route' => 'admin.social.posts',     'label_key' => 'app.admin.products.social.tab_posts',    'pattern' => 'admin.social.posts*'],
            ['route' => 'admin.social.settings',  'label_key' => 'app.admin.products.social.tab_settings', 'pattern' => 'admin.social.settings*'],
        ],
    ],
];
