<?php
session_start();

// DB Connection
$host = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com"; 
$username = "admin";
$password = "DBpicshot"; 
$database = "Photostore"; 

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if chat history session is set
if (!isset($_SESSION['chat_history'])) {
   $_SESSION['chat_history'] = [
    ["role" => "system", "content" => "your name is PicShot, photo-sharing platform ka AI assistant ho. Tum professional aur friendly tone mein jawab doge. Tumhare features profile photo upload, cover photo upload, post share karna, edit/delete post, explore karna aur doston ko follow karna hain."]
];

}


if (isset($_POST['message'])) {
    $message = $_POST['message'];

    // Add the user's message to the session history
    $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];

    // Send the message to the AI API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.chatanywhere.tech/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => $_SESSION['chat_history']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk-rwiv9ScxjbbgKWzxe07mcKMGqBOYYerGnXhXdrzgrA1NWsak",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $assistantMessage = $data['choices'][0]['message']['content'];

    // Add the assistant's response to the session history
    $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $assistantMessage];

    // Save the user message and assistant reply into the database
    $session_id = session_id(); // Unique session identifier
    $stmt = $conn->prepare("INSERT INTO chat_history (session_id, user_message, assistant_reply) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $session_id, $message, $assistantMessage);
    $stmt->execute();
    $stmt->close();

    echo $assistantMessage;
    exit;
}

// Fetch previous chat history
$session_id = session_id();
$historyQuery = "SELECT * FROM chat_history WHERE session_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($historyQuery);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = ["role" => "user", "content" => $row['user_message']];
    $history[] = ["role" => "assistant", "content" => $row['assistant_reply']];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PicShot Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        body {
            background-color: #f4f6f8;
            color: #333;
        }

        body.dark-mode {
            background-color: #000 !important;
            color: #f1f1f1;
        }

        .chat-container-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            background-color: #007bff;
            color: #fff;
            padding: 1.5rem;
            text-align: center;
            font-size: 1.2rem;
            border-bottom: 1px solid #0056b3;
        }

        body.dark-mode .chat-header {
            background-color: #111;
            border-bottom: 1px solid #333;
        }

        #chat-container {
            padding: 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            max-width: 75%;
            word-wrap: break-word;
        }

        .message.user {
            background-color: #007bff;
            color: #fff;
            margin-left: auto;
            text-align: right;
        }

        .message.assistant {
            background-color: #e9ecef;
            color: #333;
            margin-right: auto;
        }

        body.dark-mode .message.user {
            background-color: #1e90ff;
            color: #fff;
        }

        body.dark-mode .message.assistant {
            background-color: #111;
            color: #0f0;
        }

        .input-area {
            display: flex;
            border-top: 1px solid #ced4da;
            padding: 1rem;
            background-color: #fff;
        }

        body.dark-mode .input-area {
            background-color: #000;
            border-top: 1px solid #333;
        }

        #message-input {
            border-radius: 6px 0 0 6px;
            border-right: none;
            flex-grow: 1;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            background-color: #fff;
            color: #000;
        }

        body.dark-mode #message-input {
            background-color: #111;
            color: #fff;
            border: 1px solid #444;
        }

        #send-btn {
            border-radius: 0 6px 6px 0;
            padding: 0.75rem 1.25rem;
            background-color: #007bff;
            color: #fff;
            border: 1px solid #007bff;
            cursor: pointer;
        }

        #send-btn:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        #mode-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.75rem;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            border-radius: 50%;
            background-color: #6c757d;
            color: #fff;
            z-index: 1000;
        }

        .typing-animation {
            color: #999;
        }

        body.dark-mode .typing-animation {
            color: #888;
        }

        @media (max-width: 768px) {
            .chat-header {
                padding: 1rem;
                font-size: 1rem;
            }

            #chat-container {
                padding: 1rem;
            }

            .message {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .input-area {
                padding: 0.75rem;
            }

            #message-input, #send-btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            #mode-toggle {
                top: 0.5rem;
                right: 0.5rem;
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <button id="mode-toggle">🌙</button>

    <div class="chat-container-wrapper">
        <div class="chat-header">PicShot Assistant</div>
        <div id="chat-container">
            <?php
                // Display previous chat history
                foreach ($history as $msg) {
                    echo '<div class="message ' . $msg['role'] . '">' . htmlspecialchars($msg['content']) . '</div>';
                }
            ?>
        </div>
        <div class="input-area input-group">
            <input type="text" id="message-input" class="form-control" placeholder="Type your message...">
            <button class="btn btn-primary" id="send-btn">Send</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#mode-toggle').click(function () {
                $('body').removeClass('bg-light').toggleClass('dark-mode');
                $('#mode-toggle').text($('body').hasClass('dark-mode') ? '🌕' : '🌙');
            });

            function appendMessage(sender, message) {
                let messageDiv = $('<div class="message ' + sender + '"></div>').text(message);
                $('#chat-container').append(messageDiv);
                $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
            }

            $('#send-btn').click(function () {
                let userMessage = $('#message-input').val().trim();
                if (userMessage) {
                    appendMessage('user', userMessage);
                    $('#message-input').val('');

                    // Show typing animation
                    let typingDiv = $('<div class="message assistant typing-animation">Typing...</div>');
                    $('#chat-container').append(typingDiv);
                    $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);

                    $.ajax({
                        url: '', // Current page URL
                        type: 'POST',
                        data: { message: userMessage },
                        success: function (response) {
                            typingDiv.remove(); // Remove typing animation
                            appendMessage('assistant', response);
                        },
                        error: function (xhr, status, error) {
                            typingDiv.remove(); // Remove typing animation on error
                            appendMessage('assistant', 'Error: ' + error);
                        }
                    });
                }
            });

            $('#message-input').keypress(function (e) {
                if (e.which === 13) { // Enter key pressed
                    $('#send-btn').click();
                    return false; // Prevent default form submission
                }
            });

            // Scroll to the bottom on page load
            $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
        });
    </script>
</body>
</html>