<?php
/**
 * This file is for configuring page routes.
 */

return [
    'home' => [
        'controller' => \App\Controllers\ProfileController::class,
        'action' => 'index',
        'file' => 'home.php',
        'title' => 'Home',
        'auth_required' => false,
        'guest_only' => false,
    ],
    'news' => [
        'file' => 'news.php',
        'title' => 'News',
        'auth_required' => false,
        'guest_only' => false,
    ],
        'login' => [
        'file' => 'login.php',
        'title' => 'Login',
        'auth_required' => false,
        'guest_only' => false,
    ],
    'about' => [
        'file' => 'about.php',
        'title' => 'About',
        'auth_required' => false,
        'guest_only' => false,
    ],
    'contact' => [
        'file' => 'contact.php',
        'title' => 'Contact',
        'auth_required' => false,
        'guest_only' => false,
    ],
    'forum' => [
        'file' => 'forum.php',
        'title' => 'Forum',
        'auth_required' => false,
        'guest_only' => false,
    ],
    'register' => [
        'file' => 'register.php',
        'title' => 'Registration',
        'auth_required' => false,
        'guest_only' => true, // For guests only
    ],
    'account_dashboard' => [ // New key
        'file' => 'account/dashboard.php', // New path
        'title' => 'My Dashboard',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'manage_articles' => [
        'file' => 'account/manage_articles.php', // Updated path
        'title' => 'Manage Articles',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'create_article' => [
        'file' => 'account/create_article.php', // Updated path
        'title' => 'Create Article',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'delete_article' => [
        'file' => 'account/delete_article.php', // Updated path
        'title' => 'Delete Article',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'edit_article' => [
        'file' => 'account/edit_article.php', // Updated path
        'title' => 'Edit Article',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'account_edit_profile' => [ // New key
        'file' => 'account/edit_profile.php', // New path
        'title' => 'Edit Profile',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'account_settings' => [ // Key remains, path changes
        'file' => 'account/settings.php', // New path and filename
        'title' => 'Account Settings',
        'auth_required' => true,
        'guest_only' => false,
    ],
    'profile/edit' => [
        'controller' => App\Controllers\ProfileController::class,
        'action' => 'editProfilePage',
    ],
];