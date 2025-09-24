<?php
session_start();

// If user is already logged in, redirect to index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        // Normally the user would be saved to a database here
        // For this demo we simulate a successful registration through a success message
        $success_message = 'Registration successful! You can now login with your credentials.';
        
        // Reset form fields after successful registration
        $username = $email = $phone = '';
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Gaming Community - Gaming Store</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="signup-container">
        <div>
            <h1>Join the Gaming Community</h1>
            
            <?php if ($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span>:</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Choose your gamer username..."
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Gamer Email <span class="required">*</span>:</label>
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
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Optional phone number..."
                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Gaming Account</button>
            </form>
            
            <div class="login-link">
                <p>Already a player? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>