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

// Find game by ID
function findGameById($games, $id) {
    foreach ($games as $game) {
        if ($game['id'] == $id) {
            return $game;
        }
    }
    return null;
}

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
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$user_coins = getUserCoins($username);

function formatCoins($amount) {
    return number_format($amount, 0, ',', '.');
}

// Calculate cart totals
$cart_items = [];
$total_price = 0;

foreach ($cart as $game_id) {
    $game = findGameById($games, $game_id);
    if ($game && !userOwnsGame($username, $game_id)) {
        $cart_items[] = $game;
        $total_price += $game['price'];
    }
}

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase') {
    if ($user_coins >= $total_price && !empty($cart_items)) {
        // Process purchase
        $users_data = json_decode(file_get_contents('data/users.json'), true);
        
        for ($i = 0; $i < count($users_data); $i++) {
            if ($users_data[$i]['username'] === $username) {
                // Deduct coins
                $users_data[$i]['coins'] = $user_coins - $total_price;
                
                // Add games to owned_games
                if (!isset($users_data[$i]['owned_games'])) {
                    $users_data[$i]['owned_games'] = [];
                }
                
                foreach ($cart_items as $game) {
                    if (!in_array($game['id'], $users_data[$i]['owned_games'])) {
                        $users_data[$i]['owned_games'][] = $game['id'];
                    }
                }
                break;
            }
        }
        
        // Save updated user data
        file_put_contents('data/users.json', json_encode($users_data, JSON_PRETTY_PRINT));
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to success page
        header('Location: checkout.php?success=1');
        exit();
    } else {
        $error_message = $user_coins < $total_price ? 'Insufficient coins!' : 'Cart is empty!';
    }
}

$success = isset($_GET['success']) && $_GET['success'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1>GAME STORE</h1>
        <div class="user-info">
            <span>Player: <?php echo htmlspecialchars($username); ?></span>
            <span class="coins-display">🪙 <?php echo formatCoins($user_coins); ?> Coins</span>
            <a href="index.php" class="nav-btn">Dashboard</a>
            <a href="shop.php" class="nav-btn">Shop</a>
            <a href="library.php" class="nav-btn">Library</a>
            <a href="cart.php" class="nav-btn">Cart (<?php echo count($cart); ?>)</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="checkout-page">
            <div class="checkout-header">
                <h2>💳 Checkout</h2>
                <p>Complete your purchase</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    <h3>🎉 Purchase Successful!</h3>
                    <p>Your games have been added to your library. Enjoy gaming!</p>
                    <div class="success-actions">
                        <a href="library.php" class="btn btn-primary">View Library</a>
                        <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                    </div>
                </div>
            <?php elseif (empty($cart_items)): ?>
                <div class="empty-checkout">
                    <h3>Your cart is empty</h3>
                    <p>Add some games to your cart before checking out!</p>
                    <a href="shop.php" class="btn btn-primary">Browse Games</a>
                </div>
            <?php else: ?>
                <div class="checkout-content">
                    <div class="order-summary">
                        <h3>Order Summary</h3>
                        <div class="order-items">
                            <?php foreach ($cart_items as $game): ?>
                                <div class="order-item">
                                    <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="item-image">
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($game['name']); ?></h4>
                                        <p><?php echo ucfirst($game['category']); ?></p>
                                    </div>
                                    <div class="item-price">
                                        🪙 <?php echo formatCoins($game['price']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total">
                            <div class="total-line">
                                <span>Subtotal:</span>
                                <span>🪙 <?php echo formatCoins($total_price); ?></span>
                            </div>
                            <div class="total-line final">
                                <span>Total:</span>
                                <span>🪙 <?php echo formatCoins($total_price); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-section">
                        <h3>Payment</h3>
                        <div class="wallet-info">
                            <div class="wallet-balance">
                                <span>Your Coin Balance:</span>
                                <span class="balance-amount">🪙 <?php echo formatCoins($user_coins); ?></span>
                            </div>
                            
                            <?php if ($user_coins >= $total_price): ?>
                                <div class="balance-status sufficient">
                                    ✅ Sufficient balance
                                </div>
                            <?php else: ?>
                                <div class="balance-status insufficient">
                                    ❌ Insufficient balance (Need 🪙 <?php echo formatCoins($total_price - $user_coins); ?> more)
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="error-message">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="checkout-form">
                            <input type="hidden" name="action" value="purchase">
                            
                            <div class="checkout-actions">
                                <?php if ($user_coins >= $total_price): ?>
                                    <button type="submit" class="btn btn-primary checkout-btn">
                                        Complete Purchase (🪙 <?php echo formatCoins($total_price); ?>)
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-disabled" disabled>
                                        Insufficient Coins
                                    </button>
                                    <a href="#" class="btn btn-secondary" onclick="alert('Coin top-up feature coming soon!')">
                                        Add Coins
                                    </a>
                                <?php endif; ?>
                                
                                <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>