<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=home&login_required=true");
    exit;
}
use App\Controllers\ProfileController;

$userId = (int)$_SESSION['user_id'];

$profileController = new ProfileController($database_handler, $userId, $flashMessageService); 

$page_message = ['text' => '', 'type' => ''];
$userData = $profileController->getCurrentUserData();

if (!isset($_SESSION['csrf_token_change_password'])) {
    $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['csrf_token_update_email'])) {
    $_SESSION['csrf_token_update_email'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        if (!isset($_POST['csrf_token_change_password']) || !hash_equals($_SESSION['csrf_token_change_password'] ?? '', $_POST['csrf_token_change_password'] ?? '')) {
            if(isset($flashMessageService)) $flashMessageService->addError('Security error: Invalid CSRF token for password change.');
            // $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32)); // Не обновлять при ошибке, если хотим дать попытку еще раз с той же формой
        } else {
            $profileController->handleChangePasswordRequest($_POST);
            $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32)); // Обновить токен после успешной проверки/обработки
        }
        header('Location: /index.php?page=account_settings'); // Редирект в любом случае
        exit;
    } elseif (isset($_POST['update_email'])) {
        if (!isset($_POST['csrf_token_update_email']) || !hash_equals($_SESSION['csrf_token_update_email'] ?? '', $_POST['csrf_token_update_email'] ?? '')) {
            if(isset($flashMessageService)) $flashMessageService->addError('Security error: Invalid CSRF token for email update.');
        } else {
            $emailData = ['email' => $_POST['email'] ?? ''];
            $profileController->handleUpdateDetailsRequest($emailData);
            $_SESSION['csrf_token_update_email'] = bin2hex(random_bytes(32));
        }
        header('Location: /index.php?page=account_settings'); // Редирект в любом случае
        exit;
    }
}

if (!$userData) {
    $userData = ['username' => 'N/A', 'email' => 'N/A'];
    if (empty($page_message['text'])) {
      $page_message = ['text' => 'Failed to load user data.', 'type' => 'error'];
    }
    error_log("Account Settings Page: Could not load user data for user ID: " . $userId);
}
?>

<div class="form-page-container account-settings-container">
    <h1>Account Settings</h1>

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