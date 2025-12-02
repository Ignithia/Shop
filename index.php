<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    }
}

session_start();

// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Logout
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    User::logout();
    header('Location: login.php');
    exit();
}

// Get database connection
try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    header('Location: login.php');
    exit();
}

// Get current user
$current_user = User::getCurrentUser($pdo);
if (!$current_user) {
    header('Location: login.php');
    exit();
}

$username = $current_user->getUsername();

// Get dashboard statistics
$data_counts = [
    'users' => 0,
    'games' => 0,
    'owned_games' => count($current_user->getOwnedGames())
];

// Count total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$data_counts['users'] = $stmt->fetchColumn();

// Count total games
$stmt = $pdo->query("SELECT COUNT(*) FROM game");
$data_counts['games'] = $stmt->fetchColumn();

$user_balance = $current_user->getBalance();

function formatPrice($amount)
{
    return '$' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <?php include './inc/header.inc.php'; ?>


    <div class="container">
        <div class="welcome-card">
            <h2>Welcome to your dashboard!</h2>
            <p>Here you can go to the shop, library, and account settings.</p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data_counts['owned_games']; ?></div>
                    <div class="stat-label">Owned Games</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data_counts['games']; ?></div>
                    <div class="stat-label">Games in Stock</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $data_counts['users']; ?></div>
                    <div class="stat-label">Existing Users</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>🛒 Shop</h3>
                <p>Browse and purchase games.</p>
                <a href="shop.php" class="card-btn">Browse Shop</a>
            </div>

            <div class="dashboard-card">
                <h3>🕹️ Game Library</h3>
                <p>Manage your game collection and keep track of your favorites.</p>
                <a href="library.php" class="card-btn">View My Library</a>
            </div>

            <div class="dashboard-card">
                <h3>👤 Account</h3>
                <p>Manage your account settings, profile, and security preferences.</p>
                <a href="profile.php" class="card-btn secondary">View Profile</a>
            </div>

            <?php if ($current_user->isAdmin()): ?>
                <div class="dashboard-card">
                    <h3>🛠 Admin</h3>
                    <p>Quick access to admin tools and reports.</p>
                    <div class="admin-actions">
                        <a href="admin/dashboard.php" class="card-btn">Dashboard</a>
                        <a href="admin/users.php" class="card-btn secondary">Users</a>
                        <a href="admin/games.php" class="card-btn secondary">Games</a>
                        <a href="admin/categories.php" class="card-btn secondary">Categories</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include './inc/footer.inc.php'; ?>
</body>

</html>