<?php
// Include required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Game.php';
require_once 'classes/Friend.php';

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

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

    case 'search_entities':
        $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
        $type = $_GET['type'] ?? $_POST['type'] ?? 'all';
        if (strlen($q) < 1) {
            echo json_encode(['success' => false, 'message' => 'Query too short']);
            break;
        }
        $results = [];
        // search users
        if ($type === 'all' || $type === 'user') {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? ORDER BY username ASC LIMIT 10");
            $stmt->execute(["%$q%"]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $u) {
                $results[] = [
                    'type' => 'user',
                    'id' => (int)$u['id'],
                    'label' => $u['username'],
                    'url' => 'profile.php?user=' . urlencode($u['username'])
                ];
            }
        }
        // search games
        if ($type === 'all' || $type === 'game') {
            $stmt = $pdo->prepare("SELECT id, name FROM game WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
            $stmt->execute(["%$q%"]);
            $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($games as $g) {
                $results[] = [
                    'type' => 'game',
                    'id' => (int)$g['id'],
                    'label' => $g['name'],
                    'url' => 'product.php?id=' . (int)$g['id']
                ];
            }
        }

        echo json_encode(['success' => true, 'results' => $results]);
        break;

    case 'get_cart_count':
        $cart_count = count($currentUser->getShoppingCart());
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count
        ]);
        break;

    /* Friend actions */
    case 'send_friend_request':
        $target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
        $target_username = trim($_POST['username'] ?? '');
        $friendSvc = new Friend($pdo);

        if ($target_id <= 0 && $target_username === '') {
            echo json_encode(['success' => false, 'message' => 'Missing target']);
            break;
        }

        // Resolve username to id if necessary
        if ($target_id <= 0 && $target_username !== '') {
            $u = new User($pdo);
            if (!$u->loadByUsername($target_username)) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                break;
            }
            $target_id = $u->getId();
        }

        if ($target_id === $currentUser->getId()) {
            echo json_encode(['success' => false, 'message' => 'Cannot add yourself']);
            break;
        }

        $ok = $friendSvc->sendRequest($currentUser->getId(), $target_id);
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Request sent' : 'Request failed or already pending']);
        break;

    case 'remove_friend':
        $target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
        if ($target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing target_id']);
            break;
        }
        $friendSvc = new Friend($pdo);
        $ok = $friendSvc->removeFriend($currentUser->getId(), $target_id);
        echo json_encode(['success' => (bool)$ok]);
        break;

    case 'update_profile':
        $me = $currentUser;
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '');
        $public_profile = isset($_POST['public_profile']) ? 1 : 0;
        $public_library = isset($_POST['public_library']) ? 1 : 0;

        if ($username === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'Username and email required']);
            break;
        }

        $me->setUsername($username);
        $me->setEmail($email);
        $me->setAvatar($avatar);
        $me->setPublicProfile((bool)$public_profile);
        $me->setPublicLibrary((bool)$public_library);

        $ok = $me->save();
        if ($ok) echo json_encode(['success' => true, 'message' => 'Profile saved']);
        else echo json_encode(['success' => false, 'message' => 'Save failed']);
        break;

    case 'respond_friend_request':
        $from_id = isset($_POST['from_id']) ? (int)$_POST['from_id'] : 0;
        $decision = $_POST['action'] ?? '';
        if ($from_id <= 0 || !in_array($decision, ['accept', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            break;
        }
        $friendSvc = new Friend($pdo);
        $accepted = ($decision === 'accept');
        $ok = $friendSvc->respondRequest($from_id, $currentUser->getId(), $accepted);
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? ($accepted ? 'Accepted' : 'Rejected') : 'Operation failed']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
