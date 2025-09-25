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

// Get game ID from URL parameter
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load games from JSON file
function loadGames() {
    if (file_exists('data/games.json')) {
        $games_data = file_get_contents('data/games.json');
        $games = json_decode($games_data, true);
        return is_array($games) ? $games : [];
    }
    return [];
}

// Find specific game by ID
function findGameById($games, $id) {
    foreach ($games as $game) {
        if ($game['id'] == $id) {
            return $game;
        }
    }
    return null;
}

// Check if user already owns this game
function userOwnsGame($username, $game_id) {
    if (file_exists('data/users.json')) {
        $users_data = json_decode(file_get_contents('data/users.json'), true);
        if (is_array($users_data)) {
            foreach ($users_data as $user) {
                if ($user['username'] === $username) {
                    return isset($user['owned_games']) && in_array($game_id, $user['owned_games']);
                }
            }
        }
    }
    return false;
}

$games = loadGames();
$game = findGameById($games, $game_id);

// If game not found, redirect to shop
if (!$game) {
    header('Location: shop.php');
    exit();
}

$user_owns_game = userOwnsGame($username, $game_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1>GAME STORE</h1>
        <div class="user-info">
            <span>Player: <?php echo htmlspecialchars($username); ?></span>
            <a href="index.php" class="nav-btn">Dashboard</a>
            <a href="shop.php" class="nav-btn">Shop</a>
            <a href="library.php" class="nav-btn">Library</a>
            <a href="cart.php" class="nav-btn">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="product-page">
            <div class="product-header">
                <div class="product-image">
                    <?php if (isset($game['is_on_sale']) && $game['is_on_sale']): ?>
                        <div class="sale-badge">-<?php echo $game['sale_percentage']; ?>%</div>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
                </div>
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($game['name']); ?></h1>
                    <p class="product-category"><?php echo ucfirst(htmlspecialchars($game['category'])); ?></p>
                    
                    <?php if (isset($game['is_new']) && $game['is_new']): ?>
                        <div class="new-badge">NEW</div>
                    <?php endif; ?>
                    
                    <p class="product-description"><?php echo htmlspecialchars($game['description']); ?></p>
                    
                    
                    <div class="product-pricing">
                        <?php if (isset($game['is_on_sale']) && $game['is_on_sale']): ?>
                            <div class="price-container">
                                <span class="original-price">$<?php echo number_format($game['original_price'], 2); ?></span>
                                <span class="sale-price">$<?php echo number_format($game['price'], 2); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="product-price">$<?php echo number_format($game['price'], 2); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-actions">
                        <?php if ($user_owns_game): ?>
                            <button class="btn-owned" disabled>Already Owned ✓</button>
                            <a href="library.php" class="btn btn-secondary">View in Library</a>
                        <?php else: ?>
                            <form method="post" action="cart.php" style="display: inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                            </form>
                            <form method="post" action="purchase.php" style="display: inline;">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="btn card-btn">Buy Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="product-details">
                <h3>Game Details</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Category:</strong> <?php echo ucfirst(htmlspecialchars($game['category'])); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Price:</strong> $<?php echo number_format($game['price'], 2); ?>
                    </div>
                    <?php if (isset($game['is_on_sale']) && $game['is_on_sale']): ?>
                    <div class="detail-item">
                        <strong>Sale:</strong> <?php echo $game['sale_percentage']; ?>% OFF
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DLC Section -->
            <?php if (isset($game['dlcs']) && !empty($game['dlcs'])): ?>
            <div class="dlc-section">
                <h3>DLC's</h3>
                <div class="dlc-grid">
                    <?php foreach ($game['dlcs'] as $dlc): ?>
                        <div class="dlc-card">
                            <div class="dlc-info">
                                <h4 class="dlc-title"><?php echo htmlspecialchars($dlc['name']); ?></h4>
                                <p class="dlc-description"><?php echo htmlspecialchars($dlc['description']); ?></p>
                                <div class="dlc-price">
                                    <?php if ($dlc['price'] == 0): ?>
                                        <span class="free-dlc">FREE</span>
                                    <?php else: ?>
                                        $<?php echo number_format($dlc['price'], 2); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dlc-actions">
                                <?php if ($dlc['price'] == 0): ?>
                                    <button class="btn card-btn">Download Free</button>
                                <?php else: ?>
                                    <button class="btn card-btn">Add DLC to Cart</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="related-games">
                <h3>More <?php echo ucfirst(htmlspecialchars($game['category'])); ?> Games</h3>
                <div class="games-grid">
                    <?php 
                    $related_games = array_filter($games, function($g) use ($game) {
                        return $g['category'] === $game['category'] && $g['id'] !== $game['id'];
                    });
                    $related_games = array_slice($related_games, 0, 3); // Show only 3 related games
                    ?>
                    <?php foreach ($related_games as $related_game): ?>
                        <div class="game-card">
                            <img src="<?php echo htmlspecialchars($related_game['image']); ?>" alt="<?php echo htmlspecialchars($related_game['name']); ?>" class="game-image">
                            <div class="game-info">
                                <h4 class="game-title"><?php echo htmlspecialchars($related_game['name']); ?></h4>
                                <div class="game-price">$<?php echo number_format($related_game['price'], 2); ?></div>
                                <a href="product.php?id=<?php echo $related_game['id']; ?>" class="card-btn secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>