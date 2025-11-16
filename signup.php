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

$success_message = '';
$error_message = '';

// Get database connection
try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    $error_message = 'Database connection failed. Please try again later.';
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pdo)) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Additional validation for password confirmation
    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } else {
        // Use the User class register method
        $registration_result = User::register($pdo, $username, $email, $password);
        
        if ($registration_result['success']) {
            $success_message = 'You have successfully registered! Please wait while you get redirected to the login page in <span id="cd">3</span> seconds. Thank you!';
            $should_redirect = true;
            // Clear form fields
            $username = $email = '';
        } else {
            $error_message = $registration_result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="signup-container">
        <div>
            <h1>Join the community</h1>
            
            <?php if ($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span>:</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Choose your gamer username..."
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span>:</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="your.gamertag@email.com"
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span>:</label>
                        <input type="password" id="password" name="password" required minlength="6"
                               placeholder="Minimum 6 characters...">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span>:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               placeholder="Confirm your password...">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="login-link">
                <p>Already a member? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
<script>
    <?php if ($should_redirect): ?>
        let countdown = 3;
        const countdownElement = document.getElementById('cd');
        const interval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = 'login.php';
            }
        }, 1000);
    <?php endif; ?>
</script>
</html>