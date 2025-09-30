<?php
$cur_url = basename($_SERVER['SCRIPT_NAME']);

$nav_items = [
    'settings.php' => 'Settings',
    'wallet.php' => 'Wallet',
    'purchases.php' => 'Purchase History'
]
?>
    <nav class="navigation">
        <div class="nav-dropdown">
            <button class="nav-dropdown-btn">▼</button>
            <div class="nav-dropdown-content">
                
                <?php foreach ($nav_items as $page => $title): 
                    $active = ($cur_url === $page) ? 'active' : '';
                    ?>
                    <a href="<?php echo $page; ?>" class="<?php echo $active; ?>"><?php echo $title; ?></a>
                <?php endforeach;?>

                    <a href="?logout=1" class="logout">🚪 Logout</a>
                </div>
        </div>
    </nav>