<?php
namespace App\Models;

use App\Lib\Database;
use PDO;

class User {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_USER = 'user';

    private ?int $id = null;
    private string $username;
    private ?string $email = null;
    private ?string $password_hash = null;
    private ?string $role = 'user';
    private ?string $created_at = null;
    private ?string $location = null;
    private ?string $user_status = null;
    private ?string $bio = null;
    private ?string $website_url = null;

    private Database $db_handler;

    public function __construct(Database $db_handler) {
        $this->db_handler = $db_handler;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getPasswordHash(): ?string {
        return $this->password_hash;
    }

    public function getRole(): ?string {
        return $this->role;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function getLocation(): ?string {
        return $this->location;
    }

    public function getUserStatus(): ?string {
        return $this->user_status;
    }

    public function getBio(): ?string {
        return $this->bio;
    }

    public function getWebsiteUrl(): ?string {
        return $this->website_url;
    }

    public function setUsername(string $username): void {
        $this->username = trim($username);
    }

    public function setEmail(string $email): void {
        $this->email = trim($email);
    }

    public function setPassword(string $plainPassword): void {
        $this->password_hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function setRole(string $role): void {
        $this->role = $role;
    }

    public function setLocation(?string $location): void {
        $this->location = $location ? trim($location) : null;
    }

    public function setUserStatus(?string $user_status): void {
        $this->user_status = $user_status ? trim($user_status) : null;
    }

    public function setBio(?string $bio): void {
        $this->bio = $bio ? trim($bio) : null;
    }

    public function setWebsiteUrl(?string $website_url): void {
        $url = $website_url ? trim($website_url) : null;
        if ($url && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        $this->website_url = $url;
    }

    public function save(): bool {
        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        if ($this->id) {
            $sql = "UPDATE users SET username = ?, email = ?, password_hash = ?, role = ?, location = ?, user_status = ?, bio = ?, website_url = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("User model (save update): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
                return false;
            }
            $result = $stmt->execute([
                $this->username,
                $this->email,
                $this->password_hash,
                $this->role,
                $this->location,
                $this->user_status,
                $this->bio,
                $this->website_url,
                $this->id
            ]);
            if (!$result) {
                error_log("User model (save update): Failed to execute statement: " . implode(":", $stmt->errorInfo()));
            }
            return $result;
        } else {
            $sql = "INSERT INTO users (username, email, password_hash, role, created_at, location, user_status, bio, website_url) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("User model (save insert): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
                return false;
            }
            $result = $stmt->execute([
                $this->username,
                $this->email,
                $this->password_hash,
                $this->role,
                $this->location,
                $this->user_status,
                $this->bio,
                $this->website_url
            ]);
            if ($result) {
                $this->id = (int)$conn->lastInsertId();
            } else {
                error_log("User model (save insert): Failed to execute statement: " . implode(":", $stmt->errorInfo()));
            }
            return $result;
        }
    }

    public static function findByUsernameOrEmail(Database $db_handler, string $username = '', string $email = ''): ?array {
        $conn = $db_handler->getConnection();
        if (!$conn) return null;

        $stmt_check = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
        if ($stmt_check === false) {
            return null;
        }
        $stmt_check->execute([$username, $email]);
        $user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
        return $user_data ?: null;
    }

    public static function findByIdentifier(Database $db_handler, string $identifier): ?self {
        $conn = $db_handler->getConnection();
        if (!$conn) return null;

        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, created_at, location, user_status, bio, website_url FROM users WHERE username = ? OR email = ?");
        if ($stmt === false) return null;

        $stmt->execute([$identifier, $identifier]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $user = new self($db_handler);
            $user->id = (int)$userData['id'];
            $user->username = $userData['username'];
            $user->email = $userData['email'];
            $user->password_hash = $userData['password_hash'];
            $user->role = $userData['role'] ?? 'user';
            $user->created_at = $userData['created_at'];
            $user->location = $userData['location'];
            $user->user_status = $userData['user_status'];
            $user->bio = $userData['bio'];
            $user->website_url = $userData['website_url'];
            return $user;
        }
        return null;
    }

    public function findById(int $id): ?self 
    {
        $conn = $this->db_handler->getConnection();
        if (!$conn) {
            error_log("User::findById - Database connection failed.");
            return null;
        }
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                $this->id = (int)$userData['id'];
                $this->username = $userData['username'];
                $this->email = $userData['email'];
                $this->role = $userData['role'];
                $this->created_at = $userData['created_at'];
                return $this; 
            }
        } catch (\PDOException $e) {
            error_log("User::findById - PDOException for ID {$id}: " . $e->getMessage());
        }
        return null;
    }

    public function verifyPassword(string $plainPassword): bool {
        return password_verify($plainPassword, $this->password_hash);
    }

    public function updatePassword(string $newPasswordHash): bool {
        if ($this->id === null) {
            return false;
        }
        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model: Failed to prepare statement for updating password: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute([$newPasswordHash, $this->id]);
        if ($result) {
            $this->password_hash = $newPasswordHash;
        } else {
            error_log("User model: Failed to execute statement for updating password: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public function updateEmail(string $newEmail): bool {
        if ($this->id === null) {
            return false;
        }
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("User model: Invalid email format for update: " . $newEmail);
            return false;
        }

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model: Failed to prepare statement for updating email: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute([$newEmail, $this->id]);
        if ($result) {
            $this->email = $newEmail;
        } else {
            error_log("User model: Failed to execute statement for updating email: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public function updateDetails(array $details): bool {
        if ($this->id === null) return false;

        $allowedFields = ['email', 'location', 'user_status', 'bio', 'website_url'];
        $fieldsToUpdate = [];
        $valuesToUpdate = [];

        foreach ($details as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setterMethod = 'set' . ucfirst(str_replace('_', '', ucwords($key, '_')));
                if (method_exists($this, $setterMethod)) {
                    $this->$setterMethod($value);
                    $fieldsToUpdate[] = "{$key} = ?";
                    $valuesToUpdate[] = $this->$key;
                } elseif (property_exists($this, $key)) {
                    $this->$key = $value;
                    $fieldsToUpdate[] = "{$key} = ?";
                    $valuesToUpdate[] = $this->$key;
                }
            }
        }

        if (empty($fieldsToUpdate)) {
            return true;
        }

        $conn = $this->db_handler->getConnection();
        if (!$conn) return false;

        $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
        $valuesToUpdate[] = $this->id;

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("User model (updateDetails): Failed to prepare statement: " . implode(":", $conn->errorInfo()));
            return false;
        }
        $result = $stmt->execute($valuesToUpdate);
        if (!$result) {
            error_log("User model (updateDetails): Failed to execute statement: " . implode(":", $stmt->errorInfo()));
        }
        return $result;
    }

    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_EDITOR,
            self::ROLE_ADMIN,
        ];
    }
}
?>