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

// Get game ID from URL parameter
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load specific game
$game = new Game($pdo);
if (!$game->loadById($game_id)) {
    header('Location: shop.php');
    exit();
}

$user_owns_game = $currentUser->ownsGame($game_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game->getName()); ?> - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include './inc/header.inc.php'; ?>

    <div class="container">
        <div class="product-page">
            <div class="product-header">
                <div class="product-image">
                    <?php if ($game->isOnSale()): ?>
                        <div class="sale-badge">-<?php echo $game->getSalePercentage(); ?>%</div>
                    <?php endif; ?>
                    <?php 
                    $screenshots = $game->getScreenshots();
                    
                    // Get cover image
                    if (!empty($game->getCoverImage())) {
                        if (filter_var($game->getCoverImage(), FILTER_VALIDATE_URL)) {
                            $image_url = $game->getCoverImage();
                        } else {
                            $image_url = './media/' . $game->getCoverImage();
                        }
                    } else {
                        $image_url = null;
                    }
                    ?>
                    <?php if ($image_url): ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($game->getName()); ?>">
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($game->getName()); ?></h1>
                    <p class="product-category"><?php echo htmlspecialchars($game->getCategoryName()); ?></p>
                    
                    <?php if ($game->isNew()): ?>
                        <div class="new-badge">NEW</div>
                    <?php endif; ?>
                    
                    <p class="product-description"><?php echo htmlspecialchars($game->getDescription()); ?></p>
                    
                    <div class="product-pricing">
                        <?php if ($game->isOnSale() && $game->getSalePercentage() > 0): ?>
                            <div class="price-container">
                                <span class="original-price"><?php echo $game->getFormattedOriginalPrice(); ?></span>
                                <span class="sale-price"><?php echo $game->getFormattedPrice(); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="product-price"><?php echo $game->getFormattedPrice(); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-actions">
                        <?php if ($user_owns_game): ?>
                            <button class="btn-owned" disabled>Already Owned ✓</button>
                            <a href="library.php" class="btn btn-secondary">View in Library</a>
                        <?php else: ?>
                            <form method="post" action="cart.php" style="display: inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="game_id" value="<?php echo $game->getId(); ?>">
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                            </form>
                            <a href="checkout.php" class="btn card-btn">Buy Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="product-details">
                <h3>Game Details</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Category:</strong> <?php echo htmlspecialchars($game->getCategoryName()); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Price:</strong> <?php echo $game->getFormattedPrice(); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Release Date:</strong> <?php echo date('F j, Y', strtotime($game->getReleaseDate())); ?>
                    </div>
                    <?php if ($game->isOnSale() && $game->getSalePercentage() > 0): ?>
                    <div class="detail-item">
                        <strong>Sale:</strong> <?php echo $game->getSalePercentage(); ?>% OFF
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="related-games">
                <h3>More <?php echo htmlspecialchars($game->getCategoryName()); ?> Games</h3>
                <div class="games-grid">
                    <?php 
                    $related_games = Game::getByCategory($pdo, $game->getCategoryId());
                    $related_games = array_filter($related_games, function($g) use ($game_id) {
                        return $g['id'] !== $game_id;
                    });
                    $related_games = array_slice($related_games, 0, 3); // Show only 3 related games
                    ?>
                    <?php foreach ($related_games as $related_game): ?>
                        <div class="game-card">
                            <?php 
                            // Get cover image for related game
                            if (!empty($related_game['cover_image'])) {
                                if (filter_var($related_game['cover_image'], FILTER_VALIDATE_URL)) {
                                    $related_image_url = $related_game['cover_image'];
                                } else {
                                    $related_image_url = './media/' . $related_game['cover_image'];
                                }
                            } else {
                                $related_image_url = null;
                            }
                            ?>
                            <?php if ($related_image_url): ?>
                                <img src="<?php echo htmlspecialchars($related_image_url); ?>" alt="<?php echo htmlspecialchars($related_game['name']); ?>" class="game-image">
                            <?php endif; ?>
                            <div class="game-info">
                                <h4 class="game-title"><?php echo htmlspecialchars($related_game['name']); ?></h4>
                                <div class="game-price">$<?php echo number_format($related_game['price'], 2); ?></div>
                                <a href="product.php?id=<?php echo $related_game['id']; ?>" class="card-btn secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include './inc/footer.inc.php'; ?>
</body>
</html>