<?php
use App\Models\Article;
use App\Models\User;
// use App\Models\Comment; // This line seems unused, consider removing if not needed elsewhere on the page
use App\Models\Comments;
use App\Models\Category; // Make sure you have this Category model
use App\Lib\Database;

// Start session if not already started by bootstrap.php (though bootstrap should handle this)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF token for adding comments
if (!isset($_SESSION['csrf_token_add_comment'])) {
    $_SESSION['csrf_token_add_comment'] = bin2hex(random_bytes(32));
}

$selectedArticle = null;
$newsArticles = [];
$allCategories = [];
$selectedCategorySlug = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$pageTitle = "News Feed";
$errorMessage = null;

$database_handler = new Database();

if (!$database_handler->getConnection()) {
    $errorMessage = "Failed to connect to the database. Please check the configuration.";
} else {
    // Fetch all categories for the filter
    // Ensure Category::findAll() is implemented in your Category model
    $allCategories = Category::findAll($database_handler); 

    $articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($articleId && $articleId > 0) {
        $selectedArticle = Article::findById($database_handler, $articleId);
        if ($selectedArticle) {
            $pageTitle = htmlspecialchars($selectedArticle->title);
            // Optionally, load categories for the selected article if getCategories method exists
            // if (method_exists($selectedArticle, 'getCategories')) {
            //     $selectedArticle->categories = $selectedArticle->getCategories($database_handler); 
            // }
        } else {
            $errorMessage = "News article with ID {$articleId} not found.";
        }
    } elseif ($articleId !== null && $articleId <= 0) { // Check if 'id' was present but invalid
        $errorMessage = "Invalid news ID.";
    } else { // No 'id' or invalid 'id', so fetch articles (potentially filtered by category)
        if ($selectedCategorySlug) {
            // Ensure Category::findBySlug() is implemented
            $categoryObject = Category::findBySlug($database_handler, $selectedCategorySlug); 
            if ($categoryObject) {
                // Ensure Article::findByCategoryId() is implemented
                $newsArticles = Article::findByCategoryId($database_handler, $categoryObject->id); 
                $pageTitle = "News: " . htmlspecialchars($categoryObject->name);
                if (empty($newsArticles)) {
                    $errorMessage = "No news articles found in the category: " . htmlspecialchars($categoryObject->name) . ".";
                }
            } else {
                $errorMessage = "Category '" . htmlspecialchars($selectedCategorySlug) . "' not found.";
                $newsArticles = Article::findAll($database_handler); // Fallback to all articles
            }
        } else {
            $newsArticles = Article::findAll($database_handler);
        }
        if (empty($newsArticles) && !$errorMessage) { // Avoid overwriting category-specific messages
            $errorMessage = "No news articles found at the moment.";
        }
    }
}

?>

<div class="page-container news-page-container">
    <?php 
    // Display general error messages
    if ($errorMessage && !$selectedArticle && (empty($newsArticles) || $selectedCategorySlug)): 
        // Show error if articles are empty OR a category was selected but yielded no results
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
            <h1 class="single-article-title"><?php echo htmlspecialchars($selectedArticle->title); ?></h1>
            <div class="article-meta">
                <p class="date">Published on: <?php echo htmlspecialchars(date('F j, Y', strtotime($selectedArticle->date))); ?></p>
                <?php if ($selectedArticle->user_id): ?>
                    <?php 
                    $author = User::findById($database_handler, $selectedArticle->user_id);
                    echo $author ? '<p class="author">Author: ' . htmlspecialchars($author->getUsername()) . '</p>' : '<p class="author">Author ID: ' . htmlspecialchars((string)$selectedArticle->user_id) . '</p>';
                    ?>
                <?php endif; ?>
                <?php
                // Display categories for the single article
                // Ensure $selectedArticle->getCategories($database_handler) returns an array of category objects
                if (method_exists($selectedArticle, 'getCategories')) {
                    $articleCategories = $selectedArticle->getCategories($database_handler);
                    if (!empty($articleCategories)) {
                        echo '<div class="article-categories-display">';
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
            <div class="article-content"><?php echo nl2br(htmlspecialchars($selectedArticle->full_text)); // For a single article, show the full text ?></div>
            
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
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_add_comment']); ?>">
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
                        // Ensure $article_item->getCategories($database_handler) returns an array of category objects
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
    <?php elseif (!$database_handler->getConnection() && !$errorMessage && empty($newsArticles)): // Added empty($newsArticles) to avoid double message on DB error ?>
         <div class="message message--error">
            <p>Failed to load news articles. The database may be unavailable.</p>
        </div>
    <?php elseif (empty($newsArticles) && !$errorMessage): // This condition might be redundant now due to earlier checks, but safe to keep
        // This will show if $newsArticles is empty and no specific error (like category not found) was set.
        // If $errorMessage was set (e.g. "No articles in category X"), that message will be shown instead by the first error block.
        ?>
        <div class="message message--info">
            <p>No news articles are currently available<?php echo $selectedCategorySlug ? ' in this category' : ''; ?>.</p>
        </div>
    <?php endif; ?>

</div>
