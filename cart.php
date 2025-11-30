<?php
// Include required classes
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

    header('Location: cart.php');
    exit();
}

// Get cart games and calculate total
$cart_games = $currentUser->getShoppingCart();
$total_price = 0;

foreach ($cart_games as $game) {
    $total_price += $game['price'] * 100;
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
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="cart-item-image">
                                <?php endif; ?>
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
                                    <div class="game-price"><?php echo number_format($game['price'] * 100); ?> coins</div>
                                </div>
                                <div class="cart-item-actions">
                                    <button type="button" class="btn-remove ajax-remove-cart" data-game-id="<?php echo $game['id']; ?>">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3>Order Summary</h3>
                            <div class="summary-row">
                                <span>Items (<?php echo count($cart_games); ?>):</span>
                                <span><?php echo number_format($total_price); ?> coins</span>
                            </div>
                            <div class="summary-row">
                                <span>Tax:</span>
                                <span>0 coins</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span><?php echo number_format($total_price); ?> coins</span>
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

    <script>
        // AJAX Remove from Cart
        document.querySelectorAll('.ajax-remove-cart').forEach(button => {
            button.addEventListener('click', function() {
                if (!confirm('Remove this game from cart?')) return;

                const gameId = this.getAttribute('data-game-id');
                const cartItem = this.closest('.cart-item');

                fetch('ajax_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=remove_from_cart&game_id=' + gameId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cartItem.style.opacity = '0';
                            setTimeout(() => {
                                cartItem.remove();

                                const cartBadge = document.querySelector('.cart-link');
                                if (cartBadge) {
                                    cartBadge.textContent = '🛒 Cart (' + data.cart_count + ')';
                                }

                                const itemCount = document.querySelector('.summary-row span');
                                if (itemCount) {
                                    itemCount.textContent = 'Items (' + data.cart_count + '):';
                                }

                                const totalElements = document.querySelectorAll('.summary-row span:last-child');
                                if (totalElements.length >= 2) {
                                    totalElements[0].textContent = data.total_price + ' coins';
                                    totalElements[2].textContent = data.total_price + ' coins';
                                }

                                if (data.is_empty) {
                                    window.location.reload();
                                }
                            }, 300);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>

</html>