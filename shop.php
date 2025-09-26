<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Logout functionality
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Unknown';

// Load games from JSON file
function loadGames() {
    if (file_exists('data/games.json')) {
        $games_data = file_get_contents('data/games.json');
        $games = json_decode($games_data, true);
        return is_array($games) ? $games : [];
    }
    return [];
}

$games = loadGames();

// Get category and search using URL parameters
$selected_category = $_GET['category'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Filter games by category (only if a specific category is selected else show all)
$filtered_games = $games;
if ($selected_category !== 'all') {
    $filtered_games = array_filter($games, function($game) use ($selected_category) {
        return $game['category'] === $selected_category;
    });
}

if (!empty($search_query)) {
    $filtered_games = array_filter($filtered_games, function($game) use ($search_query) {
        $query_lower = strtolower($search_query);
        $name_match = strpos(strtolower($game['name']), $query_lower) !== false;
        $description_match = strpos(strtolower($game['description']), $query_lower) !== false;
        $category_match = strpos(strtolower($game['category']), $query_lower) !== false;
        return $name_match || $description_match || $category_match;
    });
}

// Load categories from JSON file
function loadCategories() {
    if (file_exists('data/categories.json')) {
        $categories_data = file_get_contents('data/categories.json');
        $categories_array = json_decode($categories_data, true);
        if (is_array($categories_array)) {
            $categories = ['all' => 'All Games'];
            foreach ($categories_array as $category) {
                $key = strtolower($category['name']);
                $categories[$key] = $category['name'];
            }
            return $categories;
        }
    }
}

$categories = loadCategories();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Store - Shop</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1>GAME STORE</h1>
        <div class="user-info">
            <span>User: <?php echo htmlspecialchars($username); ?></span>
            <a href="index.php" class="nav-btn">Dashboard</a>
            <span class="nav-btn active">Shop</span>
            <a href="library.php" class="nav-btn">Library</a>
            <a href="cart.php" class="nav-btn">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

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
            <?php foreach ($categories as $cat_key => $cat_name): ?>
                <a href="?category=<?php echo $cat_key; ?>" 
                   class="category-btn <?php echo ($selected_category === $cat_key) ? 'active' : ''; ?>">
                    <?php echo $cat_name; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="games-grid">
            <?php foreach ($filtered_games as $game): ?>
                <div class="game-card">
                    <div class="game-image-container">
                        <a href="product.php?id=<?php echo $game['id']; ?>">
                            <img src="<?php echo $game['image']; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                        </a>
                        <?php if (isset($game['is_on_sale']) && $game['is_on_sale']): ?>
                            <div class="sale-badge">-<?php echo $game['sale_percentage']; ?>%</div>
                        <?php endif; ?>
                        <?php if (isset($game['dlcs']) && !empty($game['dlcs'])): ?>
                            <div class="dlc-indicator">DLC Available</div>
                        <?php endif; ?>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">
                            <a href="product.php?id=<?php echo $game['id']; ?>">
                                <?php echo htmlspecialchars($game['name']); ?>
                            </a>
                        </h3>
                        <p class="game-category"><?php echo ucfirst($game['category']); ?></p>
                        
                        <?php if (isset($game['is_new']) && $game['is_new']): ?>
                            <div class="new-badge-small">NEW</div>
                        <?php endif; ?>
                        
                        <!-- Price -->
                        <div class="game-pricing">
                            <?php if (isset($game['is_on_sale']) && $game['is_on_sale']): ?>
                                <span class="original-price">$<?php echo number_format($game['original_price'], 2); ?></span>
                                <span class="sale-price">$<?php echo number_format($game['price'], 2); ?></span>
                            <?php else: ?>
                                <div class="game-price">$<?php echo number_format($game['price'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="game-actions">
                            <form method="post" action="cart.php" style="display: inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="buy-btn">Add to Cart</button>
                            </form>
                            <a href="product.php?id=<?php echo $game['id']; ?>" class="card-btn secondary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

            <!-- If no games are found show this message -->
        <?php if (empty($filtered_games)): ?>
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
</body>
</html>