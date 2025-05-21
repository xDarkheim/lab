<?php

return [
    [
        'url' => '/index.php?page=create_article',
        'text' => 'Create New Post',
        'icon' => '➕'
    ],
    [
        'url' => '/index.php?page=manage_articles',
        'text' => 'Manage Content',
        'icon' => '📄'
    ],
    [
        'url' => '/index.php?page=site_settings',
        'text' => 'Site Settings',
        'requires_admin' => true,
        'icon' => '⚙️'
    ],
];