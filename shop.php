<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';
require_once 'classes/Category.php';
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

// Handle the wishlist additions/removals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_action'])) {
    // Validate CSRF token
    if (!CSRF::validatePost()) {
        die('Invalid CSRF token');
    }

    try {
        $game_id = intval($_POST['game_id']);

        if ($_POST['wishlist_action'] === 'add') {
            // Check if user owns this game - don't add if owned
            if (!$currentUser->ownsGame($game_id)) {
                $result = $currentUser->addToWishlist($game_id);
                if (!$result) {
                    die('Failed to add to wishlist');
                }
            }
        } elseif ($_POST['wishlist_action'] === 'remove') {
            $result = $currentUser->removeFromWishlist($game_id);
            if (!$result) {
                die('Failed to remove from wishlist');
            }
        }

        // Redirect to same page to prevent form resubmission
        $redirect_url = $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url");
        exit();
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
}

// Get search query and category from URL parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Prepare filters for games
$filters = [];
if (!empty($search_query)) {
    $filters['search'] = $search_query;
}
if ($selected_category !== 'all') {
    // Find category ID by name
    $category_result = Category::findByName($pdo, $selected_category);
    if ($category_result) {
        // Handle both array and integer returns
        $category_id = is_array($category_result) ? $category_result['id'] : $category_result;
        $filters['category'] = $category_id;
    } else {
        // Category name not found, show all games
        $selected_category = 'all';
    }
}

// Load games and categories
$games = Game::getAll($pdo, $filters);
$categories = Game::getAllCategories($pdo);
$user_wishlist = $currentUser->getWishlist();

// Create categories array for navigation
$category_nav = ['all' => 'All Games'];
foreach ($categories as $category) {
    $key = strtolower($category['name']);
    $category_nav[$key] = $category['name'];
}

// Convert wishlist to array of game IDs for easy checking
$wishlist_game_ids = array_column($user_wishlist, 'id');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Store - Shop</title>
    <link rel="stylesheet" href="./css/main.css">
</head>

<body>
    <?php include './inc/header.inc.php'; ?>

    <div class="container">
        <!-- Debug info -->
        <?php if (isset($_GET['debug'])): ?>
            <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
                <strong>Debug Info:</strong>
                <pre><?php print_r($debug_info); ?></pre>
            </div>
        <?php endif; ?>

        <div class="shop-header">
            <h2>🛒 Game Shop</h2>
            <p>Discover amazing games and improve your collection!</p>
        </div>
        php echo htmlspecialchars($search_query); ?>"
        class="search-input"
        autocomplete="off">
        <?php if ($selected_category !== 'all'): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
        <?php endif; ?>
        <button type="submit" class="search-btn">🔍</button>
    </div>
    <div id="search-results" class="search-dropdown" style="display: none;"></div>
    <?php if (!empty($search_query)): ?>
        <div class="search-results-info">
            <span>Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></span>
            <a href="?<?php echo ($selected_category !== 'all') ? 'category=' . $selected_category : ''; ?>" class="clear-search">Clear Search</a>
        </div>
    <?php endif; ?>
    </form>
    </div>

    <div class="category-nav">
        <?php foreach ($category_nav as $cat_key => $cat_name): ?>
            <a href="?category=<?php echo $cat_key; ?>"
                class="category-btn <?php echo ($selected_category === $cat_key) ? 'active' : ''; ?>">
                <?php echo $cat_name; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="games-grid">
        <?php foreach ($games as $game): ?>
            <div class="game-card">
                <div class="game-image-container">
                    <a href="product.php?id=<?php echo $game['id']; ?>">
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
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-image">
                    </a>

                    <?php if ($game['sale'] && $game['sale_percentage'] > 0): ?>
                        <div class="sale-badge">-<?php echo $game['sale_percentage']; ?>%</div>
                    <?php endif; ?>

                    <?php if (!$currentUser->ownsGame($game['id'])): ?>
                        <div class="wishlist-overlay">
                            <?php if (in_array($game['id'], $wishlist_game_ids)): ?>
                                <form method="post" style="margin: 0;">
                                    <?= CSRF::getTokenField() ?>
                                    <input type="hidden" name="wishlist_action" value="remove">
                                    <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" class="wishlist-heart-btn in-wishlist" title="Remove from wishlist">
                                        💔
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="margin: 0;">
                                    <?= CSRF::getTokenField() ?>
                                    <input type="hidden" name="wishlist_action" value="add">
                                    <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" class="wishlist-heart-btn not-in-wishlist" title="Add to wishlist">
                                        ❤️
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="game-info">
                    <h3 class="game-title">
                        <a href="product.php?id=<?php echo $game['id']; ?>">
                            <?php echo htmlspecialchars($game['name']); ?>
                        </a>
                    </h3>
                    <p class="game-category"><?php echo htmlspecialchars($game['category_name']); ?></p>

                    <div class="game-badges">
                        <?php
                        $release_date = new DateTime($game['release_date']);
                        $thirty_days_ago = new DateTime('-30 days');
                        if ($release_date > $thirty_days_ago):
                        ?>
                            <div class="new-badge-small">NEW</div>
                        <?php endif; ?>
                    </div>

                    <div class="game-pricing">
                        <?php if ($game['sale'] && $game['sale_percentage'] > 0): ?>
                            <?php
                            $original_price = $game['price'] * 100;
                            $sale_price = $game['price'] * (1 - $game['sale_percentage'] / 100) * 100;
                            ?>
                            <span class="original-price"><?php echo number_format($original_price, 0); ?> coins</span>
                            <span class="sale-price"><?php echo number_format($sale_price, 0); ?> coins</span>
                        <?php else: ?>
                            <div class="game-price"><?php echo number_format($game['price'] * 100, 0); ?> coins</div>
                        <?php endif; ?>
                    </div>

                    <div class="game-actions">
                        <?php if ($currentUser->ownsGame($game['id'])): ?>
                            <button class="buy-btn owned" disabled>✓ Owned</button>
                        <?php else: ?>
                            <button type="button" class="buy-btn ajax-add-cart" data-game-id="<?php echo $game['id']; ?>">Add to Cart</button>

                            <?php if (in_array($game['id'], $wishlist_game_ids)): ?>
                                <button type="button" class="card-btn wishlist-btn in-wishlist ajax-wishlist"
                                    data-game-id="<?php echo $game['id']; ?>"
                                    data-in-wishlist="true"
                                    title="Remove from wishlist">
                                    Remove from wishlist
                                </button>
                            <?php else: ?>
                                <button type="button" class="card-btn wishlist-btn ajax-wishlist"
                                    data-game-id="<?php echo $game['id']; ?>"
                                    data-in-wishlist="false"
                                    title="Add to wishlist">
                                    Add to wishlist
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="product.php?id=<?php echo $game['id']; ?>" class="card-btn secondary">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- If no games are found show this message -->
    <?php if (empty($games)): ?>
        <div class="no-games">
            <?php if (!empty($search_query)): ?>
                <h3>🔍 No games found for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                <p>Try adjusting your search terms or <a href="?<?php echo ($selected_category !== 'all') ? 'category=' . $selected_category : ''; ?>">browse all games</a>.</p>
            <?php else: ?>
                <h3>No games found in this category</h3>
                <p>Try selecting a different category or browse all games.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>

    <?php include './inc/footer.inc.php'; ?>

    <script>
        document.querySelectorAll('.ajax-add-cart').forEach(button => {
            button.addEventListener('click', function() {
                const gameId = this.getAttribute('data-game-id');
                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = 'Adding...';

                fetch('ajax_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=add_to_cart&game_id=' + gameId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.textContent = '✓ Added!';
                            this.classList.add('success-btn');

                            // Update cart count in header
                            const cartBadge = document.querySelector('.cart-link');
                            if (cartBadge && data.cart_count) {
                                cartBadge.textContent = '🛒 Cart (' + data.cart_count + ')';
                            }

                            setTimeout(() => {
                                this.textContent = originalText;
                                this.disabled = false;
                                this.classList.remove('success-btn');
                            }, 2000);
                        } else {
                            this.textContent = 'Failed';
                            this.disabled = false;
                            setTimeout(() => {
                                this.textContent = originalText;
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

        document.querySelectorAll('.ajax-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const gameId = this.getAttribute('data-game-id');
                const inWishlist = this.getAttribute('data-in-wishlist') === 'true';
                const action = inWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';

                const body = 'action=' + action + '&game_id=' + gameId;

                fetch('ajax_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: body
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Find the game card parent to update both buttons
                            const gameCard = this.closest('.game-card');

                            if (inWishlist) {
                                // Removing from wishlist
                                this.textContent = 'Add to wishlist';
                                this.classList.remove('in-wishlist');
                                this.setAttribute('data-in-wishlist', 'false');
                                this.title = 'Add to wishlist';

                                // Update heart button
                                const heartBtn = gameCard.querySelector('.wishlist-heart-btn');
                                if (heartBtn) {
                                    heartBtn.textContent = '❤️';
                                    heartBtn.classList.remove('in-wishlist');
                                    heartBtn.classList.add('not-in-wishlist');
                                    heartBtn.title = 'Add to wishlist';
                                    const form = heartBtn.closest('form');
                                    const actionInput = form.querySelector('input[name="wishlist_action"]');
                                    if (actionInput) actionInput.value = 'add';
                                }
                            } else {
                                // Adding to wishlist
                                this.textContent = 'Remove from wishlist';
                                this.classList.add('in-wishlist');
                                this.setAttribute('data-in-wishlist', 'true');
                                this.title = 'Remove from wishlist';

                                // Update heart button
                                const heartBtn = gameCard.querySelector('.wishlist-heart-btn');
                                if (heartBtn) {
                                    heartBtn.textContent = '💔';
                                    heartBtn.classList.remove('not-in-wishlist');
                                    heartBtn.classList.add('in-wishlist');
                                    heartBtn.title = 'Remove from wishlist';
                                    const form = heartBtn.closest('form');
                                    const actionInput = form.querySelector('input[name="wishlist_action"]');
                                    if (actionInput) actionInput.value = 'remove';
                                }
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });

        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    const categoryInput = document.querySelector('input[name="category"]');
                    const category = categoryInput ? categoryInput.value : 'all';

                    fetch('ajax_handler.php?action=search_games&query=' + encodeURIComponent(query) + '&category=' + category)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.games.length > 0) {
                                let html = '<div class="search-results-list">';
                                data.games.slice(0, 5).forEach(game => {
                                    const imageUrl = game.cover_image ?
                                        (game.cover_image.startsWith('http') ? game.cover_image : './media/' + game.cover_image) :
                                        './media/placeholder.jpg';
                                    const price = Math.round(game.price * 100);

                                    html += `
                                <a href="product.php?id=${game.id}" class="search-result-item">
                                    <img src="${imageUrl}" alt="${game.name}" onerror="this.src='./media/placeholder.jpg'">
                                    <div class="search-result-info">
                                        <div class="search-result-name">${game.name}</div>
                                        <div class="search-result-category">${game.category_name || 'Unknown'}</div>
                                    </div>
                                    <div class="search-result-price">${price.toLocaleString()} coins</div>
                                </a>
                            `;
                                });

                                if (data.games.length > 5) {
                                    html += `<div class="search-more">+${data.games.length - 5} more results</div>`;
                                }

                                html += '</div>';
                                searchResults.innerHTML = html;
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div class="search-no-results">No games found</div>';
                                searchResults.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            searchResults.style.display = 'none';
                        });
                }, 300);
            });

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>