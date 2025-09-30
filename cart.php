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

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $game_id = (int)($_POST['game_id'] ?? 0);
    
    if ($action === 'add' && $game_id > 0) {
        // Add to cart (prevent duplicates)
        if (!in_array($game_id, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $game_id;
        }
    } elseif ($action === 'remove' && $game_id > 0) {
        // Remove from cart
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($id) use ($game_id) {
            return $id != $game_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    } elseif ($action === 'clear') {
        // Clear entire cart
        $_SESSION['cart'] = [];
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

$games = loadGames();
$cart_games = [];
$total_price = 0;

// Get cart games and calculate total
foreach ($_SESSION['cart'] as $game_id) {
    $game = findGameById($games, $game_id);
    if ($game) {
        $cart_games[] = $game;
        $total_price += $game['price'];
    }
}

// Get user's coin balance
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include './inc/header.inc.php'; ?>

    <div class="container">
        <div class="cart-page">
            <div class="cart-header">
                <h2>🛒 Your Shopping Cart</h2>
                <p>Review your selected games before checkout</p>
            </div>

            <?php if (empty($cart_games)): ?>
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Browse our amazing collection of games and add some to your cart!</p>
                    <a href="shop.php" class="btn btn-primary">Browse Games</a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <?php foreach ($cart_games as $game): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="cart-item-image">
                                <div class="cart-item-info">
                                    <h3 class="cart-item-title">
                                        <a href="product.php?id=<?php echo $game['id']; ?>">
                                            <?php echo htmlspecialchars($game['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="cart-item-category"><?php echo ucfirst(htmlspecialchars($game['category'])); ?></p>
                                    <p class="cart-item-description"><?php echo htmlspecialchars($game['description']); ?></p>
                                </div>
                                <div class="cart-item-price">
                                                                <div class="game-price">🪙 <?php echo formatCoins($game['price']); ?></div>
                                </div>
                                <div class="cart-item-actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="btn-remove" onclick="return confirm('Remove this game from cart?')">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3>Order Summary</h3>
                            <div class="summary-row">
                                <span>Items (<?php echo count($cart_games); ?>):</span>
                                <span>🪙 <?php echo formatCoins($total_price); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax:</span>
                                <span>🪙 0</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>🪙 <?php echo formatCoins($total_price); ?></span>
                            </div>
                            
                            <div class="cart-actions">
                                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Clear entire cart?')">
                                        Clear Cart
                                    </button>
                                </form>
                                <a href="shop.php" class="btn card-btn secondary">Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include './inc/footer.inc.php'; ?>
</body>
</html>