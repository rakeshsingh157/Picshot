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

PicShot is a simple, modern photo-sharing platform. It helps photographers and creators share their visual stories in a clean, easy-to-use space. You can upload photos, explore new content, and connect with other users. Whether you're a professional or just love taking pictures, PicShot helps you turn moments into memories and build a creative community.

if user ask who created picshot then reply with this or who created you

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

--- HOW TO USE PICSHOT ---

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

--- COMMON ISSUES & SOLUTIONS ---

* **'Why can't I write a title?'**
    You can't write titles on PicShot. They are **automatically generated** for your posts.

* **'Title not fetching.'**
    Sometimes, titles might not appear if the Imagga API can't scan the photo correctly.

* **'When I scroll up chats, it automatically goes down.'**
    This happens because the website uses JavaScript to **refresh chats every 3 seconds** to load new messages.

* **'When I upload a profile or cover photo, it's not uploading.'**
    Make sure your photo size is **more than 50KB**.)
    
    
    
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
        "Authorization: Bearer sk-rwiv9ScxjbbgKWzxe07mcKMGqBOYYerGnXhXdrzgrA1NWsak",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $assistantMessage = $data['choices'][0]['message']['content'];

    // Add assistant's response to session history
    $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $assistantMessage];

    echo $assistantMessage;
    exit;
}

// Fetch previous chat history for display
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
