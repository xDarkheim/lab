<?php

use App\Models\Article;
use App\Models\User; 

$page_title = "Manage Articles";

if (!isset($_SESSION['user_id'])) {
    if(isset($flashMessageService)) $flashMessageService->addError('You must be logged in to manage articles.');
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

$articles_view_data = [];
$db_connection_error = false;

if (!isset($database_handler) || !$database_handler instanceof \App\Lib\Database) {
    echo "<p class='message message--error'>Database handler not available.</p>";
    error_log("Critical: Database handler not available in manage_articles.php");
    return; // или exit();
}

if (!isset($db) || !$db instanceof PDO) {
    echo "<p class='message message--error'>Database connection error.</p>";
    $db_connection_error = true;
}

$articles_per_page = 10;
$current_page_get = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$total_articles = 0;


$articles_list = ($user_role === User::ROLE_ADMIN)
    ? Article::findAll($database_handler, $current_page_get, $articles_per_page, $total_articles)
    : Article::findByUserId($database_handler, $current_user_id);

if (!empty($articles_list)) {
    $user_ids_to_fetch = array_unique(array_filter(array_map(fn($article) => $article->user_id, $articles_list)));
    
    $authors_map = [];
    if (!empty($user_ids_to_fetch)) {
        foreach ($user_ids_to_fetch as $uid) {
            $author_user_model = new User($database_handler);
            $author_data = $author_user_model->findById($uid);
            $authors_map[$uid] = $author_data ? $author_data->getUsername() : 'Unknown User';
        }
    }

    foreach ($articles_list as $article_instance) {
        if (!$article_instance instanceof Article) {
            error_log("Manage Articles: Item in articles_list is not an Article object.");
            continue;
        }
        $categories = ($article_instance instanceof Article && method_exists($article_instance, 'getCategories'))
            ? $article_instance->getCategories($database_handler)
            : [];

        $articles_view_data[] = [
            'id' => $article_instance->id,
            'title' => $article_instance->title,
            'date' => $article_instance->date,
            'user_id' => $article_instance->user_id,
            'author_name' => $authors_map[$article_instance->user_id] ?? ($article_instance->user_id ? 'User ID: ' . $article_instance->user_id : 'N/A'),
            'categories' => $categories,
        ];
    }
}
?>

<div class="page-container manage-articles-page">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>

    <?php if (!$db_connection_error && empty($articles_view_data) && !$total_articles): ?>
        <div class="message message--info message--empty-state">
            <p>No articles found.</p>
            <?php if ($user_role === User::ROLE_ADMIN || $user_role === User::ROLE_EDITOR): ?>
            <a href="/index.php?page=create_article&from=manage" class="button button-primary">Create the First Article</a>
            <?php endif; ?>
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

    <?php
    if ($total_articles > $articles_per_page && ($user_role === User::ROLE_ADMIN)):
        $total_pages = ceil($total_articles / $articles_per_page);
        if ($total_pages > 1):
    ?>
    <nav class="pagination">
        <ul class="pagination-list">
            <?php if ($current_page_get > 1): ?>
                <li><a href="/index.php?page=manage_articles&p=<?php echo $current_page_get - 1; ?>" class="pagination-link">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li><a href="/index.php?page=manage_articles&p=<?php echo $i; ?>" class="pagination-link <?php echo ($i == $current_page_get) ? 'is-active' : ''; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($current_page_get < $total_pages): ?>
                <li><a href="/index.php?page=manage_articles&p=<?php echo $current_page_get + 1; ?>" class="pagination-link">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
        endif;
    endif;
    ?>
</div>