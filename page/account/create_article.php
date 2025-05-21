<?php

use App\Models\Article;
use App\Lib\Database;

$pageTitle = "Create New Article";

$form_validation_errors = $_SESSION['form_validation_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

$flash_messages = $_SESSION['flash_messages'] ?? [];

unset($_SESSION['form_validation_errors'], $_SESSION['form_data'], $_SESSION['flash_messages']);


if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_create_article'])) {
    $_SESSION['csrf_token_create_article'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_create_article'];


if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'You must be logged in to create an article.']];
    $redirect_url = urlencode('/index.php?page=create_article');
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$database_handler = new Database();
$db_connection_error = !$database_handler->getConnection();

if ($db_connection_error && $_SERVER['REQUEST_METHOD'] === 'GET') { 
    $flash_messages[] = ['type' => 'error', 'text' => 'Failed to connect to the database. Article creation is currently unavailable.'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db_connection_error) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Failed to connect to the database. Please try again later.']];
        $_SESSION['form_data'] = $_POST; 
        $_SESSION['csrf_token_create_article'] = bin2hex(random_bytes(32)); 
        header('Location: /index.php?page=create_article');
        exit;
    }


    $current_form_validation_errors = [];

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_create_article'], $_POST['csrf_token'])) {
        $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Invalid security token. Please refresh and try again.']];
    } else {
        $title = trim($_POST['title'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $full_text = trim($_POST['full_text'] ?? '');
        $date = trim($_POST['date'] ?? '');

        if (empty($title)) $current_form_validation_errors['title'] = "Title is required.";
        if (mb_strlen($title) > 255) $current_form_validation_errors['title'] = "Title cannot exceed 255 characters.";
        if (mb_strlen($short_description) > 500) $current_form_validation_errors['short_description'] = "Short description cannot exceed 500 characters.";
        if (empty($full_text)) $current_form_validation_errors['full_text'] = "Full text is required.";
        if (empty($date)) {
            $current_form_validation_errors['date'] = "Date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $current_form_validation_errors['date'] = "Invalid date format. Please use YYYY-MM-DD.";
        }
    }

    $has_general_errors = !empty(array_filter($_SESSION['flash_messages'] ?? [], fn($m) => $m['type'] === 'error'));

    if (empty($current_form_validation_errors) && !$has_general_errors) {
        $articleData = [
            'title' => $title,
            'short_description' => $short_description,
            'full_text' => $full_text,
            'date' => $date,
            'user_id' => $current_user_id
        ];
        $newArticleId = Article::create($database_handler, $articleData);

        if ($newArticleId) {
            $_SESSION['flash_messages'] = [['type' => 'success', 'text' => 'Article created successfully!']];
            unset($_SESSION['csrf_token_create_article']); 
            header('Location: /index.php?page=news&id=' . $newArticleId);
            exit;
        } else {
            $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Failed to create article. An internal error occurred or database issue.']];
        }
    } else {
        if (!empty($current_form_validation_errors) && !$has_general_errors) {
             $_SESSION['flash_messages'] = [['type' => 'error', 'text' => 'Please correct the errors highlighted below.']];
        }
    }

    $_SESSION['form_validation_errors'] = $current_form_validation_errors; 
    $_SESSION['form_data'] = $_POST; 
    $_SESSION['csrf_token_create_article'] = bin2hex(random_bytes(32));
    header('Location: /index.php?page=create_article');
    exit;
}
?>

<div class="form-page-container">
    <a href="/index.php?page=manage_articles" class="button button-secondary form-page-back-link">&laquo; Back to Manage Articles</a>
    <h1 style="margin-top: <?php echo (isset($_GET['from']) && $_GET['from'] === 'manage') || !empty($flash_messages) ? 'var(--spacing-3)' : '0'; ?>;"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php if (!empty($flash_messages)): ?>
        <?php foreach ($flash_messages as $message): ?>
            <div class="messages <?php echo htmlspecialchars($message['type']); ?>">
                <p><?php echo htmlspecialchars($message['text']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php 
    $can_show_form = !($db_connection_error && empty($form_validation_errors) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($flash_messages));
    if ($can_show_form): 
    ?>
    <form action="/index.php?page=create_article" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group">
            <label for="title">Title <span class="text-danger">*</span></label>
            <input type="text" id="title" name="title" class="form-control <?php echo isset($form_validation_errors['title']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
            <?php if (isset($form_validation_errors['title'])): ?><p class="error-text" style="color: var(--color-danger); font-size: 0.85em; margin-top: var(--spacing-1);"><?php echo htmlspecialchars($form_validation_errors['title']); ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="short_description">Short Description (Optional)</label>
            <textarea id="short_description" name="short_description" class="form-control <?php echo isset($form_validation_errors['short_description']) ? 'is-invalid' : ''; ?>" rows="3"><?php echo htmlspecialchars($form_data['short_description'] ?? ''); ?></textarea>
            <?php if (isset($form_validation_errors['short_description'])): ?><p class="error-text" style="color: var(--color-danger); font-size: 0.85em; margin-top: var(--spacing-1);"><?php echo htmlspecialchars($form_validation_errors['short_description']); ?></p><?php endif; ?>
            <small class="form-text text-muted">A brief summary that might be shown in listings.</small>
        </div>

        <div class="form-group">
            <label for="full_text">Full Text <span class="text-danger">*</span></label>
            <textarea id="full_text" name="full_text" class="form-control <?php echo isset($form_validation_errors['full_text']) ? 'is-invalid' : ''; ?>" rows="10" required><?php echo htmlspecialchars($form_data['full_text'] ?? ''); ?></textarea>
            <?php if (isset($form_validation_errors['full_text'])): ?><p class="error-text" style="color: var(--color-danger); font-size: 0.85em; margin-top: var(--spacing-1);"><?php echo htmlspecialchars($form_validation_errors['full_text']); ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="date">Publication Date <span class="text-danger">*</span></label>
            <input type="date" id="date" name="date" class="form-control <?php echo isset($form_validation_errors['date']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($form_data['date'] ?? date('Y-m-d')); ?>" required>
            <?php if (isset($form_validation_errors['date'])): ?><p class="error-text" style="color: var(--color-danger); font-size: 0.85em; margin-top: var(--spacing-1);"><?php echo htmlspecialchars($form_validation_errors['date']); ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">Create Article</button>
            <a href="/index.php?page=manage_articles" class="button button-secondary">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <?php if ($db_connection_error && empty($flash_messages) && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
            <div class="messages error">
                <p>Failed to connect to the database. Article creation is currently unavailable.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>