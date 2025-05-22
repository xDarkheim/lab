<?php

use App\Models\Article;
use App\Models\User;
use App\Lib\Database;
use App\Lib\FlashMessageService;

$pageTitle = "Manage Articles";
$flashMessageService = new FlashMessageService();

if (!isset($_SESSION['user_id'])) {
    $flashMessageService->addError('You must be logged in to manage articles.');
    $redirect_url = urlencode('/index.php?page=manage_articles');
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_delete_article'])) {
    $_SESSION['csrf_token_delete_article'] = bin2hex(random_bytes(32));
}
$csrf_token_delete = $_SESSION['csrf_token_delete_article'];

$current_user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

$database_handler = new Database();
$articles_view_data = [];
$db_connection_error = false;

if (!$database_handler->getConnection()) {
    $flashMessageService->addError('Failed to connect to the database. Article list cannot be loaded.');
    $db_connection_error = true;
} else {
    $articles = ($user_role === 'admin')
        ? Article::findAll($database_handler)
        : Article::findByUserId($database_handler, $current_user_id);

    if (!empty($articles)) {
        $user_ids_to_fetch = array_unique(array_filter(array_map(fn($article) => $article->user_id, $articles)));
        
        $authors_map = [];
        if (!empty($user_ids_to_fetch)) {
            foreach ($user_ids_to_fetch as $uid) {
                $author = User::findById($database_handler, $uid);
                $authors_map[$uid] = $author ? $author->getUsername() : 'Unknown User';
            }
        }

        foreach ($articles as $article) {
            $categories = ($article instanceof Article && method_exists($article, 'getCategories'))
                ? $article->getCategories($database_handler)
                : [];

            $articles_view_data[] = [
                'id' => $article->id,
                'title' => $article->title,
                'date' => $article->date,
                'user_id' => $article->user_id,
                'author_name' => $authors_map[$article->user_id] ?? ($article->user_id ? 'User ID: ' . $article->user_id : 'N/A'),
                'categories' => $categories,
            ];
        }
    }
}
?>

<div class="page-container manage-articles-page">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <?php if (!$db_connection_error && empty($articles_view_data)): ?>
        <div class="message message--info message--empty-state">
            <p>No articles found.</p>
            <a href="/index.php?page=create_article&from=manage" class="button button-primary">Create the First Article</a>
        </div>
    <?php elseif (!empty($articles_view_data)): ?>
        <div class="table-responsive">
            <table class="styled-table articles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Categories</th>
                        <th>Date</th>
                        <th class="actions-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles_view_data as $article_item): ?>
                        <tr>
                            <td><?php echo $article_item['id']; ?></td>
                            <td>
                                <a href="/index.php?page=news&id=<?php echo $article_item['id']; ?>" title="View Article">
                                    <?php echo htmlspecialchars(mb_strimwidth($article_item['title'], 0, 60, "...")); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($article_item['author_name']); ?></td>
                            <td class="categories-cell">
                                <?php if (!empty($article_item['categories'])): ?>
                                    <?php
                                    $category_links = [];
                                    foreach ($article_item['categories'] as $category) {
                                        $category_name_safe = htmlspecialchars($category->name);
                                        $category_slug_safe = htmlspecialchars($category->slug);
                                        
                                        $category_links[] = sprintf(
                                            '<a href="/index.php?page=news&category=%s" class="category-tag-small" title="%s">' .
                                            '<span style="color: white; text-indent: 0;">%s</span></a>',
                                            $category_slug_safe,
                                            $category_name_safe,
                                            $category_name_safe
                                        );
                                    }
                                    echo implode(' ', $category_links);
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted-small">None</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($article_item['date']))); ?></td>
                            <td class="actions-cell">
                                <?php if ($user_role === 'admin' || $article_item['user_id'] == $current_user_id): ?>
                                    <a href="/index.php?page=edit_article&id=<?php echo $article_item['id']; ?>" class="button button-secondary button-small">Edit</a>
                                    <form action="/index.php?page=delete_article" method="POST" onsubmit="return confirm('Are you sure you want to delete this article? This action cannot be undone.');" style="display: inline-block; margin-left: 5px;">
                                        <input type="hidden" name="article_id" value="<?php echo $article_item['id']; ?>">
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