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

function getUserOwnedGames($username) {
    if (file_exists('data/users.json')) {
        $users_data = json_decode(file_get_contents('data/users.json'), true);
        if (is_array($users_data)) {
            foreach ($users_data as $user) {
                if ($user['username'] === $username) {
                    return isset($user['owned_games']) ? $user['owned_games'] : [];
                }
            }
        }
    }
    return [];
}

function findGameById($games, $id) {
    foreach ($games as $game) {
        if ($game['id'] == $id) {
            return $game;
        }
    }
    return null;
}

$games = loadGames();
$owned_game_ids = getUserOwnedGames($username);
$owned_games = [];

foreach ($owned_game_ids as $game_id) {
    $game = findGameById($games, $game_id);
    if ($game) {
        $owned_games[] = $game;
    }
}

$selected_category = $_GET['category'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$filtered_owned_games = $owned_games;

// Get the coins for each user
function getUserCoins($username) {
    if (file_exists('data/users.json')) {
        $users_data = json_decode(file_get_contents('data/users.json'), true);
        if (is_array($users_data)) {
            foreach ($users_data as $user) {
                if ($user['username'] === $username) {
                    return isset($user['coins']) ? $user['coins'] : 0;
                }
            }
        }
    }
    return 0;
}

$user_coins = getUserCoins($username);

function formatCoins($amount) {
    return number_format($amount, 0, ',', '.');
}
if ($selected_category !== 'all') {
    $filtered_owned_games = array_filter($owned_games, function($game) use ($selected_category) {
        return $game['category'] === $selected_category;
    });
}

if (!empty($search_query)) {
    $filtered_owned_games = array_filter($filtered_owned_games, function($game) use ($search_query) {
        $query_lower = strtolower($search_query);
        $name_match = strpos(strtolower($game['name']), $query_lower) !== false;
        $description_match = strpos(strtolower($game['description']), $query_lower) !== false;
        $category_match = strpos(strtolower($game['category']), $query_lower) !== false;
        return $name_match || $description_match || $category_match;
    });
}

$games_by_category = [];
foreach ($filtered_owned_games as $game) {
    $games_by_category[$game['category']][] = $game;
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Game Library - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1><a href="index.php" class="logo-link">GAME STORE</a></h1>
        <div class="user-info">
            <div class="user-details">
                <span class="username">Player: <?php echo htmlspecialchars($username); ?></span>
                <span class="balance">🪙 <?php echo formatCoins($user_coins); ?></span>
            </div>
            <div class="navigation">
                <div class="nav-dropdown">
                    <button class="nav-dropdown-btn">Menu ▼</button>
                    <div class="nav-dropdown-content">
                        <a href="index.php">📊 Dashboard</a>
                        <a href="shop.php">🛒 Shop</a>
                        <a href="library.php" class="active">📚 Library</a>
                        <a href="cart.php">🛍️ Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                        <div class="nav-divider"></div>
                        <a href="?logout=1" class="logout">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="library-page">
            <div class="library-header">
                <h2>🕹️ My Game Library</h2>
                <p>Your personal collection of <?php echo count($owned_games); ?> games</p>
            </div>

            <?php if (!empty($owned_games)): ?>
            <div class="search-section">
                <form method="GET" action="library.php" class="search-form">
                    <div class="search-container">
                        <input type="text" 
                               name="search" 
                               placeholder="Search your library..." 
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
                    <a href="?category=<?php echo $cat_key; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                       class="category-btn <?php echo ($selected_category === $cat_key) ? 'active' : ''; ?>">
                        <?php echo $cat_name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($owned_games)): ?>
                <div class="empty-library">
                    <h3>Your library is empty</h3>
                    <p>Start building your gaming collection by purchasing games from our store!</p>
                    <a href="shop.php" class="btn btn-primary">Browse Games</a>
                </div>
            <?php else: ?>
                <div class="library-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($filtered_owned_games); ?></div>
                        <div class="stat-label">
                            <?php if (!empty($search_query) || $selected_category !== 'all'): ?>
                                Showing
                            <?php else: ?>
                                Games Owned
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($games_by_category); ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">🪙 <?php echo formatCoins(array_sum(array_column($filtered_owned_games, 'price'))); ?></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                </div>

                <?php if (!empty($games_by_category)): ?>
                    <?php foreach ($games_by_category as $category => $category_games): ?>
                        <div class="category-section">
                            <h3 class="category-title"><?php echo ucfirst($category); ?> Games (<?php echo count($category_games); ?>)</h3>
                            <div class="games-grid">
                                <?php foreach ($category_games as $game): ?>
                                    <div class="game-card library-card">
                                        <a href="product.php?id=<?php echo $game['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                                        </a>
                                        <div class="game-info">
                                            <h4 class="game-title">
                                                <a href="product.php?id=<?php echo $game['id']; ?>">
                                                    <?php echo htmlspecialchars($game['name']); ?>
                                                </a>
                                            </h4>
                                            <p class="game-category"><?php echo ucfirst(htmlspecialchars($game['category'])); ?></p>
                                            <div class="game-price">🪙 <?php echo formatCoins($game['price']); ?></div>
                                        </div>
                                        <div class="owned-badge">✓ OWNED</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- If no games found in filtered results -->
                <?php if (empty($games_by_category) && !empty($owned_games)): ?>
                    <div class="no-games">
                        <?php if (!empty($search_query)): ?>
                            <h3>🔍 No games found for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                            <p>Try adjusting your search terms or <a href="?<?php echo ($selected_category !== 'all') ? 'category=' . $selected_category : ''; ?>">browse all your games</a>.</p>
                        <?php elseif ($selected_category !== 'all'): ?>
                            <h3>No <?php echo ucfirst($selected_category); ?> games in your library</h3>
                            <p><a href="?">Browse all your games</a> or <a href="shop.php?category=<?php echo $selected_category; ?>">get more <?php echo $selected_category; ?> games</a>.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>