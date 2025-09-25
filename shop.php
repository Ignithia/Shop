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

// Get category using URL parameters
$selected_category = $_GET['category'] ?? 'all';

// Filter games by category (only if a specific category is selected else show all)
$filtered_games = $games;
if ($selected_category !== 'all') {
    $filtered_games = array_filter($games, function($game) use ($selected_category) {
        return $game['category'] === $selected_category;
    });
}

$categories = [
    'all' => 'All Games',
    'action' => 'Action',
    'strategy' => 'Strategy', 
    'sandbox' => 'Sandbox',
    'simulation' => 'Simulation'
];
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
            <span>Player: <?php echo htmlspecialchars($username); ?></span>
            <a href="index.php" class="nav-btn">Dashboard</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="shop-header">
            <h2>🛒 Game Shop</h2>
            <p>Discover amazing games and level up your collection!</p>
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
                    <img src="<?php echo $game['image']; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                    <div class="game-info">
                        <h3 class="game-title"><?php echo htmlspecialchars($game['name']); ?></h3>
                        <p class="game-category"><?php echo ucfirst($game['category']); ?></p>
                        <div class="game-price">$<?php echo number_format($game['price'], 2); ?></div>
                        <button class="buy-btn">Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

            <!-- If no games are found show this message -->
        <?php if (empty($filtered_games)): ?>
            <div class="no-games">
                <h3>No games found in this category</h3>
                <p>Try selecting a different category or browse all games.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>