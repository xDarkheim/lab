<?php if ($isLoggedIn): ?>
    <div class="user-widget darkheim-widget">
        <div class="user-widget__header">
            <span class="user-widget__avatar">
                <?php echo strtoupper(substr(htmlspecialchars($username ?? 'U'), 0, 1)); ?>
            </span>
            <span class="user-widget__username">
                <?php echo htmlspecialchars($username ?? 'User'); ?>
            </span>
        </div>
        <ul class="user-widget__nav">
                <li><a href="/index.php?page=account_dashboard"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
                <li><a href="/index.php?page=account_edit_profile"><i class="fas fa-user-edit fa-fw"></i> Edit Profile</a></li>
                <li><a href="/index.php?page=account_settings"><i class="fas fa-cog fa-fw"></i> Settings</a></li>
                <li><a href="/modules/logout_process.php" class="logout-link"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>

        </ul>
    </div>
<?php else: ?>
    <div class="login-widget darkheim-widget">
        <form action="/modules/login_process.php" method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="sidebar_username_or_email" class="sr-only">Username or Email</label>
                <input type="text" id="sidebar_username_or_email" name="username_or_email" placeholder="Username or Email" required 
                    value="<?php echo htmlspecialchars($_SESSION['form_data_login']['username_or_email'] ?? ''); ?>" 
                    class="form-control">
            </div>
            <div class="form-group">
                <label for="sidebar_password" class="sr-only">Password</label>
                <input type="password" id="sidebar_password" name="password" placeholder="Password" required class="form-control">
            </div>
            <button type="submit" class="button button-primary button-block">Sign In</button>
        </form>
        <p class="register-prompt" style="text-align: center; margin-top: var(--spacing-3);">
            <p><a href="/index.php?page=forgot_password">Forgot your password?</a></p>
            <a href="/index.php?page=register" class="register-link">Create an account</a>
        </p>
    </div>
<?php endif; ?>
<?php
if (isset($_SESSION['form_data_login'])) {
    unset($_SESSION['form_data_login']);
}
?>