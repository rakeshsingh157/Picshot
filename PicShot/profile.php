<?php
// Set default timezone to Indian/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);

// Database connection
$conn = new mysqli(
    "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com",
    "admin", 
    "DBpicshot", 
    "Photostore"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle comment submission with Indian timezone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);
    
    if ($comment !== '') {
        $ins = $conn->prepare(
            "INSERT INTO comments (post_id, user_id, comment, created_at) 
             VALUES (?, ?, ?, CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Kolkata'))"
        );
        $ins->bind_param("iis", $post_id, $user_id, $comment);
        
        if (!$ins->execute()) {
            die("Insert error: " . $ins->error);
        }
        $ins->close();
    }
}

// Fetch post with Indian timezone
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 26;
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

// Fetch comments with Indian timezone
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

// Image upload function
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

// Handle form submissions
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
            $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption, created_at) VALUES (?, ?, ?, CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Kolkata'))");
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
        $newDescription = substr($_POST['newDescription'], 0, 150);

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

// Fetch posts with default profile photo
function getPosts($conn, $userId) {
    $posts = [];
    $stmt = $conn->prepare("
        SELECT p.id, p.photo_url, p.caption, p.created_at, 
               COALESCE(u.profile_photo, 'profile.jpg') AS profile_photo, 
               u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();
    return $posts;
}

// Fetch user data with default profile photo
function getUserData($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT u.username, u.description, 
               COALESCE(u.profile_photo, 'profile.jpg') AS profile_photo, 
               COALESCE(u.cover_photo, 'topimage.jpg') AS cover_photo, 
               g.is_verified 
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

// Load user data and posts
$userData = getUserData($conn, $_SESSION['user_id']);
$posts = getPosts($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/avif" href="icon.avif">
    <title>PicShot</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="prof.css">
    <link rel="stylesheet" href="sidebar.css">  
    <link rel="stylesheet" href="post.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   
    <style>
        /* Sidebar buttons */
        .sidebg #sidebar button.an-btn {
            color: black !important;
            background-color: white !important;
        }
        
        .maingrap {
            width:95%;
            float:right;
        }
        
        @media (max-width: 768px) {
            .maingrap {
                width:100%;
                float:left;
            }
        }
        
        /* Loader Styles */
        #loader, #upload-loader {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        #loader div, #upload-loader .spinner {
            width: 80px; 
            height: 80px;
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .form-container {
            width: 100%;
        }
        
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        
        #postModalContent img {
            width:100%;
            max-width:500px;
            height:auto;
            border-radius:16px;
            box-shadow:0 6px 18px rgba(0,0,0,0.15);
            margin-bottom:10px;
        }
    </style>
</head>
<body>
    <?php include "sidebar.html";?>

    <div class="maingrap">
        <center>
            <div class="profile-wrapper">
                <div class="uppertop" id="coverContainer">
                    <img src="<?= htmlspecialchars($userData['cover_photo']) ?>" alt="Cover Image" id="coverPhoto" style="cursor: pointer;">
                </div>

                <div class="edit-cover" id="editCover">
                    <input type="file" id="coverInput" name="coverInput" accept="image/*" style="display: none;">
                </div>

                <div class="profilepic">
                    <img src="<?= htmlspecialchars($userData['profile_photo']) ?>" alt="Profile" id="profilePhoto" style="cursor: pointer;">
                    <input type="file" id="profilePicInput" name="profilePhoto" accept="image/*" style="display: none;">
                </div>

                <div class="profile-name">
                    <div class="username-line">
                        <span class="username"><?= htmlspecialchars($userData['username']) ?></span>
                        <?php if (!empty($userData['is_verified'])): ?>
                            <img src="vf.png" alt="verified" class="verified-icon" />
                        <?php endif; ?>
                    </div>
                    <span class="desc"><?= htmlspecialchars($userData['description']) ?></span>
                    <button onclick="openEditModal()" class="editp an-btn">Edit Profile</button>
                </div>
            </div>
        </center>

        <div id="editModal" class="modal">
            <div class="modal-content">
                <span onclick="closeEditModal()">&times;</span>
                <h2>Edit Profile</h2>
                <form method="POST">
                    <label for="newUsername">New Username:</label>
                    <input type="text" name="newUsername" id="newUsername" value="<?= htmlspecialchars($userData['username']) ?>" required><br><br>
                    <label for="newDescription">New Description:</label>
                    <textarea name="newDescription" id="newDescription" required><?= htmlspecialchars($userData['description']) ?></textarea><br><br>
                    <button type="submit" name="editUsernameSubmit" class="editUsernameSubmit">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="add-post-section" id="uploadBox" style="display: none;">
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
                        <button onclick="submitPost()" style="margin-top: 20px; padding: 10px 20px; border: none; border-radius: 10px; background: linear-gradient(to right, #3b5323, #ffa500); color: white; font-size: 16px; cursor: pointer;">Post</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="post-grid-box" id="postGrid">
            <ul class="nav-posts an-btn">
                <li><button onclick="toggleUpload()">Post</button></li>
            </ul>

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
            <?php endforeach; ?>
        </div>

        <!-- Loading Screen -->
        <div id="loadingOverlay">
            <div class="spinner"></div>
        </div>

        <div id="postModal" style="display: none;">
            <div id="postModalContent">
                <button class="close-btn" onclick="closePostModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>
        </div>

        <button class="plus-button" onclick="toggleUpload()">+</button>
        <div id="upload-loader" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        // Image upload constants
        const IMGBB_API_KEY = "8f23d9f5d1b5960647ba5942af8a1523";
        const IMAGGA_KEY = "acc_7300facc9d3b521";
        const IMAGGA_SECRET = "f127d8a250041a77a10d8c1e2ad78ccc";

        // DOM elements
        const loader = document.getElementById('loader');
        const fileInput = document.getElementById('fileInput');
        const previewImg = document.getElementById('previewImg');
        const placeholder = document.getElementById('placeholder');
        let latestImageUrl = "";

        // Make the cover photo clickable
        document.getElementById('coverContainer').addEventListener('click', function() {
            document.getElementById('coverInput').click();
        });

        // Handle the file input change for cover photo
        document.getElementById('coverInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('coverPhoto').src = e.target.result;
                };
                reader.readAsDataURL(file);

                uploadCoverPhoto(file);
            }
        });

        // Profile picture upload logic
        const profileImage = document.getElementById('profilePhoto');
        const profileInput = document.getElementById('profilePicInput');

        profileImage.addEventListener('click', () => {
            profileInput.click();
        });

        profileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
                const formData = new FormData();
                formData.append('profilePhoto', file);

                document.getElementById('upload-loader').style.display = 'flex';
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Profile picture upload successful:', data);
                    profileImage.src = e.target.result;
                    document.getElementById('upload-loader').style.display = 'none';
                })
                .catch(error => {
                    console.error('Error uploading profile picture:', error);
                    alert('Failed to upload profile picture. Please try again.');
                    document.getElementById('upload-loader').style.display = 'none';
                });
            };
            reader.readAsDataURL(file);
        });

        // Image upload handler
        function handleImageUpload(event) {
            const file = event.target.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);

            uploadToImgbb(file);
        }

        // Upload to ImgBB
        async function uploadToImgbb(file) {
            loader.style.display = 'flex';
            const formData = new FormData();
            formData.append("image", file);
            
            try {
                const res = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`, {
                    method: "POST", 
                    body: formData
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

        // Get tags from Imagga
        async function getTagsFromImagga(imageUrl) {
            loader.style.display = 'flex';
            const auth = btoa(IMAGGA_KEY + ":" + IMAGGA_SECRET);
            
            try {
                const res = await fetch(`https://api.imagga.com/v2/tags?image_url=${encodeURIComponent(imageUrl)}`, {
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

        // Submit post
        function submitPost() {
            const title = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const imageUrl = latestImageUrl;

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
                if (text === "SUCCESS") {
                    document.getElementById("uploadBox").style.display = "none";
                    alert("Post uploaded successfully!");
                    window.location.reload();
                } else {
                    alert("Error: " + text);
                }
            })
            .catch(err => {
                loader.style.display = 'none';
                console.error("DB insert error:", err);
                alert("Failed to submit post. Please try again.");
            });
        }

        // Toggle upload box
        function toggleUpload() {
            const uploadBox = document.getElementById("uploadBox");
            uploadBox.style.display = uploadBox.style.display === "none" ? "block" : "none";
        }

        // Edit profile modal
        function openEditModal() {
            document.getElementById("editModal").style.display = "block";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        // Post modal
        function openPostModal(postId) {
            const modal = document.getElementById('postModal');
            const content = document.getElementById('postModalContent');
            const loader = document.getElementById('loadingOverlay');

            modal.onclick = function(event) {
                if (event.target === modal) {
                    closePostModal();
                }
            };

            loader.classList.add('active');
            fetch('photovs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                loader.classList.remove('active');
            });
        }

        function closePostModal() {
            const modal = document.getElementById('postModal');
            const content = document.getElementById('postModalContent');
            modal.style.display = 'none';
            content.innerHTML = `<button class="close-btn" onclick="closePostModal()">X</button>`;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>