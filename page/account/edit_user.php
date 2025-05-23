<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, что пользователь - администратор
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($flashMessageService) && $flashMessageService instanceof \App\Lib\FlashMessageService) {
        $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    }
    header('Location: /index.php?page=home');
    exit();
}

use App\Lib\FlashMessageService;

$page_title = "Edit User";
$user_to_edit = null;
$user_id = null;
$errors = [];
$available_roles = ['user', 'editor', 'admin']; 

if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    $flashMessageService = new FlashMessageService();
}

if (!isset($db) || !$db instanceof PDO) {
    $flashMessageService->addError("Database connection is not available. Cannot edit user.");
    header('Location: /index.php?page=manage_users');
    exit();
}

if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($user_id === false || $user_id <= 0) {
        $flashMessageService->addError("Invalid User ID provided.");
        header('Location: /index.php?page=manage_users');
        exit();
    }
} else {
    $flashMessageService->addError("No User ID provided.");
    header('Location: /index.php?page=manage_users');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError("Invalid security token. Action aborted.");
    } else {
        $updated_username = trim($_POST['username'] ?? '');
        $updated_email = trim($_POST['email'] ?? '');
        $updated_role = $_POST['role'] ?? '';

        if (empty($updated_username)) {
            $errors[] = "Username cannot be empty.";
        }
        if (empty($updated_email)) {
            $errors[] = "Email cannot be empty.";
        } elseif (!filter_var($updated_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($updated_role) || !in_array($updated_role, $available_roles)) {
            $errors[] = "Invalid role selected.";
        }
        if ($user_id == $_SESSION['user_id'] && $_SESSION['user_role'] === 'admin' && $updated_role !== 'admin') {
             $errors[] = "You cannot remove your own administrator privileges.";
        }


        if (empty($errors)) {
            try {
                $stmt_check = $db->prepare("SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id");
                $stmt_check->bindParam(':email', $updated_email);
                $stmt_check->bindParam(':username', $updated_username);
                $stmt_check->bindParam(':id', $user_id, PDO::PARAM_INT);
                $stmt_check->execute();
                if ($stmt_check->fetch()) {
                    $errors[] = "Username or Email already taken by another user.";
                } else {
                    $stmt_update = $db->prepare("UPDATE users SET username = :username, email = :email, role = :role, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                    $stmt_update->bindParam(':username', $updated_username);
                    $stmt_update->bindParam(':email', $updated_email);
                    $stmt_update->bindParam(':role', $updated_role);
                    $stmt_update->bindParam(':id', $user_id, PDO::PARAM_INT);

                    if ($stmt_update->execute()) {
                        $flashMessageService->addSuccess("User (ID: {$user_id}) updated successfully.");
                        header('Location: /index.php?page=manage_users');
                        exit();
                    } else {
                        $errors[] = "Failed to update user. Please try again.";
                        error_log("Edit User Page - Failed to execute update statement for user ID: {$user_id}. Error: " . print_r($stmt_update->errorInfo(), true));
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error while updating user: " . $e->getMessage();
                error_log("Edit User Page - PDOException updating user ID {$user_id}: " . $e->getMessage());
            }
        }
    }
    $user_to_edit = [
        'id' => $user_id,
        'username' => $updated_username ?? '',
        'email' => $updated_email ?? '',
        'role' => $updated_role ?? ''
    ];
}
if ($user_id && !$user_to_edit) { 
    try {
        $stmt_select = $db->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
        $stmt_select->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $user_to_edit = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$user_to_edit) {
            $flashMessageService->addError("User not found.");
            header('Location: /index.php?page=manage_users');
            exit();
        }
    } catch (PDOException $e) {
        $flashMessageService->addError("Database error fetching user details: " . $e->getMessage());
        error_log("Edit User Page - PDOException fetching user ID {$user_id}: " . $e->getMessage());
        header('Location: /index.php?page=manage_users');
        exit();
    }
}


$csrf_token = $_SESSION['csrf_token'] ?? '';
if (empty($csrf_token) && function_exists('random_bytes')) {
     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
     $csrf_token = $_SESSION['csrf_token'];
} elseif (empty($csrf_token)) {
    $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
    $csrf_token = $_SESSION['csrf_token'];
}

?>

<div class="page-container edit-user-page">
    <header class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?>: <?php echo htmlspecialchars($user_to_edit['username'] ?? 'N/A'); ?></h1>
        <a href="/index.php?page=manage_users" class="button button-secondary page-header-action">
            <i class="fas fa-arrow-left"></i> Back to User List
        </a>
    </header>

    <?php
    if (!empty($errors)): ?>
        <div class="message message--error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($user_to_edit): ?>
    <div class="admin-content-container">
        <form action="/index.php?page=edit_user&id=<?php echo htmlspecialchars($user_id); ?>" method="POST" class="styled-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user_to_edit['username'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_to_edit['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" class="form-control" required>
                    <?php foreach ($available_roles as $role_value): ?>
                        <option value="<?php echo htmlspecialchars($role_value); ?>" <?php echo (isset($user_to_edit['role']) && $user_to_edit['role'] === $role_value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($role_value)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="/index.php?page=manage_users" class="button button-cancel">Cancel</a>
            </div>
        </form>
    </div>
    <?php else: ?>
        <div class="message message--info">
            <p>User data could not be loaded.</p>
        </div>
    <?php endif; ?>
</div>