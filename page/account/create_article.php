<?php

use App\Models\Article;
use App\Models\Category;
use App\Lib\Database;
use App\Lib\FlashMessageService;

$pageTitle = "Create New Article";

$form_validation_errors = $_SESSION['form_validation_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
$selected_categories_from_session = $form_data['categories'] ?? [];

unset($_SESSION['form_validation_errors'], $_SESSION['form_data']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['csrf_token_create_article'])) {
    $_SESSION['csrf_token_create_article'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_create_article'];

if (!isset($_SESSION['user_id'])) {
    $flashService = new FlashMessageService();
    $flashService->addError('You must be logged in to create an article.');
    $redirect_url = urlencode('/index.php?page=create_article');
    header('Location: /index.php?page=login&redirect=' . $redirect_url);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$database_handler = new Database();
$flashMessageService = new FlashMessageService();

$all_categories = Category::findAll($database_handler);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_create_article'], $_POST['csrf_token'])) {
        $flashMessageService->addError('Invalid CSRF token. Please try again.');
        header('Location: /index.php?page=create_article');
        exit;
    }

    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $short_description = trim(filter_input(INPUT_POST, 'short_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $full_text = trim(filter_input(INPUT_POST, 'full_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $date_input = trim(filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $selected_category_ids = filter_input(INPUT_POST, 'categories', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY) ?? [];

    $_SESSION['form_data'] = [
        'title' => $title,
        'short_description' => $short_description,
        'full_text' => $full_text,
        'date' => $date_input,
        'categories' => $selected_category_ids
    ];
    $selected_categories_from_session = $selected_category_ids;

    $current_form_validation_errors = [];
    if (empty($title)) {
        $current_form_validation_errors['title'] = 'Title is required.';
    }
    if (empty($full_text)) {
        $current_form_validation_errors['full_text'] = 'Full text is required.';
    }
    if (empty($date_input)) {
        $current_form_validation_errors['date'] = 'Publication date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_input) || !strtotime($date_input)) {
        $current_form_validation_errors['date'] = 'Invalid date format. Please use YYYY-MM-DD.';
    }

    $_SESSION['form_validation_errors'] = $current_form_validation_errors;
    $has_general_errors = !empty(array_filter($_SESSION['flash_messages'] ?? [], fn($m) => $m['type'] === 'error'));

    if (empty($current_form_validation_errors) && !$has_general_errors) {
        $articleData = [
            'title' => $title,
            'short_description' => $short_description,
            'full_text' => $full_text,
            'date' => $date_input,
            'user_id' => $current_user_id
        ];
        $newArticleId = Article::create($database_handler, $articleData);

        if ($newArticleId) {
            $newArticle = Article::findById($database_handler, $newArticleId);
            if ($newArticle && !empty($selected_category_ids)) {
                $newArticle->setCategories($database_handler, $selected_category_ids);
            }

            $flashMessageService->addSuccess('Article created successfully!');
            unset($_SESSION['csrf_token_create_article'], $_SESSION['form_data']);
            header('Location: /index.php?page=news&id=' . $newArticleId);
            exit;
        } else {
            $flashMessageService->addError('Failed to create article. An internal error occurred or database issue.');
            header('Location: /index.php?page=create_article');
            exit;
        }
    } else {
        header('Location: /index.php?page=create_article');
        exit;
    }
}
?>

<div class="page-container create-article-page">
    <a href="/index.php?page=manage_articles" class="button button-secondary form-page-back-link">&laquo; Back to Manage Articles</a>
    <h1 style="margin-top: <?php echo (isset($_GET['from']) && $_GET['from'] === 'manage') || !empty($page_messages) ? 'var(--spacing-3)' : '0'; ?>;"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php // Используем $page_messages, переданную из index.php
    if (!empty($page_messages)): ?>
        <?php foreach ($page_messages as $message): ?>
            <div class="messages <?php echo htmlspecialchars($message['type']); ?>">
                <p><?php echo htmlspecialchars($message['text']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form action="/index.php?page=create_article" method="POST" enctype="multipart/form-data" class="styled-form article-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group <?php echo isset($form_validation_errors['title']) ? 'has-error' : ''; ?>">
            <label for="title" class="form-label">Title <span class="required-asterisk">*</span></label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
            <?php if (isset($form_validation_errors['title'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['title']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['short_description']) ? 'has-error' : ''; ?>">
            <label for="short_description" class="form-label">Short Description (Preview)</label>
            <textarea id="short_description" name="short_description" class="form-control" rows="3"><?php echo htmlspecialchars($form_data['short_description'] ?? ''); ?></textarea>
            <?php if (isset($form_validation_errors['short_description'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['short_description']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['full_text']) ? 'has-error' : ''; ?>">
            <label for="full_text" class="form-label">Full Text <span class="required-asterisk">*</span></label>
            <textarea id="full_text" name="full_text" class="form-control" rows="10" required><?php echo htmlspecialchars($form_data['full_text'] ?? ''); ?></textarea>
            <?php if (isset($form_validation_errors['full_text'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['full_text']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['date']) ? 'has-error' : ''; ?>">
            <label for="date" class="form-label">Publication Date <span class="required-asterisk">*</span></label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($form_data['date'] ?? date('Y-m-d')); ?>" required>
            <?php if (isset($form_validation_errors['date'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['date']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($form_validation_errors['categories']) ? 'has-error' : ''; ?>">
            <label class="form-label">Categories</label>
            <?php if (!empty($all_categories)): ?>
                <div class="category-checkbox-group">
                    <?php foreach ($all_categories as $category): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" 
                                   id="category_<?php echo htmlspecialchars($category->id); ?>" 
                                   name="categories[]" 
                                   value="<?php echo htmlspecialchars($category->id); ?>"
                                   <?php echo in_array($category->id, $selected_categories_from_session) ? 'checked' : ''; ?>>
                            <label for="category_<?php echo htmlspecialchars($category->id); ?>"><?php echo htmlspecialchars($category->name); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-categories-message">No categories available. You can add them in the admin panel.</p>
            <?php endif; ?>
            <?php if (isset($form_validation_errors['categories'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($form_validation_errors['categories']); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">Create Article</button>
            <a href="/index.php?page=news" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>