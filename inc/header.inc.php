<?php
if(!isset($_SESSION)){
    session_start();
}

/* Include database classes if not already included */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/User.php';
}

/* Get current user data if logged in */
$username = 'Guest';
$user_balance = 0;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    try {
        $database = Database::getInstance();
        $pdo = $database->getConnection();
        $currentUser = User::getCurrentUser($pdo);
        
        if ($currentUser) {
            $username = $currentUser->getUsername();
            $user_balance = $currentUser->getBalance();
        }
    } catch (Exception $e) {
        /* Fallback to session data if available */
        $username = $_SESSION['username'] ?? 'Guest';
    }
}

function formatBalance($amount) {
    return '$' . number_format($amount, 2);
}

/* Determine if in admin directory for correct link paths */
$__in_admin = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false);
$__href = function($path) use ($__in_admin) {
    return ($__in_admin ? '../' : '') . ltrim($path, '/');
};
?>

<header class="header">
        <h1><a href="<?= $__href('index.php') ?>" class="logo-link">GAME STORE</a></h1>
        <div class="nav-links">
            <?php 
            $current_page = basename($_SERVER['SCRIPT_NAME']);
            ?>
            <a href="<?= $__href('shop.php') ?>" class="<?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">Shop</a>
            <a href="<?= $__href('library.php') ?>" class="<?php echo ($current_page == 'library.php') ? 'active' : ''; ?>">Library</a>
            <a href="<?= $__href('wishlist.php') ?>" class="<?php echo ($current_page == 'wishlist.php') ? 'active' : ''; ?>">Wishlist</a>
            <a href="<?= $__href('index.php') ?>" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
        </div>
        <div class="user-info">
            <div class="cart">
                <?php 
                $cart_count = 0;
                if (isset($currentUser)) {
                    try {
                        $cart_items = $currentUser->getShoppingCart();
                        $cart_count = count($cart_items);
                    } catch (Exception $e) {
                        $cart_count = 0;
                    }
                }
                ?>
                <a href="<?= $__href('cart.php') ?>" class="cart-link">🛒 Cart (<?php echo $cart_count; ?>)</a>
            </div>
            <div class="user-profile">
                <div class="user-details">
                    <a href="<?= $__href('profile.php') ?>" class="username-link" style="color: inherit; text-decoration: none;">
                        <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    </a>
                    <span class="balance"><?php echo isset($currentUser) && $currentUser ? $currentUser->getFormattedBalanceCoins() : number_format($user_balance * 100, 0) . ' coins'; ?></span>
                </div>
                <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true):
                include __DIR__ . '/nav.inc.php'; 
            endif; ?>
            </div>
        </div>
    </header>