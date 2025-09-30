<?php
if(!isset($_SESSION)){
    session_start();
}
?>

<header class="header">
        <h1><a href="index.php" class="logo-link">GAME STORE</a></h1>
        <div class="nav-links">
            <?php 
            $current_page = basename($_SERVER['SCRIPT_NAME']);
            ?>
            <a href="shop.php" class="<?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">Shop</a>
            <a href="library.php" class="<?php echo ($current_page == 'library.php') ? 'active' : ''; ?>">Library</a>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
        </div>
        <div class="user-info">
            <div class="cart">
                <a href="cart.php" class="cart-link">🛒 Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
            </div>
            <div class="user-profile">
                <div class="user-details">
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    <span class="balance">🪙 <?php echo formatCoins($user_coins); ?></span>
                </div>
                <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true):
                include 'nav.inc.php'; 
            endif; ?>
            </div>
        </div>
    </header>