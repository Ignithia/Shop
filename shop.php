<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

session_start();

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Require user to be logged in
User::requireLogin();

$currentUser = User::getCurrentUser($pdo);
if (!$currentUser) {
    User::logout();
    header('Location: login.php');
    exit();
}

// Logout functionality
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    User::logout();
    header('Location: login.php');
    exit();
}

// Handle the wishlist additions/removals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_action'])) {
    $game_id = intval($_POST['game_id']);
    
    if ($_POST['wishlist_action'] === 'add') {
        // Check if user owns this game - don't add if owned
        if (!$currentUser->ownsGame($game_id)) {
            $currentUser->addToWishlist($game_id);
        }
    } elseif ($_POST['wishlist_action'] === 'remove') {
        $currentUser->removeFromWishlist($game_id);
    }
    
    // Redirect to same page to prevent form resubmission
    $redirect_url = $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url");
    exit();
}

// Get search query and category from URL parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Prepare filters for games
$filters = [];
if (!empty($search_query)) {
    $filters['search'] = $search_query;
}
if ($selected_category !== 'all') {
    // Find category ID by name
    $stmt = $pdo->prepare("SELECT id FROM category WHERE LOWER(name) = ?");
    $stmt->execute([strtolower($selected_category)]);
    $category_id = $stmt->fetchColumn();
    if ($category_id) {
        $filters['category'] = $category_id;
    }
}

// Load games and categories
$games = Game::getAll($pdo, $filters);
$categories = Game::getAllCategories($pdo);
$user_wishlist = $currentUser->getWishlist();

// Create categories array for navigation
$category_nav = ['all' => 'All Games'];
foreach ($categories as $category) {
    $key = strtolower($category['name']);
    $category_nav[$key] = $category['name'];
}

// Convert wishlist to array of game IDs for easy checking
$wishlist_game_ids = array_column($user_wishlist, 'id');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Store - Shop</title>
    <link rel="stylesheet" href="./css/main.css">
</head>
<body>
    <?php include './inc/header.inc.php'; ?>

    <div class="container">
        <div class="shop-header">
            <h2>🛒 Game Shop</h2>
            <p>Discover amazing games and improve your collection!</p>
        </div>

        <div class="search-section">
            <form method="GET" action="shop.php" class="search-form">
                <div class="search-container">
                    <input type="text" 
                           name="search" 
                           placeholder="Search games..." 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           class="search-input">
                    <?php if ($selected_category !== 'all'): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-btn">🔍</button>
                </div>
                <?php if (!empty($search_query)): ?>
                    <div class="search-results-info">
                        <span>Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></span>
                        <a href="?<?php echo ($selected_category !== 'all') ? 'category=' . $selected_category : ''; ?>" class="clear-search">Clear Search</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="category-nav">
            <?php foreach ($category_nav as $cat_key => $cat_name): ?>
                <a href="?category=<?php echo $cat_key; ?>" 
                   class="category-btn <?php echo ($selected_category === $cat_key) ? 'active' : ''; ?>">
                    <?php echo $cat_name; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="games-grid">
            <?php foreach ($games as $game): ?>
                <div class="game-card">
                    <div class="game-image-container">
                        <a href="product.php?id=<?php echo $game['id']; ?>">
                            <?php 
                            $game_obj = new Game($pdo);
                            $game_obj->loadById($game['id']);
                            $screenshots = $game_obj->getScreenshots();
                            $image_url = !empty($screenshots) ? $screenshots[0] : './media/default-game.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                        </a>
                        
                        <?php if ($game['sale'] && $game['sale_percentage'] > 0): ?>
                            <div class="sale-badge">-<?php echo $game['sale_percentage']; ?>%</div>
                        <?php endif; ?>
                        
                        <?php if (!$currentUser->ownsGame($game['id'])): ?>
                            <div class="wishlist-overlay">
                                <?php if (in_array($game['id'], $wishlist_game_ids)): ?>
                                    <form method="post" style="margin: 0;">
                                        <input type="hidden" name="wishlist_action" value="remove">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="wishlist-heart-btn in-wishlist" title="Remove from wishlist">
                                            💔
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="margin: 0;">
                                        <input type="hidden" name="wishlist_action" value="add">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="wishlist-heart-btn not-in-wishlist" title="Add to wishlist">
                                            ❤️
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">
                            <a href="product.php?id=<?php echo $game['id']; ?>">
                                <?php echo htmlspecialchars($game['name']); ?>
                            </a>
                        </h3>
                        <p class="game-category"><?php echo htmlspecialchars($game['category_name']); ?></p>
                        
                        <div class="game-badges">
                            <?php 
                            $release_date = new DateTime($game['release_date']);
                            $thirty_days_ago = new DateTime('-30 days');
                            if ($release_date > $thirty_days_ago): 
                            ?>
                                <div class="new-badge-small">NEW</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="game-pricing">
                            <?php if ($game['sale'] && $game['sale_percentage'] > 0): ?>
                                <?php 
                                $original_price = $game['price'] / (1 - ($game['sale_percentage'] / 100));
                                ?>
                                <span class="original-price">$<?php echo number_format($original_price, 2); ?></span>
                                <span class="sale-price">$<?php echo number_format($game['price'], 2); ?></span>
                            <?php else: ?>
                                <div class="game-price">$<?php echo number_format($game['price'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="game-actions">
                            <?php if ($currentUser->ownsGame($game['id'])): ?>
                                <button class="buy-btn owned" disabled>✓ Owned</button>
                            <?php else: ?>
                                <form method="post" action="cart.php" style="display: inline;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" class="buy-btn">Add to Cart</button>
                                </form>
                                
                                <?php if (in_array($game['id'], $wishlist_game_ids)): ?>
                                    <form method="post" action="wishlist.php" style="display: inline;">
                                        <input type="hidden" name="remove_game" value="1">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="card-btn wishlist-btn in-wishlist" title="Remove from wishlist">
                                            💔
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="wishlist.php" style="display: inline;">
                                        <input type="hidden" name="add_to_wishlist" value="1">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="card-btn wishlist-btn" title="Add to wishlist">
                                            ❤️
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="product.php?id=<?php echo $game['id']; ?>" class="card-btn secondary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

            <!-- If no games are found show this message -->
        <?php if (empty($games)): ?>
            <div class="no-games">
                <?php if (!empty($search_query)): ?>
                    <h3>🔍 No games found for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                    <p>Try adjusting your search terms or <a href="?<?php echo ($selected_category !== 'all') ? 'category=' . $selected_category : ''; ?>">browse all games</a>.</p>
                <?php else: ?>
                    <h3>No games found in this category</h3>
                    <p>Try selecting a different category or browse all games.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include './inc/footer.inc.php'; ?>
</body>
</html>