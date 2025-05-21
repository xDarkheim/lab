<?php
/**
 * This file is for configuring the top menu.
 */
return [
    'main' => [
        ['key' => 'home', 'url' => '/index.php?page=home', 'text' => 'Home'],
        ['key' => 'news', 'url' => '/index.php?page=news', 'text' => 'News'],
        ['key' => 'about', 'url' => '/index.php?page=about', 'text' => 'About'],
    ],
    'user_specific' => [
        'guest' => [
            'key' => 'register',
            'url' => '/index.php?page=register',
            'text' => 'Register',
            'class' => 'nav-button register-button'
        ],
        'auth' => [
           'key' => 'account_dashboard',
            'url' => '/index.php?page=account_dashboard',
            'text' => 'Dashboard',
            'class' => 'nav-button dashboard-button'
        ],
    ],
];