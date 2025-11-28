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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_game'])) {
        $game_id = intval($_POST['game_id']);
        $currentUser->removeFromWishlist($game_id);
    }
    
    if (isset($_POST['clear_wishlist'])) {
        // Remove all items from wishlist
        $wishlist_items = $currentUser->getWishlist();
        foreach ($wishlist_items as $item) {
            $currentUser->removeFromWishlist($item['id']);
        }
    }
    
    if (isset($_POST['add_to_wishlist'])) {
        $game_id = intval($_POST['game_id']);
        if (!$currentUser->ownsGame($game_id)) {
            $currentUser->addToWishlist($game_id);
        }
    }
    
    if (isset($_POST['update_rank'])) {
        $game_id = intval($_POST['game_id']);
        $new_rank = intval($_POST['new_rank']);
        // Note: Rank updating would need additional implementation in User class
        // For now, we'll just redirect without changing rank
    }
    
    if (isset($_POST['reorder_wishlist'])) {
        $new_order = json_decode($_POST['new_order'], true);
        // Note: Reordering would need additional implementation in User class
        // For now, we'll just redirect without changing order
    }
    
    header('Location: wishlist.php');
    exit();
}

// Get user's wishlist
$user_wishlist = $currentUser->getWishlist();

// Handle search
$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $user_wishlist = array_filter($user_wishlist, function($game) use ($search_query) {
        return stripos($game['name'], $search_query) !== false || 
               stripos($game['category_name'], $search_query) !== false;
    });
}

// Handle sorting
$sort_by = $_GET['sort'] ?? 'rank';
usort($user_wishlist, function($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price-low':
            return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        case 'price-high':
            return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        case 'name':
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        case 'category':
            return strcasecmp($a['category_name'] ?? '', $b['category_name'] ?? '');
        case 'date-added':
            return strtotime($b['added_at']) <=> strtotime($a['added_at']);
        default:
            return $a['rank'] <=> $b['rank'];
    }
});

// Calculate totals
$total_value = 0;
$affordable_count = 0;
foreach ($user_wishlist as $game) {
    $price = $game['price'] ?? 0;
    $total_value += $price * 100; // Convert to coins
    if (($price * 100) <= $currentUser->getBalance()) {
        $affordable_count++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body>
    <?php include './inc/header.inc.php'; ?>

    <div class="container">
        <div class="wishlist-page">
            <div class="wishlist-header">
                <h2>My Wishlist</h2>
                <p>Games you're planning to buy - <span id="wishlist-count"><?= count($user_wishlist) ?></span> items</p>
            </div>

            <!-- Search and Sort Section -->
            <div class="wishlist-controls">
                <div class="search-container">
                    <form method="GET" action="wishlist.php" style="display: flex; gap: 10px;">
                        <input type="text" 
                               name="search"
                               value="<?= htmlspecialchars($search_query) ?>"
                               placeholder="Search your wishlist..." 
                               class="search-input">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                        <button type="submit" class="search-btn">🔍</button>
                        <?php if ($search_query): ?>
                            <a href="wishlist.php?sort=<?= htmlspecialchars($sort_by) ?>" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="sort-container">
                    <form method="GET" action="wishlist.php">
                        <?php if ($search_query): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <?php endif; ?>
                        <label for="sort-by">Sort by:</label>
                        <select name="sort" id="sort-by" class="sort-dropdown" onchange="this.form.submit()">
                            <option value="rank" <?= $sort_by === 'rank' ? 'selected' : '' ?>>My Ranking</option>
                            <option value="price-low" <?= $sort_by === 'price-low' ? 'selected' : '' ?>>Price (Low to High)</option>
                            <option value="price-high" <?= $sort_by === 'price-high' ? 'selected' : '' ?>>Price (High to Low)</option>
                            <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="category" <?= $sort_by === 'category' ? 'selected' : '' ?>>Category</option>
                            <option value="date-added" <?= $sort_by === 'date-added' ? 'selected' : '' ?>>Date Added</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="wishlist-items" id="wishlist-items">
                <?php if (empty($user_wishlist)): ?>
                    <div class="empty-wishlist" id="empty-wishlist">
                        <h3>Your wishlist is empty</h3>
                        <p>Start adding games you want to buy later!</p>
                        <a href="shop.php" class="btn btn-primary">Browse Games</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($user_wishlist as $wishlist_item): ?>
                        <?php 
                        $game = $wishlist_item;
                        $is_owned = $currentUser->ownsGame($game['id']);
                        $can_afford = ($game['price'] * 100) <= $currentUser->getBalance();
                        ?>
                        <div class="wishlist-item" data-game-id="<?= $game['id'] ?>">
                            <div class="item-rank">
                                <span class="rank-label">#<?= $game['rank'] ?></span>
                                <form method="POST" style="margin: 5px 0;">
                                    <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                    <input type="hidden" name="update_rank" value="1">
                                    <input type="number" 
                                           name="new_rank"
                                           class="rank-input" 
                                           value="<?= $game['rank'] ?>" 
                                           min="1" 
                                           max="<?= count($user_wishlist) ?>"
                                           onchange="this.form.submit()">
                                </form>
                                <div class="drag-handle" title="Drag to reorder">⋮⋮</div>
                            </div>
                            
                            <div class="item-image">
                                <a href="product.php?id=<?= $game['id'] ?>">
                                    <?php 
                                    if (!empty($game['cover_image'])) {
                                        if (filter_var($game['cover_image'], FILTER_VALIDATE_URL)) {
                                            $image_url = $game['cover_image'];
                                        } else {
                                            $image_url = './media/' . $game['cover_image'];
                                        }
                                    } else {
                                        $image_url = './media/placeholder.jpg';
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($image_url) ?>" 
                                         alt="<?= htmlspecialchars($game['name']) ?>"
                                         onerror="this.src='./media/placeholder.jpg'">
                                </a>
                            </div>
                            
                            <div class="item-info">
                                <h4 class="item-title">
                                    <a href="product.php?id=<?= $game['id'] ?>"><?= htmlspecialchars($game['name']) ?></a>
                                </h4>
                                <span class="item-category"><?= htmlspecialchars($game['category_name'] ?? 'Unknown') ?></span>
                                <span class="item-date">Added: <?= date('M j, Y', strtotime($game['added_at'])) ?></span>
                            </div>
                            
                            <div class="item-price">
                                <?php if ($game['sale']): ?>
                                    <span class="original-price"><?= number_format(($game['original_price'] ?? $game['price']) * 100) ?> coins</span>
                                    <span class="sale-price"><?= number_format($game['price'] * 100) ?> coins</span>
                                <?php else: ?>
                                    <span class="price-amount <?= $can_afford ? 'affordable' : 'expensive' ?>">
                                        <?= number_format($game['price'] * 100) ?> coins
                                    </span>
                                <?php endif; ?>
                                <?php if (!$can_afford): ?>
                                    <small style="color: #e74c3c; display: block;">Need <?= number_format(($game['price'] * 100) - $currentUser->getBalance()) ?> more coins</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <?php if (!$is_owned): ?>
                                    <form method="post" action="cart.php" style="margin-bottom: 8px;">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-primary" 
                                                <?= !$can_afford ? 'disabled title="Insufficient funds"' : '' ?>>
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="btn" style="background: #27ae60; margin-bottom: 5px;">Owned</span>
                                <?php endif; ?>
                                
                                <form method="POST" onsubmit="return confirm('Remove this game from wishlist?')">
                                    <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                    <input type="hidden" name="remove_game" value="1">
                                    <button type="submit" class="btn btn-secondary">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($user_wishlist)): ?>
                <div class="wishlist-summary" id="wishlist-summary">
                    <div class="summary-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= count($user_wishlist) ?></span>
                            <span class="stat-label">Games</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= number_format($total_value) ?> coins</span>
                            <span class="stat-label">Total Value</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $affordable_count ?></span>
                            <span class="stat-label">You Can Afford</span>
                        </div>
                    </div>
                    
                    <div class="bulk-actions">
                        <form method="POST" action="cart.php" style="display: inline;">
                            <input type="hidden" name="action" value="add_multiple">
                            <?php foreach ($user_wishlist as $game): ?>
                                <?php if ($game['price'] * 100 <= $currentUser->getBalance() && !$currentUser->ownsGame($game['id'])): ?>
                                    <input type="hidden" name="game_ids[]" value="<?= $game['id'] ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary" <?= $affordable_count === 0 ? 'disabled' : '' ?>>
                                🛒 Add Affordable to Cart (<?= $affordable_count ?>)
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Clear entire wishlist? This cannot be undone.')">
                            <input type="hidden" name="clear_wishlist" value="1">
                            <button type="submit" class="btn btn-secondary">
                                🗑️ Clear Wishlist
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include './inc/footer.inc.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wishlistItems = document.getElementById('wishlist-items');
            
            if (wishlistItems && wishlistItems.children.length > 0) {
                new Sortable(wishlistItems, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    handle: '.drag-handle',
                    onEnd: function(evt) {
                        const items = Array.from(wishlistItems.children);
                        const newOrder = items.map(item => parseInt(item.dataset.gameId));
                        
                        // Send new order to server
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        const input1 = document.createElement('input');
                        input1.type = 'hidden';
                        input1.name = 'reorder_wishlist';
                        input1.value = '1';
                        
                        const input2 = document.createElement('input');
                        input2.type = 'hidden';
                        input2.name = 'new_order';
                        input2.value = JSON.stringify(newOrder);
                        
                        form.appendChild(input1);
                        form.appendChild(input2);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>
