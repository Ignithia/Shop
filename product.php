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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    
    $stmt = $pdo->prepare("SELECT fk_user FROM review WHERE id = ? AND fk_game = ?");
    $stmt->execute([$review_id, $game_id]);
    $review_owner = $stmt->fetchColumn();
    
    if ($review_owner == $currentUser->getId() || $currentUser->isAdmin()) {
        $stmt = $pdo->prepare("DELETE FROM review WHERE id = ?");
        if ($stmt->execute([$review_id])) {
            $review_message = 'Review deleted successfully!';
        } else {
            $review_error = 'Failed to delete review.';
        }
    } else {
        $review_error = 'You do not have permission to delete this review.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review'])) {
    $review_id = intval($_POST['review_id']);
    $review_text = trim($_POST['review_text'] ?? '');
    $recommended = isset($_POST['recommended']) ? 1 : 0;
    
    if (empty($review_text)) {
        $review_error = 'Please write a review.';
    } else {
        $stmt = $pdo->prepare("SELECT fk_user FROM review WHERE id = ? AND fk_game = ?");
        $stmt->execute([$review_id, $game_id]);
        $review_owner = $stmt->fetchColumn();
        
        if ($review_owner == $currentUser->getId()) {
            $stmt = $pdo->prepare("UPDATE review SET text = ?, recommended = ? WHERE id = ?");
            if ($stmt->execute([$review_text, $recommended, $review_id])) {
                $review_message = 'Review updated successfully!';
            } else {
                $review_error = 'Failed to update review.';
            }
        } else {
            $review_error = 'You do not have permission to edit this review.';
        }
    }
}

// Handle review submission
$review_message = '';
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($user_owns_game) {
        $review_text = trim($_POST['review_text'] ?? '');
        $recommended = isset($_POST['recommended']) ? 1 : 0;
        
        if (empty($review_text)) {
            $review_error = 'Please write a review.';
        } else {
            // Check if user already reviewed this game
            $stmt = $pdo->prepare("SELECT id FROM review WHERE fk_user = ? AND fk_game = ?");
            $stmt->execute([$currentUser->getId(), $game_id]);
            
            if ($stmt->fetch()) {
                $review_error = 'You have already reviewed this game.';
            } else {
                // Insert review
                $stmt = $pdo->prepare("INSERT INTO review (text, recommended, created_at, fk_user, fk_game) VALUES (?, ?, NOW(), ?, ?)");
                if ($stmt->execute([$review_text, $recommended, $currentUser->getId(), $game_id])) {
                    $review_message = 'Review posted successfully!';
                } else {
                    $review_error = 'Failed to post review.';
                }
            }
        }
    } else {
        $review_error = 'You must own this game to review it.';
    }
}

// Get reviews for this game
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM review r 
    JOIN users u ON r.fk_user = u.id 
    WHERE r.fk_game = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$game_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate review statistics
$total_reviews = count($reviews);
$recommended_count = 0;
foreach ($reviews as $review) {
    if ($review['recommended']) {
        $recommended_count++;
    }
}
$recommendation_percentage = $total_reviews > 0 ? round(($recommended_count / $total_reviews) * 100) : 0;

// Check if current user has already reviewed
$user_has_reviewed = false;
if ($user_owns_game) {
    $stmt = $pdo->prepare("SELECT id FROM review WHERE fk_user = ? AND fk_game = ?");
    $stmt->execute([$currentUser->getId(), $game_id]);
    $user_has_reviewed = $stmt->fetch() !== false;
}
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
                <div class="product-image-section">
                    <?php 
                    $screenshots = $game->getScreenshots();
                    
                    // Collect all images
                    $all_images = [];
                    
                    if (!empty($game->getCoverImage())) {
                        if (filter_var($game->getCoverImage(), FILTER_VALIDATE_URL)) {
                            $all_images[] = $game->getCoverImage();
                        } else {
                            $all_images[] = './media/' . $game->getCoverImage();
                        }
                    }
                    
                    // Add screenshots
                    foreach ($screenshots as $screenshot) {
                        if (filter_var($screenshot, FILTER_VALIDATE_URL)) {
                            $all_images[] = $screenshot;
                        } else {
                            $all_images[] = './media/' . $screenshot;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($all_images)): ?>
                    <div class="product-image">
                        <?php if ($game->isOnSale()): ?>
                            <div class="sale-badge">-<?php echo $game->getSalePercentage(); ?>%</div>
                        <?php endif; ?>
                        <img id="mainProductImage" src="<?php echo htmlspecialchars($all_images[0]); ?>" alt="<?php echo htmlspecialchars($game->getName()); ?>">
                    </div>
                    
                    <?php if (count($all_images) > 1): ?>
                    <div class="product-thumbnails">
                        <button class="thumbnail-nav prev" onclick="scrollThumbnails(-1)">‹</button>
                        <div class="thumbnails-container">
                            <?php foreach ($all_images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                     alt="<?php echo htmlspecialchars($game->getName()); ?> screenshot <?php echo $index + 1; ?>"
                                     class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo addslashes($image); ?>', this)">
                            <?php endforeach; ?>
                        </div>
                        <button class="thumbnail-nav next" onclick="scrollThumbnails(1)">›</button>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="product-image">
                        <?php if ($game->isOnSale()): ?>
                            <div class="sale-badge">-<?php echo $game->getSalePercentage(); ?>%</div>
                        <?php endif; ?>
                        <div style="width: 100%; height: 400px; background: var(--bg-secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            <div style="text-align: center;">
                                <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                                <p>No images available</p>
                            </div>
                        </div>
                    </div>
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
                    <?php if ($total_reviews > 0): ?>
                    <div class="detail-item detail-reviews">
                        <strong>Reviews:</strong> 
                        <?php if ($recommendation_percentage >= 80): ?>
                            <span class="badge-positive">Overwhelmingly Positive</span>
                        <?php elseif ($recommendation_percentage >= 70): ?>
                            <span class="badge-positive">Very Positive</span>
                        <?php elseif ($recommendation_percentage >= 60): ?>
                            <span class="badge-positive">Positive</span>
                        <?php elseif ($recommendation_percentage >= 50): ?>
                            <span class="badge-mixed">Mixed</span>
                        <?php elseif ($recommendation_percentage >= 40): ?>
                            <span class="badge-negative">Mostly Negative</span>
                        <?php else: ?>
                            <span class="badge-negative">Negative</span>
                        <?php endif; ?>
                        <span class="review-stats-inline">
                            (<?php echo $recommendation_percentage; ?>% of <?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?>)
                        </span>
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
                    $related_games = array_slice($related_games, 0, 3);
                    ?>
                    <?php foreach ($related_games as $related_game): ?>
                        <div class="game-card">
                            <?php 
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
                                <div class="game-price"><?php echo number_format($related_game['price'] * 100); ?> coins</div>
                                <a href="product.php?id=<?php echo $related_game['id']; ?>" class="card-btn secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reviews-section">
                <h3>Reviews</h3>
                
                <?php if ($user_owns_game && !$user_has_reviewed): ?>
                <div class="review-form-card">
                    <h4>Write a Review</h4>
                    <?php if ($review_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($review_message); ?></div>
                    <?php endif; ?>
                    <?php if ($review_error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($review_error); ?></div>
                    <?php endif; ?>
                    <form method="post" class="review-form">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="recommended" value="1" checked>
                                <span class="checkbox-text">
                                    <i class="fas fa-thumbs-up"></i> I recommend this game
                                </span>
                            </label>
                        </div>
                        <div class="form-group">
                            <textarea name="review_text" class="form-control" rows="5" placeholder="Share your thoughts about this game..." required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">Post Review</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="reviews-list">
                    <?php if ($review_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($review_message); ?></div>
                    <?php endif; ?>
                    <?php if ($review_error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($review_error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($reviews)): ?>
                        <p class="no-reviews">No reviews yet. Be the first to review this game!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <?php 
                            $is_own_review = $review['fk_user'] == $currentUser->getId();
                            $can_delete = $is_own_review || $currentUser->isAdmin();
                            $is_editing = isset($_GET['edit_review']) && $_GET['edit_review'] == $review['id'];
                            ?>
                            
                            <?php if ($is_editing): ?>
                            <div class="review-item review-edit-mode">
                                <h4>Edit Your Review</h4>
                                <form method="post" class="review-form">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="recommended" value="1" <?php echo $review['recommended'] ? 'checked' : ''; ?>>
                                            <span class="checkbox-text">
                                                <i class="fas fa-thumbs-up"></i> I recommend this game
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <textarea name="review_text" class="form-control" rows="5" required><?php echo htmlspecialchars($review['text']); ?></textarea>
                                    </div>
                                    <div class="review-actions">
                                        <button type="submit" name="edit_review" class="btn btn-primary">Save Changes</button>
                                        <a href="product.php?id=<?php echo $game_id; ?>" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            <?php else: ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-author">
                                        <i class="fas fa-user-circle"></i>
                                        <span class="username"><?php echo htmlspecialchars($review['username']); ?></span>
                                        <?php if ($is_own_review): ?>
                                            <span class="review-owner-badge">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-meta">
                                        <?php if ($review['recommended']): ?>
                                            <span class="review-badge recommended">
                                                <i class="fas fa-thumbs-up"></i> Recommended
                                            </span>
                                        <?php else: ?>
                                            <span class="review-badge not-recommended">
                                                <i class="fas fa-thumbs-down"></i> Not Recommended
                                            </span>
                                        <?php endif; ?>
                                        <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($review['text'])); ?>
                                </div>
                                <?php if ($can_delete): ?>
                                <div class="review-actions">
                                    <?php if ($is_own_review): ?>
                                        <a href="product.php?id=<?php echo $game_id; ?>&edit_review=<?php echo $review['id']; ?>" class="btn-review-action btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="delete_review" class="btn-review-action btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function changeMainImage(imageUrl, thumbnail) {
        document.getElementById('mainProductImage').src = imageUrl;

        document.querySelectorAll('.thumbnail-item').forEach(item => {
            item.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
    
    function scrollThumbnails(direction) {
        const container = document.querySelector('.thumbnails-container');
        const scrollAmount = 150;
        container.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }
    </script>
    
    <?php include './inc/footer.inc.php'; ?>
</body>
</html>