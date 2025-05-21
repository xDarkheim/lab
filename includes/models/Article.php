<?php
namespace App\Models;

use App\Lib\Database;
use PDO;
use PDOException;

class Article {
    public function __construct(
        public int $id,
        public string $title,
        public string $short_description,
        public string $full_text,
        public string $date,
        public ?int $user_id,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public static function findById(Database $db_handler, int $id): ?Article {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::findById - Database connection failed.");
            return null;
        }

        try {
            $stmt = $conn->prepare("SELECT id, title, short_description, full_text, date, user_id, created_at, updated_at FROM articles WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $articleData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($articleData) {
                return new self(
                    (int)$articleData['id'],
                    $articleData['title'],
                    $articleData['short_description'],
                    $articleData['full_text'],
                    $articleData['date'],
                    isset($articleData['user_id']) ? (int)$articleData['user_id'] : null,
                    $articleData['created_at'],
                    $articleData['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Article::findById - PDOException: " . $e->getMessage());
        }
        return null;
    }

    public static function findAll(Database $db_handler): array { 
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::findAll - Database connection failed.");
            return [];
        }

        $articles = [];
        try {
            $stmt = $conn->query("SELECT id, title, short_description, full_text, date, user_id, created_at, updated_at FROM articles ORDER BY date DESC, id DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $articles[] = new self(
                    (int)$row['id'],
                    $row['title'],
                    $row['short_description'],
                    $row['full_text'],
                    $row['date'],
                    isset($row['user_id']) ? (int)$row['user_id'] : null,
                    $row['created_at'],
                    $row['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Article::findAll - PDOException: " . $e->getMessage());
        }
        return $articles;
    }

    public static function findByUserId(Database $db_handler, int $user_id): array {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::findByUserId - Database connection failed.");
            return [];
        }

        $articles = [];
        try {
            $stmt = $conn->prepare("SELECT id, title, short_description, full_text, date, user_id, created_at, updated_at FROM articles WHERE user_id = :user_id ORDER BY date DESC, id DESC");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $articles[] = new self(
                    (int)$row['id'],
                    $row['title'],
                    $row['short_description'],
                    $row['full_text'],
                    $row['date'],
                    isset($row['user_id']) ? (int)$row['user_id'] : null,
                    $row['created_at'],
                    $row['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Article::findByUserId - PDOException for user_id {$user_id}: " . $e->getMessage());
        }
        return $articles;
    }

    public static function create(Database $database_handler, array $articleData): int|false
    {
        $conn = $database_handler->getConnection();
        if (!$conn) {
            error_log("Article::create - Database connection failed.");
            return false;
        }
        
        $query = "INSERT INTO articles (title, short_description, full_text, date, user_id) 
                  VALUES (:title, :short_description, :full_text, :date, :user_id)";
        
        try {
            $statement = $conn->prepare($query);
            if ($statement->execute($articleData)) {
                return (int)$conn->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Article::create - PDOException: " . $e->getMessage());
        }
        return false;
    }
}

