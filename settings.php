<?php
session_start();

// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    User::logout();
    setcookie('remember_login', '', time() - 3600, '/');
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    $currentUser = User::getCurrentUser($pdo);
    
    if (!$currentUser) {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = 'Database connection failed. Please try again later.';
}

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pdo)) {
    if (isset($_POST['update_notifications'])) {
        // In a real app, you'd save these to a user preferences table
        $success_message = 'Notification settings updated successfully!';
    }
    
    if (isset($_POST['update_privacy'])) {
        // In a real app, you'd save these to a user preferences table
        $success_message = 'Privacy settings updated successfully!';
    }
    
    if (isset($_POST['update_display'])) {
        // In a real app, you'd save these to a user preferences table
        $success_message = 'Display settings updated successfully!';
    }
    
    if (isset($_POST['export_data'])) {
        // In a real app, you'd generate and download user data
        $success_message = 'Data export request submitted. You will receive an email when ready.';
    }
    
    if (isset($_POST['delete_account'])) {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        if ($confirm_delete === 'DELETE') {
            // In a real app, you'd properly handle account deletion
            $success_message = 'Account deletion request submitted. Please check your email for confirmation.';
        } else {
            $error_message = 'Please type "DELETE" to confirm account deletion.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'inc/header.inc.php'; ?>
    
    <div class="container">
        <div class="settings-header">
            <h1>⚙️ Account Settings</h1>
            <p class="settings-subtitle">Manage your account preferences and privacy settings</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-content">
            <div class="settings-sidebar">
                <nav class="settings-nav">
                    <a href="#profile" class="settings-nav-link active" data-section="profile">
                        👤 Profile Settings
                    </a>
                    <a href="#notifications" class="settings-nav-link" data-section="notifications">
                        🔔 Notifications
                    </a>
                    <a href="#privacy" class="settings-nav-link" data-section="privacy">
                        🔒 Privacy & Security
                    </a>
                    <a href="#display" class="settings-nav-link" data-section="display">
                        🎨 Display & Theme
                    </a>
                    <a href="#data" class="settings-nav-link" data-section="data">
                        📊 Data & Downloads
                    </a>
                    <a href="#danger" class="settings-nav-link danger" data-section="danger">
                        ⚠️ Danger Zone
                    </a>
                </nav>
            </div>

            <div class="settings-main">
                <!-- Profile Settings Section -->
                <div class="settings-section active" id="profile">
                    <h2>Profile Settings</h2>
                    <p class="section-description">Manage your basic profile information. For username, email, and avatar changes, visit your <a href="profile.php">Profile Page</a>.</p>
                    
                    <div class="setting-group">
                        <div class="current-info">
                            <div class="info-row">
                                <span class="info-label">Username:</span>
                                <span class="info-value"><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($currentUser->getEmail()); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Since:</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($currentUser->getJoindate())); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Type:</span>
                                <span class="info-value">
                                    <?php if ($currentUser->isAdmin()): ?>
                                        <span class="badge admin">Administrator</span>
                                    <?php else: ?>
                                        <span class="badge user">Standard User</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <a href="profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div class="settings-section" id="notifications">
                    <h2>Notification Preferences</h2>
                    <p class="section-description">Choose what notifications you'd like to receive.</p>
                    
                    <form method="POST" class="settings-form">
                        <div class="setting-group">
                            <h3>Email Notifications</h3>
                            <div class="checkbox-list">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_purchases" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Purchase Confirmations</strong>
                                        <small>Receive email confirmations for your purchases</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_sales" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Sales & Promotions</strong>
                                        <small>Get notified about special offers and sales</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_wishlist">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Wishlist Updates</strong>
                                        <small>Notifications when wishlist games go on sale</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_security" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Security Alerts</strong>
                                        <small>Important security notifications for your account</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-group">
                            <h3>Browser Notifications</h3>
                            <div class="checkbox-list">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="browser_cart">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Cart Reminders</strong>
                                        <small>Remind me about items in my shopping cart</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="browser_releases">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>New Game Releases</strong>
                                        <small>Notify me when new games are added to the store</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_notifications" class="btn btn-primary">Save Notification Settings</button>
                    </form>
                </div>

                <!-- Privacy & Security Section -->
                <div class="settings-section" id="privacy">
                    <h2>Privacy & Security</h2>
                    <p class="section-description">Manage your privacy settings and account security.</p>
                    
                    <form method="POST" class="settings-form">
                        <div class="setting-group">
                            <h3>Privacy Settings</h3>
                            <div class="checkbox-list">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="profile_public">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Public Profile</strong>
                                        <small>Allow other users to view your gaming statistics</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="library_public">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Public Game Library</strong>
                                        <small>Show your game collection to other users</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="achievements_public" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Public Achievements</strong>
                                        <small>Display your gaming achievements publicly</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-group">
                            <h3>Data Collection</h3>
                            <div class="checkbox-list">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="analytics" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Usage Analytics</strong>
                                        <small>Help improve our service by sharing anonymous usage data</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="personalization" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Personalized Recommendations</strong>
                                        <small>Use my data to provide personalized game recommendations</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-group">
                            <h3>Security Options</h3>
                            <div class="security-options">
                                <a href="profile.php" class="btn btn-secondary">Change Password</a>
                                <button type="button" class="btn btn-secondary" onclick="alert('Two-factor authentication setup would be implemented here.')">
                                    Setup 2FA
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_privacy" class="btn btn-primary">Save Privacy Settings</button>
                    </form>
                </div>

                <!-- Display & Theme Section -->
                <div class="settings-section" id="display">
                    <h2>Display & Theme</h2>
                    <p class="section-description">Customize the appearance and layout of the gaming store.</p>
                    
                    <form method="POST" class="settings-form">
                        <div class="setting-group">
                            <h3>Theme Preference</h3>
                            <div class="radio-list">
                                <label class="radio-item">
                                    <input type="radio" name="theme" value="dark" checked>
                                    <span class="radio-mark"></span>
                                    <div class="radio-content">
                                        <strong>Dark Theme</strong>
                                        <small>Easy on the eyes, perfect for gaming sessions</small>
                                    </div>
                                </label>
                                
                                <label class="radio-item">
                                    <input type="radio" name="theme" value="light">
                                    <span class="radio-mark"></span>
                                    <div class="radio-content">
                                        <strong>Light Theme</strong>
                                        <small>Clean and bright interface</small>
                                    </div>
                                </label>
                                
                                <label class="radio-item">
                                    <input type="radio" name="theme" value="auto">
                                    <span class="radio-mark"></span>
                                    <div class="radio-content">
                                        <strong>Auto</strong>
                                        <small>Follow system preference</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-group">
                            <h3>Layout Options</h3>
                            <div class="checkbox-list">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="compact_mode">
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Compact Mode</strong>
                                        <small>Show more content by reducing spacing</small>
                                    </div>
                                </label>
                                
                                <label class="checkbox-item">
                                    <input type="checkbox" name="grid_view" checked>
                                    <span class="checkmark"></span>
                                    <div class="checkbox-content">
                                        <strong>Grid View Default</strong>
                                        <small>Use grid layout as default for game listings</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_display" class="btn btn-primary">Save Display Settings</button>
                    </form>
                </div>

                <!-- Data & Downloads Section -->
                <div class="settings-section" id="data">
                    <h2>Data & Downloads</h2>
                    <p class="section-description">Manage your data and download your information.</p>
                    
                    <div class="setting-group">
                        <h3>Export Your Data</h3>
                        <p>Download a copy of your data including your profile, purchase history, and preferences.</p>
                        <form method="POST" class="inline-form">
                            <button type="submit" name="export_data" class="btn btn-secondary">Request Data Export</button>
                        </form>
                    </div>
                    
                    <div class="setting-group">
                        <h3>Storage Usage</h3>
                        <div class="storage-info">
                            <div class="storage-item">
                                <span class="storage-label">Profile Data:</span>
                                <span class="storage-value">0.5 MB</span>
                            </div>
                            <div class="storage-item">
                                <span class="storage-label">Purchase History:</span>
                                <span class="storage-value">1.2 MB</span>
                            </div>
                            <div class="storage-item">
                                <span class="storage-label">Cache & Preferences:</span>
                                <span class="storage-value">0.3 MB</span>
                            </div>
                            <div class="storage-item total">
                                <span class="storage-label">Total Usage:</span>
                                <span class="storage-value">2.0 MB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone Section -->
                <div class="settings-section" id="danger">
                    <h2>⚠️ Danger Zone</h2>
                    <p class="section-description">Irreversible and destructive actions. Please be careful.</p>
                    
                    <div class="danger-zone">
                        <div class="danger-item">
                            <h3>Delete Account</h3>
                            <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <form method="POST" class="danger-form">
                                <div class="form-group">
                                    <label for="confirm_delete">Type "DELETE" to confirm:</label>
                                    <input type="text" id="confirm_delete" name="confirm_delete" placeholder="DELETE" required>
                                </div>
                                <button type="submit" name="delete_account" class="btn btn-danger">Delete My Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'inc/footer.inc.php'; ?>

    <script>
        // Settings navigation
        document.querySelectorAll('.settings-nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and sections
                document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show corresponding section
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
                
                // Update URL hash
                window.location.hash = sectionId;
            });
        });

        // Handle URL hash on page load
        window.addEventListener('load', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                const link = document.querySelector(`[data-section="${hash}"]`);
                if (link) {
                    link.click();
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);

        // Confirm dangerous actions
        document.querySelector('.danger-form').addEventListener('submit', function(e) {
            const confirmText = document.getElementById('confirm_delete').value;
            if (confirmText !== 'DELETE') {
                e.preventDefault();
                alert('Please type "DELETE" exactly to confirm account deletion.');
            } else {
                if (!confirm('Are you absolutely sure? This will permanently delete your account and all data.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>