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

$user = new User($pdo);
$user->loadById($_SESSION['user_id']);

if (!$user->isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Get system statistics
$stats = [];

// User statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN banned = 1 THEN 1 END) as banned_users,
        COUNT(CASE WHEN admin = 1 THEN 1 END) as admin_users,
        COUNT(CASE WHEN joindate >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d
    FROM users
");
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Game statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_games,
        COUNT(CASE WHEN sale = 1 THEN 1 END) as games_on_sale,
        COUNT(CASE WHEN release_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_games_30d
    FROM game
");
$stats['games'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Sales statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(l.id) as total_purchases,
        COALESCE(SUM(g.price), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN l.purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN g.price END), 0) as revenue_30d,
        COUNT(CASE WHEN l.purchased_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as purchases_30d
    FROM library l
    JOIN game g ON l.fk_game = g.id
");
$stats['sales'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Library statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_library_entries,
        COUNT(DISTINCT fk_user) as users_with_games
    FROM library
");
$stats['library'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$recentPurchases = $pdo->query("
    SELECT l.*, u.username, g.name as game_name, g.price as amount, l.purchased_at as created_at
    FROM library l
    JOIN users u ON l.fk_user = u.id
    LEFT JOIN game g ON l.fk_game = g.id
    ORDER BY l.purchased_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$recentUsers = $pdo->query("
    SELECT id, username, email, joindate, admin
    FROM users
    ORDER BY joindate DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get top selling games
$topGames = $pdo->query("
    SELECT g.name, COUNT(l.id) as sales_count, COALESCE(SUM(g.price), 0) as revenue
    FROM game g
    LEFT JOIN library l ON g.id = l.fk_game
    GROUP BY g.id, g.name
    ORDER BY sales_count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Dashboard';
include '../inc/header.inc.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6><i class="fas fa-cogs"></i> Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="games.php" class="list-group-item list-group-item-action">
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
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <div class="text-muted">
                    Welcome back, <?= htmlspecialchars($user->getUsername()) ?>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['users']['total_users']) ?></h4>
                                    <span>Total Users</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>+<?= $stats['users']['new_users_30d'] ?> this month</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['games']['total_games']) ?></h4>
                                    <span>Total Games</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-gamepad fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small><?= $stats['games']['games_on_sale'] ?> on sale</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>$<?= number_format($stats['sales']['total_revenue'], 0) ?></h4>
                                    <span>Total Revenue</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>$<?= number_format($stats['sales']['revenue_30d'], 0) ?> this month</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['sales']['total_purchases']) ?></h4>
                                    <span>Total Orders</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>+<?= $stats['sales']['purchases_30d'] ?> this month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Data -->
            <div class="row">
                <!-- Recent Purchases -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Purchases</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentPurchases)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Game</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPurchases as $purchase): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($purchase['username']) ?></td>
                                                    <td><?= htmlspecialchars($purchase['game_name'] ?? 'Balance Top-up') ?></td>
                                                    <td>$<?= number_format($purchase['amount'], 2) ?></td>
                                                    <td><?= date('M j', strtotime($purchase['created_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No recent purchases.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Top Selling Games -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Top Selling Games</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($topGames)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Game</th>
                                                <th>Sales</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topGames as $game): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($game['name']) ?></td>
                                                    <td><?= $game['sales_count'] ?></td>
                                                    <td>$<?= number_format($game['revenue'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No sales data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Recent Users -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentUsers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $newUser): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($newUser['username']) ?></td>
                                                    <td><?= htmlspecialchars($newUser['email']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $newUser['admin'] ? 'danger' : 'primary' ?>">
                                                            <?= $newUser['admin'] ? 'Admin' : 'User' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M j', strtotime($newUser['joindate'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No recent users.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="games.php?action=add" class="btn btn-success mb-2">
                                    <i class="fas fa-plus"></i> Add New Game
                                </a>
                                <a href="users.php" class="btn btn-primary mb-2">
                                    <i class="fas fa-users"></i> Manage Users
                                </a>
                                <a href="orders.php" class="btn btn-info mb-2">
                                    <i class="fas fa-list"></i> View All Orders
                                </a>
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="fas fa-cog"></i> System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6>System Info</h6>
                        </div>
                        <div class="card-body">
                            <small>
                                <strong>Users with Games:</strong> <?= $stats['library']['users_with_games'] ?> / <?= $stats['users']['total_users'] ?><br>
                                <strong>Banned Users:</strong> <?= $stats['users']['banned_users'] ?><br>
                                <strong>Admin Users:</strong> <?= $stats['users']['admin_users'] ?><br>
                                <strong>Library Entries:</strong> <?= number_format($stats['library']['total_library_entries']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.inc.php'; ?>