<?php
session_start();

// Als de gebruiker al is ingelogd, redirect naar index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// Verwerk loginformulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Controleer inloggegevens
    if ($email === 'jenaam@shop.com' && $password === '12345isnotsecure') {
        // Succesvolle login
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $email;
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Onjuiste email of wachtwoord. Probeer opnieuw.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Login - Gaming Store</title>
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
                    <label for="email">Gamer Email:</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your gamer email..."
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password...">
                </div>
                
                <button type="submit" class="btn btn-primary">Login & Start Gaming</button>
            </form>
            
            <div class="signup-link">
                <p>New Player? <a href="signup.php">Create Account</a></p>
            </div>
            
            <div class="demo-credentials">
                <strong>Demo Login:</strong><br>
                Email: jenaam@shop.com<br>
                Password: 12345isnotsecure
            </div>
        </div>
    </div>
</body>
</html>