<?php

return [
    'title' => 'Site Management',
    'links' => [
        [
            'url' => '/index.php?page=create_article',
            'text' => 'Create New Post',
            'icon' => '',
            'roles' => ['admin']
        ],
        [
            'url' => '/index.php?page=manage_articles',
            'text' => 'Manage Content',
            'icon' => '',
            'roles' => ['admin']
        ],
        [
            'url' => '/index.php?page=site_settings',
            'text' => 'Site Settings',
            'icon' => '',
            'roles' => ['admin']
        ]
    ]
];
?>