<?php

/**
 * User Class
 * Handles user operations for the webshop
 */
class User
{
    private $id;
    private $username;
    private $email;
    private $password;
    private $avatar;
    private $balance;
    private $joindate;
    private $admin;
    private $banned = false;
    private $ban_reason = '';
    private $public_profile = true;
    private $public_library = true;
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;
    }

    // Setters
    public function setUsername($username)
    {
        $this->username = $username;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }
    public function setPublicProfile($v)
    {
        $this->public_profile = (bool)$v;
    }
    public function setPublicLibrary($v)
    {
        $this->public_library = (bool)$v;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getAvatar()
    {
        return $this->avatar;
    }
    public function getBalance()
    {
        return $this->balance * 100;
    }
    public function getBalanceInCoins()
    {
        return $this->balance * 100;
    }
    public function getFormattedBalanceCoins()
    {
        return number_format($this->getBalanceInCoins(), 0) . ' coins';
    }
    public function getJoindate()
    {
        return $this->joindate;
    }
    public function isAdmin()
    {
        return $this->admin;
    }
    public function isBanned()
    {
        return (bool)$this->banned;
    }
    public function getBanReason()
    {
        return $this->ban_reason;
    }
    public function isPublicProfile()
    {
        return (bool)$this->public_profile;
    }
    public function isPublicLibrary()
    {
        return (bool)$this->public_library;
    }




    /**
     * Load user by ID
     */
    public function loadById($id)
    {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->password = $userData['password'];
            $this->avatar = $userData['avatar'];
            $this->balance = $userData['balance'];
            $this->joindate = $userData['joindate'];
            $this->admin = $userData['admin'];
            $this->banned = isset($userData['banned']) ? (bool)$userData['banned'] : false;
            $this->ban_reason = $userData['ban_reason'] ?? '';
            $this->public_profile = isset($userData['public_profile']) ? (bool)$userData['public_profile'] : true;
            $this->public_library = isset($userData['public_library']) ? (bool)$userData['public_library'] : true;
            return true;
        }
        return false;
    }

    /**
     * Load user by username
     */
    public function loadByUsername($username)
    {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->password = $userData['password'];
            $this->avatar = $userData['avatar'];
            $this->balance = $userData['balance'];
            $this->joindate = $userData['joindate'];
            $this->admin = $userData['admin'];
            $this->banned = isset($userData['banned']) ? (bool)$userData['banned'] : false;
            $this->ban_reason = $userData['ban_reason'] ?? '';
            $this->public_profile = isset($userData['public_profile']) ? (bool)$userData['public_profile'] : true;
            $this->public_library = isset($userData['public_library']) ? (bool)$userData['public_library'] : true;
            return true;
        }
        return false;
    }

    /**
     * Load user by email
     */
    public function loadByEmail($email)
    {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->password = $userData['password'];
            $this->avatar = $userData['avatar'];
            $this->balance = $userData['balance'];
            $this->joindate = $userData['joindate'];
            $this->admin = $userData['admin'];
            $this->banned = isset($userData['banned']) ? (bool)$userData['banned'] : false;
            $this->ban_reason = $userData['ban_reason'] ?? '';
            return true;
        }
        return false;
    }

    /**
     * Create new user
     */
    public function create($username, $email, $password, $avatar = '')
    {
        if (!$this->pdo) return false;

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $joindate = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, avatar, balance, joindate, admin, public_profile, public_library)
            VALUES (?, ?, ?, ?, 0.00, ?, false, 1, 1)
        ");

        if ($stmt->execute([$username, $email, $hashedPassword, $avatar, $joindate])) {
            $this->id = $this->pdo->lastInsertId();
            $this->username = $username;
            $this->email = $email;
            $this->password = $hashedPassword;
            $this->avatar = $avatar;
            $this->balance = 0.00;
            $this->joindate = $joindate;
            $this->admin = false;
            $this->public_profile = true;
            $this->public_library = true;
            return true;
        }
        return false;
    }

    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * Change user password
     */
    public function changePassword($newPassword)
    {
        if (!$this->pdo || !$this->id) return false;

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

        if ($stmt->execute([$hashedPassword, $this->id])) {
            $this->password = $hashedPassword;
            return true;
        }
        return false;
    }

    /**
     * Update user information
     */
    public function save()
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, avatar = ?, balance = ?, admin = ?, public_profile = ?, public_library = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $this->username,
            $this->email,
            $this->avatar,
            $this->balance,
            $this->admin,
            (int)$this->public_profile,
            (int)$this->public_library,
            $this->id
        ]);
    }

    /**
     * Delete user account and all associated data
     */
    public function delete()
    {
        if (!$this->pdo || !$this->id) return false;

        try {
            $this->pdo->beginTransaction();

            // Delete from wishlist
            $stmt = $this->pdo->prepare("DELETE FROM wishlist WHERE fk_user = ?");
            $stmt->execute([$this->id]);

            // Delete from library
            $stmt = $this->pdo->prepare("DELETE FROM library WHERE fk_user = ?");
            $stmt->execute([$this->id]);

            // Delete from shopping_cart
            $stmt = $this->pdo->prepare("DELETE FROM shopping_cart WHERE fk_user = ?");
            $stmt->execute([$this->id]);

            // Delete from review
            $stmt = $this->pdo->prepare("DELETE FROM review WHERE fk_user = ?");
            $stmt->execute([$this->id]);

            // Delete from friendlist (both incoming and outgoing)
            $stmt = $this->pdo->prepare("DELETE FROM friendlist WHERE fk_user_out = ? OR fk_user_in = ?");
            $stmt->execute([$this->id, $this->id]);

            // Delete user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$this->id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Add balance to user account
     */
    public function addBalance($amount)
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        if ($stmt->execute([$amount, $this->id])) {
            $this->balance += $amount;
            return true;
        }
        return false;
    }

    /**
     * Add balance to user account from coins (converts coins to dollars)
     */
    public function addBalanceFromCoins($coins)
    {
        $dollars = $coins / 100;
        return $this->addBalance($dollars);
    }

    /**
     * Deduct balance from user account
     */
    public function deductBalance($amount)
    {
        if (!$this->pdo || !$this->id || $this->balance < $amount) return false;

        $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        if ($stmt->execute([$amount, $this->id])) {
            $this->balance -= $amount;
            return true;
        }
        return false;
    }

    /**
     * Deduct balance from user account in coins (converts coins to dollars)
     */
    public function deductBalanceInCoins($coins)
    {
        $dollars = $coins / 100;
        return $this->deductBalance($dollars);
    }

    /**
     * Check if user has enough coins for a purchase
     */
    public function hasEnoughCoins($coins)
    {
        return $this->getBalanceInCoins() >= $coins;
    }

    /**
     * Get user's owned games
     */
    public function getOwnedGames()
    {
        if (!$this->pdo || !$this->id) return [];

        $stmt = $this->pdo->prepare("
            SELECT g.*, c.name as category_name, l.purchased_at 
            FROM game g 
            LEFT JOIN category c ON g.fk_category = c.id
            JOIN library l ON g.id = l.fk_game 
            WHERE l.fk_user = ?
            ORDER BY l.purchased_at DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user owns a specific game
     */
    public function ownsGame($gameId)
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM library WHERE fk_user = ? AND fk_game = ?");
        $stmt->execute([$this->id, $gameId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Purchase a game
     */
    public function purchaseGame($gameId, $price)
    {
        if (!$this->pdo || !$this->id) return false;

        // Check if user already owns the game
        if ($this->ownsGame($gameId)) return false;

        // Check if user has enough balance
        if ($this->balance < $price) return false;

        // Deduct balance
        $this->deductBalance($price);

        // Add to library
        $stmt = $this->pdo->prepare("
            INSERT INTO library (fk_user, fk_game, purchased_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$this->id, $gameId]);

        return true;
    }

    /**
     * Purchase a game with coin pricing
     */
    public function purchaseGameWithCoins($gameId, $priceInCoins)
    {
        $priceInDollars = $priceInCoins / 100;
        return $this->purchaseGame($gameId, $priceInDollars);
    }

    /**
     * Get user's wishlist
     */
    public function getWishlist()
    {
        if (!$this->pdo || !$this->id) return [];

        $stmt = $this->pdo->prepare("
            SELECT g.*, c.name as category_name, w.rank, w.added_at 
            FROM game g 
            LEFT JOIN category c ON g.fk_category = c.id
            JOIN wishlist w ON g.id = w.fk_game 
            WHERE w.fk_user = ?
            ORDER BY w.rank ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add game to wishlist
     */
    public function addToWishlist($gameId)
    {
        if (!$this->pdo || !$this->id) return false;

        // Check if game is already in wishlist
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE fk_user = ? AND fk_game = ?");
        $stmt->execute([$this->id, $gameId]);
        if ($stmt->fetchColumn() > 0) return false;

        // Add to wishlist - try with rank first, if it fails, try without
        try {
            // Get next rank
            $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(rank), 0) + 1 FROM wishlist WHERE fk_user = ?");
            $stmt->execute([$this->id]);
            $nextRank = $stmt->fetchColumn();

            // Add to wishlist with rank
            $stmt = $this->pdo->prepare("
                INSERT INTO wishlist (fk_user, fk_game, rank, added_at) 
                VALUES (?, ?, ?, CURDATE())
            ");
            return $stmt->execute([$this->id, $gameId, $nextRank]);
        } catch (PDOException $e) {
            // If rank column doesn't exist, try without it
            $stmt = $this->pdo->prepare("
                INSERT INTO wishlist (fk_user, fk_game, added_at) 
                VALUES (?, ?, CURDATE())
            ");
            return $stmt->execute([$this->id, $gameId]);
        }
    }

    /**
     * Remove game from wishlist
     */
    public function removeFromWishlist($gameId)
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM wishlist WHERE fk_user = ? AND fk_game = ?");
        return $stmt->execute([$this->id, $gameId]);
    }

    /**
     * Get user's shopping cart
     */
    public function getShoppingCart()
    {
        if (!$this->pdo || !$this->id) return [];

        $stmt = $this->pdo->prepare("
            SELECT g.* 
            FROM game g 
            JOIN shopping_cart sc ON g.id = sc.fk_game 
            WHERE sc.fk_user = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add game to shopping cart
     */
    public function addToCart($gameId)
    {
        if (!$this->pdo || !$this->id) return false;

        // Check if game is already in cart
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM shopping_cart WHERE fk_user = ? AND fk_game = ?");
        $stmt->execute([$this->id, $gameId]);
        if ($stmt->fetchColumn() > 0) return false;

        $stmt = $this->pdo->prepare("INSERT INTO shopping_cart (fk_user, fk_game) VALUES (?, ?)");
        return $stmt->execute([$this->id, $gameId]);
    }

    /**
     * Remove game from shopping cart
     */
    public function removeFromCart($gameId)
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM shopping_cart WHERE fk_user = ? AND fk_game = ?");
        return $stmt->execute([$this->id, $gameId]);
    }

    /**
     * Clear shopping cart
     */
    public function clearCart()
    {
        if (!$this->pdo || !$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM shopping_cart WHERE fk_user = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Get user's purchase history using library table
     */
    public function getPurchaseHistory($limit = 50, $offset = 0)
    {
        if (!$this->pdo || !$this->id) return [];

        $stmt = $this->pdo->prepare("
            SELECT l.*, g.name as game_name, g.price, l.purchased_at as created_at
            FROM library l
            LEFT JOIN game g ON l.fk_game = g.id
            WHERE l.fk_user = ?
            ORDER BY l.purchased_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total spent by user using library table
     */
    public function getTotalSpent()
    {
        if (!$this->pdo || !$this->id) return 0;

        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(g.price), 0) as total 
            FROM library l
            JOIN game g ON l.fk_game = g.id
            WHERE l.fk_user = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchColumn();
    }

    /**
     * Get total spent by user in coins for display
     */
    public function getTotalSpentInCoins()
    {
        return $this->getTotalSpent() * 100;
    }

    /**
     * Get count of purchases (items in library)
     * @return int
     */
    public function getPurchaseCount()
    {
        if (!$this->pdo || !$this->id) return 0;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM library WHERE fk_user = ?");
        $stmt->execute([$this->id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get all users with library stats (admin only)
     */
    public static function getAllUsers($pdo, $filters = [])
    {
        $sql = "
            SELECT u.*, 
                   COUNT(l.id) as games_owned,
                   COALESCE(SUM(g.price), 0) as total_spent
            FROM users u
            LEFT JOIN library l ON u.id = l.fk_user
            LEFT JOIN game g ON l.fk_game = g.id
        ";

        $conditions = [];
        $params = [];

        // Search by username or email
        if (!empty($filters['search'])) {
            $conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        // Filter by admin status
        if (isset($filters['admin']) && $filters['admin'] !== null) {
            $conditions[] = "u.admin = ?";
            $params[] = $filters['admin'];
        }

        // Filter by banned status
        if (isset($filters['banned']) && $filters['banned'] !== null) {
            $conditions[] = "u.banned = ?";
            $params[] = $filters['banned'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY u.id ORDER BY u.joindate DESC";

        // Limit
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . intval($filters['offset']);
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count users matching filters (used by admin UI)
     * @param PDO $pdo
     * @param array $filters
     * @return int
     */
    public static function countUsers($pdo, $filters = [])
    {
        $sql = "SELECT COUNT(*) FROM users u";

        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['admin']) && $filters['admin'] !== null) {
            $conditions[] = "u.admin = ?";
            $params[] = $filters['admin'];
        }

        if (isset($filters['banned']) && $filters['banned'] !== null) {
            $conditions[] = "u.banned = ?";
            $params[] = $filters['banned'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Register new user
     * @param PDO $pdo Database connection
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Password
     * @param string $avatar Avatar URL (optional)
     * @return array ['success' => bool, 'message' => string, 'user' => User|null]
     */
    public static function register($pdo, $username, $email, $password, $avatar = '')
    {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'All fields are required',
                'user' => null
            ];
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            return [
                'success' => false,
                'message' => 'Username must be 3-20 characters long and contain only letters, numbers and underscores',
                'user' => null
            ];
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address',
                'user' => null
            ];
        }

        // Validate password length
        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'Password must be at least 6 characters long',
                'user' => null
            ];
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Username is already taken',
                'user' => null
            ];
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Email address is already in use',
                'user' => null
            ];
        }

        // Create new user
        $user = new User($pdo);
        if ($user->create($username, $email, $password, $avatar)) {
            return [
                'success' => true,
                'message' => 'Account created successfully',
                'user' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error creating account',
                'user' => null
            ];
        }
    }

    /**
     * Login user
     * @param PDO $pdo Database connection
     * @param string $login Username or email
     * @param string $password Password
     * @return array ['success' => bool, 'message' => string, 'user' => User|null]
     */
    public static function login($pdo, $login, $password)
    {
        if (empty($login) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username and password are required',
                'user' => null
            ];
        }

        $user = new User($pdo);

        // Try login with username
        $userFound = $user->loadByUsername($login);

        // If not found, try with email
        if (!$userFound) {
            $userFound = $user->loadByEmail($login);
        }

        if (!$userFound) {
            return [
                'success' => false,
                'message' => 'Invalid login credentials',
                'user' => null
            ];
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'message' => 'Invalid login credentials',
                'user' => null
            ];
        }

        // Check if banned (if column exists it was populated during load)
        if ($user->isBanned()) {
            $reason = $user->getBanReason();
            $msg = 'Your account has been banned.';
            if (!empty($reason)) $msg .= ' Reason: ' . $reason;
            return [
                'success' => false,
                'message' => $msg,
                'user' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Successfully logged in',
            'user' => $user
        ];
    }

    /**
     * Start user session
     * @param User $user User object
     */
    public static function startSession($user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['admin'] = $user->isAdmin();

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Logout user
     * @return bool
     */
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        return true;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current logged in user
     * @param PDO $pdo Database connection
     * @return User|null
     */
    public static function getCurrentUser($pdo)
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        if (isset($_SESSION['user_id'])) {
            $user = new User($pdo);
            if ($user->loadById($_SESSION['user_id'])) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Require login (redirect if not logged in)
     * @param string $redirectTo Redirect URL
     */
    public static function requireLogin($redirectTo = 'login.php')
    {
        if (!self::isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }

    /**
     * Require admin privileges
     * @param PDO $pdo Database connection
     * @param string $redirectTo Redirect URL
     */
    public static function requireAdmin($pdo, $redirectTo = 'index.php')
    {
        $user = self::getCurrentUser($pdo);
        if (!$user || !$user->isAdmin()) {
            header("Location: $redirectTo");
            exit();
        }
    }

    /**
     * Ban a user (admin only)
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function banUser($userId, $reason = '')
    {
        if (!$this->pdo || !$this->isAdmin()) return false;
        if ($userId === $this->id) return false; // Can't ban self

        // Ensure columns exist
        try {
            $colCheck = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'banned'");
            if (!$colCheck->fetch()) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN banned TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN ban_reason VARCHAR(255) NULL");
            }
        } catch (Exception $e) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE users SET banned = 1, ban_reason = ? WHERE id = ?");
        return $stmt->execute([$reason, $userId]);
    }

    /**
     * Unban a user (admin only)
     * @param int $userId
     * @return bool
     */
    public function unbanUser($userId)
    {
        if (!$this->pdo || !$this->isAdmin()) return false;

        try {
            $colCheck = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'banned'");
            if (!$colCheck->fetch()) return false;
        } catch (Exception $e) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE users SET banned = 0, ban_reason = '' WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Remove avatar for a user (admin only)
     * @param int $userId
     * @return bool
     */
    public function removeUserAvatar($userId)
    {
        if (!$this->pdo || !$this->isAdmin()) return false;

        $stmt = $this->pdo->prepare("UPDATE users SET avatar = '' WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Search users by username
     * @param PDO $pdo
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function searchUsers($pdo, $query, $limit = 10)
    {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? ORDER BY username ASC LIMIT ?");
        $stmt->execute(['%' . $query . '%', $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
