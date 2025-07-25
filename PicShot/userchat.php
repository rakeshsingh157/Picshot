<?php
// Set default timezone to Indian/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to get user info by username
function getUserInfoByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT u.id, u.username, u.profile_photo, g.is_verified
                            FROM users u
                            LEFT JOIN goldentik g ON u.id = g.user_id
                            WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();
    $stmt->close();

    // Set default profile photo if not set
    if (!empty($userInfo)) {
        $userInfo['profile_photo'] = !empty($userInfo['profile_photo']) ? $userInfo['profile_photo'] : 'profile.jpg';
    }

    return $userInfo;
}

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./index.php');
    exit;
}

// --- AJAX endpoint for searching users ---
if (isset($_GET['action']) && $_GET['action'] === 'search_users') {
    $searchQuery = trim($_GET['query'] ?? '');
    $currentUserId = $_SESSION['user_id'];
    $users = [];

    if (!empty($searchQuery)) {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.profile_photo, g.is_verified
            FROM users u
            LEFT JOIN goldentik g ON u.id = g.user_id
            WHERE u.username LIKE ? AND u.id != ?
            LIMIT 10
        ");
        $searchParam = "%" . $searchQuery . "%";
        $stmt->bind_param("si", $searchParam, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Set default profile photo if not set
            $row['profile_photo'] = !empty($row['profile_photo']) ? htmlspecialchars($row['profile_photo']) : 'profile.jpg';
            $users[] = $row;
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
}

// --- AJAX endpoint for sending messages ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_username'], $_POST['message'])) {
    $senderId = $_SESSION['user_id'];
    $receiverUsername = trim($_POST['receiver_username']);
    $message = trim($_POST['message']);

    if (!empty($message) && !empty($receiverUsername)) {
        $receiverInfo = getUserInfoByUsername($conn, $receiverUsername);
        if ($receiverInfo) {
            $receiverId = $receiverInfo['id'];

            // Insert message with Indian/Kolkata time
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at)
                                   VALUES (?, ?, ?, CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Kolkata'))");
            $stmt->bind_param("iis", $senderId, $receiverId, $message);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'sent_message' => htmlspecialchars($message)]);
            } else {
                echo json_encode(['error' => 'Failed to send message: ' . $stmt->error]);
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

// --- AJAX endpoint for fetching messages ---
if (isset($_GET['messages_only'], $_GET['username'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $otherUsername = trim($_GET['username']);
    $otherUserInfo = getUserInfoByUsername($conn, $otherUsername);

    if (!$otherUserInfo) {
        echo "<center><p class='no-messages'><i class='fas fa-user-times'></i> User not found.</p></center>";
        exit;
    }

    $otherUserId = $otherUserInfo['id'];

    // Fetch messages with Indian/Kolkata time
    $stmt = $conn->prepare("SELECT m.*, u1.profile_photo AS sender_photo
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
        $output = "<center><p class='no-messages'><i class='fas fa-comment-dots'></i> No messages yet. Start the conversation!</p></center>";
    } else {
        foreach ($messages as $message) {
            $isMe = ($message['sender_id'] == $loggedInUserId);
            $messageClass = $isMe ? 'sent-message' : 'received-message';

            // Use profile.jpg as default if no photo is set
            $photoUrl = !empty($message['sender_photo']) ? htmlspecialchars($message['sender_photo']) : 'profile.jpg';

            // Format time in Indian/Kolkata timezone
            $sentTime = date('H:i', strtotime($message['sent_at']));

            $output .= "<div class='message-container $messageClass'>";
            if ($isMe) {
                $output .= "<img src='$photoUrl' alt='Profile' class='profile-photo'>";
                $output .= "<div class='message-content-wrapper'><span class='message-body'>" . htmlspecialchars($message['message']) . "</span><small class='message-timestamp'>$sentTime</small></div>";
            } else {
                $output .= "<img src='$photoUrl' alt='Profile' class='profile-photo'>";
                $output .= "<div class='message-content-wrapper'><span class='message-body'>" . htmlspecialchars($message['message']) . "</span><small class='message-timestamp'>$sentTime</small></div>";
            }
            $output .= "</div>";
        }
    }

    echo $output;
    $stmt->close();
    exit;
}
?>

<?php include "sidebar.html"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/avif" href="icon.avif">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PicShot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="chat-style.css">
    
</head>
<body>
    <div class="wrap">
        <aside id="sidebar">
            <div class="sidebar-header">
                <h1>Chats &nbsp;&nbsp;&nbsp;  </h1>
                <i class="fas fa-plus add-icon"></i>
            </div>
            <div id="user-list">
                <?php
                $currentUserId = $_SESSION['user_id'];
                $stmt = $conn->prepare("
                    SELECT DISTINCT u.id, u.username, u.profile_photo, g.is_verified,
                                   MAX(m.sent_at) as last_msg_time
                    FROM users u
                    LEFT JOIN goldentik g ON u.id = g.user_id
                    JOIN messages m ON (u.id = m.sender_id AND m.receiver_id = ?) OR (u.id = m.receiver_id AND m.sender_id = ?)
                    WHERE u.id != ?
                    GROUP BY u.id
                    ORDER BY last_msg_time DESC
                ");
                $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $username = htmlspecialchars($row['username']);
                        $profilePhotoUrl = !empty($row['profile_photo']) ? htmlspecialchars($row['profile_photo']) : 'profile.jpg';
                        $isVerified = $row['is_verified'] ? " <img src='vf.png' alt='Verified' class='verified-badge'>" : "";
                        $activeClass = (isset($_GET['username']) && $_GET['username'] === $username) ? 'active' : '';

                        echo "<a class='user-item $activeClass' href='?username=$username'>
                                <img src='$profilePhotoUrl' alt='Profile Photo'>
                                <span>$username $isVerified</span>
                              </a>";
                    }
                } else {
                    echo "<center><p class='no-messages'><i class='fas fa-users'></i> No conversations yet. Click '+' to find users!</p></center>";
                }
                $stmt->close();
                ?>
            </div>

            <div id="search-overlay">
                <div class="search-box">
                    <i class="fas fa-arrow-left close-btn"></i>
                    <input type="text" id="search-input-overlay" placeholder="Search users by username...">
                </div>
                <div id="search-user-list">
                    <p class="search-no-results">Start typing to search for users.</p>
                </div>
            </div>
        </aside>

        <div id="chat-container">
            <div id="chat-header">
                <?php
                $receiverUsername = $_GET['username'] ?? '';
                if ($receiverUsername) {
                    $receiverInfo = getUserInfoByUsername($conn, $receiverUsername);
                    if ($receiverInfo) {
                        $profilePhotoUrl = !empty($receiverInfo['profile_photo']) ? htmlspecialchars($receiverInfo['profile_photo']) : 'profile.jpg';
                        $isVerified = $receiverInfo['is_verified'] ? "<img src='vf.png' alt='Verified' class='verified-badge'>" : "";

                        echo "<img src='$profilePhotoUrl' alt='Profile Photo'>";
                        echo "<h2 onclick=\"window.location.href='userview.php?username=" . htmlspecialchars($receiverUsername) . "'\">" . htmlspecialchars($receiverUsername) . " $isVerified</h2>";
                    } else {
                        echo "<img src='profile.jpg' alt='Default Photo' class='profile-photo'>";
                        echo "<h2>User not found.</h2>";
                    }
                } else {
                    echo "<img src='profile.jpg' alt='Default Photo' class='profile-photo'>";
                    echo "<h2>Select a user to chat</h2>";
                }
                ?>
            </div>

            <div id="message-area">
                <?php if (!isset($_GET['username'])): ?>
                    <center><p class='no-messages'><i class='fas fa-hand-point-left'></i> Select a user from the sidebar to start chatting!</p></center>
                <?php endif; ?>
            </div>

            <div id="input-area">
                <input type="text" id="message-input" placeholder="Type your message...">
                <button id="send-button" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <input type="hidden" id="receiver-username" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
        </div>
    </div>

    <script>
        const messageArea = document.getElementById('message-area');
        const receiverUsernameInput = document.getElementById('receiver-username');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const addIcon = document.querySelector('.add-icon');
        const searchOverlay = document.getElementById('search-overlay');
        const closeSearchBtn = document.querySelector('.search-box .close-btn');
        const searchInputOverlay = document.getElementById('search-input-overlay');
        const searchUserList = document.getElementById('search-user-list');
        const myProfilePhoto = <?= json_encode($_SESSION['profile_photo'] ?? 'profile.jpg') ?>;

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

        let debounceTimeout;
        const debounce = (func, delay) => {
            return function(...args) {
                const context = this;
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        function fetchMessages() {
            const username = receiverUsernameInput.value;
            if (!username) return;

            fetch(`?messages_only=1&username=${encodeURIComponent(username)}`)
                .then(response => response.text())
                .then(html => {
                    if (messageArea.innerHTML.trim() !== html.trim()) {
                        messageArea.innerHTML = html;
                        messageArea.scrollTop = messageArea.scrollHeight;
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function sendMessage() {
            const receiverUsername = receiverUsernameInput.value;
            const message = messageInput.value.trim();

            if (message === '' || !receiverUsername) {
                alert('Please type a message and select a user.');
                return;
            }

            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('receiver_username', receiverUsername);
            formData.append('message', message);

            const sentTime = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: false });
            const newMessageHtml = `
                <div class="message-container sent-message temporary-sending">
                    <img src="${myProfilePhoto}" alt="Profile" class="profile-photo">
                    <div class="message-content-wrapper">
                        <span class="message-body">${escapeHtml(message)}</span>
                        <small class="message-timestamp">${sentTime} <i class="fas fa-clock sending-indicator"></i></small>
                    </div>
                </div>
            `;
            messageArea.insertAdjacentHTML('beforeend', newMessageHtml);
            messageInput.value = '';
            messageArea.scrollTop = messageArea.scrollHeight;

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                sendButton.disabled = false;
                sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';

                const tempMessageElement = messageArea.querySelector('.temporary-sending');
                if (tempMessageElement) {
                    tempMessageElement.classList.remove('temporary-sending');
                    const sendingIndicator = tempMessageElement.querySelector('.sending-indicator');
                    if (sendingIndicator) {
                        sendingIndicator.remove();
                    }
                }

                if (!data.success) {
                    console.error('Error sending message:', data.error);
                    alert('Error sending message: ' + data.error);
                    if (tempMessageElement) {
                        tempMessageElement.classList.add('message-error');
                        const timestampSmall = tempMessageElement.querySelector('.message-timestamp');
                        if (timestampSmall) {
                            timestampSmall.innerHTML += ' <i class="fas fa-exclamation-triangle" style="color: red;"></i>';
                        }
                    }
                }
            })
            .catch(error => {
                sendButton.disabled = false;
                sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
                console.error('Network error sending message:', error);
                alert('Network error. Could not send message.');

                const tempMessageElement = messageArea.querySelector('.temporary-sending');
                if (tempMessageElement) {
                    tempMessageElement.classList.add('message-error');
                    const timestampSmall = tempMessageElement.querySelector('.message-timestamp');
                    if (timestampSmall) {
                        timestampSmall.innerHTML += ' <i class="fas fa-exclamation-triangle" style="color: red;"></i>';
                    }
                }
            });
        }

        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        if (receiverUsernameInput.value) {
            fetchMessages();
            setInterval(fetchMessages, 5000);
        }

        const currentChatUsername = receiverUsernameInput.value;
        if (currentChatUsername) {
            const userItems = document.querySelectorAll('.user-item');
            userItems.forEach(item => {
                const usernameInItem = item.querySelector('span').innerText.split(' ')[0];
                if (usernameInItem === currentChatUsername) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        addIcon.addEventListener('click', () => {
            searchOverlay.classList.add('active');
            searchInputOverlay.focus();
            searchUserList.innerHTML = '<p class="search-no-results">Start typing to search for users.</p>';
        });

        closeSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
            searchInputOverlay.value = '';
            searchUserList.innerHTML = '';
        });

        const searchUsers = debounce((query) => {
            searchUserList.innerHTML = '<p class="search-no-results">Searching...</p>';

            if (query.trim() === '') {
                searchUserList.innerHTML = '<p class="search-no-results">Start typing to search for users.</p>';
                return;
            }

            fetch(`?action=search_users&query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(users => {
                    displaySearchResults(users);
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                    searchUserList.innerHTML = '<p class="search-no-results" style="color: red;">Error searching. Please try again.</p>';
                });
        }, 300);

        function displaySearchResults(users) {
            searchUserList.innerHTML = '';

            if (users.length > 0) {
                users.forEach(user => {
                    const userDiv = document.createElement('a');
                    userDiv.href = `?username=${encodeURIComponent(user.username)}`;
                    userDiv.classList.add('search-user-item');
                    const verifiedBadge = user.is_verified ? "<img src='vf.png' alt='Verified' class='verified-badge'>" : "";
                    userDiv.innerHTML = `
                        <img src="${user.profile_photo}" alt="Profile Photo">
                        <span>${escapeHtml(user.username)} ${verifiedBadge}</span>
                    `;
                    searchUserList.appendChild(userDiv);
                });
            } else {
                searchUserList.innerHTML = '<p class="search-no-results">No users found.</p>';
            }
        }

        searchInputOverlay.addEventListener('input', (e) => {
            searchUsers(e.target.value);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>