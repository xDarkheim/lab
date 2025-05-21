<?php

use App\Lib\Database;
use App\Models\Article;
use App\Models\Category;

$pageTitle = "Edit Article"; // Default page title

// Initialize form variables
$article_id = null;
$title_form = '';
$content_form = ''; // Corresponds to full_text
$short_description_form = '';
$date_form = '';
$selected_category_ids_for_form = []; // For populating checkboxes

// Load validation errors from session, if any
$form_validation_errors = $_SESSION['form_validation_errors'] ?? [];
unset($_SESSION['form_validation_errors']); // Clear after loading

$database_handler = new Database();
$db_connection = $database_handler->getConnection();

if (!$db_connection) {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database connection error. Cannot edit article.']];
    header('Location: /index.php?page=manage_articles');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You must be logged in to edit articles.']];
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Redirect back to this page after login
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// CSRF token generation for the edit form
if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_edit_article'])) {
    $_SESSION['csrf_token_edit_article'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_edit_article'];

// Fetch all categories for the selection field
$all_categories = Category::findAll($database_handler);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'CSRF Error: Invalid token. Please try again.']];
        $post_article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
        // Redirect back to edit page if possible, otherwise to manage page
        $redirect_loc = $post_article_id ? '/index.php?page=edit_article&id=' . $post_article_id : '/index.php?page=manage_articles';
        header('Location: ' . $redirect_loc);
        exit;
    }

    $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
    $title_form = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $short_description_form = trim(filter_input(INPUT_POST, 'short_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $content_form = trim(filter_input(INPUT_POST, 'full_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $date_form = trim(filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $selected_category_ids_post = filter_input(INPUT_POST, 'categories', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY) ?? [];

    // Store submitted data in session for repopulation in case of errors
    $_SESSION['form_data'] = [
        'article_id' => $article_id,
        'title' => $title_form,
        'short_description' => $short_description_form,
        'full_text' => $content_form,
        'date' => $date_form,
        'categories' => $selected_category_ids_post
    ];
    $selected_category_ids_for_form = $selected_category_ids_post; // Use posted categories for immediate form display on error

    // Validation
    if (empty($title_form)) $form_validation_errors['title'] = "Title cannot be empty.";
    if (empty($content_form)) $form_validation_errors['full_text'] = "Full text cannot be empty.";
    if (empty($date_form)) {
        $form_validation_errors['date'] = 'Publication date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_form) || !strtotime($date_form)) {
        $form_validation_errors['date'] = 'Invalid date format. Please use YYYY-MM-DD.';
    }

    if (empty($form_validation_errors)) {
        if (!$article_id) {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Invalid article ID for update.']];
            header('Location: /index.php?page=manage_articles');
            exit;
        }
        try {
            $stmt_check_author = $db_connection->prepare("SELECT user_id FROM articles WHERE id = ?");
            $stmt_check_author->execute([$article_id]);
            $original_article_user_id = $stmt_check_author->fetchColumn();

            if ($original_article_user_id === false) {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Article not found for update.']];
                header('Location: /index.php?page=manage_articles');
                exit;
            }
            if ($original_article_user_id != $current_user_id && $user_role !== 'admin') {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You do not have permission to edit this article.']];
                header('Location: /index.php?page=manage_articles');
                exit;
            }

            $sql = "UPDATE articles SET title = ?, short_description = ?, full_text = ?, date = ?, updated_at = NOW() WHERE id = ?";
            $params = [$title_form, $short_description_form, $content_form, $date_form, $article_id];
            
            $stmt_update = $db_connection->prepare($sql);
            if ($stmt_update->execute($params)) {
                $article_to_update_categories = Article::findById($database_handler, (int)$article_id);
                if ($article_to_update_categories) {
                    $article_to_update_categories->setCategories($database_handler, $selected_category_ids_post);
                } else {
                    error_log("Edit_article: Could not find article ID {$article_id} to update categories after main content update.");
                }

                $_SESSION['flash_messages'] = [['type' => 'success', 'text' => 'Article updated successfully.']];
                unset($_SESSION['form_data'], $_SESSION['form_validation_errors'], $_SESSION['csrf_token_edit_article']);
                header('Location: /index.php?page=manage_articles');
                exit;
            } else {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Failed to update article. Please try again.']];
                error_log("Failed to update article ID: $article_id. PDO Error: " . print_r($stmt_update->errorInfo(), true));
            }
        } catch (\PDOException $e) {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database error during article update.']];
            error_log("PDOException in edit_article.php (POST): " . $e->getMessage());
        }
        // If update failed or exception occurred, redirect back to edit form (form_data is in session)
        header('Location: /index.php?page=edit_article&id=' . $article_id);
        exit;
    } else {
        // Validation errors occurred
        $_SESSION['form_validation_errors'] = $form_validation_errors; // Put errors back in session
        // $_SESSION['form_data'] is already set
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Please correct the errors below.']];
        header('Location: /index.php?page=edit_article&id=' . $article_id);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $article_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$article_id_get) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'No article ID specified or invalid ID.']];
        header('Location: /index.php?page=manage_articles');
        exit;
    }
    $article_id = $article_id_get;

    // Check if there's form data from a failed POST attempt
    if (isset($_SESSION['form_data']) && isset($_SESSION['form_data']['article_id']) && $_SESSION['form_data']['article_id'] == $article_id) {
        $form_data_from_session = $_SESSION['form_data'];
        $title_form = $form_data_from_session['title'] ?? '';
        $short_description_form = $form_data_from_session['short_description'] ?? '';
        $content_form = $form_data_from_session['full_text'] ?? '';
        $date_form = $form_data_from_session['date'] ?? '';
        $selected_category_ids_for_form = $form_data_from_session['categories'] ?? [];
        // $form_validation_errors are already loaded at the top of the script
        unset($_SESSION['form_data']); // Clear form data after use
    } else {
        // No session data, fetch from database
        try {
            $article_object = Article::findById($database_handler, $article_id);

            if (!$article_object) {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Article not found.']];
                header('Location: /index.php?page=manage_articles');
                exit;
            }

            if ($article_object->user_id != $current_user_id && $user_role !== 'admin') {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You do not have permission to edit this article.']];
                header('Location: /index.php?page=manage_articles');
                exit;
            }

            $title_form = $article_object->title;
            $short_description_form = $article_object->short_description;
            $content_form = $article_object->full_text;
            $date_form = $article_object->date;

            $current_article_categories = $article_object->getCategories($database_handler);
            $selected_category_ids_for_form = array_map(fn($cat) => $cat->id, $current_article_categories);

        } catch (\PDOException $e) {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database error while fetching article.']];
            error_log("PDOException in edit_article.php (GET) for article ID $article_id: " . $e->getMessage());
            header('Location: /index.php?page=manage_articles');
            exit;
        }
    }
} else {
    // Should not happen for this page (only GET or POST)
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Invalid request method.']];
    header('Location: /index.php?page=manage_articles');
    exit;
}

// Set dynamic page title after fetching/setting $title_form and $article_id
$pageTitle = "Edit Article: " . htmlspecialchars($title_form ?: "ID " . ($article_id ?? 'New'));

?>

<div class="page-container edit-article-page">
    <a href="/index.php?page=manage_articles" class="button button-secondary form-page-back-link">&laquo; Back to Manage Articles</a>
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    
    <form action="/index.php?page=edit_article" method="POST" class="styled-form edit-article-form">
        <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($article_id ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">

        <div class="form-group <?php echo isset($form_validation_errors['title']) ? 'has-error' : ''; ?>">
            <label for="title" class="form-label">Title <span class="required-asterisk">*</span></label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title_form); ?>" required>
            <?php if (isset($form_validation_errors['title'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['title']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['short_description']) ? 'has-error' : ''; ?>">
            <label for="short_description" class="form-label">Short Description (Preview)</label>
            <textarea id="short_description" name="short_description" class="form-control" rows="3"><?php echo htmlspecialchars($short_description_form); ?></textarea>
            <?php if (isset($form_validation_errors['short_description'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['short_description']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['full_text']) ? 'has-error' : ''; ?>">
            <label for="full_text" class="form-label">Full Text <span class="required-asterisk">*</span></label>
            <textarea id="full_text" name="full_text" class="form-control" rows="10" required><?php echo htmlspecialchars($content_form); ?></textarea>
            <?php if (isset($form_validation_errors['full_text'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['full_text']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['date']) ? 'has-error' : ''; ?>">
            <label for="date" class="form-label">Publication Date <span class="required-asterisk">*</span></label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_form); ?>" required>
            <?php if (isset($form_validation_errors['date'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['date']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['categories']) ? 'has-error' : ''; ?>">
            <label class="form-label">Categories</label>
            <?php if (!empty($all_categories)): ?>
                <div class="category-checkbox-group">
                    <?php foreach ($all_categories as $category_item): ?>
                        <div class="checkbox-item">
                            <input type="checkbox"
                                   id="category_edit_<?php echo htmlspecialchars($category_item->id); ?>"
                                   name="categories[]"
                                   value="<?php echo htmlspecialchars($category_item->id); ?>"
                                   <?php echo in_array($category_item->id, $selected_category_ids_for_form) ? 'checked' : ''; ?>>
                            <label for="category_edit_<?php echo htmlspecialchars($category_item->id); ?>"><?php echo htmlspecialchars($category_item->name); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-categories-message">No categories available.</p>
            <?php endif; ?>
            <?php if (isset($form_validation_errors['categories'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['categories']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">Update Article</button>
            <a href="/index.php?page=manage_articles" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>