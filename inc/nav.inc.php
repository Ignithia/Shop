<?php
/* Include database classes if not already included */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/User.php';
}

$cur_url = basename($_SERVER['SCRIPT_NAME']);

$nav_items = [
    'profile.php' => 'Profile',
    'settings.php' => 'Settings',
    'wallet.php' => 'Wallet',
    'purchases.php' => 'Purchase History'
];

/* Determine if in admin directory for correct link paths */
$__in_admin = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false);
$__href = function ($path) use ($__in_admin) {
    return ($__in_admin ? '../' : '') . ltrim($path, '/');
};
?>
<nav class="navigation">
    <div class="nav-dropdown">
        <button class="nav-dropdown-btn">▼</button>
        <div class="nav-dropdown-content">

            <?php foreach ($nav_items as $page => $title):
                $active = ($cur_url === basename($page)) ? 'active' : '';
                $href = $__href($page);
            ?>
                <a href="<?php echo $href; ?>" class="<?php echo $active; ?>"><?php echo $title; ?></a>
            <?php endforeach; ?>

            <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                <a href="<?php echo $__href('admin/dashboard.php'); ?>" class="admin-nav-link">Admin Dashboard</a>
            <?php endif; ?>

            <a href="?logout=1" class="logout">🚪 Logout</a>
        </div>
    </div>
</nav>