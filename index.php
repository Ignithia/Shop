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

// Ajax base for endpoints
$ajaxBase = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';

$username = $current_user->getUsername();

// Get dashboard statistics
$data_counts = [
    'users' => 0,
    'games' => 0,
    'owned_games' => count($current_user->getOwnedGames())
];

// Count total users and games
$data_counts['users'] = User::countUsers($pdo, []);
$data_counts['games'] = Game::countAllGames($pdo);

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

            <div class="site-search" style="margin-top:16px; max-width:680px;">
                <label for="globalSearch" style="display:block; margin-bottom:6px; color:var(--text-secondary);">Search games or people</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <select id="searchType" style="padding:8px;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-tertiary);color:var(--text-primary);">
                        <option value="all">All</option>
                        <option value="game">Games</option>
                        <option value="user">People</option>
                    </select>
                    <input id="globalSearch" type="search" placeholder="Search games or users" style="flex:1;padding:10px;border-radius:8px;border:1px solid var(--border-color);background:rgba(255,255,255,0.03);color:var(--text-primary);" />
                    <button id="searchBtn" class="card-btn">Search</button>
                </div>
                <div id="searchResults" style="margin-top:8px; position:relative;"></div>
            </div>

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
    <script>
        (function() {
            const AJAX_ENDPOINT = '<?php echo $ajaxBase; ?>ajax_handler.php';
            const input = document.getElementById('globalSearch');
            const typeSel = document.getElementById('searchType');
            const btn = document.getElementById('searchBtn');
            const results = document.getElementById('searchResults');

            function render(items) {
                if (!items || items.length === 0) {
                    results.innerHTML = '<div class="muted">No results</div>';
                    return;
                }
                const ul = document.createElement('ul');
                ul.className = 'search-results-list';
                ul.style.listStyle = 'none';
                ul.style.padding = '8px';
                ul.style.margin = '0';
                ul.style.background = 'var(--bg-secondary)';
                ul.style.border = '1px solid var(--border-color)';
                ul.style.borderRadius = '8px';
                items.forEach(it => {
                    const li = document.createElement('li');
                    li.style.padding = '6px 8px';
                    li.style.cursor = 'pointer';
                    li.style.display = 'flex';
                    li.style.justifyContent = 'space-between';
                    li.style.alignItems = 'center';
                    li.innerHTML = `<span style="font-weight:600">${it.label}</span><small style="color:var(--text-muted);margin-left:12px">${it.type}</small>`;
                    li.addEventListener('click', () => {
                        window.location = it.url;
                    });
                    ul.appendChild(li);
                });
                results.innerHTML = '';
                results.appendChild(ul);
            }

            function doSearch(q, type) {
                if (!q || q.trim().length < 1) {
                    results.innerHTML = '';
                    return;
                }
                fetch(`${AJAX_ENDPOINT}?action=search_entities&q=${encodeURIComponent(q)}&type=${encodeURIComponent(type)}`)
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(j => {
                        if (j.success) render(j.results);
                        else results.innerHTML = '<div class="error">No results</div>';
                    })
                    .catch(err => {
                        console.error('Search error', err);
                        results.innerHTML = '<div class="error">Search failed</div>';
                    });
            }

            if (btn) {
                btn.addEventListener('click', () => doSearch(input.value, typeSel.value));
            }
            if (input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        doSearch(input.value, typeSel.value);
                    }
                });
            }

        })();
    </script>
</body>

</html>