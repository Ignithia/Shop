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

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    $currentUser = User::getCurrentUser($pdo);
    
    if (!$currentUser) {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    die('Database connection failed. Please try again later.');
}

// Get purchase history with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$purchases = $currentUser->getPurchaseHistory($limit, $offset);

// Get total purchases for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM library WHERE fk_user = ?");
$stmt->execute([$currentUser->getId()]);
$total_purchases = $stmt->fetchColumn();
$total_pages = ceil($total_purchases / $limit);

function formatCoins($amount) {
    return number_format($amount, 0) . ' coins';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'inc/header.inc.php'; ?>
    
    <div class="container">
        <div class="purchases-header">
            <h1>📋 Purchase History</h1>
            <div class="purchase-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_purchases; ?></div>
                    <div class="stat-label">Total Purchases</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatCoins($currentUser->getTotalSpentInCoins()); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($currentUser->getOwnedGames()); ?></div>
                    <div class="stat-label">Games Owned</div>
                </div>
            </div>
        </div>

        <div class="purchases-content">
            <?php if (!empty($purchases)): ?>
                <div class="purchases-table-container">
                    <table class="purchases-table">
                        <thead>
                            <tr>
                                <th>Game</th>
                                <th>Price</th>
                                <th>Purchase Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td class="game-info">
                                        <div class="game-name">
                                            <?php echo htmlspecialchars($purchase['game_name'] ?? 'Unknown Game'); ?>
                                        </div>
                                    </td>
                                    <td class="purchase-price">
                                        <?php echo formatCoins(($purchase['price'] ?? 0) * 100); ?>
                                    </td>
                                    <td class="purchase-date">
                                        <div class="date-full"><?php echo date('M j, Y \a\t g:i A', strtotime($purchase['created_at'])); ?></div>
                                        <div class="date-relative"><?php echo timeAgo($purchase['created_at']); ?></div>
                                    </td>
                                    <td class="purchase-status">
                                        <span class="status-badge completed">✅ Completed</span>
                                    </td>
                                    <td class="purchase-actions">
                                        <a href="library.php" class="btn btn-sm btn-primary">View in Library</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-btn">← Previous</a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-btn">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-purchases">
                    <div class="no-purchases-icon">🛒</div>
                    <h2>No Purchases Yet</h2>
                    <p>You haven't made any purchases yet. Start exploring our game collection!</p>
                    <div class="no-purchases-actions">
                        <a href="shop.php" class="btn btn-primary">Browse Games</a>
                        <a href="wishlist.php" class="btn btn-secondary">View Wishlist</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Purchase Summary -->
        <div class="purchase-summary">
            <h2>📊 Purchase Summary</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Average Purchase</div>
                    <div class="summary-value">
                        <?php 
                        $avg = $total_purchases > 0 ? $currentUser->getTotalSpent() / $total_purchases : 0;
                        echo formatCoins($avg); 
                        ?>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Member Since</div>
                    <div class="summary-value">
                        <?php echo date('F Y', strtotime($currentUser->getJoindate())); ?>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Account Status</div>
                    <div class="summary-value">
                        <span class="status-badge active">Active</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Current Balance</div>
                    <div class="summary-value highlight">
                        <?php echo formatCoins($currentUser->getBalance()); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'inc/footer.inc.php'; ?>
</body>
</html>