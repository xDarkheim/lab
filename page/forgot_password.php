<?php

use App\Models\User;

if (!defined('SITE_URL')) {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $scheme . '://' . $host);
}

$page_title = "Forgot Password";

if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in forgot_password.php");
}
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in forgot_password.php");
    if (isset($flashMessageService)) $flashMessageService->addError("A system error occurred. Please try again later.");
}

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=account_dashboard");
    exit();
}

$csrf_token_name = 'csrf_token_forgot_password';
if (empty($_SESSION[$csrf_token_name])) {
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_name];

$email_sent_successfully = false;
$post_error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($database_handler)) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION[$csrf_token_name], $_POST['csrf_token'])) {
        $post_error_message = 'Invalid security token. Please try again.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $post_error_message = 'Please enter a valid email address.';
        } else {
            $userModel = new User($database_handler);
            $user = $userModel->findByEmail($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                if ($user->setPasswordResetToken($token)) {
                    $reset_link = SITE_URL . "/index.php?page=reset_password&token=" . urlencode($token) . "&email=" . urlencode($email);
                    $subject = "Password Reset Request - " . ($site_settings_from_db['site_name'] ?? 'Your Site');
                    $message_body = "Hello " . htmlspecialchars($user->getUsername()) . ",\n\n";
                    $message_body .= "You requested a password reset. Click the link below to reset your password:\n";
                    $message_body .= $reset_link . "\n\n";
                    $message_body .= "This link will expire in 1 hour.\n\n";
                    $message_body .= "If you did not request this, please ignore this email.\n\n";
                    $message_body .= "Regards,\nThe " . ($site_settings_from_db['site_name'] ?? 'Your Site') . " Team";
                    
                    $headers = "From: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'yourdomain.com') . "\r\n";
                    $headers .= "Reply-To: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'yourdomain.com') . "\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    if (mail($user->getEmail(), $subject, $message_body, $headers)) {
                        $email_sent_successfully = true;
                    } else {
                        $post_error_message = 'Could not send password reset email. Please try again later or contact support.';
                        error_log("Forgot Password: Failed to send mail to " . $user->getEmail());
                    }
                } else {
                    $post_error_message = 'Could not generate password reset token. Please try again.';
                }
            } else {

                error_log("Forgot Password: No account found for email " . htmlspecialchars($email));
                $email_sent_successfully = true; 
            }
        }
    }
    $_SESSION[$csrf_token_name] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION[$csrf_token_name];
}

?>

<div class="auth-page-container auth-layout-split">
    <div class="auth-layout-column auth-layout-column-info">
        <h1 class="page-title auth-page-main-title">Forgot Your Password?</h1>
        <div class="auth-info-content">
            <p>No problem! Enter your email address below, and we'll send you a link to reset your password.</p>
            <p>If you remember your password, you can <a href="/index.php?page=login">log in here</a>.</p>
        </div>
    </div>

    <div class="auth-layout-column auth-layout-column-form">
        <div class="auth-form-card">
            <h2 class="auth-form-title">Reset Password</h2>

            <?php
            if (!empty($page_messages)) {
                echo '<div class="page-messages-container-auth">';
                foreach ($page_messages as $message) {
                    $typeClass = 'info';
                    switch (strtolower($message['type'])) {
                        case 'success': $typeClass = 'success'; break;
                        case 'error': case 'errors': $typeClass = 'errors'; break;
                        case 'warning': $typeClass = 'warning'; break;
                    }
                    echo '<div class="messages ' . htmlspecialchars($typeClass) . '">';
                    echo '<p>' . htmlspecialchars($message['text']) . '</p>';
                    echo '</div>';
                }
                echo '</div>';
            }
            if ($post_error_message): ?>
                <div class="messages errors">
                    <p><?php echo htmlspecialchars($post_error_message); ?></p>
                </div>
            <?php endif;
            if ($email_sent_successfully && !$post_error_message): ?>
                <div class="messages success">
                    <p>If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).</p>
                </div>
            <?php endif;
            if (!($email_sent_successfully && !$post_error_message) || $post_error_message): ?>
                <form action="/index.php?page=forgot_password" method="POST" id="forgotPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Your Email Address:</label>
                        <div class="input-group">
                            <span class="input-group-icon">✉️</span>
                            <input type="email" name="email" id="email" class="form-control <?php echo $post_error_message ? 'is-invalid' : ''; ?>" 
                                   placeholder="e.g., yourname@example.com" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); // Сохраняем введенное значение при ошибке ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary button-block">Send Reset Link</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="auth-form-footer">
                <p>Remembered your password? <a href="/index.php?page=login">Sign In</a></p>
                <p>Don't have an account? <a href="/index.php?page=register">Create one</a></p>
            </div>
        </div>
    </div>
</div>