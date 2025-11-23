<?php
/**
 * Game Class
 * Handles game operations for the webshop
 */
class Game {
    private $id;
    private $name;
    private $description;
    private $price;
    private $release_date;
    private $sale;
    private $fk_percentage;
    private $fk_category;
    private $pdo;
    
    // Additional properties for display
    private $category_name;
    private $sale_percentage;
    private $original_price;
    private $cover_image;
    private $screenshots;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->screenshots = [];
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPrice() { return $this->price; }
    public function getReleaseDate() { return $this->release_date; }
    public function isOnSale() { return $this->sale; }
    public function getCategoryId() { return $this->fk_category; }
    public function getCategoryName() { return $this->category_name; }
    public function getSalePercentage() { return $this->sale_percentage; }
    public function getOriginalPrice() { return $this->original_price; }
    public function getCoverImage() { return $this->cover_image; }
    public function getScreenshots() { return $this->screenshots; }
    
    // Setters
    public function setName($name) { $this->name = $name; }
    public function setDescription($description) { $this->description = $description; }
    public function setPrice($price) { $this->price = $price; }
    public function setReleaseDate($date) { $this->release_date = $date; }
    public function setSale($sale) { $this->sale = $sale; }
    public function setCategoryId($categoryId) { $this->fk_category = $categoryId; }
    public function setCoverImage($coverImage) { $this->cover_image = $coverImage; }
    
    /**
     * Load game by ID
     */
    public function loadById($id) {
        if (!$this->pdo) return false;
        
        $stmt = $this->pdo->prepare("
            SELECT g.*, c.name as category_name, p.percentage as sale_percentage
            FROM game g
            LEFT JOIN category c ON g.fk_category = c.id
            LEFT JOIN percentage p ON g.fk_percentage = p.id
            WHERE g.id = ?
        ");
        $stmt->execute([$id]);
        $gameData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($gameData) {
            $this->id = $gameData['id'];
            $this->name = $gameData['name'];
            $this->description = $gameData['description'];
            $this->cover_image = $gameData['cover_image'] ?? null;
            $this->original_price = (float)$gameData['price'];
            $this->price = $this->original_price;
            $this->release_date = $gameData['release_date'];
            $this->sale = $gameData['sale'];
            $this->fk_percentage = $gameData['fk_percentage'];
            $this->fk_category = $gameData['fk_category'];
            $this->category_name = $gameData['category_name'];
            $this->sale_percentage = $gameData['sale_percentage'] ?? 0;

            if ($this->sale && $this->sale_percentage > 0) {
                $pct = max(0.0, min(100.0, (float)$this->sale_percentage));
                if ($pct >= 100.0) {
                    $this->price = 0.0;
                } else {
                    $this->price = round($this->original_price * (1 - ($pct / 100.0)), 2);
                }
            }
            
            $this->loadScreenshots();
            
            return true;
        }
        return false;
    }
    
    /**
     * Load screenshots for the game
     */
    private function loadScreenshots() {
        if (!$this->pdo || !$this->id) return;
        
        $stmt = $this->pdo->prepare("SELECT link FROM screenshot WHERE fk_game = ?");
        $stmt->execute([$this->id]);
        $this->screenshots = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Create new game
     */
    public function create($name, $description, $price, $releaseDate, $categoryId, $sale = false, $percentageId = null, $coverImage) {
        if (!$this->pdo) return false;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO game (name, description, cover_image, price, release_date, sale, fk_percentage, fk_category) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $description, $coverImage, $price, $releaseDate, $sale, $percentageId, $categoryId])) {
            $this->id = $this->pdo->lastInsertId();
            $this->name = $name;
            $this->description = $description;
            $this->cover_image = $coverImage;
            $this->price = $price;
            $this->release_date = $releaseDate;
            $this->sale = $sale;
            $this->fk_percentage = $percentageId;
            $this->fk_category = $categoryId;
            return true;
        }
        return false;
    }
    
    /**
     * Update game information
     */
    public function save() {
        if (!$this->pdo || !$this->id) return false;
        
        $stmt = $this->pdo->prepare("
            UPDATE game 
            SET name = ?, description = ?, cover_image = ?, price = ?, release_date = ?, sale = ?, fk_percentage = ?, fk_category = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $this->name,
            $this->description,
            $this->cover_image,
            $this->price,
            $this->release_date,
            $this->sale,
            $this->fk_percentage,
            $this->fk_category,
            $this->id
        ]);
    }
    
    /**
     * Get all games with optional filters
     */
    public static function getAll($pdo, $filters = []) {
        $sql = "
            SELECT g.*, c.name as category_name, p.percentage as sale_percentage
            FROM game g
            LEFT JOIN category c ON g.fk_category = c.id
            LEFT JOIN percentage p ON g.fk_percentage = p.id
        ";
        
        $conditions = [];
        $params = [];
        
        // Filter by category
        if (!empty($filters['category'])) {
            $conditions[] = "g.fk_category = ?";
            $params[] = $filters['category'];
        }
        
        // Filter by sale status
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $conditions[] = "g.sale = true";
        }
        
        // Filter by price range
        if (!empty($filters['min_price'])) {
            $conditions[] = "g.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $conditions[] = "g.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Search by name
        if (!empty($filters['search'])) {
            $conditions[] = "g.name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'g.name';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        $sql .= " ORDER BY $orderBy $orderDir";
        
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
     * Get games by category
     */
    public static function getByCategory($pdo, $categoryId) {
        return self::getAll($pdo, ['category' => $categoryId]);
    }
    
    /**
     * Get games on sale
     */
    public static function getOnSale($pdo) {
        return self::getAll($pdo, ['on_sale' => true]);
    }
    
    /**
     * Search games by name
     */
    public static function search($pdo, $searchTerm) {
        return self::getAll($pdo, ['search' => $searchTerm]);
    }
    
    /**
     * Get featured games (newest releases)
     */
    public static function getFeatured($pdo, $limit = 6) {
        return self::getAll($pdo, [
            'order_by' => 'g.release_date',
            'order_dir' => 'DESC',
            'limit' => $limit
        ]);
    }
    
    /**
     * Get game reviews
     */
    public function getReviews() {
        if (!$this->pdo || !$this->id) return [];
        
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.username 
            FROM review r 
            JOIN users u ON r.fk_user = u.id 
            WHERE r.fk_game = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add review for this game
     */
    public function addReview($userId, $text, $recommended) {
        if (!$this->pdo || !$this->id) return false;
        
        // Check if user already reviewed this game
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM review WHERE fk_user = ? AND fk_game = ?");
        $stmt->execute([$userId, $this->id]);
        if ($stmt->fetchColumn() > 0) return false;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO review (text, recommended, created_at, fk_user, fk_game) 
            VALUES (?, ?, NOW(), ?, ?)
        ");
        return $stmt->execute([$text, $recommended, $userId, $this->id]);
    }
    
    /**
     * Get review statistics
     */
    public function getReviewStats() {
        if (!$this->pdo || !$this->id) return ['total' => 0, 'positive' => 0, 'percentage' => 0];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN recommended = 1 THEN 1 ELSE 0 END) as positive
            FROM review 
            WHERE fk_game = ?
        ");
        $stmt->execute([$this->id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['percentage'] = $stats['total'] > 0 ? round(($stats['positive'] / $stats['total']) * 100) : 0;
        
        return $stats;
    }
    
    /**
     * Add screenshot to game
     */
    public function addScreenshot($link) {
        if (!$this->pdo || !$this->id) return false;
        
        $stmt = $this->pdo->prepare("INSERT INTO screenshot (fk_game, link) VALUES (?, ?)");
        if ($stmt->execute([$this->id, $link])) {
            $this->screenshots[] = $link;
            return true;
        }
        return false;
    }
    
    /**
     * Remove screenshot from game
     */
    public function removeScreenshot($link) {
        if (!$this->pdo || !$this->id) return false;
        
        $stmt = $this->pdo->prepare("DELETE FROM screenshot WHERE fk_game = ? AND link = ?");
        return $stmt->execute([$this->id, $link]);
    }
    
    /**
     * Get current sale price
     */
    public function getSalePrice() {
        if ($this->sale && $this->sale_percentage > 0) {
            return $this->price;
        }
        return $this->original_price;
    }
    
    /**
     * Get formatted price for display
     */
    public function getFormattedPrice() {
        return number_format($this->price * 100, 0) . ' coins';
    }
    
    /**
     * Get formatted original price for display
     */
    public function getFormattedOriginalPrice() {
        return number_format($this->original_price * 100, 0) . ' coins';
    }

    /**
     * Format an arbitrary dollar amount as coins for display
     */
    public static function formatCoinsFromDollars($amount) {
        return number_format(((float)$amount) * 100, 0) . ' coins';
    }
    
    /**
     * Check if game is new (released within last 30 days)
     */
    public function isNew() {
        $releaseTimestamp = strtotime($this->release_date);
        $thirtyDaysAgo = strtotime('-30 days');
        return $releaseTimestamp > $thirtyDaysAgo;
    }
    
    /**
     * Get all categories
     */
    public static function getCategories($pdo) {
        $stmt = $pdo->query("SELECT * FROM category ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete game (admin only)
     */
    public function delete() {
        if (!$this->pdo || !$this->id) return false;
        
        try {
            $this->pdo->beginTransaction();
            
            $imagesToDelete = [];
            
            if (!empty($this->cover_image) && !filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
                $imagesToDelete[] = '../media/' . $this->cover_image;
            }
            
            $stmt = $this->pdo->prepare("SELECT link FROM screenshot WHERE fk_game = ?");
            $stmt->execute([$this->id]);
            $screenshots = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($screenshots as $screenshot) {
                if (!filter_var($screenshot, FILTER_VALIDATE_URL)) {
                    $imagesToDelete[] = '../media/' . $screenshot;
                }
            }
            
            // Delete related records first (due to foreign key constraints)
            $this->pdo->prepare("DELETE FROM shopping_cart WHERE fk_game = ?")->execute([$this->id]);
            $this->pdo->prepare("DELETE FROM wishlist WHERE fk_game = ?")->execute([$this->id]);
            $this->pdo->prepare("DELETE FROM library WHERE fk_game = ?")->execute([$this->id]);
            $this->pdo->prepare("DELETE FROM review WHERE fk_game = ?")->execute([$this->id]);
            $this->pdo->prepare("DELETE FROM screenshot WHERE fk_game = ?")->execute([$this->id]);
            
            // Delete the game
            $stmt = $this->pdo->prepare("DELETE FROM game WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            $this->pdo->commit();
            
            // Delete image files after successful database deletion
            if ($result) {
                foreach ($imagesToDelete as $imagePath) {
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollback();
            return false;
        }
    }
    
    /**
     * Add new product/game to store
     * @param PDO $pdo Database connection
     * @param array $productData Product data
     * @return array ['success' => bool, 'message' => string, 'game_id' => int|null]
     */
    public static function addNewProduct($pdo, $productData) {
        // Validate required fields
        if (empty($productData['name']) || empty($productData['price']) || empty($productData['category_id'])) {
            return [
                'success' => false,
                'message' => 'Name, price and category are required',
                'game_id' => null
            ];
        }
        
        $name = trim($productData['name']);
        $description = trim($productData['description'] ?? '');
        $coverImage = trim($productData['cover_image'] ?? '');
        $price = floatval($productData['price']);
        $releaseDate = $productData['release_date'] ?? date('Y-m-d');
        $categoryId = intval($productData['category_id']);
        $sale = isset($productData['sale']) ? (bool)$productData['sale'] : false;
        $percentageId = isset($productData['percentage_id']) && $productData['percentage_id'] ? intval($productData['percentage_id']) : null;
        
        // Validate price
        if ($price <= 0) {
            return [
                'success' => false,
                'message' => 'Price must be greater than 0',
                'game_id' => null
            ];
        }
        
        // Validate cover image
        if (empty($coverImage)) {
            return [
                'success' => false,
                'message' => 'Cover image is required',
                'game_id' => null
            ];
        }
        
        // Check if name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM game WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Product with this name already exists',
                'game_id' => null
            ];
        }
        
        // Check if category exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM category WHERE id = ?");
        $stmt->execute([$categoryId]);
        if ($stmt->fetchColumn() == 0) {
            return [
                'success' => false,
                'message' => 'Invalid category selected',
                'game_id' => null
            ];
        }
        
        // Create new game object
        $game = new Game($pdo);
        if ($game->create($name, $description, $price, $releaseDate, $categoryId, $sale, $percentageId, $coverImage)) {
            return [
                'success' => true,
                'message' => 'Product added successfully',
                'game_id' => $game->getId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error adding product',
                'game_id' => null
            ];
        }
    }
    
    /**
     * Get all categories
     * @param PDO $pdo Database connection
     * @return array
     */
    public static function getAllCategories($pdo) {
        $stmt = $pdo->query("SELECT * FROM category ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add new category
     * @param PDO $pdo Database connection
     * @param string $name Category name
     * @param string $description Category description
     * @return array ['success' => bool, 'message' => string, 'category_id' => int|null]
     */
    public static function addCategory($pdo, $name, $description = '') {
        $name = trim($name);
        
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Category name is required',
                'category_id' => null
            ];
        }
        
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM category WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Category with this name already exists',
                'category_id' => null
            ];
        }
        
        $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
        if ($stmt->execute([$name])) {
            return [
                'success' => true,
                'message' => 'Category added successfully',
                'category_id' => $pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error adding category',
                'category_id' => null
            ];
        }
    }
    
    /**
     * Get game statistics
     * @param PDO $pdo Database connection
     * @return array
     */
    public static function getStatistics($pdo) {
        $stats = [];
        
        // Total number of games
        $stmt = $pdo->query("SELECT COUNT(*) FROM game");
        $stats['total_games'] = $stmt->fetchColumn();
        
        // Games on sale
        $stmt = $pdo->query("SELECT COUNT(*) FROM game WHERE sale = 1");
        $stats['games_on_sale'] = $stmt->fetchColumn();
        
        // Total revenue
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(g.price), 0) 
            FROM library l 
            JOIN game g ON l.fk_game = g.id
        ");
        $stats['total_revenue'] = $stmt->fetchColumn();
        
        // Most popular category
        $stmt = $pdo->query("
            SELECT c.name, COUNT(l.id) as purchases
            FROM category c
            LEFT JOIN game g ON c.id = g.fk_category
            LEFT JOIN library l ON g.id = l.fk_game
            GROUP BY c.id, c.name
            ORDER BY purchases DESC
            LIMIT 1
        ");
        $popularCategory = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['popular_category'] = $popularCategory ? $popularCategory['name'] : 'None';
        
        return $stats;
    }
}
?>