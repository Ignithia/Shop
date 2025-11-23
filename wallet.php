<?php
session_start();

// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    User::logout();
    setcookie('remember_login', '', time() - 3600, '/');
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    $currentUser = User::getCurrentUser($pdo);
    
    if (!$currentUser) {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = 'Database connection failed. Please try again later.';
}

// Handle balance addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pdo)) {
    if (isset($_POST['add_coins'])) {
        $amount = intval($_POST['coin_amount'] ?? 0);
        if ($amount <= 0 || $amount > 100000) {
            $error_message = 'Please enter a valid amount between 1 and 100,000 coins.';
        } else {
            $dollars = $amount / 100;
            if ($currentUser->addBalance($dollars)) {
                $success_message = "Successfully added " . number_format($amount) . " coins to your wallet!";
            } else {
                $error_message = 'Error adding coins. Please try again.';
            }
        }
    }
}

function formatCoins($amount) {
    return number_format($amount, 0) . ' coins';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'inc/header.inc.php'; ?>
    
    <div class="container">
        <div class="wallet-header">
            <h1>💰 My Wallet</h1>
            <div class="wallet-balance-big">
                <div class="balance-label">Current Balance</div>
                <div class="balance-amount-large">
                    ⭐ <?php echo number_format($currentUser->getBalanceInCoins(), 0); ?> coins
                </div>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="wallet-content">
            <div class="wallet-left">
                <!-- Add Coins Section -->
                <div class="wallet-section">
                    <h2>💎 Add Coins</h2>
                    <p class="section-description">Purchase coins to buy games and items in our store.</p>
                    
                    <div class="coin-packages">
                        <div class="coin-package">
                            <div class="package-coins">1,000 coins</div>
                            <div class="package-price">$10.00</div>
                            <div class="package-bonus">Best Value!</div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="coin_amount" value="1000">
                                <button type="submit" name="add_coins" class="btn btn-primary">Purchase</button>
                            </form>
                        </div>
                        
                        <div class="coin-package">
                            <div class="package-coins">500 coins</div>
                            <div class="package-price">$5.00</div>
                            <div class="package-bonus">Popular</div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="coin_amount" value="500">
                                <button type="submit" name="add_coins" class="btn btn-primary">Purchase</button>
                            </form>
                        </div>
                        
                        <div class="coin-package">
                            <div class="package-coins">100 coins</div>
                            <div class="package-price">$1.00</div>
                            <div class="package-bonus">Starter</div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="coin_amount" value="100">
                                <button type="submit" name="add_coins" class="btn btn-primary">Purchase</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="custom-amount">
                        <h3>Custom Amount</h3>
                        <form method="POST" class="coin-form">
                            <div class="form-group">
                                <label for="coin_amount">Amount:</label>
                                <div class="input-with-symbol">
                                    <span class="input-symbol">⭐</span>
                                    <input type="number" id="coin_amount" name="coin_amount" 
                                           step="1" min="1" max="100000" 
                                           placeholder="Enter amount" required>
                                </div>
                                <small class="form-help">Minimum: 1 coin, Maximum: 100,000 coins</small>
                            </div>
                            <button type="submit" name="add_coins" class="btn btn-success">Add Coins</button>
                            <p class="demo-note">⚠️ This is a demo feature. In a real application, this would integrate with a payment processor.</p>
                        </form>
                    </div>
                </div>
            </div>

            <div class="wallet-right">
                <!-- Wallet Statistics -->
                <div class="wallet-section">
                    <h2>📊 Wallet Stats</h2>
                    <div class="wallet-stats">
                        <div class="stat-item">
                            <div class="stat-icon">💰</div>
                            <div class="stat-details">
                                <div class="stat-label">Total Balance</div>
                                <div class="stat-value"><?php echo formatCoins($currentUser->getBalanceInCoins()); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">💎</div>
                            <div class="stat-details">
                                <div class="stat-label">Total Spent</div>
                                <div class="stat-value"><?php echo formatCoins($currentUser->getTotalSpent()); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">🎮</div>
                            <div class="stat-details">
                                <div class="stat-label">Games Owned</div>
                                <div class="stat-value"><?php echo count($currentUser->getOwnedGames()); ?> games</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="wallet-section">
                    <h2>⚡ Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="shop.php" class="action-btn">
                            <span class="action-icon">🛍️</span>
                            <span class="action-text">Browse Shop</span>
                        </a>
                        <a href="cart.php" class="action-btn">
                            <span class="action-icon">🛒</span>
                            <span class="action-text">View Cart</span>
                        </a>
                        <a href="purchases.php" class="action-btn">
                            <span class="action-icon">📋</span>
                            <span class="action-text">Purchase History</span>
                        </a>
                        <a href="profile.php" class="action-btn">
                            <span class="action-icon">👤</span>
                            <span class="action-text">Profile Settings</span>
                        </a>
                    </div>
                </div>

                <!-- Coin Exchange Rates -->
                <div class="wallet-section">
                    <h2>💱 Exchange Info</h2>
                    <div class="exchange-info">
                        <div class="exchange-item">
                            <span class="rate">1 coin = $0.01 USD</span>
                        </div>
                        <div class="exchange-item">
                            <span class="rate">100 coins = $1.00 USD</span>
                        </div>
                        <div class="exchange-item">
                            <span class="rate">1,000 coins = $10.00 USD</span>
                        </div>
                    </div>
                    <p class="exchange-note">All transactions are secure and processed instantly.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'inc/footer.inc.php'; ?>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);

        // Format coin amount as user types
        document.getElementById('coin_amount').addEventListener('input', function() {
            let value = parseInt(this.value);
            if (value && value > 0) {
                // Could add real-time USD conversion here
            }
        });
    </script>
</body>
</html>