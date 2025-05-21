<?php
namespace App\Lib;

use App\Models\User;
use App\Lib\Database;

class Auth {
    private Database $db_handler;

    public function __construct(Database $db_handler) {
        $this->db_handler = $db_handler;
    }


    public function register(array $data): array {
        $errors = [];
        $username = isset($data['username']) ? trim($data['username']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $password_confirm = isset($data['password_confirm']) ? $data['password_confirm'] : '';

        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $errors[] = "The username can only contain letters, numbers, and underscores.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors[] = "Incorrect email format.";
        }

        if (empty($password)){
            $errors[] = "Password is required.";
        } elseif($password !== $password_confirm) {
            $errors[] = "The passwords do not match.";
        }

        if(!empty($errors)){
            return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];  
        }

        $existingUser = User::findByUsernameOrEmail($this->db_handler, $username, $email);
        if ($existingUser)  {
            if(isset($existingUser['username']) && $existingUser['username'] === $username) {
                $errors[] = "A user with this name already exists.";
            }
            if(isset($existingUser['email']) && $existingUser['email'] === $email) {
                $errors[] = "A user with this email already exists.";
            }
            if(!empty($errors)){
                return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];            }
        }

        $user = new User($this->db_handler);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password);

        if ($user->save()){
            return ['success' => true, 'message' => "Registration successful! Now you can log in."];
        } else {
            $errors[] = "Failed to save user.";
            return ['success' => false, 'errors' => $errors, 'data' => ['username' => $username, 'email' => $email]];
        }
    }

    //Login system class
    public function login(string $identifier, string $password): array {
        $errors = [];

        if (empty($identifier)) {
            $errors[] = "Username or email required.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $user = User::findByIdentifier($this->db_handler, $identifier);

        if ($user && password_verify($password, $user->getPasswordHash())) {
            return [
                'success' => true,
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
            ];
        } else {
            $errors[] = "Invalid username/email or password.";
            return ['success' => false, 'errors' => $errors];
        }
    }
}