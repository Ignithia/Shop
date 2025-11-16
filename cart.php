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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $game_id = (int)($_POST['game_id'] ?? 0);
    
    if ($action === 'add' && $game_id > 0) {
        // Check if user already owns this game
        if (!$currentUser->ownsGame($game_id)) {
            $currentUser->addToCart($game_id);
        }
    } elseif ($action === 'add_multiple' && isset($_POST['game_ids'])) {
        foreach ($_POST['game_ids'] as $id) {
            $id = (int)$id;
            if ($id > 0 && !$currentUser->ownsGame($id)) {
                $currentUser->addToCart($id);
            }
        }
    } elseif ($action === 'remove' && $game_id > 0) {
        $currentUser->removeFromCart($game_id);
    } elseif ($action === 'clear') {
        $currentUser->clearCart();
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

// Get cart games and calculate total
$cart_games = $currentUser->getShoppingCart();
$total_price = 0;

foreach ($cart_games as $game) {
    $total_price += $game['price'];
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
                                <?php 
                                $game_obj = new Game($pdo);
                                $game_obj->loadById($game['id']);
                                $screenshots = $game_obj->getScreenshots();
                                $image_url = !empty($screenshots) ? $screenshots[0] : './media/default-game.jpg';
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="cart-item-image">
                                <div class="cart-item-info">
                                    <h3 class="cart-item-title">
                                        <a href="product.php?id=<?php echo $game['id']; ?>">
                                            <?php echo htmlspecialchars($game['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="cart-item-category"><?php echo htmlspecialchars($game['category_name'] ?? 'Unknown'); ?></p>
                                    <p class="cart-item-description"><?php echo htmlspecialchars($game['description']); ?></p>
                                </div>
                                <div class="cart-item-price">
                                    <div class="game-price">$<?php echo number_format($game['price'], 2); ?></div>
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
                                <span>$<?php echo number_format($total_price, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total_price, 2); ?></span>
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