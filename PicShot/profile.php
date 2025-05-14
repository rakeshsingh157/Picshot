<?php
session_start();
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
}

// Fetch post
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 19;
$stmt = $conn->prepare(
    "SELECT p.*, u.username
     FROM posts p JOIN users u ON p.user_id = u.id
     WHERE p.id = ?"
);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    die("Post not found!");
}

// Fetch comments
$stmt_c = $conn->prepare(
    "SELECT c.comment, c.created_at, u.username
     FROM comments c
     JOIN users u ON c.user_id = u.id
     WHERE c.post_id = ?
     ORDER BY c.created_at DESC"
);
$stmt_c->bind_param("i", $post_id);
$stmt_c->execute();
$comments = $stmt_c->get_result();
?>

<?php

// ini_set('display_errors', 1); // Enable error display
// error_reporting(E_ALL); // Report all types of errors



$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



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
        .add-post-section {
  position: fixed;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  padding: 15px;
  border-radius: 12px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  z-index: 998;
  display: none;

  /* Responsive width */
  width: 90%;         /* nearly full on small screens */
  max-width: 1000px;   /* but no more than 500px on large */
  box-sizing: border-box;
}
@media (min-width: 600px) {
  .add-post-section {
    max-width: 600px;
  }
}
@media (min-width: 900px) {
  .add-post-section {
    max-width: 800px;
  }
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

    .container {
      display: flex;
      flex-wrap: wrap;
      
      gap: 40px;
      align-items: flex-start;
    }

    .image-section {
      flex: 1 1 400px;
    }

    .post-image {
      width: 100%;
      max-width: 500px;
      height: auto;
      float: left;
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
      margin-bottom: 10px;
    }

    .details-section {
      flex: 1 1 350px;
      float: right;
    }

    .details-section h2 {
      margin-top: 0;
      font-size: 24px;
    }

    .username {
      color: #666;
      font-size: 14px;
      margin: 8px 0;
    }

    .description {
      font-size: 16px;
      margin-top: 12px;
      color: #444;
    }

.comments-container {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 12px;
  margin-bottom: 24px;
  
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

/* Webkit scrollbar styling */
.comments-container::-webkit-scrollbar {
  width: 8px;
}

.comments-container::-webkit-scrollbar-track {
 
  border-radius: 8px;
}

.comments-container::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.25);
  border-radius: 8px;
  border: 2px solid transparent;
  background-clip: content-box;
  transition: background-color 0.3s ease;
}

.comments-container::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.4);
}

.comment {
  margin-top: 15px;
  padding: 14px 16px;
  
  border-radius: 8px;
  border: 1px solid #ddd;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: background-color 0.3s ease;
}

.comment:hover {
  background-color: #f0f0f0;
}

.comment p {
  margin: 5px 0;
  color: #333;
  font-size: 15px;
  line-height: 1.4;
}

.comment small {
  font-size: 12px;
  color: #999;
}

.comment-box {
  display: flex;
  gap: 12px;
  margin-top: 28px;
  align-items: center;
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
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease;
}

.comment-box button:hover {
  background: linear-gradient(to right, #3e4f28, #e2782e);
  
}

.comment-box button:active {
  transform: scale(0.95);
}

/* Responsive adjustments */
@media (max-width: 600px) {
     #postModalContent button.close-btn {
      display: none;
    }
    .details-section {
        margin-top: -100px;
      flex: 1 1 350px;
      float: right;
    }
    
  .comment-box {
    flex-direction: column;
  }

  .comment-box input {
    width: 100%;
    border-radius: 10px;
  }

  .comment-box button {
    width: 100%;
    border-radius: 10px;
  }
}

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        align-items: center;
      }

      .comment-box {
        flex-direction: column;
      }

      .comment-box input {
        width: 100%;
      }

      .comment-box button {
        width: 100%;
      }
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

    <div class="add-post-section"  id="uploadBox" style="display: none;">
        <?php


// 1. DATABASE CONFIGURATION
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$dbUsername = "admin";
$dbPassword = "DBpicshot";
$dbname     = "Photostore";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}


if (!isset($_SESSION['user_id'])) {
   
    $_SESSION['user_id'] = 5;
}
$userId = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['imageUrl'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $photoUrl    = $_POST['imageUrl'];

    $stmt = $conn->prepare("
        INSERT INTO posts (user_id, title, caption, photo_url)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $userId, $title, $description, $photoUrl);

    if ($stmt->execute()) {
        echo 'SUCCESS';
    } else {
        echo 'ERROR: ' . $stmt->error;
    }
    $stmt->close();
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create New Post</title>
  <link rel="stylesheet" href="post.css" />
  <style>
    /* Loader Styles */
    #loader {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }
    #loader div {
      width: 80px; height: 80px;
      border: 8px solid #f3f3f3;
      border-top: 8px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    .form-container{
        width: 100%;
    }
    @keyframes spin { 0% {transform: rotate(0deg);} 100% {transform: rotate(360deg);} }


    #postModalContent img {
 width:100%;max-width:500px;height:auto;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,0.15);margin-bottom:10px;
}
  </style>
</head>
<body>
  <div class="container">
    <h2>Create New Post</h2>
    <div class="form-container">
      <div class="upload-box" onclick="fileInput.click()">
        <img id="previewImg" alt="" style="max-width: 100%; display: none;" />
        <p id="placeholder">Choose a file to upload</p>
        <input type="file" id="fileInput" style="display:none;" hidden onchange="handleImageUpload(event)">
      </div>

      <div class="form-fields">
        <label>Title</label>
        <input type="text" id="title" placeholder="Title will appear here" readonly />
        <label>Description</label>
        <textarea id="description" placeholder="Enter the description"></textarea>
        <button onclick="submitPost()">Post</button>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loader"><div></div></div>

  <script>
    const IMGBB_API_KEY = "8f23d9f5d1b5960647ba5942af8a1523";
    const IMAGGA_KEY    = "acc_7300facc9d3b521";
    const IMAGGA_SECRET = "f127d8a250041a77a10d8c1e2ad78ccc";

    const loader     = document.getElementById('loader');
    const fileInput  = document.getElementById('fileInput');
    const previewImg = document.getElementById('previewImg');
    const placeholder= document.getElementById('placeholder');
    let latestImageUrl = "";

    function handleImageUpload(event) {
      const file = event.target.files[0];
      if (!file || !file.type.startsWith('image/')) return;

      // Preview
      const reader = new FileReader();
      reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);

      uploadToImgbb(file);
    }

    async function uploadToImgbb(file) {
      loader.style.display = 'flex';
      const formData = new FormData();
      formData.append("image", file);
      try {
        const res  = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`, {
          method: "POST", body: formData
        });
        const data = await res.json();
        latestImageUrl = data.data.url;
        await getTagsFromImagga(latestImageUrl);
      } catch (err) {
        console.error("Upload to imgbb failed:", err);
      } finally {
        loader.style.display = 'none';
      }
    }

    async function getTagsFromImagga(imageUrl) {
      loader.style.display = 'flex';
      const auth = btoa(IMAGGA_KEY + ":" + IMAGGA_SECRET);
      try {
        const res    = await fetch(`https://api.imagga.com/v2/tags?image_url=${encodeURIComponent(imageUrl)}`, {
          headers: { "Authorization": "Basic " + auth }
        });
        const result = await res.json();
        if (result.result && result.result.tags) {
          const tags = result.result.tags
            .filter(t => t.confidence > 50)
            .map(t => t.tag.en);
          document.getElementById("title").value = tags.join(", ") || "No title found";
        }
      } catch (err) {
        console.error("Imagga error:", err);
      } finally {
        loader.style.display = 'none';
      }
    }

    function submitPost() {
      const title       = document.getElementById('title').value;
      const description = document.getElementById('description').value;
      const imageUrl    = latestImageUrl;

      if (!title || !description || !imageUrl) {
        alert("Please complete all fields and wait for upload/tagging.");
        return;
      }

      loader.style.display = 'flex';
      fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          title: title,
          description: description,
          imageUrl: imageUrl
        })
      })
      .then(res => res.text())
      .then(text => {
        loader.style.display = 'none';
        if (response === "SUCCESS") {
  document.getElementById("uploadBox").style.display = "none"; // Auto close
  alert("Post uploaded successfully!"); // Optional confirmation
}
         else {
            document.getElementById("uploadBox").style.display = "none"; 
        }
      })
      .catch(err => {
        loader.style.display = 'none';
        console.error("DB insert error:", err);
      });
    }
  </script>
</body>
</html>

    </div>

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


<div id="postModal" style="display: none;">
  <div id="postModalContent" >
    <button class="close-btn" onclick="closePostModal()">
      <i class="fa-solid fa-x"></i>
    </button>
    <!-- Post detail will be dynamically loaded here via JS -->
  </div>
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

        
    </script>
    <div id="upload-loader" style="display: none;">
        <div class="spinner"></div>
    </div>
</body>
</html>