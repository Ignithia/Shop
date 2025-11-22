<?php

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

// Handle category actions
if (($_POST['action'] ?? '') === 'add_category') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                $message = 'Category added successfully!';
            } else {
                $error = 'Failed to add category.';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if (($_POST['action'] ?? '') === 'update_category') {
    $categoryId = intval($_POST['category_id']);
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE category SET name = ? WHERE id = ?");
            if ($stmt->execute([$name, $categoryId])) {
                $message = 'Category updated successfully!';
            } else {
                $error = 'Failed to update category.';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if (($_POST['action'] ?? '') === 'delete_category') {
    $categoryId = intval($_POST['category_id']);
    
    // Check if category has games
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game WHERE fk_category = ?");
    $stmt->execute([$categoryId]);
    $gameCount = $stmt->fetchColumn();
    
    if ($gameCount > 0) {
        $error = "Cannot delete category. It has {$gameCount} game(s) assigned to it.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
            if ($stmt->execute([$categoryId])) {
                $message = 'Category deleted successfully!';
            } else {
                $error = 'Failed to delete category.';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all categories with game counts
$stmt = $pdo->query("
    SELECT c.*, COUNT(g.id) as game_count 
    FROM category c 
    LEFT JOIN game g ON c.id = g.fk_category 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Category Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
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
                    <a href="games.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-gamepad"></i> Games
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                    <a href="../settings.php" class="list-group-item list-group-item-action">
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
                <h1><i class="fas fa-tags"></i> Category Management</h1>
                <div>
                    <button type="button" class="btn btn-success" id="toggleAddCategory">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                    <span class="badge badge-primary ml-2"><?= count($categories) ?> Total Categories</span>
                </div>
            </div>

            <!-- Add Category Form -->
            <div class="card mb-4" id="addCategoryCard" style="display:none;">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-plus-circle"></i> Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_category">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Action, Adventure, RPG" required>
                            <small class="form-text text-muted">Enter a descriptive name for the game category.</small>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary mr-2" onclick="document.getElementById('addCategoryCard').style.display='none'">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Create Category</button>
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
            
            <!-- Categories List -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                        <div class="admin-games-list">
                            <?php foreach ($categories as $category): ?>
                                <div class="admin-game-item">
                                    <div class="admin-game-row">
                                        <div class="admin-game-info">
                                            <div class="admin-game-thumb-placeholder">
                                                <i class="fas fa-tag"></i>
                                            </div>
                                            <div class="admin-game-details">
                                                <h4 class="admin-game-name"><?= htmlspecialchars($category['name']) ?></h4>
                                                <div class="admin-game-tags">
                                                    <span class="tag">ID: <?= $category['id'] ?></span>
                                                    <span class="tag"><?= $category['game_count'] ?> Games</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="admin-game-btns">
                                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editCategoryModal<?= $category['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#deleteCategoryModal<?= $category['id'] ?>" <?= $category['game_count'] > 0 ? 'disabled title="Cannot delete category with games"' : '' ?>>
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Modals for all categories -->
                        <?php foreach ($categories as $category): ?>
                            <!-- Edit Category Modal -->
                            <div class="modal fade" id="editCategoryModal<?= $category['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Category: <?= htmlspecialchars($category['name']) ?></h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update_category">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                
                                                <div class="form-group">
                                                    <label>Category Name</label>
                                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i> This category currently has <strong><?= $category['game_count'] ?></strong> game(s) assigned to it.
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
                            
                            <!-- Delete Category Modal -->
                            <?php if ($category['game_count'] == 0): ?>
                                <div class="modal fade" id="deleteCategoryModal<?= $category['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Category: <?= htmlspecialchars($category['name']) ?></h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                    
                                                    <div class="alert alert-warning">
                                                        <strong>Warning!</strong> This action cannot be undone.
                                                    </div>
                                                    
                                                    <p>Are you sure you want to delete the category <strong><?= htmlspecialchars($category['name']) ?></strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Delete Category</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5>No Categories Found</h5>
                            <p class="text-muted">Click "Add New Category" to create your first category.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle add category form card
document.getElementById('toggleAddCategory')?.addEventListener('click', function(){
    const card = document.getElementById('addCategoryCard');
    if (!card) return;
    card.style.display = (card.style.display === 'none' || card.style.display === '') ? 'block' : 'none';
});
</script>

<!-- Bootstrap JS and dependencies for modals -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../inc/footer.inc.php'; ?>
</body>
</html>
