<?php
session_start();
ob_start();

$username = $_GET['username'] ?? '';
$sessionUsername = $_SESSION['username'] ?? '';

if (strtolower($sessionUsername) === strtolower($username)) {
    header("Location: profile.php");
    exit();
}

ob_end_flush();
?>

<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);

$conn = new mysqli(
    "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com",
    "admin", "DBpicshot", "Photostore"
);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);
    if ($comment !== '') {
        $ins = $conn->prepare(
            "INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)"
        );
        $ins->bind_param("iis", $post_id, $user_id, $comment);
        if (!$ins->execute()) {
            die("Insert error: " . $ins->error);
        }
        $ins->close();
    }

    $username = $_GET['username'] ?? '';
var_dump($_SESSION['username'] ?? null, $username); // Avoid warning using null coalescing operator

if (isset($_SESSION['username']) && $_SESSION['username'] === $username) {
    header("Location: profile.php");
    exit();
}
}?>























<?php
// --- Configuration ---
$dbConfig = [
    'servername' => "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com",
    'username' => "admin",
    'password' => "DBpicshot",
    'dbname' => "Photostore",
];
$imgbbApiKey = "8f23d9f5d1b5960647ba5942af8a1523";

// --- Database Connection ---
$conn = new mysqli(...array_values($dbConfig));
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Helper Functions ---

/**
 * Uploads an image to imgbb.com.
 * @param array $file $_FILES array for the uploaded image.
 * @return string|null The URL of the uploaded image, or null on failure.
 */
function uploadToImgBB(array $file): ?string
{
    global $imgbbApiKey;
    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.imgbb.com/1/upload?key=" . $imgbbApiKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $imageData]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['data']['url'] ?? null;
}

/**
 * Fetches a user ID by their username.
 * @param mysqli $conn Database connection.
 * @param string $username The username to search for.
 * @return int|null The user ID, or null if not found.
 */
function getUserIdByUsername(mysqli $conn, string $username): ?int
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['id'] ?? null;
}

/**
 * Fetches user data (including verification status) by user ID.
 * @param mysqli $conn Database connection.
 * @param int $userId The ID of the user to fetch.
 * @return array|null An associative array containing user data, or null if not found.
 */
function getUserDataByUserId(mysqli $conn, int $userId): ?array
{
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

/**
 * Fetches posts for a specific user ID.
 * @param mysqli $conn Database connection.
 * @param int $userId The ID of the user whose posts to fetch.
 * @return array An array of associative arrays, each representing a post.
 */
function getPostsByUserId(mysqli $conn, int $userId): array
{
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

// --- Handle Form Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        exit("User not logged in."); // Consider redirecting to login page
    }

    // Handle Profile Photo Upload
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === 0) {
        $profilePhotoUrl = uploadToImgBB($_FILES['profilePhoto']);
        if ($profilePhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $profilePhotoUrl, $userId);
            $stmt->execute();
            // Consider adding error handling for database update
        }
    }

    // Handle Cover Photo Upload
    if (isset($_FILES['coverInput']) && $_FILES['coverInput']['error'] === 0) {
        $coverPhotoUrl = uploadToImgBB($_FILES['coverInput']);
        if ($coverPhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $coverPhotoUrl, $userId);
            $stmt->execute();
            // Consider adding error handling for database update
        }
    }

    // Handle Post Image Upload
    if (isset($_FILES['imageInput']) && $_FILES['imageInput']['error'] === 0) {
        $postPhotoUrl = uploadToImgBB($_FILES['imageInput']);
        $postDescription = $_POST['descInput'] ?? '';
        if ($postPhotoUrl) {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $postPhotoUrl, $postDescription);
            $stmt->execute();
            // Consider adding error handling for database insert
        }
    }

    // Handle Delete Post
    if (isset($_POST['delete_post_id'])) {
        $deletePostId = $_POST['delete_post_id'];
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deletePostId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>window.onload = function() { alert('Post deleted successfully!'); window.location.reload(); };</script>";
        } else {
            echo "<script>window.onload = function() { alert('Failed to delete post. Please try again.'); };</script>";
        }
        $stmt->close();
    }
}

// --- Determine User Profile to Display ---
$requestedUsername = $_GET['username'] ?? null;
$viewingUserId = null;
$userData = [];
$posts = [];
$isOwnProfile = false;

if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    if ($requestedUsername) {
        $viewingUserId = getUserIdByUsername($conn, $requestedUsername);
        if ($viewingUserId === $loggedInUserId) {
            $isOwnProfile = true;
        }
    } else {
        $viewingUserId = $loggedInUserId;
        $isOwnProfile = true;
    }
} else {
    if (!$requestedUsername) {
        exit("Please log in to view your profile."); // Consider redirecting to login page
    } else {
        $viewingUserId = getUserIdByUsername($conn, $requestedUsername);
    }
}

if ($viewingUserId) {
    $userData = getUserDataByUserId($conn, $viewingUserId);
    $posts = getPostsByUserId($conn, $viewingUserId);
} else {
    echo "<center><h1>User not found</h1></center>";
    exit;
}
?>

<?php include "sidebar.html" ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?=htmlspecialchars($userData['username'] ?? 'User Profile')?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="postview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .maingrap{
 width:95%;
 float:right;
}
        #postModal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      overflow: auto;
      padding: 40px 10px;
    }

    #postModalContent {
     
      margin: auto;
      padding:  30px;
      width: 80%;
      max-width: 1000px;
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    #postModalContent button.close-btn {
      position: absolute;
      top: 0px;
      right:0px;
      background: red;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
    }

 .outer-card{position:relative;max-width:1100px;margin:auto;background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:30px;}
   
    .comment-box
{
    position: absolute;
    bottom:0;
    padding-bottom:20px;
    margin-top: 900px;
}
.comment-box input {
  flex: 1;
  padding: 14px 20px;
  border: 1px solid #ccc;
  border-radius: 30px;
  font-size: 15px;
  width: 100%;
  max-width: 450px;
  transition: border-color 0.3s ease;
}

.comment-box input:focus {
  border-color: #465A31;
  outline: none;
  box-shadow: 0 0 5px rgba(70, 90, 49, 0.5);
}

.comment-box button {
  padding: 14px 24px;
  margin-top:-4px;
  border: none;
  border-radius: 30px;
  background: linear-gradient(to right, #465A31, #FE9042);
  color: white;
  font-weight: 600;
  height: 50px;
  margin-top:10px;
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease;
}

.comment-box button:hover {
  background: linear-gradient(to right, #3e4f28, #e2782e);
  
}

.comment-box button:active {
  transform: scale(0.95);
}




        .edit-cover {
            display: <?= $isOwnProfile ? 'block' : 'none' ?>;
        }
        .add-post-section {
            display: none; /* Initially hidden, toggled by button */
        }
        .plus-button {
            display: <?= $isOwnProfile ? 'block' : 'none' ?>;
        }
        .post-overlay .delete-button {
            display: <?= $isOwnProfile ? 'block' : 'none' ?>;
        }
    </style>
</head>
<body>

<div class="maingrap">
    <center>
        <div class="profile-wrapper">
            <div class="uppertop">
                <img src="<?= htmlspecialchars($userData['cover_photo'] ?? 'topimage.jpg') ?>" alt="demo image">
            </div>

            <div class="profilepic">
                <img src="<?= htmlspecialchars($userData['profile_photo'] ?? 'profile.jpg') ?>" alt="Profile" id="profilePhoto" style="cursor: <?= $isOwnProfile ? 'pointer' : 'default' ?>;">
                <?php if ($isOwnProfile): ?>
                    <input type="file" id="profilePicInput" name="profilePhoto" accept="image/*" style="display: none;">
                <?php endif; ?>
            </div>

            <div class="profile-name">
                <div class="username-line">
                    <span class="username"><?= htmlspecialchars($userData['username'] ?? 'Username') ?></span>
                    <?php if (!empty($userData['is_verified'])): ?>
                        <img src="vf.png" alt="verified" class="verified-icon" />
                    <?php endif; ?>
                </div>
                <span class="desc"><?= htmlspecialchars($userData['description'] ?? 'Description') ?></span>
                <?php if (!$isOwnProfile): ?>
                    <button onclick="follow()" class="follow-button">Follow</button>
                    <button class="contact-button" value="<?= htmlspecialchars($userData['username'] ?? '') ?>">Contact</button>
                <?php endif; ?>
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
        <li><button onclick="">Posts</button></li>
        
    </ul>


    <div class="post-grid-box" id="postGrid">
    <?php foreach ($posts as $post): ?>
        <div class="post-card" onclick="openPostModal(<?= $post['id'] ?>)">
            <img src="<?= htmlspecialchars($post['photo_url']) ?>" alt="User Post">
            <div class="post-overlay">
                <div class="post-desc"><?= htmlspecialchars($post['caption'] ?? 'No Caption') ?></div>
                <div class="post-username">@<?= htmlspecialchars($post['username']) ?></div>

                <form method="post" onsubmit="return confirm('Are you sure you want to delete this post?');">
                    <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
                    <button type="submit" class="delete-button">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Hidden form to redirect -->
        <form id="postForm-<?= $post['id'] ?>" action="photovs.php" method="POST" style="display: none;">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        </form>
    <?php endforeach; ?>
</div>
<!-- Loading Screen -->

<style>
#loadingOverlay {
  display: none; /* Hidden by default */
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.6);
  z-index: 10000;
  justify-content: center;
  align-items: center;
}

#loadingOverlay.active {
  display: flex;
}

.spinner {
  border: 8px solid #f3f3f3;
  border-top: 8px solid #3498db;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}


</style>
<div id="loadingOverlay">
  <div class="spinner"></div>
</div>



<script>
function openPostModal(postId) {
  const modal = document.getElementById('postModal');
  const content = document.getElementById('postModalContent');
  const loader = document.getElementById('loadingOverlay');

  // Add close on outside click here (better than inline HTML)
  modal.onclick = function(event) {
    if (event.target === modal) {
      closePostModal();
    }
  };

  loader.classList.add('active'); // Show loading

  fetch('photovs.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'post_id=' + postId
  })
  .then(response => response.text())
  .then(data => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(data, 'text/html');
    const innerContent = doc.querySelector('.outer-card');

    if (innerContent) {
      content.innerHTML = `
        <button class="close-btn" onclick="closePostModal()">X</button>
        ${innerContent.outerHTML}
      `;
      modal.style.display = 'block';
    } else {
      alert('Post content not found.');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to load post.');
  })
  .finally(() => {
    loader.classList.remove('active'); // Hide loading
  });
}


  function closePostModal()
  {
    const modal = document.getElementById('postModal');
    const content = document.getElementById('postModalContent');
    modal.style.display = 'none';
    content.innerHTML = `<button class="close-btn" onclick="closePostModal()">X</button>`;
  }
</script>

    

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
        if (profileImage && profileInput) {
            profileImage.addEventListener('click', () => {
                if ('<?= $isOwnProfile ? '1' : '0' ?>' === '1') {
                    profileInput.click();
                }
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
                        // Optionally update the image source again after server response
                    })
                    .catch(error => {
                        console.error('Error uploading profile picture:', error);
                        alert('Failed to upload profile picture. Please try again.');
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        // Cover image upload logic
        const coverInput = document.getElementById('coverInput');
        const coverImage = document.querySelector('.uppertop img');
        if (coverInput && coverImage) {
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
                        console.log('Upload successful:', data);
                        // Optionally update the image source again after server response
                    })
                    .catch(error => {
                        console.error('Error uploading cover picture:', error);
                        alert('Failed to upload cover picture. Please try again.');
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        // Contact button functionality
        const contactButton = document.querySelector('.contact-button');
        if (contactButton) {
            contactButton.addEventListener('click', function() {
                const usernameToContact = this.value;
                window.location.href = `userchat.php?username=${usernameToContact}`;
            });
        }
    </script></div>
</body>
</html>