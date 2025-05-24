<?php
use App\Models\Comments;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$selectedArticle = null;
$newsArticles = [];
$allCategories = [];
$selectedArticleViewCategories = [];
$selectedCategorySlug = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pageTitle = "News Feed";
$errorMessage = null;

if (!isset($db) || !$db instanceof PDO) {
    if (isset($flashMessageService)) {
        $flashMessageService->addError("Database connection is not available.");
    }
    echo "<p class='message message--error'>Database connection error. Please try again later.</p>";
    return; 
}

$allCategories = Category::findAll($database_handler);

if ($articleId !== null && $articleId > 0) {
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
    $errorMessage = "Invalid news ID specified.";
} else {
    $pageTitle = "News Feed";

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
        $newsArticles = Article::findAll($database_handler);
    }

    if (empty($newsArticles) && !$errorMessage) {
        $errorMessage = "No news articles found at the moment.";
    }
}

if ($selectedArticle && isset($_SESSION['user_id']) && !isset($_SESSION['csrf_token_add_comment_article_' . $selectedArticle->id])) {
    $_SESSION['csrf_token_add_comment_article_' . $selectedArticle->id] = bin2hex(random_bytes(32));
}



if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === User::ROLE_ADMIN) {
    if (isset($_GET['action'], $_GET['comment_id'], $_GET['article_id'], $_GET['csrf_token'])) {
        if (hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
            $action = $_GET['action'];
            $comment_id_to_manage = filter_var($_GET['comment_id'], FILTER_VALIDATE_INT);
            $article_id_redirect = filter_var($_GET['article_id'], FILTER_VALIDATE_INT);
            $commentModelForAction = new Comments($database_handler); 
            if ($comment_id_to_manage && $article_id_redirect) {
                $success = false;
                if ($action === 'approve_comment') {
                    $success = $commentModelForAction->updateStatus($comment_id_to_manage, Comments::STATUS_APPROVED);
                    if ($success && isset($flashMessageService)) $flashMessageService->addSuccess("Comment approved.");
                } elseif ($action === 'reject_comment') {
                    $success = $commentModelForAction->updateStatus($comment_id_to_manage, Comments::STATUS_REJECTED);
                    if ($success && isset($flashMessageService)) $flashMessageService->addSuccess("Comment rejected.");
                } elseif ($action === 'pend_comment') {
                    $success = $commentModelForAction->updateStatus($comment_id_to_manage, Comments::STATUS_PENDING);
                    if ($success && isset($flashMessageService)) $flashMessageService->addSuccess("Comment status set to pending.");
                }


                if (!$success && $action !== 'delete_comment' && isset($flashMessageService)) {
                     $flashMessageService->addError("Failed to update comment status.");
                }

                header("Location: /index.php?page=news&id=" . $article_id_redirect . "#comment-" . $comment_id_to_manage);
                exit();
            }
        } else {
            if (isset($flashMessageService)) $flashMessageService->addError("Invalid security token for comment action.");
        }
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
        if (isset($_POST['comment_id'], $_POST['article_id'], $_POST['csrf_token'])) {
            if (hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
                $comment_id_to_delete = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
                $article_id_redirect = filter_var($_POST['article_id'], FILTER_VALIDATE_INT);
                 if (isset($flashMessageService)) $flashMessageService->addError("Delete comment logic not yet implemented."); // Заглушка
            } else {
                 if (isset($flashMessageService)) $flashMessageService->addError("Invalid security token for deleting comment.");
            }
            $article_id_redirect_val = filter_var($_POST['article_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($article_id_redirect_val) {
                 header("Location: /index.php?page=news&id=" . $article_id_redirect_val);
                 exit();
            }
        }
    }
}

?>

<div class="page-container news-page-container">
    <?php 
    if ($errorMessage): 
    ?>
        <div class="message message--error">
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$selectedArticle && !empty($allCategories)): ?>
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
                $comments_list = Comments::findByArticleId($database_handler, $selectedArticle->id, Comments::STATUS_APPROVED);
                if (!empty($comments_list)): ?>
                    <div class="comments-list">
                        <?php foreach ($comments_list as $comment_item): ?>
                            <div class="comment-item" id="comment-<?php echo htmlspecialchars($comment_item['id']); ?>">
                                <p class="comment-author">
                                    <strong>
                                        <?php
                                        echo htmlspecialchars($comment_item['author_username'] ?? ($comment_item['author_name'] ?? 'Anonymous'));
                                        ?>
                                    </strong>
                                    <span class="comment-date"> - <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($comment_item['created_at'])));?></span>
                                </p>
                                <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment_item['content'])); ?></div>

                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === User::ROLE_ADMIN && isset($csrf_token)): ?>
                                    <div class="comment-actions admin-actions" style="font-size: 0.9em; margin-top: 5px;">
                                        Current status: <?php echo htmlspecialchars(ucfirst($comment_item['status'])); ?>
                                        <?php if ($comment_item['status'] !== Comments::STATUS_APPROVED): ?>
                                            <a href="/index.php?page=news&action=approve_comment&comment_id=<?php echo $comment_item['id']; ?>&article_id=<?php echo $selectedArticle->id; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" class="button button-small button-success">Approve</a>
                                        <?php endif; ?>
                                        <?php if ($comment_item['status'] !== Comments::STATUS_REJECTED): ?>
                                            <a href="/index.php?page=news&action=reject_comment&comment_id=<?php echo $comment_item['id']; ?>&article_id=<?php echo $selectedArticle->id; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" class="button button-small button-warning">Reject</a>
                                        <?php endif; ?>
                                        <?php if ($comment_item['status'] !== Comments::STATUS_PENDING && $comment_item['status'] === Comments::STATUS_APPROVED): ?>
                                             <a href="/index.php?page=news&action=pend_comment&comment_id=<?php echo $comment_item['id']; ?>&article_id=<?php echo $selectedArticle->id; ?>&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" class="button button-small button-secondary">Set to Pending</a>
                                        <?php endif; ?>
                                        <form action="/index.php?page=news&action=delete_comment" method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment_item['id']; ?>">
                                            <input type="hidden" name="article_id" value="<?php echo $selectedArticle->id; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="button button-small button-danger">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
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
                        $user_model_for_comment = new User($database_handler);
                        $current_user_for_comment = $user_model_for_comment->findById((int)$_SESSION['user_id']); // ИЗМЕНЕНО
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
                            $userModel = new User($database_handler);
                            $author = $userModel->findById($article_item->user_id);
                            echo $author ? ' by <span class="author-name">' . htmlspecialchars($author->getUsername()) . '</span>' : '';
                            ?>
                        <?php endif; ?>
                        <?php if (method_exists($article_item, 'getCategories')) {
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
