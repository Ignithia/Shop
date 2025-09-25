<?php
session_start();
$users_file = "./data/users.json";

// If user is already logged in, redirect to index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $existing_users_json = file_get_contents($users_file);
    $existing_users = json_decode($existing_users_json, true);
    

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
    } else {
        foreach($existing_users as $user){
            if($user['username'] === $username){
                $errors[] = "Username has been taken already.";
            break;
            }
        }
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }else {
        foreach($existing_users as $user){
            if($user['email'] === $email){
                $errors[] = "Email has registered already.";
            break;
            }
        }
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
        $new_user = [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ];
        $existing_users[] = $new_user;
        $add_user_json = json_encode($existing_users, JSON_PRETTY_PRINT);
        file_put_contents($users_file, $add_user_json);
        
        $username = $email = $phone = '';

        $success_message = 'You have successfully registered! Please wait a while you get redirected to the login page in <span id="cd">3</span> seconds. Thank you!';
        $should_redirect = true; 
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
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Optional phone number..."
                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
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