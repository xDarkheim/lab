<?php

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=account_dashboard");
    exit();
}

if (empty($_SESSION['csrf_token_login'])) {
    $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
}
$csrf_token_login = $_SESSION['csrf_token_login'];

$errors_login = $_SESSION['login_errors'] ?? []; // Use 'login_errors'
$submitted_username_or_email = $_SESSION['form_data_login']['username_or_email'] ?? ''; // Use 'form_data_login' and 'username_or_email'
$submitted_remember_me = isset($_SESSION['form_data_login']['remember_me']) && $_SESSION['form_data_login']['remember_me'];

$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

?>

<div class="auth-page-container">
    <h1 class="page-title auth-page-main-title">Login to Your Account</h1>
    <div class="auth-form-card">      
        <form action="/modules/login_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_login); ?>">
            <div class="form-group">
                <label for="login_username_or_email" class="form-label">Username or Email:</label>
                <div class="input-group">
                    <span class="input-group-icon">👤</span>
                    <input type="text" id="login_username_or_email" name="username_or_email" class="form-control <?php echo !empty($errors_login['username_or_email']) || !empty($errors_login['credentials']) || !empty($errors_login['csrf']) ? 'is-invalid' : ''; ?>" placeholder="e.g., yourname or email@example.com" required value="<?php echo htmlspecialchars($submitted_username_or_email); ?>">
                </div>
                <?php if (!empty($errors_login['username_or_email'])): ?>
                    <div class="error-text"><?php echo htmlspecialchars($errors_login['username_or_email']['text']); ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="login_password" class="form-label">Password:</label>
                <div class="input-group">
                    <span class="input-group-icon">🔑</span>
                    <input type="password" id="login_password" name="password" class="form-control <?php echo !empty($errors_login['password']) || !empty($errors_login['credentials']) ? 'is-invalid' : ''; ?>" placeholder="Enter your password" required>
                </div>
                <?php if (!empty($errors_login['password'])): ?>
                    <div class="error-text"><?php echo htmlspecialchars($errors_login['password']['text']); ?></div>
                <?php endif; ?>
                <?php if (!empty($errors_login['credentials'])): ?>
                    <div class="error-text"><?php echo htmlspecialchars($errors_login['credentials']['text']); ?></div>
                <?php endif; ?>
                 <?php if (!empty($errors_login['csrf'])):  ?>
                    <div class="error-text"><?php echo htmlspecialchars($errors_login['csrf']['text']); ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group form-checkbox-group">
                <input type="checkbox" id="login_remember_me" name="remember_me" value="1" <?php echo $submitted_remember_me ? 'checked' : ''; ?>>
                <label for="login_remember_me">Remember me</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="button button-primary button-block">Login</button>
            </div>
        </form>
        <div class="auth-form-footer">
            <p><a href="/index.php?page=forgot_password">Forgot your password?</a></p>
            <p>Don't have an account? <a href="/index.php?page=register">Register here</a></p>
        </div>
        
    </div>
        <div class="auth-warning-message">
        <p><strong>Important Security Notice:</strong></p>
        <p>Never share your login details with anyone. Our administrators will <strong>never</strong> ask for your password or other sensitive information. Protect your account by keeping your credentials confidential.</p>
    </div>
</div>

<?php
unset($_SESSION['login_errors']);
unset($_SESSION['form_data_login']);
?>