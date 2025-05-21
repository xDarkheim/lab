<?php
namespace App\Models;

use App\Lib\Database;
use PDO;
use PDOException;

class Category {
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public static function findById(Database $db_handler, int $id): ?Category {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Category::findById - Database connection failed.");
            return null;
        }

        try {
            $stmt = $conn->prepare("SELECT id, name, slug, created_at, updated_at FROM categories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoryData) {
                return new self(
                    (int)$categoryData['id'],
                    $categoryData['name'],
                    $categoryData['slug'],
                    $categoryData['created_at'],
                    $categoryData['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findById - PDOException: " . $e->getMessage());
        }
        return null;
    }

    public static function findBySlug(Database $db_handler, string $slug): ?Category {
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Category::findBySlug - Database connection failed.");
            return null;
        }

        try {
            $stmt = $conn->prepare("SELECT id, name, slug, created_at, updated_at FROM categories WHERE slug = :slug");
            $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoryData) {
                return new self(
                    (int)$categoryData['id'],
                    $categoryData['name'],
                    $categoryData['slug'],
                    $categoryData['created_at'],
                    $categoryData['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findBySlug - PDOException: " . $e->getMessage());
        }
        return null;
    }

    public static function findAll(Database $db_handler): array { 
        $conn = $db_handler->getConnection();
        if (!$conn) {
            error_log("Category::findAll - Database connection failed.");
            return [];
        }

        $categories = [];
        try {
            // It's good practice to order results, e.g., by name
            $stmt = $conn->query("SELECT id, name, slug, created_at, updated_at FROM categories ORDER BY name ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new self(
                    (int)$row['id'],
                    $row['name'],
                    $row['slug'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Category::findAll - PDOException: " . $e->getMessage());
        }
        return $categories;
    }

}
?>