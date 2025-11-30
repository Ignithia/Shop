<?php
session_start();

// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';

// If user is already logged in, redirect to index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// Get database connection
try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    $error_message = 'Database connection failed. Please try again later.';
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pdo)) {
    $login_input = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    $login_result = User::login($pdo, $login_input, $password);

    if ($login_result['success']) {
        User::startSession($login_result['user']);

        if ($remember_me) {
            setcookie('remember_login', $login_input, time() + (30 * 24 * 60 * 60), '/');
        }

        header('Location: index.php');
        exit();
    } else {
        $error_message = $login_result['message'];
    }
}

// Check for previous login
$remembered_email = $_COOKIE['remember_login'] ?? '';
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <div class="login-container">
        <div>
            <h1>Player Login</h1>

            <?php if ($error_message): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email or Username:</label>
                    <input type="text" id="email" name="email" required
                        placeholder="Enter your email or username..."
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($remembered_email); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required
                        placeholder="Enter your password...">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="signup-link">
                <p>New User? <a href="signup.php">Create Account</a></p>
            </div>

            <div class="demo-credentials">
                <strong>Demo Login:</strong><br>
                Email: test@shop.com<br>
                Username: test<br>
                Password: test123<br>
                Create an account or login with existing credentials<br>
                All accounts use secure password hashing
            </div>
        </div>
    </div>
</body>

</html>