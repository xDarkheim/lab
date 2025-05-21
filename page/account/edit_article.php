<?php

global $db; 

$pageTitle = "Edit Article";
$article_id = null;
$title_form = ''; 
$content_form = '';


$form_validation_errors = $_SESSION['form_validation_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? []; 


unset($_SESSION['form_validation_errors'], $_SESSION['form_data']);


if (!$db) {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database connection error. Cannot edit article.']];
    header('Location: /index.php?page=manage_articles');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You must be logged in to edit articles.']];
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); 
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// CSRF token for the edit form
if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_edit_article'])) {
    $_SESSION['csrf_token_edit_article'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_edit_article'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_edit_article'], $_POST['csrf_token'])) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'CSRF Error: Invalid token. Please try again.']];
        header('Location: /index.php?page=edit_article&id=' . filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT));
        exit;
    }

    $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
    $title_form = trim($_POST['title'] ?? ''); // Use $title_form
    $content_form = trim($_POST['content'] ?? ''); // Use $content_form

    if (!$article_id) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Invalid article ID.']];
        header('Location: /index.php?page=manage_articles');
        exit;
    }

    if (empty($title_form)) {
        $form_validation_errors['title'] = "Title cannot be empty.";
    }
    if (mb_strlen($title_form) > 255) {
        $form_validation_errors['title'] = "Title cannot exceed 255 characters.";
    }
    if (empty($content_form)) {
        $form_validation_errors['content'] = "Content cannot be empty.";
    }

    if (empty($form_validation_errors)) {
        try {
            $stmt_check_author = $db->prepare("SELECT user_id FROM articles WHERE id = ?");
            $stmt_check_author->execute([$article_id]);
            $original_article_user_id = $stmt_check_author->fetchColumn();

            if ($original_article_user_id === false) {
                 $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Article not found.']];
                 header('Location: /index.php?page=manage_articles');
                 exit;
            }

            if ($original_article_user_id != $current_user_id && $user_role !== 'admin') {
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You do not have permission to edit this article.']];
                header('Location: /index.php?page=manage_articles');
                exit;
            }

            $sql = "UPDATE articles SET title = ?, full_text = ?, updated_at = NOW() WHERE id = ?";
            $params = [$title_form, $content_form, $article_id];

            $stmt_update = $db->prepare($sql);
            if ($stmt_update->execute($params)) {
                $_SESSION['flash_messages'] = [['type' => 'success', 'text' => 'Article updated successfully.']];
                unset($_SESSION['csrf_token_edit_article']); // Unset token on success
                header('Location: /index.php?page=manage_articles');
                exit;
            } else {
                // Set flash message for the redirect
                $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Failed to update article. Please try again.']];
                error_log("Failed to update article ID: $article_id. PDO Error: " . print_r($stmt_update->errorInfo(), true));
                $_SESSION['form_data'] = $_POST; // Keep form data for repopulation
            }
        } catch (PDOException $e) {
            // Set flash message for the redirect
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database error during article update.']];
            error_log("PDOException in edit_article.php (POST): " . $e->getMessage());
            $_SESSION['form_data'] = $_POST;
        }
        // If update failed or DB error, redirect back (flash messages are already set)
        header('Location: /index.php?page=edit_article&id=' . $article_id);
        exit;

    } else {
        // Validation errors occurred
        $_SESSION['form_validation_errors'] = $form_validation_errors;
        $_SESSION['form_data'] = $_POST; 
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Please correct the errors below.']];
        header('Location: /index.php?page=edit_article&id=' . $article_id); 
        exit;
    }
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $article_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$article_id_get) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'No article ID specified or invalid ID.']];
        header('Location: /index.php?page=manage_articles');
        exit;
    }
    $article_id = $article_id_get; 

    try {
        $stmt = $db->prepare("SELECT id, title, full_text, user_id FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        $article_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article_data) {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Article not found.']];
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        if ($article_data['user_id'] != $current_user_id && $user_role !== 'admin') {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You do not have permission to edit this article.']];
            header('Location: /index.php?page=manage_articles');
            exit;
        }

        // Populate form fields: use session form_data if available (from failed POST), otherwise DB data
        $title_form = $form_data['title'] ?? $article_data['title'] ?? ''; 
        $content_form = $form_data['content'] ?? ($article_data['full_text'] ?? ''); 

        // CSRF token is already set/regenerated at the top for GET requests.

    } catch (PDOException $e) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Database error while fetching article: ' . $e->getMessage()]];
        error_log("PDOException in edit_article.php (GET) for article ID $article_id: " . $e->getMessage());
        header('Location: /index.php?page=manage_articles');
        exit;
    }
} else {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Invalid request method.']];
    header('Location: /index.php?page=manage_articles');
    exit;
}

?>

<div class="form-page-container"> <?php // Changed from form-container ?>
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php 
    // The global $page_messages variable (populated in public/index.php) will be displayed
    // by the theme's template (e.g., header.php or template.php).
    // So, no need for a specific flash message loop here for general messages.
    /*
    <?php if (!empty($flash_messages)): ?>
        <?php foreach ($flash_messages as $message): ?>
            <div class="messages <?php echo htmlspecialchars($message['type']); ?>">
                <p><?php echo htmlspecialchars($message['text']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    */
    ?>

    <?php // Display field-specific validation errors if they were set (typically after a redirect from POST) ?>
    <?php if (!empty($form_validation_errors) && is_array($form_validation_errors)): ?>
        <?php // This is still relevant for displaying field-specific errors directly on this form page.
              // The general "Please correct errors" message would come from $page_messages.
        /*
        <div class="messages errors">
            <p>Please correct the following field errors:</p>
            <ul>
                <?php foreach ($form_validation_errors as $field_error):?>
                     <li><?php echo htmlspecialchars($field_error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        */ ?>
    <?php endif; ?>


    <form action="/index.php?page=edit_article" method="POST">
        <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($article_id ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">

        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" class="form-control <?php echo isset($form_validation_errors['title']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title_form); ?>" required>
            <?php if (isset($form_validation_errors['title'])): ?>
                <p class="error-text"><?php echo htmlspecialchars($form_validation_errors['title']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="10" class="form-control <?php echo isset($form_validation_errors['content']) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($content_form); ?></textarea>
            <?php if (isset($form_validation_errors['content'])): ?>
                <p class="error-text"><?php echo htmlspecialchars($form_validation_errors['content']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">Update Article</button>
            <a href="/index.php?page=manage_articles" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>