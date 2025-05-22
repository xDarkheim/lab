<?php

require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\FlashMessageService;

$flashMessageService = new FlashMessageService();

$database_handler = new Database();
$db = $database_handler->getConnection();

if (!$db) {
    $flashMessageService->addError('Database connection error.');
    header('Location: /index.php?page=manage_articles');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_delete_article']) || !hash_equals($_SESSION['csrf_token_delete_article'], $_POST['csrf_token'])) {
        $flashMessageService->addError('CSRF Error: Invalid token. Please try again.');
        header('Location: /index.php?page=manage_articles');
        exit;
    }
    unset($_SESSION['csrf_token_delete_article']);

    if (isset($_POST['article_id']) && filter_var($_POST['article_id'], FILTER_VALIDATE_INT)) {
        $article_id = (int)$_POST['article_id'];
        try {
            $stmt_check = $db->prepare("SELECT id FROM articles WHERE id = ?");
            $stmt_check->execute([$article_id]);

            if ($stmt_check->rowCount() > 0) {
                $stmt_delete = $db->prepare("DELETE FROM articles WHERE id = ?");
                if ($stmt_delete->execute([$article_id])) {
                    if ($stmt_delete->rowCount() > 0) {
                        $flashMessageService->addSuccess('Article successfully deleted.');
                    } else {
                        $flashMessageService->addError('Article not found or already deleted during the process.');
                    }
                } else {
                    $flashMessageService->addError('Failed to delete article. Please try again.');
                    error_log("Failed to delete article ID: $article_id. PDO Error: " . print_r($stmt_delete->errorInfo(), true));
                }
            } else {
                $flashMessageService->addError('Article with the specified ID not found.');
            }
        } catch (PDOException $e) {
            $flashMessageService->addError('Database error while deleting article: ' . $e->getMessage());
            error_log("PDOException in delete_article.php for article ID $article_id: " . $e->getMessage());
        }
    } else {
        $flashMessageService->addError('Invalid article ID for deletion.');
    }
} else {
    $flashMessageService->addError('Invalid request method.');
}

header('Location: /index.php?page=manage_articles');
exit;
?>