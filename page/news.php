<?php
use App\Models\Article;
use App\Models\User;
// use App\Models\Comment; // This line seems unused, consider removing if not needed elsewhere on the page
use App\Models\Comments;
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
$pageTitle = "News Feed";
$errorMessage = null;

$database_handler = new Database();

if (!$database_handler->getConnection()) {
    $errorMessage = "Failed to connect to the database. Please check the configuration.";
} else {
    $articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($articleId && $articleId > 0) {
        $selectedArticle = Article::findById($database_handler, $articleId);
        if ($selectedArticle) {
            $pageTitle = htmlspecialchars($selectedArticle->title);
        } else {
            $errorMessage = "News article with ID {$articleId} not found.";
        }
    } elseif ($articleId !== null && $articleId <= 0) { // Check if 'id' was present but invalid
        $errorMessage = "Invalid news ID.";
    } else { // No 'id' or invalid 'id', so fetch all articles
        $newsArticles = Article::findAll($database_handler);
        if (empty($newsArticles)) {
            $errorMessage = "No news articles found at the moment.";
        }
    }
}

?>

<div class="page-container news-page-container">
    <?php 
    // Display general error messages
    if ($errorMessage && !$selectedArticle && empty($newsArticles)): ?>
        <div class="message message--error">
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
        </div>
    <?php endif; ?>

    <?php // --- START: Category selection block (placeholder) --- ?>
    <?php if (!$selectedArticle && !empty($newsArticles)): ?>
    <div class="category-filter-section">
        <h3 class="category-filter-title">Browse by Category:</h3>
        <ul class="category-filter-list">
            <li><a href="/index.php?page=news" class="category-link is-active">All News</a></li>
            <li><a href="#" class="category-link">PHP</a></li>
            <li><a href="#" class="category-link">JavaScript</a></li>
            <li><a href="#" class="category-link">HTML & CSS</a></li>
            <li><a href="#" class="category-link">Frameworks</a></li>
            <li><a href="#" class="category-link">Tutorials</a></li>
            <?php // Categories can be displayed dynamically here in the future ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php // --- END: Category selection block (placeholder) --- ?>

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
        <div class="news-feed-container"> <?php // Changed container class ?>
            <?php foreach ($newsArticles as $article_item): ?>
                <article class="news-feed-item"> <?php // Changed article item class ?>
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
                    </div>
                    <div class="news-feed-item-content">
                        <?php 
                        // Display short description or beginning of full text
                        // Length can be configured, or use $article_item->short_description if available
                        $content_preview = !empty($article_item->short_description) ? $article_item->short_description : $article_item->full_text;
                        echo nl2br(htmlspecialchars(mb_strimwidth($content_preview, 0, 500, '...'))); // Increased preview length
                        ?>
                    </div>
                    <a href="/index.php?page=news&id=<?php echo $article_item->id; ?>" class="button button-outline button-small read-more-link">Read More &raquo;</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$database_handler->getConnection() && !$errorMessage): ?>
         <div class="message message--error">
            <p>Failed to load news due to a database connection problem.</p>
        </div>
    <?php endif; ?>
</div>
