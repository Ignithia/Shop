<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!User::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $currentUser = User::getCurrentUser($pdo);
    
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_to_cart':
        $game_id = intval($_POST['game_id'] ?? 0);
        if ($game_id > 0 && !$currentUser->ownsGame($game_id)) {
            $success = $currentUser->addToCart($game_id);
            $cart_count = count($currentUser->getShoppingCart());
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Added to cart!' : 'Failed to add to cart',
                'cart_count' => $cart_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid game or already owned']);
        }
        break;
        
    case 'remove_from_cart':
        $game_id = intval($_POST['game_id'] ?? 0);
        if ($game_id > 0) {
            $success = $currentUser->removeFromCart($game_id);
            $cart_items = $currentUser->getShoppingCart();
            $cart_count = count($cart_items);
            
            // Calculate new total
            $total_price = 0;
            foreach ($cart_items as $game) {
                $total_price += $game['price'] * 100;
            }
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Removed from cart' : 'Failed to remove',
                'cart_count' => $cart_count,
                'total_price' => number_format($total_price),
                'is_empty' => $cart_count === 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid game ID']);
        }
        break;
        
    case 'add_to_wishlist':
        $game_id = intval($_POST['game_id'] ?? 0);
        if ($game_id > 0 && !$currentUser->ownsGame($game_id)) {
            $success = $currentUser->addToWishlist($game_id);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Added to wishlist!' : 'Already in wishlist'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid game or already owned']);
        }
        break;
        
    case 'remove_from_wishlist':
        $game_id = intval($_POST['game_id'] ?? 0);
        if ($game_id > 0) {
            $success = $currentUser->removeFromWishlist($game_id);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Removed from wishlist' : 'Failed to remove'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid game ID']);
        }
        break;
        
    case 'add_coins':
        $amount = intval($_POST['coin_amount'] ?? 0);
        if ($amount > 0 && $amount <= 100000) {
            $dollars = $amount / 100;
            $success = $currentUser->addBalance($dollars);
            $new_balance = $currentUser->getBalance();
            echo json_encode([
                'success' => $success,
                'message' => $success ? "Successfully added " . number_format($amount) . " coins!" : 'Failed to add coins',
                'new_balance' => number_format($new_balance)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        }
        break;
        
    case 'search_games':
        $query = $_GET['query'] ?? '';
        $category = $_GET['category'] ?? '';
        
        if (strlen($query) < 2 && empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Query too short']);
            exit();
        }
        
        $sql = "SELECT g.*, c.name as category_name 
                FROM game g 
                LEFT JOIN category c ON g.fk_category = c.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (g.name LIKE ? OR g.description LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        if (!empty($category) && $category !== 'all') {
            $sql .= " AND g.fk_category = ?";
            $params[] = intval($category);
        }
        
        $sql .= " ORDER BY g.name ASC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'games' => $games,
            'count' => count($games)
        ]);
        break;
        
    case 'get_cart_count':
        $cart_count = count($currentUser->getShoppingCart());
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
