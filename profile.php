<?php
session_start();

/* Require classes */
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

/* Auth check */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
	header('Location: login.php');
	exit();
}

/* DB connection */
try {
	$database = Database::getInstance();
	$pdo = $database->getConnection();
} catch (Exception $e) {
	header('Location: login.php');
	exit();
}

/* Current user */
$current_user = User::getCurrentUser($pdo);
if (!$current_user) {
	header('Location: login.php');
	exit();
}

$is_admin      = $current_user->isAdmin();
$username      = $current_user->getUsername();
$owned_games   = $current_user->getOwnedGames();
$wishlist_games = $current_user->getWishlist();
$balance       = $current_user->getBalance();

/* Tabs */
$selected_tab = $_GET['tab'] ?? 'overview';
$valid_tabs   = ['overview', 'library', 'wishlist', 'settings'];
if (!in_array($selected_tab, $valid_tabs)) {
	$selected_tab = 'overview';
}

function esc($v)
{
	return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0" />
	<title>Profile - Gaming Store</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
	<link rel="stylesheet" href="./css/main.css" />
</head>

<body>
	<?php include 'inc/header.inc.php'; ?>

	<div class="container">
		<div class="profile-page">
			<div class="profile-header">
				<h2><i class="fas fa-user-circle"></i> Player Profile</h2>
				<p>Manage your gaming profile and view your statistics</p>
			</div>

			<div class="profile-content">
				<div class="profile-sidebar">
					<div class="user-profile-card">
						<div class="user-avatar">
							<div class="avatar-placeholder"><i class="fas fa-user"></i></div>
						</div>
						<h3 class="username-glow"><?= esc($username) ?></h3>
						<div class="user-badges">
							<span class="badge user-badge"><i class="fas fa-gamepad"></i> Gamer</span>
							<?php if ($is_admin): ?><span class="badge admin-badge"><i class="fas fa-crown"></i> Admin</span><?php endif; ?>
						</div>
						<div class="user-balance">
							<span class="balance-label">Balance</span>
							<span class="balance-amount"><?= $current_user->getFormattedBalanceCoins() ?></span>
						</div>
						<p class="member-since"><i class="fas fa-calendar"></i> Member since <?= esc(date('M Y', strtotime($current_user->getJoindate()))) ?></p>
					</div>

					<?php if ($is_admin): ?>
						<div class="admin-panel-card">
							<h4><i class="fas fa-tools"></i> Admin Panel</h4>
							<div class="admin-links">
								<a href="admin/dashboard.php" class="admin-link">Dashboard</a>
								<a href="admin/users.php" class="admin-link">Users</a>
								<a href="admin/games.php" class="admin-link">Games</a>
								<a href="admin/categories.php" class="admin-link">Categories</a>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="profile-main">
					<div class="tab-nav">
						<button class="tab-btn" data-tab="overview">Overview</button>
						<button class="tab-btn" data-tab="library">Library</button>
						<button class="tab-btn" data-tab="wishlist">Wishlist</button>
						<button class="tab-btn" data-tab="settings">Settings</button>
					</div>

					<div class="tab-content">
						<div id="tab-overview" class="tab-pane">
							<h3>Overview</h3>
							<div class="profile-stat-badges">
								<div class="profile-stat-badge good"><i class="fas fa-gamepad"></i> Games: <?= count($owned_games) ?></div>
								<div class="profile-stat-badge warn"><i class="fas fa-heart"></i> Wishlist: <?= count($wishlist_games) ?></div>
								<div class="profile-stat-badge bad"><i class="fas fa-wallet"></i> Balance: <?= $current_user->getFormattedBalanceCoins() ?></div>
							</div>
							<p>Welcome back, <?= esc($username) ?>. Use the tabs above to explore your games and customize preferences.</p>
						</div>

						<div id="tab-library" class="tab-pane">
							<h3>Your Library</h3>
							<?php if (empty($owned_games)): ?>
								<p>You do not own any games yet. Visit the <a href="shop.php">Shop</a>.</p>
							<?php else: ?>
								<div class="library-grid">
									<?php foreach ($owned_games as $g): ?>
										<div class="library-card">
											<div class="game-info" style="padding:1rem;">
												<h4 style="margin:0 0 .4rem 0; font-family:var(--font-gaming); font-size:.9rem;">
													<?= esc($g['name'] ?? 'Game') ?>
												</h4>
												<p style="margin:0; font-size:.65rem; color:var(--text-muted);">
													<?= esc($g['category_name'] ?? 'Category') ?>
												</p>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<div id="tab-wishlist" class="tab-pane">
							<h3>Your Wishlist</h3>
							<?php if (empty($wishlist_games)): ?>
								<p>Wishlist empty. Browse the <a href="shop.php">Shop</a> to add games.</p>
							<?php else: ?>
								<div class="wishlist-grid">
									<?php foreach ($wishlist_games as $w): ?>
										<div class="wishlist-card">
											<div style="padding:1rem;">
												<h4 style="margin:0 0 .4rem 0; font-family:var(--font-gaming); font-size:.9rem;">
													<?= esc($w['name'] ?? 'Game') ?>
												</h4>
												<p style="margin:0; font-size:.65rem; color:var(--text-muted);">
													<?= esc($w['category_name'] ?? 'Category') ?>
												</p>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<div id="tab-settings" class="tab-pane">
							<h3>Account Settings</h3>
							<div class="setting-card">
								<h4><i class="fas fa-key"></i> Change Password</h4>
								<p>Update your password to keep your account secure.</p>
								<a href="settings.php#privacy" class="btn btn-primary">Change Password</a>
							</div

								<div class="setting-card">
							<h4><i class="fas fa-cog"></i> More Settings</h4>
							<p>Access all account settings including notifications, privacy, and data management.</p>
							<a href="settings.php" class="btn btn-primary">Open Full Settings</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>

	<?php include 'inc/footer.inc.php'; ?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const tabButtons = document.querySelectorAll('.tab-btn');
			const tabPanes = document.querySelectorAll('.tab-pane');

			function showTab(tabName) {
				tabPanes.forEach(pane => {
					pane.classList.remove('active');
				});

				tabButtons.forEach(btn => {
					btn.classList.remove('active');
				});

				const selectedPane = document.getElementById('tab-' + tabName);
				if (selectedPane) {
					selectedPane.classList.add('active');
				}

				const selectedBtn = document.querySelector('.tab-btn[data-tab="' + tabName + '"]');
				if (selectedBtn) {
					selectedBtn.classList.add('active');
				}
			}

			tabButtons.forEach(btn => {
				btn.addEventListener('click', function() {
					const tabName = this.getAttribute('data-tab');
					showTab(tabName);
				});
			});

			showTab('overview');
		});
	</script>
</body>

</html>