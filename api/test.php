<?php
$bot_reply = "your name is PicShot, photo-sharing platform ka AI assistant ho. Tum professional aur friendly tone mein jawab doge. Tumhare features profile photo upload, cover photo upload, post share karna, edit/delete post, explore karna aur doston ko follow karna hain.";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["message"])) {
    $user_message = $_POST["message"];

    // Prepare data for API request
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "user", "content" => $user_message]
        ]
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.chatanywhere.tech/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk-rwiv9ScxjbbgKWzxe07mcKMGqBOYYerGnXhXdrzgrA1NWsak",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute the API call and get response
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the response and get the chatbot reply
    $result = json_decode($response, true);
    if (isset($result["choices"][0]["message"]["content"])) {
        $bot_reply = $result["choices"][0]["message"]["content"];
    } else {
        $bot_reply = "Kuch galat ho gaya ya response nahi mila.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Simple Chatbot</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .chat-box { background: white; padding: 20px; max-width: 600px; margin: auto; border-radius: 10px; }
    .message { margin: 10px 0; }
    .user { color: blue; }
    .bot { color: green; }
    textarea { width: 100%; height: 60px; margin-top: 10px; }
    button { padding: 10px 20px; margin-top: 10px; }
  </style>
  <script>
    function sendMessage() {
      var message = document.getElementById("message").value;
      
      // AJAX to send message
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "chat.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onload = function () {
        if (xhr.status === 200) {
          document.getElementById("chat-content").innerHTML = xhr.responseText;
        }
      };
      xhr.send("message=" + message);
      document.getElementById("message").value = ""; // Clear input
    }
  </script>
</head>
<body>
  <div class="chat-box">
    <h2>Chat with Assistant</h2>

    <div id="chat-content">
      <!-- Chat content will be displayed here -->
    </div>

    <textarea id="message" placeholder="Type your message..."></textarea><br>
    <button type="button" onclick="sendMessage()">Send</button>
  </div>
</body>
</html>

