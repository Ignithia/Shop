<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Friend.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    echo "DB error";
    exit;
}

$ajaxBase = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';

$me = User::getCurrentUser($pdo);
$friend = new Friend($pdo);
$friends = $friend->getFriends($me->getId());
$incoming = $friend->getIncomingRequests($me->getId());
$outgoing = $friend->getOutgoingRequests($me->getId());

$friend_message = '';
$friend_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['add_friend_username'])) {
    $target_username = trim($_POST['add_friend_username']);
    if ($target_username === '') {
        $friend_error = 'Please provide a username.';
    } else {
        $targetUser = new User($pdo);
        if ($targetUser->loadByUsername($target_username)) {
            $targetId = $targetUser->getId();
            if ($targetId === $me->getId()) {
                $friend_error = 'You cannot add yourself.';
            } else {
                $ok = $friend->sendRequest($me->getId(), $targetId);
                if ($ok) {
                    $friend_message = 'Friend request sent to ' . htmlspecialchars($target_username);
                    // refresh lists
                    $friends = $friend->getFriends($me->getId());
                    $incoming = $friend->getIncomingRequests($me->getId());
                    $outgoing = $friend->getOutgoingRequests($me->getId());
                } else {
                    $friend_error = 'Could not send friend request. Possibly already pending or friends.';
                }
            }
        } else {
            $friend_error = 'User not found.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Your Friends</title>
    <link rel="stylesheet" href="css/main.css" />
</head>

<body>
    <?php include 'inc/header.inc.php'; ?>
    <div class="container">
        <h2>Your Friends</h2>
        <?php if ($friend_message): ?>
            <div class="success" style="margin-bottom:12px"><?php echo $friend_message; ?></div>
        <?php endif; ?>
        <?php if ($friend_error): ?>
            <div class="error" style="margin-bottom:12px"><?php echo $friend_error; ?></div>
        <?php endif; ?>

        <section style="margin-bottom:1.25rem;">
            <h3>Add Friend</h3>
            <form id="addFriendForm" method="POST" style="display:flex;gap:8px;align-items:center;max-width:540px;">
                <input id="addFriendInput" type="text" name="add_friend_username" placeholder="Username" class="modal-input" />
                <button type="submit" class="card-btn" id="addFriendBtn">Send</button>
            </form>
            <div id="addFriendStatus" style="margin-top:8px"></div>
        </section>
        <section>
            <h3>Friends</h3>
            <?php if (empty($friends)): ?>
                <p>You have no friends yet.</p>
            <?php else: ?>
                <ul class="friends-list">
                    <?php foreach ($friends as $f): ?>
                        <li class="friend-item" data-user-id="<?php echo (int)$f['id']; ?>">
                            <div class="friend-left">
                                <div class="friend-avatar" aria-hidden="true"></div>
                                <a class="friend-link" href="profile.php?user=<?php echo urlencode($f['username']); ?>"><?php echo htmlspecialchars($f['username']); ?></a>
                            </div>
                            <div class="friend-actions">
                                <form method="post" action="<?php echo $ajaxBase; ?>ajax_handler.php?action=remove_friend" class="remove-friend-form">
                                    <input type="hidden" name="target_id" value="<?php echo (int)$f['id']; ?>">
                                    <button type="submit" class="btn-danger" title="Remove friend">Remove</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section>
            <h3>Incoming Requests</h3>
            <?php if (empty($incoming)): ?>
                <p>No incoming requests.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($incoming as $r): ?>
                        <li class="friend-request-item"><a href="profile.php?user=<?php echo urlencode($r['username']); ?>"><?php echo htmlspecialchars($r['username']); ?></a>
                            <form method="post" action="<?php echo $ajaxBase; ?>ajax_handler.php?action=respond_friend_request" style="display:inline" class="friend-form">
                                <input type="hidden" name="from_id" value="<?php echo (int)$r['id']; ?>">
                                <button type="submit" class="friend-action-btn btn-accept" name="action" value="accept">Accept</button>
                                <button type="submit" class="friend-action-btn btn-reject" name="action" value="reject">Reject</button>
                                <span class="friend-status" aria-live="polite" style="margin-left:8px"></span>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section>
            <h3>Outgoing Requests</h3>
            <?php if (empty($outgoing)): ?>
                <p>No outgoing requests.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($outgoing as $r): ?>
                        <li><a href="profile.php?user=<?php echo urlencode($r['username']); ?>"><?php echo htmlspecialchars($r['username']); ?></a> (pending)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    </div>
    <?php include 'inc/footer.inc.php'; ?>
    <script>
        (function() {
            const form = document.getElementById('addFriendForm');
            const input = document.getElementById('addFriendInput');
            const status = document.getElementById('addFriendStatus');
            const btn = document.getElementById('addFriendBtn');

            const AJAX_ENDPOINT = '<?php echo $ajaxBase; ?>ajax_handler.php';
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const username = input.value.trim();
                    if (!username) {
                        status.innerText = 'Enter a username';
                        return;
                    }
                    btn.disabled = true;
                    status.innerText = 'Sending...';

                    fetch(AJAX_ENDPOINT + '?action=send_friend_request', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            username: username
                        })
                    }).then(response => {
                        if (!response.ok) throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                        return response.text();
                    }).then(text => {
                        try {
                            const j = JSON.parse(text);
                            if (j.success) {
                                status.innerText = j.message || 'Request sent';
                                input.value = '';
                                setTimeout(() => location.reload(), 700);
                            } else {
                                status.innerText = j.message || 'Failed';
                            }
                        } catch (err) {
                            console.error('Invalid JSON from add friend:', text);
                            status.innerText = 'Server error';
                        }
                    }).catch(err => {
                        console.error('Add friend fetch error:', err);
                        status.innerText = 'Network error: ' + (err.message || '');
                    }).finally(() => {
                        btn.disabled = false;
                    });
                });
                document.querySelectorAll('.friend-form').forEach(function(f) {
                    f.querySelectorAll('button').forEach(btn => {
                        btn.addEventListener('click', function() {
                            f._lastClicked = this;
                        });

                        document.querySelectorAll('.remove-friend-form').forEach(function(frm) {
                            frm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                const form = e.currentTarget;
                                const btn = form.querySelector('button');
                                if (btn) btn.disabled = true;
                                const formData = new FormData(form);
                                let url;
                                try {
                                    url = new URL(form.action, window.location.href).href;
                                } catch (err) {
                                    url = form.action;
                                }
                                fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: new URLSearchParams(Array.from(formData.entries()))
                                }).then(r => r.json()).then(j => {
                                    if (j.success) {
                                        const li = form.closest('.friend-item');
                                        if (li) li.remove();
                                    } else {
                                        alert(j.message || 'Failed to remove friend');
                                        if (btn) btn.disabled = false;
                                    }
                                }).catch(err => {
                                    console.error('Remove friend error', err);
                                    alert('Network error');
                                    if (btn) btn.disabled = false;
                                });
                            });
                        });
                    });

                    f.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const frm = e.currentTarget;
                        const li = frm.closest('.friend-request-item');
                        const statusEl = frm.querySelector('.friend-status');
                        const buttons = frm.querySelectorAll('button');

                        buttons.forEach(b => b.disabled = true);
                        if (statusEl) statusEl.textContent = 'Processing...';

                        const formData = new FormData(frm);
                        const submitter = e.submitter || frm._lastClicked || document.activeElement;
                        if (submitter && submitter.name) formData.set(submitter.name, submitter.value || '');

                        let url;
                        try {
                            url = new URL(frm.action, window.location.href).href;
                        } catch (err) {
                            url = frm.action;
                        }

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams(Array.from(formData.entries()))
                        }).then(response => {
                            if (!response.ok) throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                            return response.text();
                        }).then(text => {
                            try {
                                const j = JSON.parse(text);
                                if (j.success) {
                                    statusEl.textContent = j.message || 'Done';
                                    setTimeout(() => {
                                        if (li) li.remove();
                                    }, 700);
                                } else {
                                    statusEl.textContent = j.message || 'Failed';
                                    buttons.forEach(b => b.disabled = false);
                                }
                            } catch (err) {
                                console.error('Invalid JSON from respond_friend:', text);
                                statusEl.textContent = 'Server error';
                                buttons.forEach(b => b.disabled = false);
                            }
                        }).catch(err => {
                            console.error('Respond friend fetch error:', err);
                            statusEl.textContent = 'Network error: ' + (err.message || '');
                            buttons.forEach(b => b.disabled = false);
                        });
                    });
                });
            }
        })();
    </script>
</body>

</html>