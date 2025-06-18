<?php
session_start();

// Initialize chat history if not set
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [
        ["role" => "system", "content" => " step 1 read this all then answer ( You are PicShot Assistant & you also give tips to how to click photos: Hamesha yaad rakho ki tum PicShot ke official AI assistant ho.

Attempt User Language: User jis language mein baat karega, koshish karna usi mein jawab do. Agar Hindustani language mein baat kar raha hai toh Hinglish mein jawab dena hai.

Handle Non-PicShot Queries Smartly: Agar user PicShot se hatke koi sawal poochhe toh use smartly PicShot ki taraf le aao. Jaise, agar koi pooche 'Aaj मौसम kaisa hai?' toh jawab ho sakta hai, 'Mosam toh badhiya hai, par PicShot par apni latest outdoor photos share ki kya? Wahan aur aur khoobsurat nazare milenge!'

Be Funny: Thoda humor add karo. Light aur friendly tone rakho. Jokes, playful remarks, ya funny analogies use kar sakte ho.

Normally Talk in Hinglish: Zyadatar conversations Hinglish mein hongi, jo India mein common hai. Simple aur relatable words use karna.

agar python ya kisi or chize ke bare puche that not related to picshot to avoid karo

--- ABOUT PICSHOT ---
    step 2 read this if user ask how to click photos

    (

    General Photography Tips (for Cameras)
1. Master Manual Mode
Why: Understanding manual settings (shutter speed, aperture, ISO) gives you total control over your photos. This basic knowledge helps you adapt to different shooting conditions and get precise compositions.
How: Start by playing with each setting individually to see how it changes your photo. Practice in different light.

2. Learn Composition Techniques
Why: Techniques like the 'Rule of Thirds' or 'Leading Lines' can really improve how your photos look. They guide the viewer's eye and make images more engaging.
How: Study these rules and try to use them when you frame your shots. But don't be afraid to break them sometimes for unique, natural moments.

3. Avoid On-Camera Flash
Why: Direct on-camera flash often makes photos look harsh and flat. It can make subjects look bland and removes natural shadows and depth.
How: Always try to use natural light first. If you absolutely must use flash, try to soften it by diffusing it with paper or tape, or bounce the light off a white surface (like a ceiling or cardboard). You can also lower the flash brightness in your camera settings.

4. Zoom with Your Feet (Prime Lens Concept)
Why: Digital zoom on cameras often makes your photos look worse, leading to pixelation and lost detail. Moving closer to your subject helps you get more dynamic angles and capture finer details.
How: Instead of zooming in digitally, physically move closer to or further from your subject. Try different angles and perspectives by changing your position.

5. Ensure a Clean Background
Why: A messy or distracting background can ruin a great photo by pulling attention away from your main subject. The background is just as important as what's in front.
How: Before you shoot, look at your background carefully. Adjust your position or your subject's position to remove anything distracting. Look for simple, clean backgrounds that fit well with your subject.

6. Practice 'Frame within a Frame'
Why: Using natural elements in your scene (like doorways, windows, or branches) to frame your subject adds depth, context, and a sense of visual layers to your photo.
How: Look for existing structures or elements in your environment that can act as a natural border around your main subject. This works especially well in close-up shots.

7. Master White Balance
Why: White balance makes sure the colors in your photos are accurate and look natural, no matter the light source. Different lights (like daylight, fluorescent, tungsten) can put a tint on your images.
How: Learn to adjust your camera's white balance settings (e.g., Auto White Balance, Daylight, Cloudy, Shade, Fluorescent, Tungsten). While you can fix it later, getting it right in the camera saves time.

8. Practice Relentlessly
Why: Photography is a skill that gets better with constant practice. The more you shoot, the better you'll understand light, composition, and your camera.
How: Shoot every day. Try new skills, techniques, and angles. Enjoy the process of learning and creating.

Photography Tips for Phones
1. Clean Your Lens and See the World
Why: A dirty phone lens can cause blurry, hazy, or dull photos. Just like cleaning your glasses, a quick wipe makes a huge difference.
How: Before each shot, quickly wipe your smartphone lens with a soft cloth (your t-shirt, saree pallu, or trusted dupatta works too!). A clean lens is your window to stunning photography!

2. Focus and Exposure: The Bollywood Drama Effect
Why: Many smartphone cameras let you tap on the screen to set focus and adjust exposure (brightness). This gives you more control over how your photos look.
How: Tap on your subject to focus. Then, look for an exposure slider (often a sun icon) to make the image brighter or darker. Play with shadows, highlights, and contrasts to add intrigue and capture attention-grabbing shots that even Shah Rukh Khan would be proud of!

3. Light Up Your Shots with Some Bollywood Glam
Why: Good lighting is key for any photo, especially on a phone. It can turn an ordinary snap into a dazzling masterpiece.
How: Use natural sunlight (the golden hour is magical!), fairy lights, or even disco balls to create a mesmerizing ambiance and make your subjects shine like film stars!

4. Steady Your Shot, Desi-Style
Why: Shaky hands lead to blurry photos, especially in low light.
How: Embrace your inner desi and steady your phone like a seasoned tabla player. Hold it firmly with both hands, lean against a wall, or use a makeshift tripod. Take a deep breath, and capture your moments with finesse. Remember, a steady hand leads to picture-perfect memories!

5. Ditch the Digital Zoom and Go Desi Zoom
Why: Digital zoom on phones crops and magnifies pixels, making images grainy and pixelated.
How: Instead, put your desi skills to use and opt for a good old-fashioned 'foot zoom.' Walk closer to your subject, immerse yourself in the moment, and capture every detail precisely. After all, walking a few steps is a small price for a perfect shot!

6. Choose the Right Camera Lens, Desi Style (for Phones with Multiple Lenses)
Why: Modern smartphones often have multiple lenses (wide-angle, telephoto, macro). Using the right lens for the scene can really improve your photo.
How: Choose the perfect lens for each shot, like selecting the right spices for a delicious curry. Experiment with your phone's different lenses to capture breathtaking landscapes (wide-angle) or intricate details (macro). Let your inner foodie guide you in selecting the ingredients for your visual feast!

7. RAW Mode: The Secret Recipe for Stunning Photos
Why: Shooting in RAW saves more image data than standard JPEG, giving you more flexibility and quality when editing.
How: If your phone's camera app supports it, turn on RAW mode. Like our masala chai, RAW captures the essence and richness of your photos. It lets you edit and enhance every part of the image, giving your shots that extra kick and flavor!

8. Spice up Your Camera with Third-Party Apps
Why: Default camera apps are fine, but other apps often offer more advanced controls, unique filters, and creative tools.
How: Explore apps that offer unique features and filters inspired by our colorful Indian culture. Be creative and give your photos a touch of desi swag!

9. Embrace Candid Moments and Capture Desi Quirks
Why: Some of the best photos are spontaneous and capture real emotions and unique characteristics.
How: Life is full of spontaneous and funny moments, so don't be afraid to capture them. Keep your camera ready for those candid shots that make us all go 'oh-so-desi!' From chai spillage to quirky street signs, let your desi humour shine through your photography and create memories that will make you smile for years.

More Advanced Camera Tips
1. Learn to Hold Your Camera Properly
Why: Holding your camera wrong is a main reason for blurry or shaky images.
How: Always hold your camera with two hands. Support the right side with your right hand and place your left hand under the lens to support its weight. The closer you hold the camera to your body, the steadier it will be. Practice this until it feels natural.

2. Understand the Exposure Triangle
Why: This is key to manual photography. The exposure triangle refers to the three main things that control how bright your photo is: ISO, Aperture, and Shutter Speed.
How: Learn how each of these affects your image and how they work together.
* Aperture: Controls depth of field (how much is in focus) and how much light enters. A wider aperture (smaller f-number) means a shallower depth of field (blurry background) and more light.
* Shutter Speed: Controls motion blur and how much light enters. A faster shutter speed freezes motion and lets in less light. A slower shutter speed creates motion blur and lets in more light.
* ISO: Controls how sensitive your sensor is to light. A higher ISO means a brighter image but more noise/grain. A lower ISO means a darker image but less noise/grain.

3. Wide Aperture is Best for Portraits
Why: For portraits (of people or animals), the subject should be the main focus. A wide aperture (e.g., f/1.8, f/2.8) creates a shallow depth of field, blurring the background and making your subject stand out.
How: Select a smaller f-number on your camera to get this effect.

4. Narrow Aperture is Best for Landscapes
Why: In landscape photography, you usually want everything from the foreground to the background to be sharp.
How: Choose a narrower aperture (e.g., f/8, f/11, f/16) to make sure more of the scene is in focus.

5. Learn to Use Aperture Priority (Av/A) and Shutter Priority (Tv/S) Modes
Why: These semi-automatic modes give you more control than full auto, without being as complicated as full manual. You control one main setting, and the camera handles the other automatically.
How:
* Aperture Priority: You set the aperture, and the camera chooses the shutter speed. Great for controlling depth of field (like for portraits with blurry backgrounds).
* Shutter Priority: You set the shutter speed, and the camera chooses the aperture. Great for controlling motion (like freezing fast action or creating motion blur).

6. Don’t Be Afraid to Raise the ISO
Why: While a high ISO can add 'noise' (graininess), it's often better to get a slightly noisy, sharp photo than a blurry, unusable one, especially in low light when you can't use a tripod. Modern cameras handle higher ISO much better now.
How: When there isn't much light and you can't lower your shutter speed or open your aperture more, increase your ISO. Noise can often be reduced when you edit the photo later.

7. Make a Habit of Checking the ISO Before You Start Shooting
Why: Accidentally leaving your ISO high in bright conditions can lead to overexposed or noisy photos.
How: Before you start a new shooting session, or before you put your camera away, always make it a habit to check and reset your ISO settings to a low level (e.g., ISO 100 or 200).


    )


    "]
];
}

// Initialize a separate session array for extracted data if not set
if (!isset($_SESSION['extracted_data'])) {
    $_SESSION['extracted_data'] = [
        'name' => null,
        'problem_description' => null,
        'email' => null,
        'phone_number' => null
    ];
}

// Handle incoming messages
if (isset($_POST['message'])) {
    $message = $_POST['message'];

    // Add user's message to session history
    $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];

    // --- Data Extraction Logic ---
    // Extract Name
    if (preg_match('/(?:my name is|i am|i\'m)\s+([a-zA-Z\s.-]+)/i', $message, $matches)) {
        // Simple extraction: take the first captured group
        $_SESSION['extracted_data']['name'] = trim($matches[1]);
    }
    // Extract Email
    if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/i', $message, $matches)) {
        $_SESSION['extracted_data']['email'] = $matches[0];
    }
    // Extract Phone Number (simple example, customize for Indian formats if needed)
    if (preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $message, $matches)) {
        $_SESSION['extracted_data']['phone_number'] = $matches[0];
    }
    // Extract Problem Description (more complex, consider using AI for better parsing)
    // For now, a very basic example: if "problem" or "issue" is mentioned, capture the rest of the sentence.
    if (preg_match('/(?:my problem is|i have an issue with|i\'m having trouble with)\s+(.+)/i', $message, $matches)) {
        if (empty($_SESSION['extracted_data']['problem_description'])) { // Only store if not already set
            $_SESSION['extracted_data']['problem_description'] = trim($matches[1]);
        }
    }
    // You can add more specific regexes and logic here for other data points


    // --- Logic to create limited history for API call ---
    $messages_for_api = [];
    
    // Always include the system message at the beginning
    if (!empty($_SESSION['chat_history']) && $_SESSION['chat_history'][0]['role'] === 'system') {
        $messages_for_api[] = $_SESSION['chat_history'][0];
    }

    // Optionally, add extracted data to the API messages to inform the AI
    // This is useful if you want the AI to remember the name, problem, etc., even if it's not in the last two messages.
    $extracted_info_for_ai = '';
    if (!empty($_SESSION['extracted_data']['name'])) {
        $extracted_info_for_ai .= "The user's name is " . $_SESSION['extracted_data']['name'] . ". ";
    }
    if (!empty($_SESSION['extracted_data']['problem_description'])) {
        $extracted_info_for_ai .= "The user previously described a problem: " . $_SESSION['extracted_data']['problem_description'] . ". ";
    }
    if (!empty($_SESSION['extracted_data']['email'])) {
        $extracted_info_for_ai .= "Their email is " . $_SESSION['extracted_data']['email'] . ". ";
    }
    if (!empty($_SESSION['extracted_data']['phone_number'])) {
        $extracted_info_for_ai .= "Their phone is " . $_SESSION['extracted_data']['phone_number'] . ". ";
    }

    if (!empty($extracted_info_for_ai)) {
        // Prepend this extracted info to the user's current message, or add as a separate "user" role if the AI understands it.
        // For simplicity, we can add it as an additional "user" message or modify the last user message.
        // A better approach might be to add it to the *system* prompt if it's persistent context for the AI.
        // For now, let's add it as a new "user" message right before the actual last user message.
        // This makes it clear to the AI that this is relevant user-provided info.
        // You might need to adjust the AI's system prompt to handle this kind of context if it's not already aware.
        $messages_for_api[] = ["role" => "user", "content" => "Previously provided context: " . trim($extracted_info_for_ai)];
    }


    // Get only user and assistant messages from the full history
    $user_assistant_messages = array_filter($_SESSION['chat_history'], function($entry) {
        return $entry['role'] !== 'system';
    });
    
    // Take only the last two user/assistant messages (from the actual conversation)
    $last_two_messages = array_slice($user_assistant_messages, -2);
    
    // Combine the system message, any extracted data context, and the last two user/assistant messages
    $messages_for_api = array_merge($messages_for_api, $last_two_messages);

    // Send message to the AI API with the limited history and extracted data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.chatanywhere.tech/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => $messages_for_api // Use the filtered and limited history here
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer sk-rwiv9ScxjbbgKWzxe07mcKMGqBOYYerGnXhXdrzgrA1NWsak",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $assistantMessage = $data['choices'][0]['message']['content'];

    // Add assistant's response to the full session history (for display purposes)
    $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $assistantMessage];

    echo $assistantMessage;
    exit;
}

// Fetch previous chat history for display (still uses full history for display)
$history = [];
foreach ($_SESSION['chat_history'] as $entry) {
    if (isset($entry['role']) && isset($entry['content'])) {
        if ($entry['role'] !== 'system') {
            $history[] = $entry;
        }
    }
}
?>

<?php include 'sidebar.html'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/avif" href="icon.avif">
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
            foreach ($history as $entry) {
                $role = $entry['role'];
                $avatar = $role === 'user'
                    ? '<span class="avatar" title="You"><i class="fas fa-user"></i></span>'
                    : '<span class="avatar" title="PicShot"><i class="fas fa-robot"></i></span>';
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
function renderMarkdownInBubbles() {
    document.querySelectorAll('.bubble.assistant-md').forEach(function(el) {
        if (!el.classList.contains('rendered')) {
            el.innerHTML = marked.parse(el.textContent);
            el.classList.add('rendered');
        }
    });
}
renderMarkdownInBubbles();

document.getElementById('chat-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    var input = document.getElementById('message-input');
    var message = input.value.trim();
    if (!message) return;

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
        typingDiv.remove();

        // Show bot reply with letter-by-letter animation and Markdown
        var assistantMsg = document.createElement('div');
        assistantMsg.className = 'message assistant';
        assistantMsg.innerHTML = '<span class="avatar" title="PicShot"><i class="fas fa-robot"></i></span>' +
            '<div class="bubble assistant-md" data-role="assistant"></div>';
        chatContainer.appendChild(assistantMsg);
        chatContainer.scrollTop = chatContainer.scrollHeight;

        typeWriterMarkdown(assistantMsg.querySelector('.bubble'), reply, 0, 16, function() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        });
    })
    .finally(() => {
        input.disabled = false;
        input.focus();
    });
});

function typeWriterMarkdown(el, text, i, speed, cb) {
    let current = text.slice(0, i);
    el.innerHTML = marked.parse(current);
    if (i < text.length) {
        setTimeout(function() {
            typeWriterMarkdown(el, text, i + 1, speed, cb);
        }, text[i] === '\n' ? speed * 2 : speed);
    } else if (cb) {
        cb();
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