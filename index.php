<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Logout
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Unknown';

// Counts get checked by using data 
function getDataCounts($current_username) {
    $counts = [
        'users' => 0,
        'games' => 0,
        'owned_games' => 0 
    ];
    
    // Count amount of total users and current user's owned games
    if (file_exists('data/users.json')) {
        $users_data = json_decode(file_get_contents('data/users.json'), true);
        if (is_array($users_data)) {
            $counts['users'] = count($users_data);
            
            // Find current user and count their owned games
            foreach ($users_data as $user) {
                if ($user['username'] === $current_username) {
                    if (isset($user['owned_games']) && is_array($user['owned_games'])) {
                        $counts['owned_games'] = count($user['owned_games']);
                    }
                    break;
                }
            }
        }
    }
    
    // Count amount of games in the store
    if (file_exists('data/games.json')) {
        $games_data = json_decode(file_get_contents('data/games.json'), true);
        if (is_array($games_data)) {
            $counts['games'] = count($games_data);
        }
    }
    
    return $counts;
}

$data_counts = getDataCounts($username);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Store Dashboard - Level Up Your Shop</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header class="header">
        <h1>GAME STORE</h1>
        <div class="user-info">
            <span>Player: <?php echo htmlspecialchars($username); ?></span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome to your Gaming Store Command Center!</h2>
            <p>You've successfully logged in. Manage your gaming empire and level up your business from this dashboard.</p>
            
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
                    <div class="stat-label">Active Gamers</div>
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
                <a href="#" class="card-btn">Manage Librarys</a>
            </div>

            <div class="dashboard-card">
                <h3>👤 Account</h3>
                <p>Manage your account settings, profile, and security preferences.</p>
                <a href="#" class="card-btn secondary">Account Settings</a>
            </div>
        </div>
    </div>
</body>
</html>