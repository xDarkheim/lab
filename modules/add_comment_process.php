<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Models\Comments;
use App\Lib\FlashMessageService;

$redirect_url = '/index.php?page=news';
if (isset($_POST['article_id']) && filter_var($_POST['article_id'], FILTER_VALIDATE_INT)) {
    $redirect_url = '/index.php?page=news&id=' . (int)$_POST['article_id'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('Invalid request method.');
    header('Location: ' . $redirect_url);
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_add_comment']) || !hash_equals($_SESSION['csrf_token_add_comment'], $_POST['csrf_token'])) {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('CSRF token validation failed. Please try again.');
    $_SESSION['csrf_token_add_comment'] = bin2hex(random_bytes(32));
    header('Location: ' . $redirect_url);
    exit;
}

$_SESSION['csrf_token_add_comment'] = bin2hex(random_bytes(32));

if (!isset($_SESSION['user_id'])) {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('You must be logged in to post a comment.');
    header('Location: /index.php?page=login&return_to=' . urlencode($redirect_url));
    exit;
}

$article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
$user_id_form = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$author_name = filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_SPECIAL_CHARS);
$content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

$flashMessageService = new FlashMessageService();

if (!$article_id || !$user_id_form || empty($author_name) || empty($content)) {
    $flashMessageService->addError('All required fields to post a comment are not filled.');
    header('Location: ' . $redirect_url);
    exit;
}

if ($user_id_form !== (int)$_SESSION['user_id']) {
    $flashMessageService->addError('User authentication mismatch. Cannot post comment.');
    error_log("Comment submission: User ID mismatch. Form: {$user_id_form}, Session: {$_SESSION['user_id']}");
    header('Location: ' . $redirect_url);
    exit;
}

$database_handler = new Database();
if (!$database_handler->getConnection()) {
    $flashMessageService->addError('Database connection error. Could not post comment.');
    error_log("add_comment_process.php: Database connection failed.");
    header('Location: ' . $redirect_url);
    exit;
}

$commentModel = new Comments($database_handler);

$status = Comments::STATUS_PENDING;

if ($commentModel->addComment($article_id, $user_id, $content, $author_name, $status)) {
    $flashMessageService->addSuccess('Comment added successfully!');
    $redirect_url .= '#comment-' . $comment_id;
} else {
    $flashMessageService->addError('Failed to add comment. Please try again.');
    error_log("add_comment_process.php: Failed to add comment for article ID {$article_id} by user ID {$_SESSION['user_id']}");
}

header('Location: ' . $redirect_url);
exit;
?>