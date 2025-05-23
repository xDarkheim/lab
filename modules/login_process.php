<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Auth;
use App\Lib\FlashMessageService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database_handler = new Database();
    $auth = new Auth($database_handler);
    $flashMessageService = new FlashMessageService();

    $identifier = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $flashMessageService->addError('Username and password cannot be empty.');
        header("Location: /index.php?page=login");
        exit();
    }

    $loginResult = $auth->login($identifier, $password);

    if ($loginResult && isset($loginResult['success']) && $loginResult['success']) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $loginResult['user_id'];
        $_SESSION['username'] = $loginResult['username'];
        $_SESSION['user_role'] = $loginResult['role'];

        $flashMessageService->addSuccess('Login successful. Welcome back, ' . htmlspecialchars($loginResult['username']) . '!');

        header('Location: /index.php?page=account_dashboard');
        exit;
    } else {
        $_SESSION['login_errors'] = ['credentials' => 'Invalid username/email or password.'];
        if (isset($loginResult['errors']) && is_array($loginResult['errors'])) {
            $_SESSION['login_errors']['details'] = $loginResult['errors'];
        }
        $_SESSION['form_data_login_username'] = $identifier;

        $flashMessageService->addError('Invalid username/email or password.');

        header('Location: /index.php?page=login');
        exit();
    }
} else {
    header("Location: /index.php?page=login");
    exit();
}

?>