<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Models\Comments;
// use App\Models\User; // Already imported or available via autoloader

// Default redirect URL (e.g., news overview or home)
$redirect_url = '/index.php?page=news';
if (isset($_POST['article_id']) && filter_var($_POST['article_id'], FILTER_VALIDATE_INT)) {
    $redirect_url = '/index.php?page=news&id=' . (int)$_POST['article_id'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Invalid request method.'];
    header('Location: ' . $redirect_url);
    exit;
}


if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_add_comment']) || !hash_equals($_SESSION['csrf_token_add_comment'], $_POST['csrf_token'])) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'CSRF token validation failed. Please try again.'];
    $_SESSION['csrf_token_add_comment'] = bin2hex(random_bytes(32));
    header('Location: ' . $redirect_url);
    exit;
}

$_SESSION['csrf_token_add_comment'] = bin2hex(random_bytes(32));



if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'You must be logged in to post a comment.'];
    header('Location: /index.php?page=login&return_to=' . urlencode($redirect_url)); // Optionally redirect to login
    exit;
}


$article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
$user_id_form = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$author_name = filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_SPECIAL_CHARS);
$content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

if (!$article_id || !$user_id_form || empty($author_name) || empty($content)) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'All fields are required to post a comment.'];
    header('Location: ' . $redirect_url);
    exit;
}


if ($user_id_form !== (int)$_SESSION['user_id']) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'User authentication mismatch. Cannot post comment.'];
    error_log("Comment submission: User ID mismatch. Form: {$user_id_form}, Session: {$_SESSION['user_id']}");
    header('Location: ' . $redirect_url);
    exit;
}

$database_handler = new Database();
if (!$database_handler->getConnection()) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Database connection error. Could not post comment.'];
    error_log("add_comment_process.php: Database connection failed.");
    header('Location: ' . $redirect_url);
    exit;
}

$comment_data = [
    'article_id' => $article_id,
    'user_id' => (int)$_SESSION['user_id'],
    'author_name' => $author_name,
    'content' => $content,
    'status' => 'approved' 
];

$comment_id = Comments::create($database_handler, $comment_data);

if ($comment_id) {
    $_SESSION['flash_messages'][] = ['type' => 'success', 'text' => 'Comment added successfully!'];
    $redirect_url .= '#comment-' . $comment_id;
} else {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Failed to add comment. Please try again.'];
    error_log("add_comment_process.php: Failed to add comment for article ID {$article_id} by user ID {$_SESSION['user_id']}");
}

header('Location: ' . $redirect_url);
exit;
?>