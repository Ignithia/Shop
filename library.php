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

// Get user's owned games
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

// Find game by ID
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

// Get full game details for owned games
foreach ($owned_game_ids as $game_id) {
    $game = findGameById($games, $game_id);
    if ($game) {
        $owned_games[] = $game;
    }
}

// Group games by category for better organization
$games_by_category = [];
foreach ($owned_games as $game) {
    $games_by_category[$game['category']][] = $game;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Game Library - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1>GAME STORE</h1>
        <div class="user-info">
            <span>Player: <?php echo htmlspecialchars($username); ?></span>
            <a href="index.php" class="nav-btn">Dashboard</a>
            <a href="shop.php" class="nav-btn">Shop</a>
            <span class="nav-btn active">Library</span>
            <a href="cart.php" class="nav-btn">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="library-page">
            <div class="library-header">
                <h2>🕹️ My Game Library</h2>
                <p>Your personal collection of <?php echo count($owned_games); ?> games</p>
            </div>

            <?php if (empty($owned_games)): ?>
                <div class="empty-library">
                    <h3>Your library is empty</h3>
                    <p>Start building your gaming collection by purchasing games from our store!</p>
                    <a href="shop.php" class="btn btn-primary">Browse Games</a>
                </div>
            <?php else: ?>
                <div class="library-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($owned_games); ?></div>
                        <div class="stat-label">Games Owned</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($games_by_category); ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format(array_sum(array_column($owned_games, 'price')), 2); ?></div>
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
                                        <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                                        <div class="game-info">
                                            <h4 class="game-title"><?php echo htmlspecialchars($game['name']); ?></h4>
                                            <p class="game-category"><?php echo ucfirst(htmlspecialchars($game['category'])); ?></p>
                                            <div class="game-price">$<?php echo number_format($game['price'], 2); ?></div>
                                            <div class="game-actions">
                                                <a href="product.php?id=<?php echo $game['id']; ?>" class="card-btn">View Details</a>
                                                <button class="card-btn secondary" onclick="alert('Play feature coming soon!')">Play Game</button>
                                            </div>
                                        </div>
                                        <div class="owned-badge">✓ OWNED</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="library-actions">
                    <h3>Discover More Games</h3>
                    <p>Expand your library with more amazing games!</p>
                    <a href="shop.php" class="btn btn-primary">Browse All Games</a>
                    <a href="shop.php?category=<?php echo array_keys($games_by_category)[0] ?? 'all'; ?>" class="btn card-btn">
                        More <?php echo ucfirst(array_keys($games_by_category)[0] ?? 'Games'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>