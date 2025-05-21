<?php
namespace App\Components;

use App\Lib\View;

class UserPanelComponent {
    private ?string $currentUserRole;
    private array $loginErrors;
    private ?string $successMessage;

    public function __construct(?string $currentUserRole, array $loginErrors = [], ?string $successMessage = null) {
        $this->currentUserRole = $currentUserRole;
        $this->loginErrors = $loginErrors;
        $this->successMessage = $successMessage;
    }

    public function render(): string {
        $isLoggedIn = isset($_SESSION['user_id']);
        $username = $_SESSION['username'] ?? null;
        $userRole = $this->currentUserRole;

        // Data for the view
        $data = [
            'isLoggedIn' => $isLoggedIn,
            'username' => $username,
            'userRole' => $userRole,
            'loginErrors' => $this->loginErrors,
            'successMessage' => $this->successMessage,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ];

        return View::make(
            ROOT_PATH . DS . 'includes' . DS . 'view' . DS . '_sidebar_user_panel.php',
            $data
        );
    }
}