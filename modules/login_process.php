<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Auth;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database_handler = new Database();
    $auth = new Auth($database_handler);

    $identifier = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($identifier, $password);

    if($result['success']){
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['user_role'] = $result['role']; 
        header("Location: /index.php?page=account_dashboard");
        exit();
    } else {
        $_SESSION['login_errors'] = $result['errors'];
        $_SESSION['form_data_login'] = ['username_or_email' => $identifier];
        header("Location: /index.php?page=login");
        exit();
    }
} else {
    header("Location: /index.php?page=login");
    exit();
}

?>