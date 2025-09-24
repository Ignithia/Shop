<?php
session_start();

// Als de gebruiker al is ingelogd, redirect naar index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Verwerk registratieformulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = trim($_POST['voornaam'] ?? '');
    $achternaam = trim($_POST['achternaam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $telefoon = trim($_POST['telefoon'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $stad = trim($_POST['stad'] ?? '');
    
    // Validatie
    $errors = [];
    
    if (empty($voornaam)) {
        $errors[] = 'Voornaam is verplicht';
    }
    
    if (empty($achternaam)) {
        $errors[] = 'Achternaam is verplicht';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is verplicht';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ongeldig email adres';
    }
    
    if (empty($password)) {
        $errors[] = 'Wachtwoord is verplicht';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Wachtwoord moet minimaal 6 karakters bevatten';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Wachtwoorden komen niet overeen';
    }
    
    if (empty($adres)) {
        $errors[] = 'Adres is verplicht';
    }
    
    if (empty($postcode)) {
        $errors[] = 'Postcode is verplicht';
    }
    
    if (empty($stad)) {
        $errors[] = 'Stad is verplicht';
    }
    
    if (empty($errors)) {
        // Hier zou normaal de gebruiker in een database worden opgeslagen
        // Voor deze demo simuleren we een succesvolle registratie
        $success_message = 'Registratie succesvol! Je kunt nu inloggen met je gegevens.';
        
        // Reset formulier velden na succesvolle registratie
        $voornaam = $achternaam = $email = $telefoon = $adres = $postcode = $stad = '';
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="voornaam">First Name <span class="required">*</span>:</label>
                        <input type="text" id="voornaam" name="voornaam" required 
                               placeholder="Enter your first name..."
                               value="<?php echo isset($voornaam) ? htmlspecialchars($voornaam) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="achternaam">Last Name <span class="required">*</span>:</label>
                        <input type="text" id="achternaam" name="achternaam" required 
                               placeholder="Enter your last name..."
                               value="<?php echo isset($achternaam) ? htmlspecialchars($achternaam) : ''; ?>">
                    </div>
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
                    <label for="telefoon">Phone Number:</label>
                    <input type="tel" id="telefoon" name="telefoon" 
                           placeholder="Optional phone number..."
                           value="<?php echo isset($telefoon) ? htmlspecialchars($telefoon) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="adres">Address <span class="required">*</span>:</label>
                    <input type="text" id="adres" name="adres" required 
                           placeholder="Street address for game deliveries..."
                           value="<?php echo isset($adres) ? htmlspecialchars($adres) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="postcode">Postal Code <span class="required">*</span>:</label>
                        <input type="text" id="postcode" name="postcode" required 
                               placeholder="Postal code..."
                               value="<?php echo isset($postcode) ? htmlspecialchars($postcode) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="stad">City <span class="required">*</span>:</label>
                        <input type="text" id="stad" name="stad" required 
                               placeholder="Your city..."
                               value="<?php echo isset($stad) ? htmlspecialchars($stad) : ''; ?>">
                    </div>
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