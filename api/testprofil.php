<?php
session_start();

// ✅ Database config
$servername = "sql12.freesqldatabase.com";
$username = "sql12777439";
$password = "nmMjJrQPE9";
$dbname = "sql12777439";

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
                <button  onclick="follow()" class="follow-button">Follow</button>
                <button  class="contact-button">Contact</button>
            </div>
        </div>
    </center>
    <div class="edit-cover" id="editCover">
        <label for="coverInput" style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
            <img src="image.png" alt="" style="width: 24px; height: 24px;"> Edit Cover
        </label>
        <input type="file" id="coverInput" name="coverInput" accept="image/*" style="display: none;">
    </div>


    <ul class="nav-post">
        <li><button onclick="toggleUpload()">Post</button></li>
        <li><button onclick="">Drafts</button></li>
    </ul>

    <div class="add-post-section" id="uploadBox">
        <form method="POST" enctype="multipart/form-data">
            <label for="imageInput">Upload an image:</label>
            <input type="file" name="imageInput" id="imageInput" accept="image/*" required><br>
            <input type="text" name="descInput" id="descInput" placeholder="Enter description" required><br>
            <button type="submit" name="upload">Add Post</button>
        </form>
    </div>

    <div class="post-grid-box" id="postGrid">
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <img src="<?=$post['photo_url']?>" alt="User Post">
                <div class="post-overlay">
                    <div class="post-desc"><?=$post['caption'] ?? 'No Caption'?></div>
                    <div class="post-username">@<?=$post['username']?></div>
                    <form method="post">
                        <input type="hidden" name="delete_post_id" value="<?=$post['id']?>">
                        <button type="submit" class="delete-button">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="plus-button" onclick="toggleUpload()">+</button>

    <script>
        function follow() {
            const followButton = document.querySelector('.follow-button');
            if (followButton.innerText === 'Follow') {
                followButton.innerText = 'Following';
                followButton.style.background = 'linear-gradient(90deg, #A9A9A9 30%, #D3D3D3)';
            } else {
                followButton.innerText = 'Follow';
            }
        }

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
            };
            reader.readAsDataURL(file);
        });
    </script>
</body>
</html>
