<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getUserInfoByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT u.id, u.username, u.profile_photo, g.is_verified
                            FROM users u
                            LEFT JOIN goldentik g ON u.id = g.user_id
                            WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ./index.php');
    exit;
}

// Sending Message...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_username'], $_POST['message'])) {
    $senderId = $_SESSION['user_id'];
    $receiverUsername = trim($_POST['receiver_username']);
    $message = trim($_POST['message']);

    if (!empty($message) && !empty($receiverUsername)) {
        $receiverInfo = getUserInfoByUsername($conn, $receiverUsername);
        if ($receiverInfo) {
            $receiverId = $receiverInfo['id'];
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $senderId, $receiverId, $message);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'sent_message' => htmlspecialchars($message)]);
            } else {
                echo json_encode(['error' => 'Failed to send.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'User not found.']);
        }
    } else {
        echo json_encode(['error' => 'Empty message or username.']);
    }
    exit;
}

// Fetching Messages
if (isset($_GET['messages_only'], $_GET['username'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $otherUsername = trim($_GET['username']);
    $otherUserInfo = getUserInfoByUsername($conn, $otherUsername);

    if (!$otherUserInfo) {
        echo "<center><p>User not found.</p></center>";
        exit;
    }

    $otherUserId = $otherUserInfo['id'];
    $stmt = $conn->prepare("SELECT m.*, u1.username AS sender_username, u1.profile_photo AS sender_photo
                                FROM messages m
                                JOIN users u1 ON m.sender_id = u1.id
                                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                                ORDER BY m.sent_at ASC");
    $stmt->bind_param("iiii", $loggedInUserId, $otherUserId, $otherUserId, $loggedInUserId);
    $stmt->execute();
    $messagesResult = $stmt->get_result();
    $messages = $messagesResult->fetch_all(MYSQLI_ASSOC);

    $output = '';
    if (empty($messages)) {
        $output = "<center><p>No messages yet. Start the conversation!</p></center>";
    } else {
        foreach ($messages as $message) {
            $isMe = ($message['sender_id'] == $loggedInUserId);
            $align = $isMe ? 'right' : 'left';
            $bgColor = $isMe ? '#CDDCBE' : '#f0f0f0';
            $color = $isMe ? '#717171' : '#465A31';
            $border = $isMe ? '8px 0px 15px 8px' : '0px 8px 8px 15px';
            $photoUrl = htmlspecialchars($message['sender_photo']);

            $output .= "<div style='text-align: $align; margin-bottom: 12px; display: flex; align-items: flex-start; flex-direction: " . ($isMe ? 'row-reverse' : 'row') . ";'>";
            $output .= "<img src='$photoUrl' alt='Profile' style='width: 30px; height: 30px; border-radius: 50% ; margin-" . ($isMe ? 'left' : 'right') . ": 8px;'>";
            $output .= "<div>";
            $output .= "<span style='display: inline-block; background-color: $bgColor; color: $color; padding: 8px 12px; max-width: 50vw; margin-top:2px; word-wrap: break-word; border-radius:$border;'>";
            $output .= htmlspecialchars($message['message']);
            $output .= "</span><br><small style='color: gray;'>{$message['sent_at']}</small>";
            $output .= "</div></div>";
        }
    }

    echo $output;
    $stmt->close();
    exit;
}
?>
<?php include "sidebar.html";?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?= htmlspecialchars($_GET['username'] ?? 'User') ?></title>
    <link rel="stylesheet" href="chat-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrap">
    <aside id="sidebar">
        <h1>Chat</h1>
        <center><div id="user-list">
            <?php
            $currentUserId = $_SESSION['user_id'];
            $stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.username, u.profile_photo, g.is_verified,
                    MAX(m.sent_at) as last_msg_time
    FROM users u
    LEFT JOIN goldentik g ON u.id = g.user_id
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE u.id != ? AND (m.sender_id = ? OR m.receiver_id = ?)
    GROUP BY u.id
    ORDER BY last_msg_time DESC
");

            $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $username = htmlspecialchars($row['username']);
                $profilePhotoUrl = htmlspecialchars($row['profile_photo']);
                $isVerified = $row['is_verified'] ? " <img src='vf.png' alt='Verified' style='width: 20px; height: 20px;'>" : "";
                echo "<a class='user-item' href='?username=$username'>
                            <img src='$profilePhotoUrl' alt='Profile Photo'>
                            <span>$username $isVerified</span>
                        </a>";
            }

            $stmt->close();
            ?></center>
        </div>
    </aside>
            <div style="width:50px;"></div>
    <div id="chat-container">
              
            
        <div id="chat-header">
            <?php
            $receiverUsername = $_GET['username'] ?? '';
            if ($receiverUsername) {
                $receiverInfo = getUserInfoByUsername($conn, $receiverUsername);
                if ($receiverInfo) {
                    $profilePhotoUrl = htmlspecialchars($receiverInfo['profile_photo']);
                    $isVerified = $receiverInfo['is_verified'] ? "<img src='vf.png' alt='Verified' style='width: 20px; height: 20px;'>" : "";

                    echo "<img src='$profilePhotoUrl' alt='Profile Photo'>";
                    echo "<h2>" . htmlspecialchars($receiverUsername) . " $isVerified</h2>";
                } else {
                    echo "<p>User not found.</p>";
                }
            }
            ?>
        </div>


       

        <div id="message-area">
            <center><p>Loading messages...</p></center>
        </div>

        <div id="input-area">
            <input type="text" id="message-input" placeholder="Type your message...">
            <button id="send-button" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <input type="hidden" id="receiver-username" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
    </div>

    <script>
        const messageArea = document.getElementById('message-area');
        const receiverUsernameInput = document.getElementById('receiver-username');
        const messageInput = document.getElementById('message-input');
        // Optionally, store your own profile photo for optimistic message
        const myProfilePhoto = <?= json_encode($_SESSION['profile_photo'] ?? 'default.png') ?>;

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function loadMessages() {
            const receiverUsername = receiverUsernameInput.value;
            if (receiverUsername) {
                fetch(`?messages_only=true&username=${receiverUsername}`)
                    .then(res => res.text())
                    .then(data => {
                        messageArea.innerHTML = data;
                        messageArea.scrollTop = messageArea.scrollHeight; // Scroll to the bottom
                    });
            }
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                const receiverUsername = receiverUsernameInput.value;

                // --- Optimistic UI update: Add message instantly ---
                const align = 'right';
                const bgColor = '#CDDCBE';
                const color = '#717171';
                const border = '8px 0px 15px 8px';
                const now = new Date();
                const sentAt = now.getHours().toString().padStart(2, '0') + ':' +
                               now.getMinutes().toString().padStart(2, '0');

                const optimisticMsg = `
                    <div style='text-align: ${align}; margin-bottom: 12px; display: flex; align-items: flex-start; flex-direction: row-reverse;'>
                        <div>
                            <span style='display: inline-block; background-color: ${bgColor}; color: ${color}; padding: 8px 12px; max-width: 50vw; margin-top:2px; word-wrap: break-word; border-radius:${border};'>
                                ${escapeHtml(message)}
                            </span><br>
                            <small style='color: gray;'>${sentAt} (sending...)</small>
                        </div>
                    </div>
                `;

                messageArea.innerHTML += optimisticMsg;
                messageArea.scrollTop = messageArea.scrollHeight;

                // Clear the input field instantly
                messageInput.value = '';

                // --- Send to server in background ---
                fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({
                        receiver_username: receiverUsername,
                        message: message
                    })
                })
                .then(res => res.json())
                .then(response => {
                    if (!response.success) {
                        alert(response.error);
                        // Remove the optimistic message by reloading messages
                        loadMessages();
                    } else {
                        // Replace optimistic message with real one (with correct time)
                        loadMessages();
                    }
                });
            }
        }

        // Load messages when the page loads
        loadMessages();

        // Auto-refresh messages every 1 second for smooth chat
        setInterval(loadMessages, 1000);

        // Optional: Send message on Enter key
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script></div>
</body>
</html>
