<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';


$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();


session_start();
session_regenerate_id(true);


$_SESSION['flash_messages'][] = ['type' => 'success', 'text' => 'You have successfully logged out.'];



$redirect_url = '/index.php?page=home';
header("Location: " . $redirect_url);
exit();
?>