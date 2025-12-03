<?php

/**
 * Admin User Management
 * View, edit, and manage user accounts
 */

session_start();
require_once '../classes/Database.php';
require_once '../classes/User.php';

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

// Handle user actions
if ($_POST['action'] ?? '' === 'update_user') {
    $userId = intval($_POST['user_id']);
    $editUser = new User($pdo);

    if ($editUser->loadById($userId)) {
        $editUser->setUsername(trim($_POST['username']));
        $editUser->setEmail(trim($_POST['email']));
        $editUser->setBalance(floatval($_POST['balance']));
        $isAdmin = false;
        if (isset($_POST['role'])) {
            $isAdmin = $_POST['role'] === 'admin';
        } elseif (isset($_POST['admin'])) {
            $isAdmin = (bool)$_POST['admin'];
        }
        $editUser->setAdmin($isAdmin);

        if ($editUser->save()) {
            $message = 'User updated successfully!';
        } else {
            $error = 'Failed to update user.';
        }
    }
}
// Handle ban user action
if ($_POST['action'] ?? '' === 'ban_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    $reason = trim($_POST['ban_reason'] ?? '');
    if ($userId > 0) {
        if ($userId === $currentUser->getId()) {
            $error = 'Cannot ban your own account from this panel.';
        } else {
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'banned'");
                $hasBanned = $colCheck->fetch() !== false;
            } catch (Exception $e) {
                $hasBanned = false;
            }
            if (!$hasBanned) {
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN banned TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN ban_reason VARCHAR(255) NULL");
                } catch (Exception $e) {
                    $error = 'Failed to update schema for banning: ' . $e->getMessage();
                }
            }
            if (empty($error)) {
                $stmt = $pdo->prepare("UPDATE users SET banned = 1, ban_reason = ? WHERE id = ?");
                if ($stmt->execute([$reason, $userId])) {
                    $message = 'User banned.';
                } else {
                    $error = 'Failed to ban user.';
                }
            }
        }
    }
}

// Handle unban user action
if ($_POST['action'] ?? '' === 'unban_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'banned'");
            $hasBanned = $colCheck->fetch() !== false;
        } catch (Exception $e) {
            $hasBanned = false;
        }
        if (!$hasBanned) {
            $error = 'Schema missing ban columns; nothing to unban.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET banned = 0, ban_reason = '' WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $message = 'User unbanned.';
            } else {
                $error = 'Failed to unban user.';
            }
        }
    }
}

// Handle delete user action
if ($_POST['action'] ?? '' === 'delete_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        if ($userId === $currentUser->getId()) {
            $error = 'Cannot delete your own account from this panel.';
        } else {
            $delUser = new User($pdo);
            if ($delUser->loadById($userId)) {
                if ($delUser->delete()) {
                    $message = 'User deleted successfully.';
                } else {
                    $error = 'Failed to delete user.';
                }
            } else {
                $error = 'User not found.';
            }
        }
    }
}

// Handle remove avatar action
if ($_POST['action'] ?? '' === 'remove_avatar') {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        $u = new User($pdo);
        if ($u->loadById($userId)) {
            $u->setAvatar('');
            if ($u->save()) {
                $message = 'Avatar removed.';
            } else {
                $error = 'Failed to remove avatar.';
            }
        }
    }
}

// Get filter parameters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'admin' => isset($_GET['admin']) && $_GET['admin'] !== '' ? ($_GET['admin'] === '1') : null,
    'banned' => isset($_GET['banned']) && $_GET['banned'] !== '' ? ($_GET['banned'] === '1') : null,
    'limit' => 20,
    'offset' => intval($_GET['page'] ?? 0) * 20
];

// Get users
$users = User::getAllUsers($pdo, $filters);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM users WHERE 1=1";
$countParams = [];
if ($filters['search']) {
    $countQuery .= " AND (username LIKE ? OR email LIKE ?)";
    $countParams[] = '%' . $filters['search'] . '%';
    $countParams[] = '%' . $filters['search'] . '%';
}
if ($filters['admin'] !== null) {
    $countQuery .= " AND admin = ?";
    $countParams[] = $filters['admin'];
}
if ($filters['banned'] !== null) {
    $countQuery .= " AND banned = ?";
    $countParams[] = $filters['banned'];
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / 20);
$currentPage = intval($_GET['page'] ?? 0);

$pageTitle = 'User Management';
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
                        <a href="users.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a href="games.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-gamepad"></i> Games
                        </a>
                        <a href="categories.php" class="list-group-item list-group-item-action">
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
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    <div>
                        <span class="badge badge-primary"><?= number_format($totalUsers) ?> Total Users</span>
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
                <div class="admin-filter-bar">
                    <form method="get" class="admin-filter-form users-filter">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                        <div class="filter-group">
                            <label>User Type</label>
                            <select name="admin">
                                <option value="">All Users</option>
                                <option value="0" <?= isset($filters['admin']) && $filters['admin'] === false ? 'selected' : '' ?>>Regular Users</option>
                                <option value="1" <?= isset($filters['admin']) && $filters['admin'] === true ? 'selected' : '' ?>>Admins</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="banned">
                                <option value="">All Users</option>
                                <option value="0" <?= $filters['banned'] === false ? 'selected' : '' ?>>Active Only</option>
                                <option value="1" <?= $filters['banned'] === true ? 'selected' : '' ?>>Banned Only</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="users.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($users)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>User Type</th>
                                            <th>Balance</th>
                                            <th>Games Owned</th>
                                            <th>Total Spent</th>
                                            <th>Join Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $userRow): ?>
                                            <tr class="<?= $userRow['banned'] ? 'table-danger' : '' ?>">
                                                <td><?= $userRow['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($userRow['username']) ?></strong>
                                                    <?php if ($userRow['id'] == $currentUser->getId()): ?>
                                                        <span class="badge badge-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($userRow['email']) ?></td>
                                                <td>
                                                    <?php if ($userRow['admin']): ?>
                                                        <span class="badge badge-danger"><i class="fas fa-crown"></i> Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary"><i class="fas fa-user"></i> User</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?= number_format($userRow['balance'], 2) ?></td>
                                                <td><?= $userRow['games_owned'] ?></td>
                                                <td>$<?= number_format($userRow['total_spent'], 2) ?></td>
                                                <td><?= date('M j, Y', strtotime($userRow['joindate'])) ?></td>
                                                <td>
                                                    <?php if ($userRow['banned']): ?>
                                                        <span class="badge badge-danger">Banned</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editUserModal<?= $userRow['id'] ?>">
                                                            Edit
                                                        </button>
                                                        <?php if ($userRow['id'] !== $currentUser->getId()): ?>
                                                            <?php if ($userRow['banned']): ?>
                                                                <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#unbanUserModal<?= $userRow['id'] ?>">
                                                                    Unban
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#banUserModal<?= $userRow['id'] ?>">
                                                                    Ban
                                                                </button>
                                                            <?php endif; ?>

                                                            <!-- Remove avatar -->
                                                            <form method="post" style="display:inline;margin-left:4px;" onsubmit="return confirm('Remove avatar for <?= htmlspecialchars($userRow['username']) ?>?');">
                                                                <input type="hidden" name="action" value="remove_avatar">
                                                                <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-secondary">Remove Avatar</button>
                                                            </form>

                                                            <!-- Delete user -->
                                                            <form method="post" style="display:inline;margin-left:4px;" onsubmit="return confirm('Delete user <?= htmlspecialchars($userRow['username']) ?> and all their data? This cannot be undone.');">
                                                                <input type="hidden" name="action" value="delete_user">
                                                                <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-danger">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Edit User Modal -->
                                            <div class="modal fade" id="editUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_user">
                                                                <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">

                                                                <div class="form-group">
                                                                    <label>Username</label>
                                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($userRow['username']) ?>" required>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>Email</label>
                                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($userRow['email']) ?>" required>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>Balance</label>
                                                                    <input type="number" step="0.01" class="form-control" name="balance" value="<?= $userRow['balance'] ?>">
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>Role</label>
                                                                    <select class="form-control" name="role">
                                                                        <option value="user" <?= ($userRow['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                                                        <option value="moderator" <?= $userRow['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                                                        <option value="admin" <?= $userRow['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                                    </select>
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

                                            <!-- Ban User Modal -->
                                            <?php if ($userRow['id'] !== $currentUser->getId() && !$userRow['banned']): ?>
                                                <div class="modal fade" id="banUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Ban User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="ban_user">
                                                                    <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">

                                                                    <p>Are you sure you want to ban this user?</p>

                                                                    <div class="form-group">
                                                                        <label>Ban Reason</label>
                                                                        <textarea class="form-control" name="ban_reason" rows="3" placeholder="Enter reason for ban..." required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Ban User</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Unban User Modal -->
                                            <?php if ($userRow['banned']): ?>
                                                <div class="modal fade" id="unbanUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Unban User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="unban_user">
                                                                    <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">

                                                                    <p>Are you sure you want to unban this user?</p>
                                                                    <?php if ($userRow['ban_reason']): ?>
                                                                        <p><strong>Ban Reason:</strong> <?= htmlspecialchars($userRow['ban_reason']) ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-success">Unban User</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="User pagination">
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
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Users Found</h5>
                                <p class="text-muted">Try adjusting your search criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies for modals -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php include '../inc/footer.inc.php'; ?>
</body>

</html>