<?php
/**
 * Admin Game Management
 * Add, edit, and manage games in the store
 */

session_start();
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Game.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$currentUser = new User($pdo);
$currentUser->loadById($_SESSION['user_id']);

if (!$currentUser->isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$message = '';
$error = '';

// Handle game actions
if ($_POST['action'] ?? '' === 'add_game') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $releaseDate = $_POST['release_date'];
    $categoryId = intval($_POST['category_id']);
    $sale = isset($_POST['sale']);
    $percentageId = $sale ? intval($_POST['percentage_id']) : null;
    
    $game = new Game($pdo);
    if ($game->create($name, $description, $price, $releaseDate, $categoryId, $sale, $percentageId)) {
        // Add screenshot if provided
        if (!empty($_POST['screenshot_url'])) {
            $game->addScreenshot($_POST['screenshot_url']);
        }
        $message = 'Game added successfully!';
    } else {
        $error = 'Failed to add game.';
    }
}

if ($_POST['action'] ?? '' === 'update_game') {
    $gameId = intval($_POST['game_id']);
    $game = new Game($pdo);
    
    if ($game->loadById($gameId)) {
        $game->setName(trim($_POST['name']));
        $game->setDescription(trim($_POST['description']));
        $game->setPrice(floatval($_POST['price']));
        $game->setReleaseDate($_POST['release_date']);
        $game->setCategoryId(intval($_POST['category_id']));
        $game->setSale(isset($_POST['sale']));
        
        if ($game->save()) {
            $message = 'Game updated successfully!';
        } else {
            $error = 'Failed to update game.';
        }
    }
}

if ($_POST['action'] ?? '' === 'delete_game') {
    $gameId = intval($_POST['game_id']);
    $game = new Game($pdo);
    
    if ($game->loadById($gameId)) {
        if ($game->delete()) {
            $message = 'Game deleted successfully!';
        } else {
            $error = 'Failed to delete game.';
        }
    }
}

// Get filter parameters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'category' => $_GET['category'] ?? '',
    'on_sale' => isset($_GET['on_sale']) ? true : null,
    'limit' => 20,
    'offset' => intval($_GET['page'] ?? 0) * 20
];

// Get games
$games = Game::getAll($pdo, $filters);

// Get categories for filters and forms
$categories = Game::getCategories($pdo);

// Get percentages for sale options
$stmt = $pdo->query("SELECT * FROM percentage ORDER BY percentage");
$percentages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM game g WHERE 1=1";
$countParams = [];
if ($filters['search']) {
    $countQuery .= " AND g.name LIKE ?";
    $countParams[] = '%' . $filters['search'] . '%';
}
if ($filters['category']) {
    $countQuery .= " AND g.fk_category = ?";
    $countParams[] = $filters['category'];
}
if ($filters['on_sale']) {
    $countQuery .= " AND g.sale = 1";
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalGames = $stmt->fetchColumn();
$totalPages = ceil($totalGames / 20);
$currentPage = intval($_GET['page'] ?? 0);

$pageTitle = 'Game Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
<?php include '../inc/header.inc.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6><i class="fas fa-cogs"></i> Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="games.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-gamepad"></i> Games
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="../index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home"></i> Back to Shop
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-gamepad"></i> Game Management</h1>
                <div>
                    <button type="button" class="btn btn-success" id="toggleAddGame">
                        <i class="fas fa-plus"></i> Add New Game
                    </button>
                    <span class="badge badge-primary ml-2"><?= number_format($totalGames) ?> Total Games</span>
                </div>
            </div>

            <!-- Inline Add Game Form (replaces Bootstrap modal) -->
            <div class="card mb-4" id="addGameCard" style="display:none;">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-plus-circle"></i> Add New Game</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_game">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Game Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label>Price (in dollars)</label>
                                    <input type="number" step="0.01" class="form-control" name="price" required>
                                </div>
                                <div class="form-group">
                                    <label>Release Date</label>
                                    <input type="date" class="form-control" name="release_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Screenshot URL</label>
                                    <input type="url" class="form-control" name="screenshot_url" placeholder="https://example.com/image.jpg">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select class="form-control" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="sale" id="addGameSale">
                                    <label class="form-check-label" for="addGameSale">On Sale</label>
                                </div>
                                <div class="form-group" id="addGamePercentageGroup" style="display:none;">
                                    <label>Sale Percentage</label>
                                    <select class="form-control" name="percentage_id">
                                        <?php foreach ($percentages as $percentage): ?>
                                            <?php if ($percentage['percentage'] > 0): ?>
                                                <option value="<?= $percentage['id'] ?>"><?= $percentage['percentage'] ?>% Off</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Create Game</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="form-inline">
                        <div class="form-group mr-3">
                            <input type="text" class="form-control" name="search" placeholder="Search games..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        <div class="form-group mr-3">
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $filters['category'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check mr-3">
                            <input class="form-check-input" type="checkbox" name="on_sale" id="on_sale" <?= $filters['on_sale'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="on_sale">On Sale Only</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="games.php" class="btn btn-secondary ml-2">Clear</a>
                    </form>
                </div>
            </div>
            
            <!-- Games Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($games)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Release Date</th>
                                        <th>Sale Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($games as $gameRow): ?>
                                        <tr>
                                            <td><?= $gameRow['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $gameObj = new Game($pdo);
                                                    $gameObj->loadById($gameRow['id']);
                                                    $screenshots = $gameObj->getScreenshots();
                                                    ?>
                                                    <?php if (!empty($screenshots)): ?>
                                                        <img src="<?= htmlspecialchars($screenshots[0]) ?>" alt="<?= htmlspecialchars($gameRow['name']) ?>" class="mr-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($gameRow['name']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($gameRow['description'], 0, 60)) ?>...</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($gameRow['category_name']) ?></td>
                                            <td>
                                                $<?= number_format($gameRow['price'], 2) ?>
                                                <?php if ($gameRow['sale']): ?>
                                                    <br><span class="badge badge-danger"><?= $gameRow['sale_percentage'] ?>% OFF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($gameRow['release_date'])) ?></td>
                                            <td>
                                                <?php if ($gameRow['sale']): ?>
                                                    <span class="badge badge-success">On Sale</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Regular</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../product.php?id=<?= $gameRow['id'] ?>" class="btn btn-outline-info" target="_blank">View</a>
                                                    <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editGameModal<?= $gameRow['id'] ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#deleteGameModal<?= $gameRow['id'] ?>">
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Game Modal -->
                                        <div class="modal fade" id="editGameModal<?= $gameRow['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Game: <?= htmlspecialchars($gameRow['name']) ?></h5>
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="update_game">
                                                            <input type="hidden" name="game_id" value="<?= $gameRow['id'] ?>">
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Game Name</label>
                                                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($gameRow['name']) ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label>Price</label>
                                                                        <input type="number" step="0.01" class="form-control" name="price" value="<?= $gameRow['price'] ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label>Release Date</label>
                                                                        <input type="date" class="form-control" name="release_date" value="<?= $gameRow['release_date'] ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Category</label>
                                                                        <select class="form-control" name="category_id" required>
                                                                            <?php foreach ($categories as $category): ?>
                                                                                <option value="<?= $category['id'] ?>" <?= $gameRow['fk_category'] == $category['id'] ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($category['name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="sale" id="sale<?= $gameRow['id'] ?>" <?= $gameRow['sale'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="sale<?= $gameRow['id'] ?>">
                                                                            On Sale
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Description</label>
                                                                <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($gameRow['description']) ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Game Modal -->
                                        <div class="modal fade" id="deleteGameModal<?= $gameRow['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Game: <?= htmlspecialchars($gameRow['name']) ?></h5>
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="delete_game">
                                                            <input type="hidden" name="game_id" value="<?= $gameRow['id'] ?>">
                                                            
                                                            <div class="alert alert-warning">
                                                                <strong>Warning!</strong> This action cannot be undone. Deleting this game will also remove it from all user libraries, wishlists, and purchase history.
                                                            </div>
                                                            
                                                            <p>Are you sure you want to delete <strong><?= htmlspecialchars($gameRow['name']) ?></strong>?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Delete Game</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Game pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($currentPage > 0): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $currentPage - 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(0, $currentPage - 2); $i <= min($totalPages - 1, $currentPage + 2); $i++): ?>
                                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>"><?= $i + 1 ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages - 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $currentPage + 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                            <h5>No Games Found</h5>
                            <p class="text-muted">Try adjusting your search criteria or add some games.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle add game form card
document.getElementById('toggleAddGame')?.addEventListener('click', function(){
    const card = document.getElementById('addGameCard');
    if (!card) return;
    card.style.display = (card.style.display === 'none' || card.style.display === '') ? 'block' : 'none';
});

// Show/hide sale percentage when sale checkbox is toggled
document.getElementById('addGameSale')?.addEventListener('change', function() {
    const percentageGroup = document.getElementById('addGamePercentageGroup');
    if (!percentageGroup) return;
    percentageGroup.style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include '../inc/footer.inc.php'; ?>
</body>
</html>