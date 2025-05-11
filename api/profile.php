<?php
session_start();
// ini_set('display_errors', 1); // Enable error display
// error_reporting(E_ALL); // Report all types of errors

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
    $tempFilePath = $file['tmp_name'];
    error_log("Temporary file path: " . $tempFilePath); // Log the temp path

    $apiKey = "8f23d9f5d1b5960647ba5942af8a1523";
    $imageData = base64_encode(file_get_contents($tempFilePath));

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
    $userId = $_SESSION['user_id'];

    // Process Profile Photo Upload
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === 0) {
        echo '<script>document.getElementById("upload-loader").style.display = "flex";</script>';
        $profilePhotoUrl = uploadToImgBB($_FILES['profilePhoto']);
        if ($profilePhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $profilePhotoUrl, $userId);
            $stmt->execute();
            echo '<script>document.getElementById("upload-loader").style.display = "none";</script>';
        } else {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Failed to upload profile picture.");</script>';
        }
    }

    // Process Cover Photo Upload
    if (isset($_FILES['coverInput']) && $_FILES['coverInput']['error'] === 0) {
        echo '<script>document.getElementById("upload-loader").style.display = "flex";</script>';
        $coverPhotoUrl = uploadToImgBB($_FILES['coverInput']);
        if ($coverPhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $coverPhotoUrl, $userId);
            $stmt->execute();
            echo '<script>document.getElementById("upload-loader").style.display = "none";</script>';
        } else {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Failed to upload cover photo.");</script>';
        }
    }

    // Process Post Image Upload
    if (isset($_FILES['imageInput']) && $_FILES['imageInput']['error'] === 0) {
        echo '<script>document.getElementById("upload-loader").style.display = "flex";</script>';
        $postPhotoUrl = uploadToImgBB($_FILES['imageInput']);
        $postDescription = $_POST['descInput'] ?? '';

        if ($postPhotoUrl) {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $postPhotoUrl, $postDescription);
            $stmt->execute();
            echo '<script>document.getElementById("upload-loader").style.display = "none"; window.location.reload();</script>';
        } else {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Failed to upload post.");</script>';
        }
    }

    // Edit username and description
    if (isset($_POST['editUsernameSubmit']) && isset($_POST['newUsername']) && isset($_POST['newDescription'])) {
        echo '<script>document.getElementById("upload-loader").style.display = "flex";</script>';
        $newUsername = $_POST['newUsername'];
        $newDescription = substr($_POST['newDescription'], 0, 150); // ⛔ 150 characters max


        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkStmt->bind_param("si", $newUsername, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();


        if (strlen($_POST['newDescription']) > 150) {
    echo '<script>alert("Description must be 150 characters or less."); window.history.back();</script>';
    exit();
}
        if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
            echo '<script>alert("Username must be between 3 and 20 characters."); window.history.back();</script>';
            exit();
        }

        if ($checkResult->num_rows > 0) {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Username already exists. Please choose a different one.");</script>';
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newUsername, $newDescription, $userId);
            $stmt->execute();
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Profile updated successfully!"); window.location.reload();</script>';
        }
        $checkStmt->close();
    }

    // Delete Post
    if (isset($_POST['delete_post_id'])) {
        echo '<script>document.getElementById("upload-loader").style.display = "flex";</script>';
        $deletePostId = $_POST['delete_post_id'];
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deletePostId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Post deleted successfully!"); window.location.href = window.location.href;</script>';
        } else {
            echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Failed to delete post. Please try again.");</script>';
        }
        $stmt->close();
    }
}

// Fetch posts
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

// Fetch user data
function getUserData($conn, $userId) {
    $stmt = $conn->prepare("SELECT u.username, u.description, u.profile_photo, u.cover_photo, g.is_verified FROM users u LEFT JOIN goldentik g ON u.id = g.user_id WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    return $userData;
}

// Load user data and posts
if (isset($_SESSION['user_id'])) {
    $userData = getUserData($conn, $_SESSION['user_id']);
    $posts = getPosts($conn, $_SESSION['user_id']);
} else {
    echo "Please log in to view your profile.";
    exit();
}
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
        /* Loading Overlay Styles */
        #upload-loader {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            z-index: 1000; /* Ensure it's on top */
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            font-size: 1.2em;
        }

        .spinner {
            border: 8px solid #f3f3f3; /* Light grey border */
            border-top: 8px solid #3498db; /* Blue top border */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin-bottom: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

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
           <div class="uppertop" id="coverContainer">
    <img src="<?=$userData['cover_photo'] ?? 'topimage.jpg'?>" alt="Cover Image" id="coverPhoto" style="cursor: pointer;">
</div>

<div class="edit-cover" id="editCover">
    <input type="file" id="coverInput" name="coverInput" accept="image/*" style="display: none;">
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
       
    </ul>

    <div class="add-post-section" id="uploadBox" style="display: none;">
        <form method="POST" enctype="multipart/form-data" onsubmit="document.getElementById('upload-loader').style.display = 'flex';">
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
    <div id="upload-loader" style="display: none;">
        <div class="spinner"></div>
    </div>

    <script>

            // Make the cover photo clickable
document.getElementById('coverContainer').addEventListener('click', function() {
    document.getElementById('coverInput').click();  // Trigger the file input
});

// Handle the file input change (i.e., when a new image is selected)
document.getElementById('coverInput').addEventListener('change', function(event) {
    const file = event.target.files[0];  // Get the selected file
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Update the cover photo with the selected image
            document.getElementById('coverPhoto').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Optionally, send the file to the server (via AJAX or a form submission)
        uploadCoverPhoto(file);
    }
});

// Function to upload the cover photo (to be implemented in PHP)
function uploadCoverPhoto(file) {
    const formData = new FormData();
    formData.append('cover_photo', file);

    fetch('upload_cover_photo.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
        // Handle the response from the server (e.g., show success message)
        console.log(data);
      })
      .catch(error => console.error('Error:', error));
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

                document.getElementById('upload-loader').style.display = 'flex'; // Show loader
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Profile picture upload successful:', data);
                    profileImage.src = e.target.result;
                    document.getElementById('upload-loader').style.display = 'none'; // Hide loader
                })
                .catch(error => {
                    console.error('Error uploading profile picture:', error);
                    alert('Failed to upload profile picture. Please try again.');
                    document.getElementById('upload-loader').style.display = 'none'; // Hide loader on error
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

                document.getElementById('upload-loader').style.display = 'flex'; // Show loader
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Cover upload successful:', data);
                    document.getElementById('upload-loader').style.display = 'none'; // Hide loader
                })
                .catch(error => {
                    console.error('Error uploading cover image:', error);
                    alert('Failed to upload cover image. Please try again.');
                    document.getElementById('upload-loader').style.display = 'none'; // Hide loader on error
                });
            };
            reader.readAsDataURL(file);
        });

        // Edit profile modal open/close
        function openEditModal() {
            document.getElementById("editModal").style.display = "block";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function openFullscreen(imageUrl, caption) {
            const fullscreenModal = document.getElementById('fullscreenModal');
            const fullscreenImage = document.getElementById('fullscreenImage');
            const fullscreenCaption = document.getElementById('fullscreenCaption');

            fullscreenImage.src = imageUrl;
            fullscreenCaption.textContent = caption;
            fullscreenModal.style.display = 'flex';
        }

        function closeFullscreen() {
            const fullscreenModal = document.getElementById('fullscreenModal');
            fullscreenModal.style.display = 'none';
        }
    </script>
    <div id="upload-loader" style="display: none;">
        <div class="spinner"></div>
    </div>
</body>
</html>