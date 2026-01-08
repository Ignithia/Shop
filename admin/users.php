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
$totalUsers = User::countUsers($pdo, $filters);
$totalPages = ceil($totalUsers / ($filters['limit'] ?? 20));
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
                    <div class="alert alert-success alert-dismissible fade show" id="message-alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div id="ajax-message-container"></div>

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
                </div> <!-- Users Table -->
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
                                            <tr class="<?= $userRow['banned'] ? 'table-danger' : '' ?>" data-user-id="<?= $userRow['id'] ?>">
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
                                                    <div class="admin-user-actions">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#editUserModal<?= $userRow['id'] ?>">
                                                            Edit
                                                        </button>
                                                        <?php if ($userRow['id'] !== $currentUser->getId()): ?>
                                                            <?php if ($userRow['banned']): ?>
                                                                <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#unbanUserModal<?= $userRow['id'] ?>">
                                                                    Unban
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#banUserModal<?= $userRow['id'] ?>">
                                                                    Ban
                                                                </button>
                                                            <?php endif; ?>

                                                            <button type="button" class="btn btn-outline-secondary btn-sm remove-avatar-btn" data-user-id="<?= $userRow['id'] ?>" data-username="<?= htmlspecialchars($userRow['username']) ?>">
                                                                Remove Avatar
                                                            </button>

                                                            <button type="button" class="btn btn-outline-danger btn-sm delete-user-btn" data-user-id="<?= $userRow['id'] ?>" data-username="<?= htmlspecialchars($userRow['username']) ?>">
                                                                Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- All Modals -->
                            <?php foreach ($users as $userRow): ?>
                                <div class="modal fade" id="editUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <input type="text" class="form-control edit-username" data-user-id="<?= $userRow['id'] ?>" value="<?= htmlspecialchars($userRow['username']) ?>" required>
                                                </div>

                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control edit-email" data-user-id="<?= $userRow['id'] ?>" value="<?= htmlspecialchars($userRow['email']) ?>" required>
                                                </div>

                                                <div class="form-group">
                                                    <label>Balance</label>
                                                    <input type="number" step="0.01" class="form-control edit-balance" data-user-id="<?= $userRow['id'] ?>" value="<?= $userRow['balance'] ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label>Role</label>
                                                    <select class="form-control edit-role" data-user-id="<?= $userRow['id'] ?>">
                                                        <option value="user" <?= ($userRow['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                                        <option value="admin" <?= $userRow['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary save-user-btn" data-user-id="<?= $userRow['id'] ?>">Save Changes</button>
                                            </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ban User Modal -->
                                <?php if ($userRow['id'] !== $currentUser->getId() && !$userRow['banned']): ?>
                                    <div class="modal fade" id="banUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ban User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to ban <strong><?= htmlspecialchars($userRow['username']) ?></strong>?</p>
                                                    <div class="form-group">
                                                        <label>Ban Reason</label>
                                                        <textarea class="form-control ban-reason" data-user-id="<?= $userRow['id'] ?>" rows="3" placeholder="Enter reason for ban..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-danger ban-user-btn" data-user-id="<?= $userRow['id'] ?>" data-username="<?= htmlspecialchars($userRow['username']) ?>">Ban User</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Unban User Modal -->
                                <?php if ($userRow['banned']): ?>
                                    <div class="modal fade" id="unbanUserModal<?= $userRow['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Unban User: <?= htmlspecialchars($userRow['username']) ?></h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to unban <strong><?= htmlspecialchars($userRow['username']) ?></strong>?</p>
                                                    <?php if ($userRow['ban_reason']): ?>
                                                        <p><strong>Ban Reason:</strong> <?= htmlspecialchars($userRow['ban_reason']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-success unban-user-btn" data-user-id="<?= $userRow['id'] ?>" data-username="<?= htmlspecialchars($userRow['username']) ?>">Unban User</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Setup CSRF for jQuery after jQuery is loaded -->
    <script>
        if (typeof window.setupJQueryCSRF === 'function') {
            window.setupJQueryCSRF();
        }
    </script>

    <script>
        $(document).ready(function() {
            // Show message function
            function showMessage(message, type = 'success') {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show";>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            `;
                $('#ajax-message-container').html(alertHtml);

                // Scroll to message
                $('html, body').animate({
                    scrollTop: $('#ajax-message-container').offset().top - 100
                }, 300);

                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $('#ajax-message-container .alert').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Save user (Edit)
            $(document).on('click', '.save-user-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const userId = $(this).data('user-id');
                const username = $(`.edit-username[data-user-id="${userId}"]`).val();
                const email = $(`.edit-email[data-user-id="${userId}"]`).val();
                const balance = $(`.edit-balance[data-user-id="${userId}"]`).val();
                const role = $(`.edit-role[data-user-id="${userId}"]`).val();

                if (!username || !email) {
                    showMessage('Username and email are required.', 'error');
                    return;
                }

                $.ajax({
                    url: '../ajax_handler.php',
                    method: 'POST',
                    data: {
                        action: 'admin_update_user',
                        user_id: userId,
                        username: username,
                        email: email,
                        balance: balance,
                        role: role
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.message || 'User updated successfully!', 'success');
                            $(`#editUserModal${userId}`).modal('hide');

                            // Auto-reload page
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showMessage(response.message || 'Failed to update user.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while updating the user.', 'error');
                    }
                });
            });

            // Ban user
            $(document).on('click', '.ban-user-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const userId = $(this).data('user-id');
                const username = $(this).data('username');
                const banReason = $(`.ban-reason[data-user-id="${userId}"]`).val();

                $.ajax({
                    url: '../ajax_handler.php',
                    method: 'POST',
                    data: {
                        action: 'admin_ban_user',
                        user_id: userId,
                        ban_reason: banReason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(`#banUserModal${userId}`).modal('hide');
                            showMessage(response.message || `User "${username}" banned successfully.`, 'success');

                            // Auto-reload page
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showMessage(response.message || 'Failed to ban user.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while banning the user.', 'error');
                    }
                });
            });

            // Unban user
            $(document).on('click', '.unban-user-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const userId = $(this).data('user-id');
                const username = $(this).data('username');

                $.ajax({
                    url: '../ajax_handler.php',
                    method: 'POST',
                    data: {
                        action: 'admin_unban_user',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(`#unbanUserModal${userId}`).modal('hide');
                            showMessage(response.message || `User "${username}" unbanned successfully.`, 'success');

                            // Auto-reload page
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showMessage(response.message || 'Failed to unban user.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while unbanning the user.', 'error');
                    }
                });
            });

            // Remove avatar
            $(document).on('click', '.remove-avatar-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const userId = $(this).data('user-id');
                const username = $(this).data('username');

                if (!confirm(`Remove avatar for ${username}?`)) {
                    return;
                }

                $.ajax({
                    url: '../ajax_handler.php',
                    method: 'POST',
                    data: {
                        action: 'admin_remove_avatar',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.message || 'Avatar removed successfully.', 'success');

                            // Auto-reload page
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            showMessage(response.message || 'Failed to remove avatar.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while removing the avatar.', 'error');
                    }
                });
            });

            // Delete user
            $(document).on('click', '.delete-user-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const userId = $(this).data('user-id');
                const username = $(this).data('username');

                if (!confirm(`Delete user "${username}" and all their data? This cannot be undone.`)) {
                    return;
                }

                $.ajax({
                    url: '../ajax_handler.php',
                    method: 'POST',
                    data: {
                        action: 'admin_delete_user',
                        user_id: userIdwhat
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.message || 'User deleted successfully.', 'success');

                            // Remove the row from table
                            $(`tr[data-user-id="${userId}"]`).fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            showMessage(response.message || 'Failed to delete user.', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr.responseText);
                        showMessage('An error occurred while deleting the user.', 'error');
                    }
                });
            });

            // Prevent modal forms from submitting on Enter key
            $('.modal').on('keypress', 'input, textarea', function(e) {
                if (e.which === 13 && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>

    <?php include '../inc/footer.inc.php'; ?>
</body>

</html>