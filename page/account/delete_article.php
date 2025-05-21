<?php


global $db;

if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [];
}

if (!$db) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Database connection error.'];
    header('Location: /index.php?page=manage_articles');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_delete_article']) || !hash_equals($_SESSION['csrf_token_delete_article'], $_POST['csrf_token'])) {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'CSRF Error: Invalid token. Please try again.'];
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
                        $_SESSION['flash_messages'][] = ['type' => 'success', 'text' => 'Article successfully deleted.'];
                    } else {
                        $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Article not found or already deleted during the process.'];
                    }
                } else {
                    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Failed to delete article. Please try again.'];
                    error_log("Failed to delete article ID: $article_id. PDO Error: " . print_r($stmt_delete->errorInfo(), true));
                }
            } else {
                $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Article with the specified ID not found.'];
            }
        } catch (PDOException $e) {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Database error while deleting article: ' . $e->getMessage()];
            error_log("PDOException in delete_article.php for article ID $article_id: " . $e->getMessage());
        }
    } else {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Invalid article ID for deletion.'];
    }
} else {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'text' => 'Invalid request method.'];
}

header('Location: /index.php?page=manage_articles');
exit;
?>