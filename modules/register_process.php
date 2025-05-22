<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Auth;
use App\Lib\FlashMessageService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database_handler = new Database();
    $auth = new Auth($database_handler);
    $flashMessageService = new FlashMessageService();

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $registrationData = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'password_confirm' => $password_confirm
    ];
    $result = $auth->register($registrationData);

    if ($result['success']) {
        $flashMessageService->addSuccess($result['message'] ?? 'Registration successful! You can now log in.');
        header("Location: /index.php?page=login");
        exit();
    } else {
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $flashMessageService->addError($error);
            }
        }
        $_SESSION['form_data'] = $result['data'] ?? ['username' => $username, 'email' => $email];
        header("Location: /index.php?page=register");
        exit();
    }
} else {
    header("Location: /index.php?page=register");
    exit();
}
?>