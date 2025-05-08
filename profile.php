<?php
session_start();

// ✅ Database config
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";

// ✅ Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ imgbb Upload Function
function uploadToImgBB($file) {
    $apiKey = "8f23d9f5d1b5960647ba5942af8a1523";
    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.imgbb.com/1/upload?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $imageData]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['data']['url'] ?? null;
}

// ✅ Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ⚠️ Make sure user_id is stored in session (e.g. after login)
    $userId = $_SESSION['user_id'] ?? 1; // For testing, you can set this manually

    $profilePhotoUrl = null;
    $coverPhotoUrl = null;
    $postPhotoUrl = null;

    // Process Profile Photo Upload
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === 0) {
        $profilePhotoUrl = uploadToImgBB($_FILES['profilePhoto']);
        if ($profilePhotoUrl) {
            // Store URL in database
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $profilePhotoUrl, $userId);
            $stmt->execute();
        }
    }

    // Process Cover Photo Upload
    if (isset($_FILES['coverInput']) && $_FILES['coverInput']['error'] === 0) {
        $coverPhotoUrl = uploadToImgBB($_FILES['coverInput']);
        if ($coverPhotoUrl) {
            // Store URL in database
            $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $coverPhotoUrl, $userId);
            $stmt->execute();
        }
    }

    // Process Post Image Upload
    if (isset($_FILES['imageInput']) && $_FILES['imageInput']['error'] === 0) {
        $postPhotoUrl = uploadToImgBB($_FILES['imageInput']);
        $postDescription = $_POST['descInput'] ?? ''; // Get post description

        if ($postPhotoUrl) {
            // Store post details in the posts table
            $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $postPhotoUrl, $postDescription);
            $stmt->execute();
        }
    }

    // Handle editing username and description
    if (isset($_POST['editUsernameSubmit']) && isset($_POST['newUsername']) && isset($_POST['newDescription'])) {
        $newUsername = $_POST['newUsername'];
        $newDescription = $_POST['newDescription'];

        // Update the username and description in the database
        $stmt = $conn->prepare("UPDATE users SET username = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newUsername, $newDescription, $userId);
        $stmt->execute();
        echo "<script>
            window.onload = function() {
                alert('Profile updated successfully!');
                window.location.reload();
            };
        </script>";
    }

    // Delete Post
    if (isset($_POST['delete_post_id'])) {
        $deletePostId = $_POST['delete_post_id'];
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deletePostId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>
                window.onload = function() {
                    alert('Post deleted successfully!');
                    window.location.reload();
                };
            </script>";
        } else {
            echo "<script>
                window.onload = function() {
                    alert('Failed to delete post. Please try again.');
                };
            </script>";
        }
        $stmt->close();
    }
}

// Function to fetch posts from the database
function getPosts($conn, $userId) {
    $posts = [];
    $stmt = $conn->prepare("SELECT p.id, p.photo_url, p.caption, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();
    return $posts;
}

//Get user data including verification status
function getUserData($conn, $userId){
    $stmt = $conn->prepare("
        SELECT u.username, u.description, u.profile_photo, u.cover_photo, g.is_verified
        FROM users u
        LEFT JOIN goldentik g ON u.id = g.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    return $userData;
}

$userData = getUserData($conn, 1); //hardcoded user.
$posts = getPosts($conn, 1); //For now its hardcoded.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Full screen modal styles */
        .fullscreen-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9); /* Dark background */
            overflow: auto;
            justify-content: center;
            align-items: center;
        }

        .fullscreen-modal-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 95%;
            max-height: 95%;
        }

        .fullscreen-image {
            max-width: 100%;
            max-height: 90%; /* Leave some space for caption/close button */
            object-fit: contain;
        }

        .fullscreen-caption {
            color: white;
            margin-top: 10px;
            text-align: center;
        }

        .close-fullscreen {
            color: white;
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .close-fullscreen:hover {
            opacity: 1;
        }
    </style>
</head>
<body>

    <center>
        <div class="profile-wrapper">
            <div class="uppertop">
                <img src="<?=$userData['cover_photo'] ?? 'topimage.jpg'?>" alt="demo image">
            </div>

            <div class="profilepic">
                <img src="<?=$userData['profile_photo'] ?? 'profile.jpg'?>" alt="Profile" id="profilePhoto" style="cursor: pointer;">
                <input type="file" id="profilePicInput" name="profilePhoto" accept="image/*" style="display: none;">
            </div>

            <div class="profile-name">
                <div class="username-line">
                    <span class="username"><?=$userData['username'] ?? 'Username'?></span>
                    <?php if (!empty($userData['is_verified'])): ?>
                        <img src="vf.png" alt="verified" class="verified-icon" />
                    <?php endif; ?>
                </div>
                <span class="desc"><?=$userData['description'] ?? 'Description'?></span>
                <button onclick="openEditModal()" class="editp">Edit Profile</button>

            </div>
        </div>
    </center>
    <div class="edit-cover" id="editCover">
        <label for="coverInput" style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
            <img src="image.png" alt="" style="width: 24px; height: 24px;"> Edit Cover
        </label>
        <input type="file" id="coverInput" name="coverInput" accept="image/*" style="display: none;">
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span onclick="closeEditModal()">&times;</span>
            <h2>Edit Profile</h2>
            <form method="POST">
                <label for="newUsername">New Username:</label>
                <input type="text" name="newUsername" id="newUsername" value="<?=$userData['username']?>" required><br><br>
                <label for="newDescription">New Description:</label>
                <textarea name="newDescription" id="newDescription" required><?=$userData['description']?></textarea><br><br>
                <button type="submit" name="editUsernameSubmit" class="editUsernameSubmit">Save Changes</button>
            </form>
        </div>
    </div>

    <ul class="nav-post">
        <li><button onclick="toggleUpload()">Post</button></li>
        <li><button onclick="">Drafts</button></li>
    </ul>

    <div class="add-post-section" id="uploadBox" style="display: none;">
        <form method="POST" enctype="multipart/form-data">
            <label for="imageInput">Upload an image:</label>
            <input type="file" name="imageInput" id="imageInput" accept="image/*" required><br>
            <input type="text" name="descInput" id="descInput" placeholder="Enter description" required><br>
            <button type="submit" name="upload">Add Post</button>
        </form>
    </div>

    <div class="post-grid-box" id="postGrid">
        <?php foreach ($posts as $post): ?>
            <div class="post-card" onclick="openFullscreen('<?=$post['photo_url']?>', '<?=$post['caption']?>')">
                <img src="<?=$post['photo_url']?>" alt="User Post">
                <div class="post-overlay">
                    <div class="post-desc"><?=$post['caption'] ?? 'No Caption'?></div>
                    <div class="post-username">@<?=$post['username']?></div>
                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this post?');">
                        <input type="hidden" name="delete_post_id" value="<?=$post['id']?>">
                        <button type="submit" class="delete-button">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
        <span class="close-fullscreen">&times;</span>
        <div class="fullscreen-modal-content">
            <img id="fullscreenImage" class="fullscreen-image">
            <div id="fullscreenCaption" class="fullscreen-caption"></div>
        </div>
    </div>

    <button class="plus-button" onclick="toggleUpload()">+</button>

    <script>
        function toggleUpload() {
            const uploadBox = document.getElementById("uploadBox");
            uploadBox.style.display = uploadBox.style.display === "none" ? "block" : "none";
        }

        // Profile picture upload logic
        const profileImage = document.getElementById('profilePhoto');
        const profileInput = document.getElementById('profilePicInput');

        profileImage.addEventListener('click', () => {
            profileInput.click();
        });

        profileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                profileImage.src = e.target.result;
                const formData = new FormData();
                formData.append('profilePhoto', file);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Upload successful:', data);
                    profileImage.src = e.target.result;
                })
                .catch(error => {
                    console.error('Error uploading profile picture:', error);
                    alert('Failed to upload profile picture. Please try again.');
                });
            };
            reader.readAsDataURL(file);
        });

        // Cover image upload logic
        const coverInput = document.getElementById('coverInput');
        const coverImage = document.querySelector('.uppertop img');

        coverInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                coverImage.src = e.target.result;
                const formData = new FormData();
                formData.append('coverInput', file);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Cover upload successful:', data);
                })
                .catch(error => {
                    console.error('Error uploading cover image:', error);
                    alert('Failed to upload cover image. Please try again.');
                });
            };
            reader.readAsDataURL(file);
        });

        // Function to open the edit profile modal
        function openEditModal() {
            document.getElementById("editModal").style.display = "block";
        }

        // Function to close the edit profile modal
        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        // Close the edit modal if the user clicks outside of it
        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Function to open the full screen modal
        function openFullscreen(imageUrl, caption) {
            const fullscreenModal = document.getElementById('fullscreenModal');
            const fullscreenImage = document.getElementById('fullscreenImage');
            const fullscreenCaption = document.getElementById('fullscreenCaption');

            fullscreenImage.src = imageUrl;
            fullscreenCaption.textContent = caption;
            fullscreenModal.style.display = 'flex';
        }

        // Function to close the full screen modal
        function closeFullscreen() {
            const fullscreenModal = document.getElementById('fullscreenModal');
            fullscreenModal.style.display = 'none';
        }
    </script>
</body>
</html>