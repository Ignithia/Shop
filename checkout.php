<?php
// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';
require_once 'classes/CSRF.php';

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

// Get cart items and calculate totals
$cart_items = $currentUser->getShoppingCart();
$total_price = 0;

foreach ($cart_items as $game) {
    $total_price += $game['price'] * 100;
}

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase') {
    if (!CSRF::validatePost()) {
        die('Invalid CSRF token');
    }

    if ($currentUser->getBalance() >= $total_price && !empty($cart_items)) {
        $transaction_started = false;
        try {
            $pdo->beginTransaction();
            $transaction_started = true;

            foreach ($cart_items as $game) {
                $currentUser->purchaseGame($game['id'], $game['price']);
            }

            $currentUser->clearCart();

            $pdo->commit();
            $transaction_started = false;

            header('Location: checkout.php?success=1');
            exit();
        } catch (Exception $e) {
            if ($transaction_started) {
                $pdo->rollback();
            }
            $error_message = 'Purchase failed. Please try again.';
        }
    } else {
        $error_message = $currentUser->getBalance() < $total_price ? 'Insufficient balance!' : 'Cart is empty!';
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
    <?php include './inc/header.inc.php'; ?>

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
                                    <?php
                                    if (!empty($game['cover_image'])) {
                                        if (filter_var($game['cover_image'], FILTER_VALIDATE_URL)) {
                                            $image_url = $game['cover_image'];
                                        } else {
                                            $image_url = './media/' . $game['cover_image'];
                                        }
                                    } else {
                                        $image_url = null;
                                    }
                                    ?>
                                    <?php if ($image_url): ?>
                                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="item-image">
                                    <?php endif; ?>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($game['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($game['category_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="item-price">
                                        <?php echo number_format($game['price'] * 100); ?> coins
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-total">
                            <div class="total-line">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($total_price); ?> coins</span>
                            </div>
                            <div class="total-line final">
                                <span>Total:</span>
                                <span><?php echo number_format($total_price); ?> coins</span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-section">
                        <h3>Payment</h3>
                        <div class="wallet-info">
                            <div class="wallet-balance">
                                <span>Your Balance:</span>
                                <span class="balance-amount"><?php echo number_format($currentUser->getBalance()); ?> coins</span>
                            </div>

                            <?php if ($currentUser->getBalance() >= $total_price): ?>
                                <div class="balance-status sufficient">
                                    ✅ Sufficient balance
                                </div>
                            <?php else: ?>
                                <div class="balance-status insufficient">
                                    ❌ Insufficient balance (Need <?php echo number_format($total_price - $currentUser->getBalance()); ?> more coins)
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
                                <?php if ($currentUser->getBalance() >= $total_price): ?>
                                    <button type="submit" class="btn btn-primary checkout-btn">
                                        Complete Purchase (<?php echo number_format($total_price); ?> coins)
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-disabled" disabled>
                                        Insufficient Balance
                                    </button>
                                    <a href="#" class="btn btn-secondary" onclick="alert('Balance top-up feature coming soon!')">
                                        Add Funds
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
    <?php include './inc/footer.inc.php'; ?>
</body>

</html>