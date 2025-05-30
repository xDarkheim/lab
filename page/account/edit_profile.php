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

if (!isset($_SESSION['csrf_token_edit_profile_info'])) {
    $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['csrf_token_change_password'])) {
    $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile_info'])) {
        if (!isset($_POST['csrf_token_edit_profile_info']) || !hash_equals($_SESSION['csrf_token_edit_profile_info'] ?? '', $_POST['csrf_token_edit_profile_info'] ?? '')) {

            if (isset($flashMessageService)) { 
                $flashMessageService->addError('Security error: Invalid CSRF token for profile info. Please refresh and try again.');
            } else {
                $page_message['text'] = 'Security error: Invalid CSRF token for profile info. Please refresh and try again.';
                $page_message['type'] = 'error';
            }
            header('Location: /index.php?page=account_edit_profile');
            exit;
        }
        $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32));

        $profileInfoData = [
            'email' => $_POST['email'] ?? null,
            'location' => $_POST['location'] ?? null,
            'user_status' => $_POST['user_status'] ?? null,
            'bio' => $_POST['bio'] ?? null,
            'website_url' => $_POST['website_url'] ?? null,
        ];
        $profileController->handleUpdateDetailsRequest($profileInfoData);
    } elseif (isset($_POST['change_password_submit'])) {
        if (!isset($_POST['csrf_token_change_password']) || !hash_equals($_SESSION['csrf_token_change_password'] ?? '', $_POST['csrf_token_change_password'] ?? '')) {

            if (isset($flashMessageService)) {
                $flashMessageService->addError('Security error: Invalid CSRF token for password change. Please refresh and try again.');
            } else {
                $page_message['text'] = 'Security error: Invalid CSRF token for password change. Please refresh and try again.';
                $page_message['type'] = 'error';
            }
            header('Location: /index.php?page=account_edit_profile');
            exit;
        }
        $_SESSION['csrf_token_change_password'] = bin2hex(random_bytes(32));
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $profileController->handleChangePasswordRequest($currentPassword, $newPassword, $confirmPassword);
    } // <<<< THIS CLOSING BRACE WAS ADDED

    if (isset($_POST['update_profile_info']) || isset($_POST['change_password_submit'])) {
        header('Location: /index.php?page=account_edit_profile');
        exit;
    }
}

if (!$userData) {
    $userData = [
        'username' => 'N/A', 'email' => 'N/A',
        'location' => '', 'user_status' => '', 'bio' => '', 'website_url' => ''
    ];
    if (empty($page_message['text'])) {
      // Use FlashMessageService if available and preferred for consistency
      if (isset($flashMessageService)) {
          $flashMessageService->addError('Failed to load user data.');
      } else {
          $page_message = ['text' => 'Failed to load user data.', 'type' => 'error'];
      }
    }
    error_log("Edit Profile Page: Could not load user data for user ID: " . $userId);
}
?>

<div class="form-page-container account-settings-container">
    <h1>Edit Profile Information</h1>

    <?php 
    // Flash messages will be displayed globally by webengine.php
    // This local message display can be removed if global display is sufficient
    // or kept for messages specific to this page load before redirect.
    if (!empty($page_message['text']) && !isset($flashMessageService)) : // Only show if flash service isn't handling it
    ?>
        <div class="messages <?php echo htmlspecialchars($page_message['type'] === 'success' ? 'success' : ($page_message['type'] === 'info' ? 'info' : 'errors')); ?>">
            <p><?php echo htmlspecialchars($page_message['text']); ?></p>
        </div>
    <?php endif; ?>

    <div class="settings-section profile-details-section">
        <h2>Profile Details</h2>
        <form action="/index.php?page=account_edit_profile" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_edit_profile_info" value="<?php echo htmlspecialchars($_SESSION['csrf_token_edit_profile_info']); ?>">

            <div class="setting-item">
                <div class="setting-label">
                    <label for="username-display">Username:</label>
                    <small class="setting-description">Your public display name. Cannot be changed here.</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="username-display" name="username_display" class="form-control" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" disabled>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="email">Email:</label>
                    <small class="setting-description">Your account email address. Changing it will require confirmation via the new email.</small>
                </div>
                <div class="setting-control">
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" > 
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="location">Location:</label>
                    <small class="setting-description">Where are you based? (e.g., City, Country)</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>" placeholder="e.g., City, Country" maxlength="100">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="user_status">Status / Mood:</label>
                    <small class="setting-description">A short status or what you're up to.</small>
                </div>
                <div class="setting-control">
                    <input type="text" id="user_status" name="user_status" class="form-control" value="<?php echo htmlspecialchars($userData['user_status'] ?? ''); ?>" placeholder="e.g., Coding a new feature!" maxlength="150">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="bio">Bio / About Me:</label>
                    <small class="setting-description">Tell us a little about yourself.</small>
                </div>
                <div class="setting-control">
                    <textarea id="bio" name="bio" class="form-control" rows="4" placeholder="A brief introduction..." maxlength="1000"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="website_url">Website URL:</label>
                    <small class="setting-description">Your personal or professional website (include http:// or https://).</small>
                </div>
                <div class="setting-control">
                    <input type="url" id="website_url" name="website_url" class="form-control" value="<?php echo htmlspecialchars($userData['website_url'] ?? ''); ?>" placeholder="https://example.com">
                </div>
            </div>

            <div class="form-actions setting-actions">
                <button type="submit" name="update_profile_info" class="button button-primary">Save Profile Information</button>
            </div>
        </form>
    </div>

    <hr class="section-divider"> <!-- Added for visual separation -->

    <div class="settings-section password-change-section">
        <h2>Change Password</h2>
        <form action="/index.php?page=account_edit_profile" method="post" class="settings-form">
            <input type="hidden" name="csrf_token_change_password" value="<?php echo htmlspecialchars($_SESSION['csrf_token_change_password']); ?>">

            <div class="setting-item">
                <div class="setting-label">
                    <label for="current_password">Current Password:</label>
                    <small class="setting-description">Enter your current password.</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="new_password">New Password:</label>
                    <small class="setting-description">Choose a strong new password (min. 8 characters).</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                </div>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <label for="confirm_password">Confirm New Password:</label>
                    <small class="setting-description">Enter your new password again.</small>
                </div>
                <div class="setting-control">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                </div>
            </div>

            <div class="form-actions setting-actions">
                <button type="submit" name="change_password_submit" class="button button-danger">Change Password</button>
            </div>
        </form>
    </div>

    <div class="page-actions">
        <p><a href="/index.php?page=account_dashboard" class="button button-secondary">Back to Dashboard</a></p>
    </div>
</div>