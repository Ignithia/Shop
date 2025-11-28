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
                            <button type="button" class="btn btn-primary ajax-add-coins" data-amount="1000">Purchase</button>
                        </div>
                        
                        <div class="coin-package">
                            <div class="package-coins">500 coins</div>
                            <div class="package-price">$5.00</div>
                            <div class="package-bonus">Popular</div>
                            <button type="button" class="btn btn-primary ajax-add-coins" data-amount="500">Purchase</button>
                        </div>
                        
                        <div class="coin-package">
                            <div class="package-coins">100 coins</div>
                            <div class="package-price">$1.00</div>
                            <div class="package-bonus">Starter</div>
                            <button type="button" class="btn btn-primary ajax-add-coins" data-amount="100">Purchase</button>
                        </div>
                    </div>
                    
                    <div class="custom-amount">
                        <h3>Custom Amount</h3>
                        <div class="coin-form">
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
                            <button type="button" id="ajax-add-custom-coins" class="btn btn-success">Add Coins</button>
                            <p class="demo-note">⚠️ This is a demo feature. In a real application, this would integrate with a payment processor.</p>
                        </div>
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

        // AJAX Coin Purchase - Package buttons
        document.querySelectorAll('.ajax-add-coins').forEach(button => {
            button.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = 'Processing...';
                
                fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=add_coins&coin_amount=' + amount
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = '✓ Added!';
                        
                        // Update balance display
                        const balanceDisplay = document.querySelector('.balance-amount-large');
                        if (balanceDisplay) {
                            balanceDisplay.textContent = '⭐ ' + data.new_balance + ' coins';
                        }
                        
                        const statValue = document.querySelector('.stat-value');
                        if (statValue) {
                            statValue.textContent = data.new_balance + ' coins';
                        }
                        
                        // Show success message
                        showMessage(data.message, 'success');
                        
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.disabled = false;
                        }, 2000);
                    } else {
                        this.textContent = 'Failed';
                        showMessage(data.message, 'error');
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.disabled = false;
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.textContent = originalText;
                    this.disabled = false;
                });
            });
        });
        
        // AJAX Coin Purchase - Custom amount
        document.getElementById('ajax-add-custom-coins').addEventListener('click', function() {
            const amountInput = document.getElementById('coin_amount');
            const amount = parseInt(amountInput.value);
            
            if (!amount || amount < 1 || amount > 100000) {
                showMessage('Please enter a valid amount between 1 and 100,000 coins', 'error');
                return;
            }
            
            const originalText = this.textContent;
            this.disabled = true;
            this.textContent = 'Processing...';
            
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=add_coins&coin_amount=' + amount
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.textContent = '✓ Added!';
                    
                    // Update balance display
                    const balanceDisplay = document.querySelector('.balance-amount-large');
                    if (balanceDisplay) {
                        balanceDisplay.textContent = '⭐ ' + data.new_balance + ' coins';
                    }
                    
                    const statValue = document.querySelector('.stat-value');
                    if (statValue) {
                        statValue.textContent = data.new_balance + ' coins';
                    }
                    
                    // Show success message
                    showMessage(data.message, 'success');
                    
                    // Clear input
                    amountInput.value = '';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.disabled = false;
                    }, 2000);
                } else {
                    this.textContent = 'Failed';
                    showMessage(data.message, 'error');
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.textContent = originalText;
                this.disabled = false;
            });
        });
        
        // Helper function to show messages
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.wallet-header');
            if (container) {
                container.after(alertDiv);
                
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 3000);
            }
        }
    </script>
</body>
</html>