<?php
use App\Models\Article;
use App\Models\User;
use App\Models\Comments;
use App\Models\Category;
use App\Lib\Database;

// Start session if not already started by bootstrap.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$selectedArticle = null;
$newsArticles = [];
$allCategories = [];
$selectedArticleViewCategories = [];
$selectedCategorySlug = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pageTitle = "News Feed"; // Default page title
$errorMessage = null;

$database_handler = new Database();

if (!$database_handler->getConnection()) {
    $errorMessage = "Failed to connect to the database. Please check the configuration.";
} else {
    // Always load all categories for the filter if the database connection exists
    $allCategories = Category::findAll($database_handler);

    if ($articleId !== null && $articleId > 0) {
        // Logic for displaying a single article
        $selectedArticle = Article::findById($database_handler, $articleId);
        if ($selectedArticle) {
            $pageTitle = htmlspecialchars($selectedArticle->title);
            if (method_exists($selectedArticle, 'getCategories')) {
                $categoriesData = $selectedArticle->getCategories($database_handler);
                if (is_array($categoriesData)) {
                    $selectedArticleViewCategories = $categoriesData;
                }
            }
        } else {
            $errorMessage = "News article with ID {$articleId} not found.";
        }
    } elseif ($articleId !== null && $articleId <= 0) {
        // Logic for invalid article ID
        $errorMessage = "Invalid news ID specified.";
    } else {
        // Logic for displaying a list of articles (possibly filtered by category)
        $pageTitle = "News Feed"; // Reset to default if no category is found

        if ($selectedCategorySlug) {
            $categoryObject = Category::findBySlug($database_handler, $selectedCategorySlug);
            if ($categoryObject) {
                $newsArticles = Article::findByCategoryId($database_handler, $categoryObject->id);
                $pageTitle = "News: " . htmlspecialchars($categoryObject->name);
                if (empty($newsArticles)) {
                    $errorMessage = "No news articles found in the category: " . htmlspecialchars($categoryObject->name) . ".";
                }
            } else {
                $errorMessage = "Category '" . htmlspecialchars($selectedCategorySlug) . "' not found.";
            }
        } else {
            // No article ID and no category - show all articles
            $newsArticles = Article::findAll($database_handler);
        }

        // General message if no articles are found and no specific error was set
        if (empty($newsArticles) && !$errorMessage) {
            $errorMessage = "No news articles found at the moment.";
        }
    }
}

// Generate CSRF for the comment form if the user is logged in and a selected article exists
if ($selectedArticle && isset($_SESSION['user_id']) && !isset($_SESSION['csrf_token_add_comment_article_' . $selectedArticle->id])) {
    $_SESSION['csrf_token_add_comment_article_' . $selectedArticle->id] = bin2hex(random_bytes(32));
}

?>

<div class="page-container news-page-container">
    <?php 
    // Display error messages
    if ($errorMessage): 
    ?>
        <div class="message message--error">
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
        </div>
    <?php endif; ?>

    <?php // --- START: Category selection block --- ?>
    <?php if (!$selectedArticle && !empty($allCategories)): // Show categories if not viewing a single article and categories exist ?>
    <div class="category-filter-section">
        <h3 class="category-filter-title">Browse by Category:</h3>
        <ul class="category-filter-list">
            <li><a href="/index.php?page=news" class="category-link<?php echo !$selectedCategorySlug ? ' is-active' : ''; ?>">All News</a></li>
            <?php foreach ($allCategories as $category): ?>
                <li><a href="/index.php?page=news&category=<?php echo htmlspecialchars($category->slug); ?>" class="category-link<?php echo ($selectedCategorySlug === $category->slug) ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($category->name); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php // --- END: Category selection block --- ?>

    <?php if ($selectedArticle): ?>
        <article class="single-article">
            <header class="single-article-header">
                <h1 class="single-article-title"><?php echo htmlspecialchars($selectedArticle->title); ?></h1>
                <div class="article-meta">
                    <span>By <?php echo htmlspecialchars($selectedArticle->author_name ?? 'Unknown'); ?></span> |
                    <span><?php echo htmlspecialchars(date('F j, Y', strtotime($selectedArticle->date))); ?></span>
                    <?php if (!empty($selectedArticleViewCategories)): ?>
                        | <span>Categories: 
                        <?php 
                        $cat_links = [];
                        foreach ($selectedArticleViewCategories as $category) {
                            $cat_links[] = '<a href="/index.php?page=news&category=' . htmlspecialchars($category->slug) . '">' . htmlspecialchars($category->name) . '</a>';
                        }
                        echo implode(', ', $cat_links);
                        ?>
                        </span>
                    <?php endif; ?>
                </div>
            </header>
            
            <div class="article-content"><?php echo nl2br(htmlspecialchars($selectedArticle->full_text)); ?></div>

            <?php 
            $can_manage_article = false;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $current_user_id_from_session = $_SESSION['user_id'] ?? null;
                $current_user_role_from_session = $_SESSION['user_role'] ?? null;

                if ($current_user_id_from_session && 
                    ($selectedArticle->user_id == $current_user_id_from_session || $current_user_role_from_session === 'admin')) {
                    $can_manage_article = true;
                }
            }

            if ($can_manage_article) :
            ?>
                <div class="article-admin-actions" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <h4>Admin Actions:</h4>
                    <a href="/index.php?page=edit_article&id=<?php echo $selectedArticle->id; ?>" class="button button-secondary button-small">Edit Article</a>
                    
                    <?php 
                    $csrf_token_for_delete = $_SESSION['csrf_token'] ?? ''; 
                    ?>
                    <form action="/index.php?page=delete_article" method="POST" onsubmit="return confirm('Are you sure you want to delete this article? This action cannot be undone.');" style="display: inline-block; margin-left: 10px;">
                        <input type="hidden" name="article_id" value="<?php echo $selectedArticle->id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_for_delete); ?>">
                        <button type="submit" class="button button-danger button-small">Delete Article</button>
                    </form>
                </div>
            <?php endif; ?>
    
            <div class="comments-section">
                <h2 class="comments-section-title">Comments</h2>
                <?php
                $comments_list = Comments::findByArticleId($database_handler, $selectedArticle->id, 'approved');
                if (!empty($comments_list)): ?>
                    <div class="comments-list">
                        <?php foreach ($comments_list as $comment_item): ?>
                            <div class="comment-item" id="comment-<?php echo $comment_item->id; ?>">
                                <p class="comment-author"><strong><?php echo htmlspecialchars($comment_item->author_name); ?></strong>
                                    <span class="comment-date"> - <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($comment_item->created_at))); ?></span>
                                </p>
                                <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment_item->content)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-comments-message">No approved comments yet. Be the first to comment!</p>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="/modules/add_comment_process.php" method="POST" class="comment-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_comment_article_' . $selectedArticle->id] ?? ''); ?>">
                        <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($selectedArticle->id); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                        <?php
                        $current_user_for_comment = User::findById($database_handler, $_SESSION['user_id']);
                        $author_name_for_comment = $current_user_for_comment ? $current_user_for_comment->getUsername() : 'Registered User';
                        ?>
                        <input type="hidden" name="author_name" value="<?php echo htmlspecialchars($author_name_for_comment); ?>">
                        <div class="form-group">
                            <label for="comment_content_<?php echo $selectedArticle->id; ?>" class="form-label">Your Comment:</label>
                            <textarea id="comment_content_<?php echo $selectedArticle->id; ?>" name="content" rows="4" placeholder="Write a comment..." required class="form-control"></textarea>
                        </div>
                        <button type="submit" class="button button-primary">Post Comment</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt"><a href="/index.php?page=login">Log in</a> to post a comment.</p>
                <?php endif; ?>
            </div>
        </article>
    <?php elseif (!empty($newsArticles)): ?>
        <div class="news-feed-container"> 
            <?php foreach ($newsArticles as $article_item): ?>
                <article class="news-feed-item"> 
                    <h2 class="news-feed-item-title">
                        <a href="/index.php?page=news&id=<?php echo $article_item->id; ?>"><?php echo htmlspecialchars($article_item->title); ?></a>
                    </h2>
                    <div class="article-meta">
                        <span class="date">Published on: <?php echo htmlspecialchars(date('F j, Y', strtotime($article_item->date))); ?></span>
                        <?php if ($article_item->user_id): ?>
                            <?php
                            $author = User::findById($database_handler, $article_item->user_id);
                            echo $author ? ' by <span class="author-name">' . htmlspecialchars($author->getUsername()) . '</span>' : '';
                            ?>
                        <?php endif; ?>
                         <?php
                        // Display categories for articles in the feed
                        if (method_exists($article_item, 'getCategories')) {
                            $articleCategories = $article_item->getCategories($database_handler);
                            if (!empty($articleCategories)) {
                                echo '<div class="article-categories-display article-categories-feed">'; 
                                echo '<span>Categories: </span>';
                                foreach ($articleCategories as $index => $cat) {
                                    echo '<a href="/index.php?page=news&category=' . htmlspecialchars($cat->slug) . '" class="category-tag">' . htmlspecialchars($cat->name) . '</a>';
                                    if ($index < count($articleCategories) - 1) {
                                        echo ', ';
                                    }
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="news-feed-item-content">
                        <?php 
                        $content_preview = !empty($article_item->short_description) ? $article_item->short_description : $article_item->full_text;
                        echo nl2br(htmlspecialchars(mb_strimwidth($content_preview, 0, 500, '...'))); 
                        ?>
                    </div>
                    <a href="/index.php?page=news&id=<?php echo $article_item->id; ?>" class="button button-outline button-small read-more-link">Read More &raquo;</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$errorMessage && !$database_handler->getConnection()): ?>
        <div class="message message--error">
            <p>Failed to load news articles. The database may be unavailable.</p>
        </div>
    <?php elseif (!$errorMessage): ?>
        <div class="message message--info">
            <p>No news articles are currently available<?php echo $selectedCategorySlug ? ' in this category' : ''; ?>.</p>
        </div>
    <?php endif; ?>

</div>
