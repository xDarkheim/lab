<?php
// session_start(); // Already started in bootstrap.php

// Authentication check (also performed in Router.php for 'auth_required' routes)
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=home&login_required=true");
    exit;
}
use App\Lib\Database;
use App\Controllers\ProfileController;

$userId = (int)$_SESSION['user_id'];

// Create a Database instance (ideally via Dependency Injection or Service Locator)
$database_handler = new Database();

// Create a controller instance
$profileController = new ProfileController($database_handler, $userId);

$page_message = ['text' => '', 'type' => ''];
$userData = $profileController->getCurrentUserData();

// CSRF tokens for forms on this page
if (!isset($_SESSION['csrf_token_change_password'])) {
    $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['csrf_token_update_email'])) {
    $_SESSION['csrf_token_update_email'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        if (!isset($_POST['csrf_token_change_password']) || !hash_equals($_SESSION['csrf_token_change_password'], $_POST['csrf_token_change_password'])) {
            $page_message['text'] = 'Security error: Invalid CSRF token for password change. Please refresh and try again.';
            $page_message['type'] = 'error';
        } else {
            $page_message = $profileController->handleChangePasswordRequest($_POST);
            // Regenerate token after use, regardless of success/failure, if page reloads with form
            $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
        }
    } elseif (isset($_POST['update_email'])) {
        if (!isset($_POST['csrf_token_update_email']) || !hash_equals($_SESSION['csrf_token_update_email'], $_POST['csrf_token_update_email'])) {
            $page_message['text'] = 'Security error: Invalid CSRF token for email update. Please refresh and try again.';
            $page_message['type'] = 'error';
        } else {
            $emailData = ['email' => $_POST['email'] ?? ''];
            $page_message = $profileController->handleUpdateDetailsRequest($emailData);
            if ($page_message['type'] === 'success' || $page_message['type'] === 'info') {
                $userData = $profileController->getCurrentUserData(); // Refresh email
            }
            // Regenerate token
            $_SESSION['csrf_token_update_email'] = bin2hex(random_bytes(32));
        }
    }
}

// Handle case where user data is not loaded
if (!$userData) {
    $userData = ['username' => 'N/A', 'email' => 'N/A']; // Default values
    if (empty($page_message['text'])) { // Show error only if no other message exists
      $page_message = ['text' => 'Failed to load user data.', 'type' => 'error'];
    }
    error_log("Account Settings Page: Could not load user data for user ID: " . $userId);
}
?>
<?php // HTML structure remains in theme files header.php and footer.php ?>
<?php // Title and other metadata are assumed to be set in public/index.php ?>
<?php // Styles should be part of your style.css, only page-specific styles here if needed ?>

<div class="form-page-container account-settings-container">
    <h1>Account Settings</h1>

    <?php if (!empty($page_message['text'])): ?>
        <div class="messages <?php echo htmlspecialchars($page_message['type'] === 'success' ? 'success' : ($page_message['type'] === 'info' ? 'info' : 'errors')); ?>">
            <p><?php echo htmlspecialchars($page_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <div class="settings-section">
        <h2>Change Password</h2>
        <form action="/index.php?page=account_settings" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_change_password" value="<?php echo htmlspecialchars($_SESSION['csrf_token_change_password']); ?>">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
            </div>
            <div class="form-actions">
                <button type="submit" name="change_password" class="button button-primary">Change Password</button>
            </div>
        </form>
    </div>

    <hr class="section-divider">

    <div class="settings-section">
        <h2>Change Email</h2>
        <form action="/index.php?page=account_settings" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_update_email" value="<?php echo htmlspecialchars($_SESSION['csrf_token_update_email']); ?>">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_email" class="button button-primary">Update Email</button>
            </div>
        </form>
    </div>

    <div class="page-actions">
        <p><a href="/index.php?page=account_dashboard" class="button button-secondary">Back to Dashboard</a></p>
    </div>
</div>