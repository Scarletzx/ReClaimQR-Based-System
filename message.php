<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION["user_id"];

// Helper: canonical user pair key (lower ID = user_a, higher = user_b)
function convKey($uid1, $uid2) {
    return [min($uid1, $uid2), max($uid1, $uid2)];
}

// -------------------------------------------------------
// AJAX: Send a message + snapshot item on first message
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajax_send"])) {
    $receiver_id = intval($_POST["receiver_id"]);
    $message     = trim($_POST["message"]);
    $item_id     = intval($_POST["item_id"] ?? 0);
    $item_type   = $_POST["item_type"] ?? "Lost";

    if (!empty($message) && $receiver_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, item_id, item_type, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $current_user_id, $receiver_id, $item_id, $item_type, $message);
        $stmt->execute();
        $inserted_id = $conn->insert_id;

        // Snapshot item permanently on first message of this conversation
        if ($item_id > 0) {
            [$ua, $ub] = convKey($current_user_id, $receiver_id);

            $chk = $conn->prepare("SELECT id FROM message_item_snapshots WHERE user_a = ? AND user_b = ?");
            $chk->bind_param("ii", $ua, $ub);
            $chk->execute();

            if ($chk->get_result()->num_rows === 0) {
                // Fetch from live table
                $table  = ($item_type === "Found") ? "items_found" : "items";
                $i_stmt = $conn->prepare("SELECT item_name, category, location, image FROM $table WHERE id = ?");
                $i_stmt->bind_param("i", $item_id);
                $i_stmt->execute();
                $item_row = $i_stmt->get_result()->fetch_assoc();

                // Fallback: already claimed/deleted — get from claimed_items
                if (!$item_row) {
                    $ci = $conn->prepare("SELECT item_name, item_category AS category, item_location AS location, item_image AS image FROM claimed_items WHERE item_id = ? AND item_type = ? LIMIT 1");
                    $ci->bind_param("is", $item_id, $item_type);
                    $ci->execute();
                    $item_row = $ci->get_result()->fetch_assoc();
                }

                if ($item_row) {
                    $snap = $conn->prepare("INSERT IGNORE INTO message_item_snapshots (user_a, user_b, item_id, item_type, item_name, category, location, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $snap->bind_param("iiisssss", $ua, $ub, $item_id, $item_type, $item_row['item_name'], $item_row['category'], $item_row['location'], $item_row['image']);
                    $snap->execute();
                }
            }
        }

        echo json_encode(["success" => true, "id" => $inserted_id, "message" => htmlspecialchars($message), "created_at" => date("h:i A")]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit();
}

// -------------------------------------------------------
// AJAX: Poll for new messages
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["poll"])) {
    $receiver_id = intval($_GET["receiver_id"]);
    $last_msg_id = intval($_GET["last_id"] ?? 0);

    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.message, m.created_at, u.fullname
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiiii", $current_user_id, $receiver_id, $receiver_id, $current_user_id, $last_msg_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $mark = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $mark->bind_param("ii", $receiver_id, $current_user_id);
    $mark->execute();

    echo json_encode($rows);
    exit();
}

// -------------------------------------------------------
// Get conversation list
// -------------------------------------------------------
$conv_stmt = $conn->prepare("
    SELECT DISTINCT
        u.id,
        u.fullname,
        (SELECT m2.message FROM messages m2
         WHERE ((m2.sender_id = u.id AND m2.receiver_id = ?) OR (m2.sender_id = ? AND m2.receiver_id = u.id))
         ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
        (SELECT m3.created_at FROM messages m3
         WHERE ((m3.sender_id = u.id AND m3.receiver_id = ?) OR (m3.sender_id = ? AND m3.receiver_id = u.id))
         ORDER BY m3.created_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM messages m4
         WHERE m4.sender_id = u.id AND m4.receiver_id = ? AND m4.is_read = 0) AS unread_count
    FROM users u
    WHERE u.id IN (
        SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        FROM messages WHERE sender_id = ? OR receiver_id = ?
    )
    AND u.id != ?
    ORDER BY last_time DESC
");
$conv_stmt->bind_param("iiiiiiiii",
    $current_user_id, $current_user_id,
    $current_user_id, $current_user_id,
    $current_user_id,
    $current_user_id, $current_user_id, $current_user_id,
    $current_user_id
);
$conv_stmt->execute();
$conversations = $conv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// -------------------------------------------------------
// Active chat
// -------------------------------------------------------
$chat_user_id  = intval($_GET["chat"] ?? 0);
$chat_user     = null;
$chat_item     = null;
$chat_messages = [];
$item_id       = intval($_GET["item_id"] ?? 0);
$item_type     = $_GET["item_type"] ?? "Lost";

if ($chat_user_id > 0) {
    $u_stmt = $conn->prepare("SELECT id, fullname FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $chat_user_id);
    $u_stmt->execute();
    $chat_user = $u_stmt->get_result()->fetch_assoc();

    // --- Always load item card from snapshot first ---
    [$ua, $ub] = convKey($current_user_id, $chat_user_id);
    $snap_stmt = $conn->prepare("SELECT * FROM message_item_snapshots WHERE user_a = ? AND user_b = ?");
    $snap_stmt->bind_param("ii", $ua, $ub);
    $snap_stmt->execute();
    $snapshot = $snap_stmt->get_result()->fetch_assoc();

    if ($snapshot) {
        // Snapshot exists — use it directly. Both users get the same card.
        $chat_item = [
            'id'        => $snapshot['item_id'],
            'item_name' => $snapshot['item_name'],
            'category'  => $snapshot['category'],
            'location'  => $snapshot['location'],
            'image'     => $snapshot['image'],
            'type'      => $snapshot['item_type'],
        ];
    } else {
        // No snapshot yet — try to resolve item and create snapshot
        $resolve_id   = $item_id;
        $resolve_type = $item_type;

        if ($resolve_id <= 0) {
            // Try to get item_id from first message in this conversation
            $fms = $conn->prepare("SELECT item_id, item_type FROM messages WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND item_id > 0 ORDER BY created_at ASC LIMIT 1");
            $fms->bind_param("iiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id);
            $fms->execute();
            $fm = $fms->get_result()->fetch_assoc();
            if ($fm) {
                $resolve_id   = $fm['item_id'];
                $resolve_type = $fm['item_type'];
            }
        }

        if ($resolve_id > 0) {
            // Try live item table
            $table  = ($resolve_type === "Found") ? "items_found" : "items";
            $i_stmt = $conn->prepare("SELECT id, item_name, category, location, image FROM $table WHERE id = ?");
            $i_stmt->bind_param("i", $resolve_id);
            $i_stmt->execute();
            $live_item = $i_stmt->get_result()->fetch_assoc();

            // Fallback: claimed_items
            if (!$live_item) {
                $ci = $conn->prepare("SELECT item_id AS id, item_name, item_category AS category, item_location AS location, item_image AS image FROM claimed_items WHERE item_id = ? AND item_type = ? LIMIT 1");
                $ci->bind_param("is", $resolve_id, $resolve_type);
                $ci->execute();
                $live_item = $ci->get_result()->fetch_assoc();
            }

            if ($live_item) {
                $live_item['type'] = $resolve_type;
                $chat_item = $live_item;

                // Create snapshot now so it's permanent going forward
                $snap_ins = $conn->prepare("INSERT IGNORE INTO message_item_snapshots (user_a, user_b, item_id, item_type, item_name, category, location, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $snap_ins->bind_param("iiisssss", $ua, $ub, $resolve_id, $resolve_type, $live_item['item_name'], $live_item['category'], $live_item['location'], $live_item['image']);
                $snap_ins->execute();
            }
        }
    }

    // Load messages
    $msg_stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.message, m.created_at, u.fullname
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $msg_stmt->bind_param("iiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id);
    $msg_stmt->execute();
    $chat_messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $read_stmt->bind_param("ii", $chat_user_id, $current_user_id);
    $read_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/in_app_message.css">
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>
    <div class="main-content">
        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <a href="settings_personal.php" class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="msg-layout">

            <!-- LEFT: Conversation List -->
            <div class="msg-sidebar">
                <div class="msg-sidebar-header"><h2>Message</h2></div>
                <div class="msg-conversation-list">
                    <?php if (empty($conversations)): ?>
                        <div class="msg-empty-conv">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <p>No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="message.php?chat=<?php echo $conv['id']; ?>"
                               class="msg-conv-item <?php echo ($chat_user_id == $conv['id']) ? 'active' : ''; ?>">
                                <div class="msg-conv-avatar">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <div class="msg-conv-info">
                                    <span class="msg-conv-name"><?php echo htmlspecialchars($conv['fullname']); ?></span>
                                    <?php if (!empty($conv['last_message'])): ?>
                                        <span class="msg-conv-preview"><?php echo htmlspecialchars(substr($conv['last_message'], 0, 30)) . (strlen($conv['last_message']) > 30 ? '...' : ''); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="msg-unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Chat Window -->
            <div class="msg-chat-panel">
                <?php if ($chat_user): ?>

                    <div class="msg-chat-header">
                        <div class="msg-chat-avatar">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <span class="msg-chat-name"><?php echo htmlspecialchars($chat_user['fullname']); ?></span>
                    </div>

                    <div class="msg-chat-body" id="chatBody">

                        <!-- PERMANENT ITEM CARD - visible to BOTH users always -->
                        <?php if ($chat_item): ?>
                        <div class="msg-item-context">
                            <div class="msg-item-context-img-wrap">
                                <?php if (!empty($chat_item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($chat_item['image']); ?>" alt="Item">
                                <?php else: ?>
                                    <div class="msg-item-context-no-img">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21,15 16,10 5,21"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="msg-item-context-info">
                                <span class="msg-item-badge badge-<?php echo strtolower($chat_item['type']); ?>">
                                    <?php echo htmlspecialchars($chat_item['type']); ?>
                                </span>
                                <strong class="msg-item-name"><?php echo htmlspecialchars($chat_item['item_name']); ?></strong>
                                <span class="msg-item-meta">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                                    </svg>
                                    <?php echo htmlspecialchars($chat_item['category']); ?>
                                </span>
                                <span class="msg-item-meta">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo htmlspecialchars($chat_item['location']); ?>
                                </span>
                            </div>
                            <div class="msg-item-context-label">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                Regarding this item
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="msg-messages-wrap" id="messagesWrap">
                            <?php if (empty($chat_messages)): ?>
                                <div class="msg-no-messages"><p>Start the conversation!</p></div>
                            <?php else: ?>
                                <?php
                                $last_date = "";
                                foreach ($chat_messages as $msg):
                                    $msg_date = date("d M Y", strtotime($msg['created_at']));
                                    $is_mine  = ($msg['sender_id'] == $current_user_id);
                                ?>
                                    <?php if ($msg_date !== $last_date): ?>
                                        <div class="msg-date-divider"><span><?php echo $msg_date; ?></span></div>
                                        <?php $last_date = $msg_date; ?>
                                    <?php endif; ?>
                                    <div class="msg-bubble-wrap <?php echo $is_mine ? 'mine' : 'theirs'; ?>">
                                        <div class="msg-bubble <?php echo $is_mine ? 'bubble-mine' : 'bubble-theirs'; ?>">
                                            <?php echo htmlspecialchars($msg['message']); ?>
                                        </div>
                                        <span class="msg-time"><?php echo date("h:i A", strtotime($msg['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="msg-chat-input-area">
                        <div class="msg-input-wrap">
                            <input type="text" id="msgInput" placeholder="Type a message..." autocomplete="off">
                            <button id="sendBtn" onclick="sendMessage()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="msg-no-chat">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d0d4ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <h3>Select a conversation</h3>
                        <p>Choose from your existing conversations or start one from an item's details page</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const currentUserId = <?php echo $current_user_id; ?>;
const chatUserId    = <?php echo $chat_user_id; ?>;
const itemId        = <?php echo $item_id; ?>;
const itemType      = "<?php echo htmlspecialchars($item_type); ?>";

function scrollToBottom() {
    const wrap = document.getElementById('messagesWrap');
    if (wrap) wrap.scrollTop = wrap.scrollHeight;
}
scrollToBottom();

let lastMsgId = <?php echo !empty($chat_messages) ? end($chat_messages)['id'] : 0; ?>;

function sendMessage() {
    const input = document.getElementById('msgInput');
    const text  = input.value.trim();
    if (!text || chatUserId === 0) return;
    input.value = '';
    fetch('message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax_send=1&receiver_id=${chatUserId}&message=${encodeURIComponent(text)}&item_id=${itemId}&item_type=${itemType}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            lastMsgId = data.id;
            appendMessage(text, data.created_at, true);
            scrollToBottom();
        }
    });
}

function appendMessage(text, time, isMine) {
    const wrap = document.getElementById('messagesWrap');
    if (!wrap) return;
    const placeholder = wrap.querySelector('.msg-no-messages');
    if (placeholder) placeholder.remove();
    const div = document.createElement('div');
    div.className = `msg-bubble-wrap ${isMine ? 'mine' : 'theirs'}`;
    div.innerHTML = `<div class="msg-bubble ${isMine ? 'bubble-mine' : 'bubble-theirs'}">${escapeHtml(text)}</div><span class="msg-time">${time}</span>`;
    wrap.appendChild(div);
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

if (chatUserId > 0) {
    setInterval(() => {
        fetch(`message.php?poll=1&receiver_id=${chatUserId}&last_id=${lastMsgId}`)
        .then(r => r.json())
        .then(msgs => {
            if (msgs.length > 0) {
                msgs.forEach(m => {
                    lastMsgId = Math.max(lastMsgId, parseInt(m.id));
                    if (parseInt(m.sender_id) !== currentUserId) {
                        const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        appendMessage(m.message, time, false);
                        scrollToBottom();
                    }
                });
            }
        });
    }, 3000);
}

document.getElementById('msgInput') &&
document.getElementById('msgInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
</script>
</body>
</html>