<?php
session_start();

/* Require classes */
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';
require_once 'classes/Friend.php';

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

// Ajax base in case this page is served from a subfolder
$ajaxBase = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';

$me = User::getCurrentUser($pdo);
if (!$me) {
	header('Location: login.php');
	exit();
}

/* Determine which profile to view: ?user=username or ?id=ID */
$profile_user = new User($pdo);
if (!empty($_GET['user'])) {
	$loaded = $profile_user->loadByUsername($_GET['user']);
} elseif (!empty($_GET['id'])) {
	$loaded = $profile_user->loadById((int)$_GET['id']);
} else {
	$profile_user = $me;
	$loaded = true;
}

if (!$loaded) {
	$profile_user = $me;
}

$viewer_is_admin = $me->isAdmin();
$profile_is_admin = $profile_user->isAdmin();
$display_username = $profile_user->getUsername();
$owned_games   = $profile_user->getOwnedGames();
$wishlist_games = $profile_user->getWishlist();
$balance       = $profile_user->getBalance();

// Friend helper (determine relationship between viewer ($me) and profile)
$friendHelper = new Friend($pdo);
$friend_status = null;
if ($profile_user->getId() !== $me->getId()) {
	$friend_status = $friendHelper->getStatus($me->getId(), $profile_user->getId());
}

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
			<?php $profile_private = ($profile_user->getId() !== $me->getId() && !$profile_user->isPublicProfile());
			if ($profile_private): ?>
				<div class="notice">
					<h3>Profile is private</h3>
					<p>This user's profile is private. You cannot view their details.</p>
				</div>
			<?php else: ?>
				<div class="profile-header">
					<h2><i class="fas fa-user-circle"></i> Player Profile</h2>
					<p>Manage your gaming profile and view your statistics</p>
				</div>

				<div class="profile-content">
					<div class="profile-sidebar">
						<div class="user-profile-card">
							<div class="user-avatar">
								<?php if ($profile_user->getAvatar()): ?>
									<img class="avatar-img" src="<?= esc($profile_user->getAvatar()) ?>" alt="<?= esc($display_username) ?>'s avatar" />
								<?php else: ?>
									<div class="avatar-placeholder"><i class="fas fa-user"></i></div>
								<?php endif; ?>
							</div>
							<h3 class="username-glow"><?= esc($display_username) ?></h3>
							<div class="user-badges">
								<span class="badge user-badge"><i class="fas fa-gamepad"></i> Gamer</span>
								<?php if ($profile_user->isBanned()): ?>
									<span class="badge banned-badge" title="<?= esc($profile_user->getBanReason()) ?>"><i class="fas fa-ban"></i> Banned</span>
								<?php endif; ?>
								<?php if ($profile_is_admin): ?><span class="badge admin-badge"><i class="fas fa-crown"></i> Admin</span><?php endif; ?>
							</div>
							<div class="user-balance">
								<span class="balance-label">Balance</span>
								<span class="balance-amount">
									<?php if ($profile_user->getId() === $me->getId()): ?>
										<?= $profile_user->getFormattedBalanceCoins() ?>
									<?php else: ?>
										<p>Hidden</p>
									<?php endif; ?>
								</span>
							</div>
							<p class="member-since"><i class="fas fa-calendar"></i> Member since <?= esc(date('M Y', strtotime($profile_user->getJoindate()))) ?></p>
							<?php if ($profile_user->getId() !== $me->getId()): ?>
								<div class="friend-actions" style="margin-top:.75rem;">
									<?php if ($friend_status === 'none'): ?>
										<form method="post" action="<?php echo $ajaxBase; ?>ajax_handler.php?action=send_friend_request" class="friend-form">
											<input type="hidden" name="target_id" value="<?= (int)$profile_user->getId() ?>">
											<button class="btn btn-primary">Add Friend</button>
										</form>
									<?php elseif ($friend_status === 'pending_outgoing'): ?>
										<button class="btn" disabled>Request Sent</button>
									<?php elseif ($friend_status === 'pending_incoming'): ?>
										<form method="post" action="<?php echo $ajaxBase; ?>ajax_handler.php?action=respond_friend_request" style="display:inline-block;" class="friend-form">
											<input type="hidden" name="from_id" value="<?= (int)$profile_user->getId() ?>">
											<button name="action" value="accept" class="friend-action-btn btn-accept">Accept</button>
											<button name="action" value="reject" class="friend-action-btn btn-reject">Reject</button>
											<span class="friend-status" aria-live="polite" style="margin-left:8px"></span>
										</form>
									<?php elseif ($friend_status === 'friends'): ?>
										<form method="post" action="<?php echo $ajaxBase; ?>ajax_handler.php?action=remove_friend" class="friend-form">
											<input type="hidden" name="target_id" value="<?= (int)$profile_user->getId() ?>">
											<button class="btn btn-danger">Remove Friend</button>
										</form>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if ($viewer_is_admin && $profile_user->getId() === $me->getId()): ?>
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
									<div class="profile-stat-badge bad"><i class="fas fa-wallet"></i> Balance: <?php if ($profile_user->getId() === $me->getId()) {
																													echo $profile_user->getFormattedBalanceCoins();
																												} else {
																													echo '<p>Hidden</p>';
																												} ?></div>
								</div>
								<?php if ($profile_user->getId() === $me->getId()): ?>
									<p>Welcome back, <?= esc($display_username) ?>. Use the tabs above to explore your games and customize preferences.</p>
								<?php else: ?>
									<p>Viewing profile of <?= esc($display_username) ?>. Use the tabs above to browse their public library and wishlist.</p>
								<?php endif; ?>
							</div>

							<div id="tab-library" class="tab-pane">
								<h3>Your Library</h3>
								<?php if ($profile_user->getId() !== $me->getId() && !$profile_user->isPublicLibrary()): ?>
									<p>This user's library is private.</p>
								<?php else: ?>
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
								<?php endif; ?>
							</div>

							<div id="tab-wishlist" class="tab-pane">
								<h3><?php echo ($profile_user->getId() === $me->getId()) ? 'Your Wishlist' : 'Wishlist of ' . esc($profile_user->getUsername()); ?></h3>
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
								<?php if ($profile_user->getId() === $me->getId()): ?>
									<h3>Account Settings</h3>
									<button id="editProfileBtn" class="edit-profile-btn" style="margin-bottom:.5rem;">Edit Profile</button>
									<div class="setting-card">
										<h4><i class="fas fa-key"></i> Change Password</h4>
										<p>Update your password to keep your account secure.</p>
										<a href="settings.php#privacy" class="btn btn-primary">Change Password</a>
									</div>

									<div class="setting-card">
										<h4><i class="fas fa-cog"></i> More Settings</h4>
										<p>Access all account settings including notifications, privacy, and data management.</p>
										<a href="settings.php" class="btn btn-primary">Open Full Settings</a>
									</div>
								<?php else: ?>
									<h3>Account Settings</h3>
									<p>You are viewing another user's profile. Account settings are not available.</p>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
		</div>
	</div>

<?php endif; ?>
<?php include 'inc/footer.inc.php'; ?>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal-backdrop" style="display:none;">
	<div class="modal" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
		<div class="modal-header">
			<h3 id="editProfileTitle">Edit Profile</h3>
			<button type="button" class="modal-close" id="cancelEditProfile" aria-label="Close">✕</button>
		</div>
		<div class="modal-body">
			<form id="editProfileForm">
				<div class="modal-row">
					<div class="modal-col">
						<label for="ep_username">Username</label>
						<input id="ep_username" class="modal-input" type="text" name="username" value="<?= esc($profile_user->getUsername()) ?>" required />
					</div>
				</div>

				<div class="modal-row">
					<div class="modal-col">
						<label for="ep_email">Email</label>
						<input id="ep_email" class="modal-input" type="email" name="email" value="<?= esc($profile_user->getEmail()) ?>" required />
					</div>
				</div>

				<div class="modal-row">
					<div class="modal-col">
						<label for="ep_avatar">Avatar URL</label>
						<input id="ep_avatar" class="modal-input" type="text" name="avatar" value="<?= esc($profile_user->getAvatar()) ?>" />
					</div>
				</div>

				<div class="modal-row modal-checkbox">
					<label><input type="checkbox" name="public_profile" <?= $profile_user->isPublicProfile() ? 'checked' : '' ?>> Public profile</label>
					<label><input type="checkbox" name="public_library" <?= $profile_user->isPublicLibrary() ? 'checked' : '' ?>> Public library</label>
				</div>

				<div class="modal-footer">
					<button type="button" class="modal-btn-cancel" id="cancelEditProfileFooter">Cancel</button>
					<button type="submit" class="modal-btn-save">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>

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

		showTab('<?= esc($selected_tab) ?>');
	});

	// Edit profile modal behavior
	const editBtn = document.getElementById('editProfileBtn');
	const editModal = document.getElementById('editProfileModal');
	const cancelEdit = document.getElementById('cancelEditProfile');
	const cancelEditFooter = document.getElementById('cancelEditProfileFooter');
	const editForm = document.getElementById('editProfileForm');

	if (editBtn && editModal) {
		editBtn.addEventListener('click', function() {
			editModal.style.display = 'flex';
		});
	}
	if (cancelEdit && editModal) {
		cancelEdit.addEventListener('click', function() {
			editModal.style.display = 'none';
		});
	}
	if (cancelEditFooter && editModal) {
		cancelEditFooter.addEventListener('click', function() {
			editModal.style.display = 'none';
		});
	}

	try {
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('edit') === '1' && editModal) {
			editModal.style.display = 'flex';
			// remove query param so refresh doesn't re-open
			history.replaceState(null, '', window.location.pathname + window.location.hash);
		}
	} catch (e) {
		// ignore in older browsers
	}

	if (editForm) {
		editForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const fd = new FormData(editForm);
			const AJAX_ENDPOINT = '<?php echo $ajaxBase; ?>ajax_handler.php';
			fetch(AJAX_ENDPOINT + '?action=update_profile', {
					method: 'POST',
					body: fd
				})
				.then(r => r.json())
				.then(j => {
					if (j.success) {
						alert('Profile updated');
						location.reload();
					} else {
						alert('Save failed: ' + (j.message || 'unknown'));
					}
				})
				.catch(err => {
					alert('Network or server error');
				});
		});
	}

	// Intercept friend action forms (send/respond/remove) and post via AJAX
	(function() {
		document.querySelectorAll('.friend-form').forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				const btn = form.querySelector('button[type=submit], button');
				if (btn) btn.disabled = true;
				form.querySelectorAll('button').forEach(b => b.addEventListener('click', function() {
					form._lastClicked = this;
				}));
				const url = (function() {
					try {
						return new URL(form.getAttribute('action'), window.location.href).href;
					} catch (e) {
						return form.getAttribute('action');
					}
				})();
				const formDataObj = new FormData(form);
				const submitter = (e.submitter || form._lastClicked || document.activeElement);
				if (submitter && submitter.name) formDataObj.set(submitter.name, submitter.value || '');
				const body = new URLSearchParams(Array.from(formDataObj.entries()));
				fetch(url, {
						method: 'POST',
						headers: {
							'X-Requested-With': 'XMLHttpRequest'
						},
						body
					})
					.then(r => r.json())
					.then(j => {
						if (j.success) {
							// brief success then reload to reflect new state
							setTimeout(() => location.reload(), 300);
						} else {
							alert(j.message || 'Action failed');
						}
					}).catch(err => {
						alert('Network error');
					}).finally(() => {
						if (btn) btn.disabled = false;
					});
			});
		});
	})();
</script>
</body>

</html>