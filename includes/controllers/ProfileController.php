<?php
namespace App\Controllers;

use App\Lib\Database;
use App\Models\User;
use App\Lib\FlashMessageService; // Assuming FlashService is in App\Lib

class ProfileController {
    private Database $db_handler;
    private int $userId;
    private FlashMessageService $flashService;
    private ?User $user = null;

    public function __construct(Database $db_handler, int $userId, FlashMessageService $flashService) {
        $this->db_handler = $db_handler;
        $this->userId = $userId;
        $this->flashService = $flashService;
    }

    private function loadUser(): ?User {
        if ($this->user === null) {
            $userInstance = new User($this->db_handler);
            $this->user = $userInstance->findById($this->userId);
        }
        return $this->user;
    }

    public function getCurrentUserData(): ?array {
        $user = $this->loadUser();
        if (!$user) {
            return null;
        }
        $userData = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'created_at' => $user->getCreatedAt(),
            'location' => $user->getLocation(),
            'user_status' => $user->getUserStatus(),
            'bio' => $user->getBio(),
            'website_url' => $user->getWebsiteUrl(),
        ];
        return $userData;
    }

    public function handleChangePasswordRequest(array $postData): array {
        $message = ['text' => '', 'type' => 'error'];

        $currentPassword = $postData['current_password'] ?? '';
        $newPassword = $postData['new_password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message['text'] = "Please fill in all password fields.";
            return $message;
        }
        if ($newPassword !== $confirmPassword) {
            $message['text'] = "New password and confirmation do not match.";
            return $message;
        }
        if (strlen($newPassword) < 8) {
            $message['text'] = "New password must be at least 8 characters long.";
            return $message;
        }

        $user = $this->loadUser();
        if (!$user) {
            $message['text'] = "Error: User not found.";
            error_log("ProfileController: User not found for ID: " . $this->userId);
            return $message;
        }

        if (!$user->verifyPassword($currentPassword)) {
            $message['text'] = "Current password incorrect.";
            return $message;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($user->updatePassword($newPasswordHash)) {
            $message['text'] = "Password changed successfully.";
            $message['type'] = 'success';
        } else {
            $message['text'] = "Failed to update password. Please try again.";
            error_log("ProfileController: Failed to update password for user ID: " . $this->userId);
        }
        return $message;
    }

    public function handleUpdateDetailsRequest(array $postData): void {
        $user = $this->loadUser();
        if (!$user) {
            error_log("ProfileController: User not found for ID: " . $this->userId . " during details update.");
            return;
        }

        $detailsToUpdate = [];
        $updateAttemptedFields = array_keys($postData);

        if (in_array('email', $updateAttemptedFields)) {
            $newEmail = trim($postData['email'] ?? '');
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $this->flashService->addError("Please enter a valid email address.");
                return;
            }
            if ($newEmail !== $user->getEmail()) {
                $existingUserByEmail = User::findByUsernameOrEmail($this->db_handler, '', $newEmail);
                if ($existingUserByEmail && $existingUserByEmail['id'] != $this->userId) {
                    $this->flashService->addError("This email is already in use by another account.");
                    return;
                }
                $detailsToUpdate['email'] = $newEmail;
            }
        }

        if (in_array('location', $updateAttemptedFields)) {
            $newLocation = isset($postData['location']) ? trim($postData['location']) : null;
            if ($newLocation !== $user->getLocation()) {
                $detailsToUpdate['location'] = $newLocation;
            }
        }

        if (in_array('user_status', $updateAttemptedFields)) {
            $newUserStatus = isset($postData['user_status']) ? trim($postData['user_status']) : null;
            if ($newUserStatus !== $user->getUserStatus()) {
                $detailsToUpdate['user_status'] = $newUserStatus;
            }
        }

        if (in_array('bio', $updateAttemptedFields)) {
            $newBio = isset($postData['bio']) ? trim($postData['bio']) : null;
            if ($newBio !== $user->getBio()) {
                $detailsToUpdate['bio'] = $newBio;
            }
        }

        if (in_array('website_url', $updateAttemptedFields)) {
            $newWebsiteUrl = isset($postData['website_url']) ? trim($postData['website_url']) : null;
            if ($newWebsiteUrl && !filter_var($newWebsiteUrl, FILTER_VALIDATE_URL)) {
                if (!preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $newWebsiteUrl) && !filter_var("http://" . $newWebsiteUrl, FILTER_VALIDATE_URL)) {
                    $this->flashService->addError("Please enter a valid website URL.");
                    return;
                }
            }
            if ($newWebsiteUrl !== $user->getWebsiteUrl()) {
                $detailsToUpdate['website_url'] = $newWebsiteUrl;
            }
        }

        if (empty($detailsToUpdate)) {
            $this->flashService->addInfo("No changes detected.");
            return;
        }

        if ($this->user->updateDetails($detailsToUpdate)) {
            $this->flashService->addSuccess("Profile details updated successfully.");
        } else {
            $this->flashService->addError("Failed to update profile details. Please try again.");
            error_log("ProfileController: Failed to update details for user ID: " . $this->userId . " Details: " . print_r($detailsToUpdate, true));
        }
    }
}
?>