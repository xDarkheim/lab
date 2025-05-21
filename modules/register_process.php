<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Auth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /index.php?page=register");
    exit();
}

// Use the specific CSRF token for registration
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_register']) || !hash_equals($_SESSION['csrf_token_register'], $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Invalid CSRF token. Please refresh the page and try again.'];
    header("Location: /index.php?page=register");
    exit();
}

$database_handler = new Database();
$auth = new Auth($database_handler);

$result = $auth->register($_POST);

if($result['success']) {
    $_SESSION['success_message'] = $result['message'];
    unset($_SESSION['csrf_token_register']); // Clear token on success
    header("Location: /index.php?page=login&registration=success"); // Redirect to login after successful registration
    exit();
} else {
    $_SESSION['errors'] = $result['errors'];
    $_SESSION['form_data'] = $result['data'] ?? ['username' => $_POST['username'], 'email' => $_POST['email'] ?? ''];
    header("Location: /index.php?page=register");
    exit();
}
?>