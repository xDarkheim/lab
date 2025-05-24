<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=home&login_required=true");
    exit;
}
use App\Lib\Database;
use App\Controllers\ProfileController;

$userId = (int)$_SESSION['user_id'];
$profileController = new ProfileController($database_handler, $userId, $flashMessageService);

$page_message = ['text' => '', 'type' => ''];
$userData = $profileController->getCurrentUserData();

if (!isset($_SESSION['csrf_token_edit_profile_info'])) {
    $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token_edit_profile_info']) || !hash_equals($_SESSION['csrf_token_edit_profile_info'] ?? '', $_POST['csrf_token_edit_profile_info'] ?? '')) {

        if (isset($flashMessageService)) { 
            $flashMessageService->addError('Security error: Invalid CSRF token. Please refresh the page and try again.');
        } else {
            $page_message['text'] = 'Security error: Invalid CSRF token. Please refresh the page and try again.';
            $page_message['type'] = 'error';
        }
        header('Location: /index.php?page=account_edit_profile');
        exit;
    } else {
        $_SESSION['csrf_token_edit_profile_info'] = bin2hex(random_bytes(32));

        if (isset($_POST['update_profile_info'])) {
            $profileInfoData = [
                'location' => $_POST['location'] ?? null,
                'user_status' => $_POST['user_status'] ?? null,
                'bio' => $_POST['bio'] ?? null,
                'website_url' => $_POST['website_url'] ?? null,
            ];
            $profileController->handleUpdateDetailsRequest($profileInfoData);

            header('Location: /index.php?page=account_edit_profile');
            exit;
        }
    }
}

if (!$userData) {
    $userData = [
        'username' => 'N/A', 'email' => 'N/A',
        'location' => '', 'user_status' => '', 'bio' => '', 'website_url' => ''
    ];
    if (empty($page_message['text'])) {
      $page_message = ['text' => 'Failed to load user data.', 'type' => 'error'];
    }
    error_log("Edit Profile Page: Could not load user data for user ID: " . $userId);
}
?>

<div class="form-page-container account-settings-container">
    <h1>Edit Profile Information</h1>

    <?php if (!empty($page_message['text'])): ?>
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
                    <small class="setting-description">Your account email address.</small>
                </div>
                <div class="setting-control">
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" disabled>
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

    <div class="page-actions">
        <p><a href="/index.php?page=account_dashboard" class="button button-secondary">Back to Dashboard</a></p>
    </div>
</div>