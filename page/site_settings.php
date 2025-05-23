<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<div class='message message--error'><p>Access Denied. You do not have permission to view this page.</p></div>";
    return;
}

use App\Lib\FlashMessageService;
use App\Lib\SettingsManager;

$page_title = "Site Settings";

if (!isset($settingsManager) || !$settingsManager instanceof SettingsManager) {
    if (!isset($database_handler) || !$database_handler instanceof \App\Lib\Database) {
        error_log("Site Settings Page: Database handler not available for creating SettingsManager.");
        echo "<div class='message message--error'><p>Critical error: System configuration issue.</p></div>";
        return;
    }
    $settingsManager = new SettingsManager($database_handler);
}

if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    $flashMessageService = new FlashMessageService();
}

$settings = $settingsManager->getAllSettings();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Settings not saved.";
    } else {
        $settingsToUpdate = [
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_tagline' => trim($_POST['site_tagline'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? '')
        ];

        if (empty($settingsToUpdate['site_name'])) {
            $errors[] = "Site Name cannot be empty.";
        }
        if (empty($settingsToUpdate['admin_email'])) {
            $errors[] = "Admin Email cannot be empty.";
        } elseif (!filter_var($settingsToUpdate['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Admin Email is not a valid email address.";
        }

        if (empty($errors)) {
            if ($settingsManager->updateSettings($settingsToUpdate)) {
                $flashMessageService->addSuccess("Site settings updated successfully!");
                $settings = $settingsManager->getAllSettings();
            } else {
                $errors[] = "Error saving settings. Please check the logs.";
            }
        }
    }
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
if (empty($csrf_token)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION['csrf_token'];
}

?>

<div class="page-container site-settings-page">
    <header class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="message message--error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="/index.php?page=site_settings" method="POST" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group">
            <label for="site_name">Site Name:</label>
            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="site_tagline">Site Tagline/Description:</label>
            <textarea id="site_tagline" name="site_tagline" rows="3"><?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="admin_email">Administrator Email:</label>
            <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">Save Settings</button>
        </div>
    </form>
</div>
