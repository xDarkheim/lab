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
        public ?string $updated_at = null,
        public ?string $author_name = null // Added author_name property
    ) {}

    public static function findById(Database $db_handler, int $id): ?Article {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::findById - Database connection failed.");
            return null;
        }

        try {
            $sql = "SELECT a.*, u.username AS author_name 
                    FROM articles a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    WHERE a.id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $article = new self(
                    (int)$result['id'],
                    $result['title'],
                    $result['short_description'],
                    $result['full_text'],
                    $result['date'],
                    isset($result['user_id']) ? (int)$result['user_id'] : null,
                    $result['created_at'],
                    $result['updated_at'],
                    $result['author_name'] ?? null // Assign author_name if available
                );
                return $article;
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

    public static function findByCategoryId(Database $db_handler, int $category_id): array {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::findByCategoryId - Database connection failed.");
            return [];
        }

        $articles = [];
        $sql = "SELECT a.id, a.title, a.short_description, a.full_text, a.date, a.user_id, a.created_at, a.updated_at 
                FROM articles a
                INNER JOIN article_categories ac ON a.id = ac.article_id
                WHERE ac.category_id = :category_id
                ORDER BY a.date DESC, a.id DESC";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
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
            error_log("Article::findByCategoryId - PDOException for category_id {$category_id}: " . $e->getMessage());
        }
        return $articles;
    }

    public function getCategories(Database $db_handler): array {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::getCategories - Database connection failed for article ID {$this->id}.");
            return [];
        }

        $categories = [];
        $sql = "SELECT c.id, c.name, c.slug, c.created_at, c.updated_at
                FROM categories c
                INNER JOIN article_categories ac ON c.id = ac.category_id
                WHERE ac.article_id = :article_id
                ORDER BY c.name ASC";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new Category(
                    (int)$row['id'],
                    $row['name'],
                    $row['slug'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Article::getCategories - PDOException for article ID {$this->id}: " . $e->getMessage());
        }
        return $categories;
    }

    public function addCategory(Database $db_handler, int $category_id): bool {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::addCategory - Database connection failed.");
            return false;
        }

        $checkSql = "SELECT COUNT(*) FROM article_categories WHERE article_id = :article_id AND category_id = :category_id";
        try {
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
            $checkStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Article::addCategory - PDOException (check): " . $e->getMessage());
            return false;
        }

        $sql = "INSERT INTO article_categories (article_id, category_id) VALUES (:article_id, :category_id)";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] != 1062) {
                error_log("Article::addCategory - PDOException: " . $e->getMessage());
            }
            return false;
        }
    }

    public function removeCategory(Database $db_handler, int $category_id): bool {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::removeCategory - Database connection failed.");
            return false;
        }

        $sql = "DELETE FROM article_categories WHERE article_id = :article_id AND category_id = :category_id";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Article::removeCategory - PDOException: " . $e->getMessage());
            return false;
        }
    }

    public function setCategories(Database $db_handler, array $category_ids): bool {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Article::setCategories - Database connection failed.");
            return false;
        }

        try {
            $conn->beginTransaction();

            $deleteSql = "DELETE FROM article_categories WHERE article_id = :article_id";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
            $deleteStmt->execute();

            if (!empty($category_ids)) {
                $insertSql = "INSERT INTO article_categories (article_id, category_id) VALUES (:article_id, :category_id)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindParam(':article_id', $this->id, PDO::PARAM_INT);
                
                foreach ($category_ids as $category_id) {
                    if (is_numeric($category_id) && $category_id > 0) {
                        $catId = (int)$category_id;
                        $insertStmt->bindParam(':category_id', $catId, PDO::PARAM_INT);
                        $insertStmt->execute();
                    }
                }
            }
            
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Article::setCategories - PDOException for article ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
}

