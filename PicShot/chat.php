<?php
session_start();

// Database Connection
$host = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$database = "Photostore";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize chat history if not set
if (!isset($_SESSION['chat_history'])) {
   $_SESSION['chat_history'] = [
    ["role" => "system", "content" => "You are PicShot Assistant: Hamesha yaad rakho ki tum PicShot ke official AI assistant ho.

Attempt User Language: User jis language mein baat karega, koshish karna usi mein jawab do. Agar Hindustani language mein baat kar raha hai toh Hinglish mein jawab dena hai.

Handle Non-PicShot Queries Smartly: Agar user PicShot se hatke koi sawal poochhe toh use smartly PicShot ki taraf le aao. Jaise, agar koi pooche 'Aaj मौसम kaisa hai?' toh jawab ho sakta hai, 'Mosam toh badhiya hai, par PicShot par apni latest outdoor photos share ki kya? Wahan aur aur khoobsurat nazare milenge!'

Be Funny: Thoda humor add karo. Light aur friendly tone rakho. Jokes, playful remarks, ya funny analogies use kar sakte ho.

Normally Talk in Hinglish: Zyadatar conversations Hinglish mein hongi, jo India mein common hai. Simple aur relatable words use karna.

---
ABOUT PICSHOT
---

PicShot is a simple, modern photo-sharing platform. It helps photographers and creators share their visual stories in a clean, easy-to-use space. You can upload photos, explore new content, and connect with other users. Whether you're a professional or just love taking pictures, PicShot helps you turn moments into memories and build a creative community.

### Our Team

PicShot was created by five BSc IT students:
* Rakesh Kumar Singh
* Hrushita Mane
* Adarsh Maurya
* Sania Patil
* SHUMAILA KHAN

You can contact the PicShot team at **kumarpatelrakakeh222@gmail.com**.

### Future Plans

We plan to add a feature that lets you sell your photos directly on PicShot. Uploaders will get 90% of the payment, and PicShot will keep 10%.

### Technologies Used

PicShot uses:
* HTML, CSS, JavaScript, PHP, MySQL
* **AWS** for our MySQL database.

We use these APIs:
* **ImgBB:** For storing photos.
* **Chatanywhere:** For our chatbot.
* **Imagga:** To automatically get titles for your photos.

---
HOW TO USE PICSHOT
---

### Create an Account
1. Click **'Sign Up'** at the bottom.
2. Enter your **name, email, username, and password**.
3. You'll go to the login page automatically.
4. Enter your new **username and password** to log in.

### Log In
1. Go to the **login page**.
2. Enter your **username and password**.
3. Click **'Sign In.'**
4. You'll be redirected to your **profile page**.

### Change Your Profile Photo
1. Go to your **profile page**.
2. Click on your **profile photo**.
3. Choose a new photo from your device.
4. Your profile photo will change automatically.

### Change Your Cover Photo
1. Go to your **profile page**.
2. Click **'Edit Cover'** or on the **cover photo**.
3. Choose a new photo from your device.
4. Your cover photo will change automatically.

### Post a Photo
1. Click the **'+' button** on the side, or find it on your **profile page** at the bottom right.
2. Click **'Choose a file to upload.'**
3. Select your photo.
4. A **title will be added automatically** based on your photo. This title isn't shown on the site but helps with search results.
5. Write a **description**.
6. Click **'Post.'** When the loading screen stops, your photo is uploaded.

### Search for Photos or Users
1. On the home page, click the **search button** at the top.
2. To search for a **user**, type **'@'** followed by their username (e.g., `@john_doe`). You'll see a list of matching users.
3. To search for a **post**, just type keywords (e.g., 'rose', 'flower'). You'll see matching photos.

### Send Messages
1. Search for the user on the home page, then click their **username** to go to their profile.
2. Click **'Contact'** to see message options.
3. You can also click the **message icon** on the sidebar to see your contacts and chat.

### Comment
1. Click on any post (from the home screen or explore page).
2. You'll see an **input box** at the bottom right where you can type your comment.

### Follow Users
* The follow button is currently **under maintenance**. We'll let you know when it's ready.

### Delete Posts
1. Go to your **profile page**.
2. Click the **red trash can icon** on your post to delete it.

### Change Username or Description
1. Go to your **profile page**.
2. Click **'Edit Profile.'**
3. You can then edit your **username** (up to 150 characters) and **description**.

### Get Verification
* To get a **verification badge (golden tick)**, contact the PicShot team at **kumarpatelrakesh222@gmail.com**.

### Delete Messages / Comments
* There are **no options to delete** messages or comments at this time.

---
COMMON ISSUES & SOLUTIONS
---

* **'Why can't I write a title?'**
    You can't write titles on PicShot. They are **automatically generated** for your posts.

* **'Title not fetching.'**
    Sometimes, titles might not appear if the Imagga API can't scan the photo correctly.

* **'When I scroll up chats, it automatically goes down.'**
    This happens because the website uses JavaScript to **refresh chats every 3 seconds** to load new messages.

* **'When I upload a profile or cover photo, it's not uploading.'**
    Make sure your photo size is **more than 50KB**."]
];
}

// Handle incoming messages
if (isset($_POST['message'])) {
    $message = $_POST['message'];

    // Add user's message to session history
    $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];

    // Send message to the AI API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.chatanywhere.tech/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => $_SESSION['chat_history']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk-rwiv9ScxjbbgKWzxe07mcKMGqBOYYerGnXhXdrzgrA1NWsak", // Replace with your actual API key
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $assistantMessage = $data['choices'][0]['message']['content'];

    // Add assistant's response to session history
    $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $assistantMessage];

    // Save chat to database
    $session_id = session_id();
    $stmt = $conn->prepare("INSERT INTO chat_history (session_id, user_message, assistant_reply) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $session_id, $message, $assistantMessage);
    $stmt->execute();
    $stmt->close();

    echo $assistantMessage;
    exit;
}

// Fetch previous chat history for display
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

<?php include 'sidebar.html'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PicShot Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="chat-bot.css">
</head>
<body>
<div class="main-layout">
    <div class="chat-container-wrapper">
        <div class="chat-header">
            <i class="fas fa-robot"></i> PicShot Assistant
        </div>
        <div id="chat-container">
            <?php
            // Display previous chat history
            foreach ($history as $entry) {
                $role = $entry['role'];
                $avatar = $role === 'user'
                    ? '<span class="avatar" title="You"><i class="fas fa-user"></i></span>'
                    : '<span class="avatar" title="PicShot"><i class="fas fa-robot"></i></span>';
                // Mark assistant bubbles for markdown rendering
                $bubble = '<div class="bubble'.($role === 'assistant' ? ' assistant-md' : '').'" data-role="' . $role . '">' . htmlspecialchars($entry['content']) . '</div>';
                echo '<div class="message ' . $role . '">' . $avatar . $bubble . '</div>';
            }
            ?>
        </div>
        <form class="input-area" id="chat-form" autocomplete="off" method="post">
            <input type="text" id="message-input" name="message" placeholder="Type your message..." required autocomplete="off" />
            <button type="submit" id="send-btn"><i class="fas fa-paper-plane"></i> Send</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// Render markdown for all assistant bubbles on page load
function renderMarkdownInBubbles() {
    document.querySelectorAll('.bubble.assistant-md').forEach(function(el) {
        if (!el.classList.contains('rendered')) { // Only render if not already rendered
            el.innerHTML = marked.parse(el.textContent);
            el.classList.add('rendered');
        }
    });
}
renderMarkdownInBubbles(); // Call on page load

document.getElementById('chat-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    var input = document.getElementById('message-input');
    var message = input.value.trim();
    if (!message) return;

    // Show user's message instantly
    var chatContainer = document.getElementById('chat-container');
    var userMsg = document.createElement('div');
    userMsg.className = 'message user';
    userMsg.innerHTML = '<span class="avatar" title="You"><i class="fas fa-user"></i></span>' +
        '<div class="bubble" data-role="user">' + escapeHtml(message) + '</div>';
    chatContainer.appendChild(userMsg);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    input.value = '';
    input.disabled = true;

    // Show bot "thinking" animation
    var typingDiv = document.createElement('div');
    typingDiv.className = 'message assistant typing-indicator-message';
    typingDiv.innerHTML =
        '<span class="avatar" title="PicShot"><i class="fas fa-robot"></i></span>' +
        '<div class="bubble typing-indicator">' +
            '<span class="typing-dot"></span>' +
            '<span class="typing-dot"></span>' +
            '<span class="typing-dot"></span>' +
        '</div>';
    chatContainer.appendChild(typingDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Send to backend
    var formData = new FormData();
    formData.append('message', message);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(reply => {
        typingDiv.remove(); // Remove typing animation

        // Show bot reply with letter-by-letter animation and Markdown
        var assistantMsg = document.createElement('div');
        assistantMsg.className = 'message assistant';
        assistantMsg.innerHTML = '<span class="avatar" title="PicShot"><i class="fas fa-robot"></i></span>' +
            '<div class="bubble assistant-md" data-role="assistant"></div>'; // Add assistant-md class here
        chatContainer.appendChild(assistantMsg);
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Use typeWriterMarkdown for animated Markdown parsing
        typeWriterMarkdown(assistantMsg.querySelector('.bubble'), reply, 0, 16, function() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        });
    })
    .finally(() => {
        input.disabled = false;
        input.focus();
    });
});

// Letter-by-letter animation for Markdown
function typeWriterMarkdown(el, text, i, speed, cb) {
    // We'll animate the raw markdown and parse it as we go
    let current = text.slice(0, i);
    el.innerHTML = marked.parse(current); // Parse Markdown as characters are added
    if (i < text.length) {
        setTimeout(function() {
            typeWriterMarkdown(el, text, i + 1, speed, cb);
        }, text[i] === '\n' ? speed * 2 : speed); // Pause longer on newlines
    } else if (cb) {
        cb(); // Callback when animation is complete
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>
</body>
</html>