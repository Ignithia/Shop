<?php
session_start();

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Gebruiker is niet ingelogd, redirect naar login.php
    header('Location: login.php');
    exit();
}

// Logout functionaliteit
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['user_email'] ?? 'Onbekend';
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
            <span>Player: <?php echo htmlspecialchars($user_email); ?></span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome to your Gaming Store Command Center!</h2>
            <p>You've successfully logged in. Manage your gaming empire and level up your business from this dashboard.</p>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">47</div>
                    <div class="stat-label">New Orders</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">1,337</div>
                    <div class="stat-label">Games in Stock</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2,856</div>
                    <div class="stat-label">Active Gamers</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>🛒 Shop</h3>
                <p>Browse and purchase games, consoles, and gaming accessories.</p>
                <a href="#" class="card-btn">Browse Shop</a>
            </div>

            <div class="dashboard-card">
                <h3>🕹️ Game Library</h3>
                <p>Add new games, manage inventory, and update product information.</p>
                <a href="#" class="card-btn">Manage Games</a>
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