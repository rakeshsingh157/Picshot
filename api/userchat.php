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
    $stmt = $conn->prepare("SELECT id, profile_photo FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ./index.php');
    exit;
}

// Sending Message
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

<!DOCTYPE html>
<html lang="en">
<head>
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Chat with <?= htmlspecialchars($_GET['username'] ?? 'User') ?></title>
   
    <link rel="stylesheet" href="chat-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
 
    
    </style>
</head>
<body>
    <aside id="sidebar">
        <h1>Chat</h1>
        <center><div id="user-list">
            <?php
            $currentUserId = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT DISTINCT u.id, u.username, u.profile_photo
                                        FROM users u
                                        JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
                                        WHERE u.id != ? AND (m.sender_id = ? OR m.receiver_id = ?)");
            $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $username = htmlspecialchars($row['username']);
                $profilePhotoUrl = htmlspecialchars($row['profile_photo']);
                echo "<a class='user-item' href='?username=$username'>
                            <img src='$profilePhotoUrl' alt='Profile Photo'>
                            <span>$username</span>
                        </a>";
            }

            $stmt->close();
            ?></center>
        </div>
    </aside>

    <div id="chat-container">
        <div id="chat-header">
            <?php
            $receiverUsername = $_GET['username'] ?? '';
            if ($receiverUsername) {
                $receiverInfo = getUserInfoByUsername($conn, $receiverUsername);
                if ($receiverInfo) {
                    $profilePhotoUrl = htmlspecialchars($receiverInfo['profile_photo']);
                    echo "<img src='$profilePhotoUrl' alt='Profile Photo'>";
                    echo "<h2>" . htmlspecialchars($receiverUsername) . "</h2>";
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
        let isUserScrolling = false;
        let scrollTimeout;

        messageArea.addEventListener('scroll', () => {
            isUserScrolling = true;
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                isUserScrolling = false;
            }, 200);
        });

        function loadMessages() {
            const receiverUsername = receiverUsernameInput.value;
            if (receiverUsername) {
                const currentScrollTop = messageArea.scrollTop;
                const isScrolledToBottom = (messageArea.scrollHeight - messageArea.clientHeight) <= currentScrollTop + 1;

                fetch(`?messages_only=true&username=${receiverUsername}`)
                    .then(res => res.text())
                    .then(html => {
                        messageArea.innerHTML = html;
                        if (!isUserScrolling && isScrolledToBottom) {
                            messageArea.scrollTop = messageArea.scrollHeight;
                        } else if (!isUserScrolling) {
                            messageArea.scrollTop = currentScrollTop;
                        }
                    });
            } else {
                messageArea.innerHTML = "<center><p>No recipient selected.</p></center>";
            }
        }

        function sendMessage() {
            const receiverUsername = receiverUsernameInput.value;
            const message = messageInput.value.trim();

            if (receiverUsername && message) {
                const formData = new URLSearchParams();
                formData.append('receiver_username', receiverUsername);
                formData.append('message', message);

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Message failed: ' + (data.error || 'Unknown error.'));
                    }
                });
                messageInput.value = '';
            } else {
                alert('Please type a message and select user.');
            }
        }

        loadMessages();
        setInterval(loadMessages, 1000);

        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
</body>
</html>