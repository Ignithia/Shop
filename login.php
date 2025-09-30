<?php
session_start();

// If user is already logged in, redirect to index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// Mock user database
$users_file = 'data/users.json';
if(file_exists($users_file)){
    $users = json_decode(file_get_contents($users_file),true);
} else {
    $users = [
        [
            'username' => 'test',
            'email' => 'test@shop.com',
            'password' => 'test123'
        ]
    ];
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);
    
    $user_found = null;
    
    // Check login credentials
    foreach ($users as $user) {
        if (($user['email'] === $login_input || $user['username'] === $login_input) && $user['password'] === $password) {
            $user_found = $user;
            break;
        }
    }
    
    if ($user_found) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $user_found['email'];
        $_SESSION['username'] = $user_found['username'];
        
        // Handle Remember Me functionality
        if ($remember_me) {
            // Set cookie for 30 days 
            setcookie('remember_login', $login_input, time() + (30 * 24 * 60 * 60), '/');
        }
        
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Incorrect email/username or password. Please try again.';
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
                Email: test@shop.com OR Username: test<br>
                Password: test123
            </div>
        </div>
    </div>
</body>
</html>