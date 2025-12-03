<?php

/**
 * Review Class
 * Handles review CRUD and retrieval
 */
class Review
{
    private $id;
    private $text;
    private $recommended;
    private $created_at;
    private $fk_user;
    private $fk_game;
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getText()
    {
        return $this->text;
    }
    public function isRecommended()
    {
        return (bool)$this->recommended;
    }
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function getUserId()
    {
        return $this->fk_user;
    }
    public function getGameId()
    {
        return $this->fk_game;
    }

    // Load by id
    public function loadById($id)
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("SELECT * FROM review WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->text = $row['text'];
            $this->recommended = $row['recommended'];
            $this->created_at = $row['created_at'];
            $this->fk_user = $row['fk_user'];
            $this->fk_game = $row['fk_game'];
            return true;
        }
        return false;
    }

    /**
     * Create a new review
     */
    public function create($userId, $gameId, $text, $recommended = false)
    {
        if (!$this->pdo) return ['success' => false, 'message' => 'Database error', 'id' => null];
        $text = trim($text);
        if (empty($text)) return ['success' => false, 'message' => 'Review text is required', 'id' => null];

        // Check if user already reviewed this game
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM review WHERE fk_user = ? AND fk_game = ?");
        $stmt->execute([$userId, $gameId]);
        if ($stmt->fetchColumn() > 0) return ['success' => false, 'message' => 'You have already reviewed this game.', 'id' => null];

        $stmt = $this->pdo->prepare("INSERT INTO review (text, recommended, created_at, fk_user, fk_game) VALUES (?, ?, NOW(), ?, ?)");
        if ($stmt->execute([$text, $recommended ? 1 : 0, $userId, $gameId])) {
            $this->id = $this->pdo->lastInsertId();
            $this->text = $text;
            $this->recommended = $recommended ? 1 : 0;
            $this->created_at = date('Y-m-d H:i:s');
            $this->fk_user = $userId;
            $this->fk_game = $gameId;
            return ['success' => true, 'message' => 'Review posted successfully!', 'id' => $this->id];
        }
        return ['success' => false, 'message' => 'Failed to post review', 'id' => null];
    }

    /**
     * Delete this review
     */
    public function delete()
    {
        if (!$this->pdo || !$this->id) return false;
        $stmt = $this->pdo->prepare("DELETE FROM review WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Update this review's text/recommendation
     */
    public function update($text, $recommended = false)
    {
        if (!$this->pdo || !$this->id) return false;
        $text = trim($text);
        $rec = $recommended ? 1 : 0;
        $stmt = $this->pdo->prepare("UPDATE review SET text = ?, recommended = ? WHERE id = ?");
        if ($stmt->execute([$text, $rec, $this->id])) {
            $this->text = $text;
            $this->recommended = $rec;
            return true;
        }
        return false;
    }

    /**
     * Check if a given user has already reviewed a game
     */
    public static function existsByUserGame($pdo, $userId, $gameId)
    {
        $stmt = $pdo->prepare("SELECT id FROM review WHERE fk_user = ? AND fk_game = ? LIMIT 1");
        $stmt->execute([$userId, $gameId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get reviews by game
     */
    public static function getByGame($pdo, $gameId, $limit = 100)
    {

        $stmt = $pdo->prepare("SELECT r.*, u.username FROM review r JOIN users u ON r.fk_user = u.id WHERE r.fk_game = ? AND COALESCE(u.banned, 0) = 0 ORDER BY r.created_at DESC LIMIT ?");
        $stmt->execute([$gameId, intval($limit)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reviews by user
     */
    public static function getByUser($pdo, $userId, $limit = 100)
    {

        $stmt = $pdo->prepare("SELECT r.*, g.name as game_name FROM review r JOIN game g ON r.fk_game = g.id JOIN users u ON r.fk_user = u.id WHERE r.fk_user = ? AND COALESCE(u.banned, 0) = 0 ORDER BY r.created_at DESC LIMIT ?");
        $stmt->execute([$userId, intval($limit)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregate stats for a game reviews
     */
    public static function getStatsForGame($pdo, $gameId)
    {
        // Count only reviews from non-banned users
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN r.recommended = 1 THEN 1 ELSE 0 END) as positive FROM review r JOIN users u ON r.fk_user = u.id WHERE r.fk_game = ? AND COALESCE(u.banned, 0) = 0");
        $stmt->execute([$gameId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['percentage'] = $stats['total'] > 0 ? round(($stats['positive'] / $stats['total']) * 100) : 0;
        return $stats;
    }
}
