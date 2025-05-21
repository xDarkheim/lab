<?php

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=account_dashboard");
    exit();
}

// Form data (if there were validation errors)
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : ['username' => '', 'email' => ''];
// Errors and success messages from session
$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? null;

// Clear session after use
unset($_SESSION['errors']);
unset($_SESSION['success_message']);
// form_data will be cleared at the end of the file if that's your logic

// Use a common CSRF token for registration
$csrf_token = $_SESSION['csrf_token_register'] ?? ''; // Use csrf_token_register
if (empty($csrf_token)) {
    $_SESSION['csrf_token_register'] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION['csrf_token_register'];
}
?>

<div class="auth-page-container auth-layout-split">
    <div class="auth-layout-column auth-layout-column-info">
        <h1 class="page-title auth-page-main-title">Create Your Account</h1>
        <div class="auth-warning-message">
            <p><strong>Important Security Notice:</strong></p>
            <p>Choose a strong, unique password and never share it. Our administrators will <strong>never</strong> ask for your password or other sensitive information. Be vigilant against phishing attempts.</p>
        </div>
    </div>
    <div class="auth-layout-column auth-layout-column-form">
        <div class="auth-form-card">
            <?php
            // Display error messages
            if (!empty($errors)) {
                echo '<div class="messages errors">';
                foreach ($errors as $error) {
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                }
                echo '</div>';
            }

            // Display success messages (if applicable on this page)
            if ($success_message) {
                echo '<div class="messages success">';
                echo '<p>' . htmlspecialchars($success_message) . '</p>';
                echo '</div>';
            }
            ?>
            <form action="/modules/register_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Username:</label>
                    <div class="input-group">
                        <span class="input-group-icon">👤</span>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Choose a unique username" required value="<?php echo htmlspecialchars($form_data['username']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address:</label>
                    <div class="input-group">
                        <span class="input-group-icon">✉️</span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password:</label>
                    <div class="input-group">
                        <span class="input-group-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm Password:</label>
                    <div class="input-group">
                        <span class="input-group-icon">🔒</span>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-block">Create Account</button>
                </div>
            </form>

            <div class="auth-form-footer">
                <p>Already have an account? <a href="/index.php?page=login">Sign In</a></p>
            </div>
        </div>
    </div>
</div>
<?php unset($_SESSION['form_data']); ?>
