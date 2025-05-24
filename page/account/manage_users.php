<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


use App\Models\User;

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== User::ROLE_ADMIN) {
    if (isset($flashMessageService) && $flashMessageService instanceof \App\Lib\FlashMessageService) {
        $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    }
    echo "<div class='message message--error'><p>Access Denied. You do not have permission to view this page.</p></div>";
    return; 
}

use App\Lib\FlashMessageService;


if (!isset($flashMessageService) || !$flashMessageService instanceof FlashMessageService) {
    $flashMessageService = new FlashMessageService();
}
if (!isset($db) || !$db instanceof PDO) {  
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($db) || !$db instanceof PDO) {
        $flashMessageService->addError("Database connection is not available. Action aborted.");
        header('Location: /index.php?page=manage_users');
        exit();
    }

    if (isset($db) && $db instanceof PDO) {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $flashMessageService->addError("Invalid security token. Action aborted.");
            
            header('Location: /index.php?page=manage_users');
            exit();
        }

        
        if (isset($_POST['action']) && $_POST['action'] === 'delete_user' && isset($_POST['user_id_to_delete'])) {
            $user_id_to_delete = filter_var($_POST['user_id_to_delete'], FILTER_VALIDATE_INT);

            if ($user_id_to_delete === false || $user_id_to_delete <= 0) {
                $flashMessageService->addError("Invalid user ID for deletion.");
            } elseif ($user_id_to_delete == $_SESSION['user_id']) { 
                $flashMessageService->addError("You cannot delete your own account.");
            } else {
                try {
                    $stmt_delete = $db->prepare("DELETE FROM users WHERE id = :id");
                    $stmt_delete->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);
                    
                    if ($stmt_delete->execute()) {
                        if ($stmt_delete->rowCount() > 0) {
                            $flashMessageService->addSuccess("User (ID: {$user_id_to_delete}) deleted successfully.");
                        } else {
                            $flashMessageService->addError("User (ID: {$user_id_to_delete}) not found or already deleted.");
                        }
                    } else {
                        $flashMessageService->addError("Failed to delete user. Please try again.");
                        
                        error_log("Manage Users Page - Failed to execute delete statement for user ID: {$user_id_to_delete}. Error: " . print_r($stmt_delete->errorInfo(), true));
                    }
                } catch (PDOException $e) {
                    $flashMessageService->addError("Database error while deleting user: " . $e->getMessage());
                    error_log("Manage Users Page - PDOException deleting user ID {$user_id_to_delete}: " . $e->getMessage());
                }
            }
            
            
            header('Location: /index.php?page=manage_users');
            exit();
        }
    }
}



$page_title = "Manage Users";
$users = [];
$errors = []; 

if (!isset($db) || !$db instanceof PDO) {
    $errors[] = "Database connection is not available.";
    error_log("Manage Users Page: PDO connection not available.");
} else {
    try {
        $stmt = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Error fetching users: " . $e->getMessage();
        error_log("Manage Users Page - PDOException fetching users: " . $e->getMessage());
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

<div class="page-container manage-users-page">
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

    <div class="admin-content-container">

        <?php if (empty($users) && empty($errors)): ?>
            <div class="message message--info" style="padding: var(--spacing-5); text-align: center;">
                <p style="font-size: 1.1rem; margin-bottom: var(--spacing-3);">No users found in the system.</p>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === User::ROLE_ADMIN): ?>
                <p>
                    <a href="/index.php?page=create_user" class="button button-secondary">
                        <i class="fas fa-user-plus"></i> Create the First User
                    </a>
                </p>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table admin-table users-table styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($user['created_at']))); ?></td>
                                <td class="actions-cell">
                                    <a href="/index.php?page=edit_user&id=<?php echo $user['id']; ?>" class="button button-small button-secondary" title="Edit User">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $user['id']):  ?>
                                    <form action="/index.php?page=manage_users&action=delete_user" method="POST" style="display: inline-block; margin-left: var(--spacing-1);" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="user_id_to_delete" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="button button-small button-danger" title="Delete User">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

