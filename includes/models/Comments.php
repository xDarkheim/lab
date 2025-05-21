<?php

namespace App\Models;

use App\Lib\Database;
use PDO;
use PDOException;

class Comments {
    private ?PDO $db;
    private Database $database_handler;

    public int $id;
    public int $article_id;
    public ?int $user_id;
    public string $author_name;
    public string $content;
    public string $created_at;
    public string $updated_at;
    public string $status; 

    public function __construct(Database $database_handler) {
        $this->database_handler = $database_handler;
        $this->db = $this->database_handler->getConnection();
    }

    public static function findByArticleId(Database $database_handler, int $article_id, string $status = 'approved'): array {
        $comments = [];
        $db = $database_handler->getConnection();
        if (!$db) {
            error_log("Comments::findByArticleId - Database connection failed.");
            return $comments;
        }

        try {
            $sql = "SELECT c.*, u.username AS author_username 
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.article_id = :article_id AND c.status = :status
                    ORDER BY c.created_at ASC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $comment = new self($database_handler);
                $comment->id = (int)$row['id'];
                $comment->article_id = (int)$row['article_id'];
                $comment->user_id = isset($row['user_id']) ? (int)$row['user_id'] : null;
                $comment->author_name = $row['author_username'] ?? $row['author_name'] ?? 'Anonymous';
                $comment->content = $row['content'];
                $comment->created_at = $row['created_at'];
                $comment->updated_at = $row['updated_at'];
                $comment->status = $row['status'];
                $comments[] = $comment;
            }
        } catch (PDOException $e) {
            error_log("Comments::findByArticleId - PDOException for article_id {$article_id}: " . $e->getMessage());
        }
        return $comments;
    }

    public static function create(Database $database_handler, array $data): int|false {
        $db = $database_handler->getConnection();
        if (!$db) {
            error_log("Comments::create - Database connection failed.");
            return false;
        }

        $sql = "INSERT INTO comments (article_id, user_id, author_name, content, status, created_at, updated_at) 
                VALUES (:article_id, :user_id, :author_name, :content, :status, NOW(), NOW())";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':article_id', $data['article_id'], PDO::PARAM_INT);
            if (is_null($data['user_id'])) {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            }
            $stmt->bindParam(':author_name', $data['author_name'], PDO::PARAM_STR);
            $stmt->bindParam(':content', $data['content'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR); 

            if ($stmt->execute()) {
                return (int)$db->lastInsertId();
            } else {
                error_log("Comments::create - Failed to execute statement: " . implode(":", $stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Comments::create - PDOException: " . $e->getMessage());
            return false;
        }
    }


    public function updateStatus(int $comment_id, string $new_status): bool {
        if (!$this->db) return false;
        try {
            $sql = "UPDATE comments SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Comments::updateStatus - PDOException for comment_id {$comment_id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $comment_id): bool {
        if (!$this->db) return false;
        try {
            $sql = "DELETE FROM comments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Comments::delete - PDOException for comment_id {$comment_id}: " . $e->getMessage());
            return false;
        }
    }
}