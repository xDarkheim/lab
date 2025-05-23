<?php

use App\Controllers\ProfileController;
use App\Lib\FlashMessageService;

if (!isset($_SESSION['user_id'])) {
    $flashMessageService = new FlashMessageService();
    $flashMessageService->addError('Please log in to access your dashboard.');
    header("Location: /index.php?page=login");
    exit();
}

$userId = (int)$_SESSION['user_id'];

$profileController = new ProfileController($database_handler, $userId);
$userData = $profileController->getCurrentUserData();

$user_article_count = 0;
$user_comment_count = 0;
$user_notification_count = 0;

if ($database_handler && $pdo = $database_handler->getConnection()) {
    try {
        $stmt_articles = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
        $stmt_articles->execute([$userId]);
        $user_article_count = (int)$stmt_articles->fetchColumn();

        $stmt_comments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
        $stmt_comments->execute([$userId]);
        $user_comment_count = (int)$stmt_comments->fetchColumn();

        $stmt_notifications = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_notifications->execute([$userId]);
        $user_notification_count = (int)$stmt_notifications->fetchColumn();

    } catch (PDOException $e) {
        error_log("Dashboard Stats Error for user ID $userId: " . $e->getMessage());
    }
} else {
    error_log("Dashboard: Database handler or connection not available for user ID $userId. Stats will be 0.");
}

if (!$userData) {
    $userData = [
        'username' => $_SESSION['username'] ?? 'User',
        'email' => 'N/A',
        'location' => 'Not set',
        'user_status' => 'Not set',
        'bio' => '',
        'website_url' => ''
    ];
    error_log("Dashboard: Failed to load full user data for user ID: " . $userId . ". Using defaults.");
}

?>

<div class="page-container dashboard-container">
    <h2 class="dashboard-header">My Dashboard</h2>

    <p class="dashboard-welcome-message">
        Welcome back, <strong><?php echo htmlspecialchars($userData['username'] ?? 'User'); ?></strong>!
        <?php if (!empty($userData['user_status'])): ?>
            <br><span class="user-current-status">Current status: <?php echo htmlspecialchars($userData['user_status']); ?></span>
        <?php endif; ?>
    </p>
    <p class="dashboard-intro">Here you can manage your articles, profile, and account settings for the blog.</p>

    <!-- Overview Cards Section (Stats) -->
    <div class="dashboard-overview">
        <div class="overview-card">
            <span class="overview-card-icon">📄</span>
            <span class="overview-card-value"><?php echo $user_article_count; ?></span>
            <span class="overview-card-label">Your Articles</span>
        </div>
        <div class="overview-card">
            <span class="overview-card-icon">💬</span>
            <span class="overview-card-value"><?php echo $user_comment_count; ?></span>
            <span class="overview-card-label">Your Comments</span>
        </div>
        <div class="overview-card">
            <span class="overview-card-icon">🔔</span>
            <span class="overview-card-value"><?php echo $user_notification_count; ?></span>
            <span class="overview-card-label">Notifications</span>
        </div>
    </div>

    <!-- Profile Snapshot Section -->
    <div class="dashboard-profile-snapshot">
        <h3 class="dashboard-section-title">Profile Snapshot</h3>
        <ul class="profile-details-list">
            <li><strong>Email:</strong> <?php echo htmlspecialchars($userData['email'] ?? 'N/A'); ?></li>
            <li><strong>Location:</strong> <?php echo htmlspecialchars($userData['location'] ?? 'Not specified'); ?></li>
            <?php if (!empty($userData['website_url'])): ?>
            <li><strong>Website:</strong> <a href="<?php echo htmlspecialchars($userData['website_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($userData['website_url']); ?></a></li>
            <?php endif; ?>
            <?php if (!empty($userData['bio'])): ?>
            <li class="profile-bio"><strong>Bio:</strong> <p><?php echo nl2br(htmlspecialchars($userData['bio'])); ?></p></li>
            <?php endif; ?>
        </ul>
        <p class="snapshot-edit-link">
            <a href="/index.php?page=account_edit_profile" class="button button-outline button-small">Edit Full Profile</a>
        </p>
    </div>

    <!-- Quick Actions Section -->
    <h3 class="dashboard-section-title">Quick Actions</h3>
    <ul class="dashboard-actions">
        <li>
            <a href="/index.php?page=create_article">
                Create New Article
                <span class="action-status">(Write & Publish a new blog post)</span>
            </a>
        </li>
        <li>
            <a href="/index.php?page=manage_articles">
                Manage My Articles
                <span class="action-status">(View, Edit, Delete your blog posts)</span>
            </a>
        </li>
        <li>
            <a href="/index.php?page=account_edit_profile">
                Edit My Profile
                <span class="action-status">(Update personal info, bio, social links, etc.)</span>
            </a>
        </li>
        <li>
            <a href="/index.php?page=account_settings">
                Account Settings
                <span class="action-status">(Change password, email preferences)</span>
            </a>
        </li>
        <?php 
        // Additional actions for admin users
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li>
            <a href="/index.php?page=site_settings">
                Site Settings
                <span class="action-status">(Manage global website settings)</span>
            </a>
        </li>
        <li>
            <a href="/index.php?page=manage_users">
                Manage Users
                <span class="action-status">(View and manage user accounts)</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>