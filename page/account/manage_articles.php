<?php

use App\Models\Article;
use App\Models\User;
use App\Lib\Database;

$pageTitle = "Manage Articles";

// Flash messages are now handled globally by public/index.php
// $flash_messages = $_SESSION['flash_messages'] ?? [];
// unset($_SESSION['flash_messages']);

if (!isset($_SESSION['user_id'])) {
    // Use flash_messages for the login page
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You must be logged in to manage articles.']];
    $redirect_url = urlencode('/index.php?page=manage_articles');
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

// CSRF token for delete forms on this page
// Regenerate if not set or on GET request to ensure it's fresh for the page load
if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_delete_article'])) {
    $_SESSION['csrf_token_delete_article'] = bin2hex(random_bytes(32));
}
$csrf_token_delete = $_SESSION['csrf_token_delete_article'];

$current_user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

$database_handler = new Database();
$articles = [];
$authors = [];
$db_connection_error = false;

if (!$database_handler->getConnection()) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Failed to connect to the database. Article list cannot be loaded.'];
    $db_connection_error = true;
} else {
    // Fetch articles based on role
    if ($user_role === 'admin') {
        $articles = Article::findAll($database_handler); // Admin sees all articles
    } else {
        $articles = Article::findByUserId($database_handler, $current_user_id); // Regular user sees only their articles
    }

    if (!empty($articles)) {
        $user_ids = array_unique(array_filter(array_column($articles, 'user_id')));
        if (!empty($user_ids)) {
            foreach ($user_ids as $uid) {
                $author = User::findById($database_handler, $uid);
                $authors[$uid] = $author ? $author->getUsername() : 'Unknown User';
            }
        }
    }
}
?>

<div class="admin-content-container">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <div class="admin-actions-bar">
        <a href="/index.php?page=create_article&from=manage" class="button button-primary">Create New Article</a>
    </div>

    <?php 
    // The global $page_messages variable (populated in public/index.php) will be displayed
    // by the theme's template (e.g., header.php or template.php).
    // So, no need for a specific flash message loop here.
    ?>

    <?php if ($db_connection_error && empty($articles)): ?>
        <?php // Error message already handled by flash_messages if it was a DB connection error ?>
    <?php elseif (empty($articles) && !$db_connection_error): ?>
        <div class="message message--info message--empty-state">
            <p>No articles found.</p>
            <a href="/index.php?page=create_article&from=manage" class="button button-primary">Create the First Article</a>
        </div>
    <?php elseif (!empty($articles)): ?>
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th style="min-width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?php echo $article->id; ?></td>
                            <td><a href="/index.php?page=news&id=<?php echo $article->id; ?>" title="View Article"><?php echo htmlspecialchars(mb_strimwidth($article->title, 0, 70, "...")); ?></a></td>
                            <td>
                                <?php
                                echo htmlspecialchars($authors[$article->user_id] ?? ($article->user_id ? 'User ID: ' . $article->user_id : 'N/A'));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($article->date))); ?></td>
                            <td class="actions-cell">
                                <?php // Edit button: only if user is author or admin ?>
                                <?php if ($user_role === 'admin' || $article->user_id == $current_user_id): ?>
                                    <a href="/index.php?page=edit_article&id=<?php echo $article->id; ?>" class="button button-secondary button-small">Edit</a>
                                <?php endif; ?>

                                <?php // Delete button: only if user is author or admin ?>
                                <?php if ($user_role === 'admin' || $article->user_id == $current_user_id): ?>
                                    <form action="/index.php?page=delete_article" method="POST" onsubmit="return confirm('Are you sure you want to delete this article? This action cannot be undone.');" style="display: inline;">
                                        <input type="hidden" name="article_id" value="<?php echo $article->id; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_delete); ?>">
                                        <button type="submit" class="button button-danger button-small">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>