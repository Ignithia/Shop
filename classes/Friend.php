<?php

/**
 * Friend class
 * Manages friend requests and relationships using `friendlist` table.
 */
class Friend
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Send a friend request.
    public function sendRequest(int $fromId, int $toId): bool
    {
        if ($fromId === $toId) return false;

        $stmt = $this->pdo->prepare("SELECT id, accepted, fk_user_out, fk_user_in FROM friendlist WHERE (fk_user_out = ? AND fk_user_in = ?) OR (fk_user_out = ? AND fk_user_in = ?)");
        $stmt->execute([$fromId, $toId, $toId, $fromId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ($row['accepted']) return false;

            if ($row['fk_user_out'] == $toId && $row['fk_user_in'] == $fromId && !$row['accepted']) {
                $upd = $this->pdo->prepare("UPDATE friendlist SET accepted = 1 WHERE id = ?");
                return $upd->execute([$row['id']]);
            }

            return false;
        }

        $ins = $this->pdo->prepare("INSERT INTO friendlist (fk_user_out, fk_user_in, accepted) VALUES (?, ?, 0)");
        return $ins->execute([$fromId, $toId]);
    }

    // Respond to an incoming request
    public function respondRequest(int $fromId, int $toId, bool $accept): bool
    {
        $stmt = $this->pdo->prepare("SELECT id, accepted FROM friendlist WHERE fk_user_out = ? AND fk_user_in = ?");
        $stmt->execute([$fromId, $toId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ($accept) {
            $upd = $this->pdo->prepare("UPDATE friendlist SET accepted = 1 WHERE id = ?");
            return $upd->execute([$row['id']]);
        } else {
            $del = $this->pdo->prepare("DELETE FROM friendlist WHERE id = ?");
            return $del->execute([$row['id']]);
        }
    }

    // Remove friendship
    public function removeFriend(int $userA, int $userB): bool
    {
        $del = $this->pdo->prepare("DELETE FROM friendlist WHERE (fk_user_out = ? AND fk_user_in = ?) OR (fk_user_out = ? AND fk_user_in = ?)");
        return $del->execute([$userA, $userB, $userB, $userA]);
    }

    // Check friendship status
    public function getStatus(int $meId, int $otherId): string
    {
        $stmt = $this->pdo->prepare("SELECT fk_user_out, fk_user_in, accepted FROM friendlist WHERE (fk_user_out = ? AND fk_user_in = ?) OR (fk_user_out = ? AND fk_user_in = ?)");
        $stmt->execute([$meId, $otherId, $otherId, $meId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return 'none';
        if ($row['accepted']) return 'friends';
        if ($row['fk_user_out'] == $meId) return 'pending_outgoing';
        return 'pending_incoming';
    }

    // Get friends list for a user (accepted relationships only)
    public function getFriends(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.avatar FROM users u
            JOIN friendlist f ON ( (f.fk_user_out = ? AND f.fk_user_in = u.id) OR (f.fk_user_in = ? AND f.fk_user_out = u.id) )
            WHERE f.accepted = 1");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get incoming pending requests
    public function getIncomingRequests(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.avatar, f.id as request_id FROM users u JOIN friendlist f ON f.fk_user_out = u.id WHERE f.fk_user_in = ? AND f.accepted = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get outgoing pending requests
    public function getOutgoingRequests(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.avatar, f.id as request_id FROM users u JOIN friendlist f ON f.fk_user_in = u.id WHERE f.fk_user_out = ? AND f.accepted = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
