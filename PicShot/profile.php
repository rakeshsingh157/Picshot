<?php
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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

// Create time_logs table if needed
$table_check = $conn->query("SHOW TABLES LIKE 'time_logs'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE time_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        time_recorded DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    if (!$conn->query($create_table)) {
        error_log("Error creating time_logs table: " . $conn->error);
    }
}

// Save current time
$current_time = date('Y-m-d H:i:s');
try {
    $time_stmt = $conn->prepare("INSERT INTO time_logs (user_id, time_recorded) VALUES (?, ?)");
    if ($time_stmt) {
        $time_stmt->bind_param("is", $user_id, $current_time);
        $time_stmt->execute();
        $time_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error saving time: " . $e->getMessage());
}

/**
 * Uploads a file to ImgBB using their API.
 * Includes enhanced error handling for cURL and API responses.
 *
 * @param array $file The $_FILES array for the uploaded file.
 * @return string|null The URL of the uploaded image if successful, otherwise null.
 */
function uploadToImgBB($file) {
    $apiKey = "8f23d9f5d1b5960647ba5942af8a1523"; // Ensure this API key is valid and active
    
    // Check if file content is available before encoding
    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name']) || filesize($file['tmp_name']) === 0) {
        error_log("ImgBB Upload Error: Temporary file not found or empty for " . $file['name']);
        return null;
    }

    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.imgbb.com/1/upload?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $imageData]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a timeout for the cURL request (30 seconds)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Consider setting this to true in production with proper CA certs

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['data']['url'])) {
        return $json['data']['url'];
    } else {
        // Log the full ImgBB response for detailed debugging
        error_log("ImgBB API Error: " . print_r($json, true) . " for file " . $file['name']);
        return null;
    }
}

/**
 * Provides a user-friendly error message for PHP file upload errors.
 *
 * @param int $errorCode The error code from $_FILES['name']['error'].
 * @return string A descriptive error message.
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the maximum file size allowed by the server (php.ini).";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the maximum file size specified in the form.";
        case UPLOAD_ERR_PARTIAL:
            return "The file was only partially uploaded. Please try again.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded. Please select a file.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder on the server for uploads.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write the file to disk on the server.";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload. Check server configuration.";
        default:
            return "An unknown file upload error occurred.";
    }
}

// Handle all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle comments
    if (isset($_POST['comment'])) {
        $post_id = intval($_POST['post_id']);
        $comment = trim($_POST['comment']);
        if ($comment !== '') {
            $current_time = date('Y-m-d H:i:s');
            $ins = $conn->prepare(
                "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("iiss", $post_id, $user_id, $comment, $current_time);
            if (!$ins->execute()) {
                error_log("Comment insert error: " . $ins->error);
            }
            $ins->close();
        }
    }
    
    // Handle file uploads (profile, cover, and post images)
    $handled = false;
    
    // Profile photo upload
    // Check for 'profilePhoto' as the name attribute in the file input
    if (!$handled && isset($_FILES['profilePhoto'])) {
        if ($_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
            $profilePhotoUrl = uploadToImgBB($_FILES['profilePhoto']);
            if ($profilePhotoUrl) {
                $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->bind_param("si", $profilePhotoUrl, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['profile_photo'] = $profilePhotoUrl;
                    echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Profile photo updated successfully!"); window.location.reload();</script>';
                } else {
                    echo '<script>alert("Error: Could not save profile photo link to database.");</script>';
                    error_log("DB update error for profile photo: " . $stmt->error);
                }
                $stmt->close();
            } else {
                echo '<script>alert("Error: Failed to upload profile photo to ImgBB. Please try again.");</script>';
            }
        } else {
            // Log and alert specific upload error from PHP's built-in file upload mechanism
            $errorMessage = getUploadErrorMessage($_FILES['profilePhoto']['error']);
            error_log("Profile Photo Upload Error: " . $errorMessage . " (Code: " . $_FILES['profilePhoto']['error'] . ")");
            echo '<script>alert("File upload error for profile photo: ' . $errorMessage . '");</script>';
        }
        $handled = true; // Mark as handled even if there's an error, to prevent other file handlers from running
    }
    
    // Cover photo upload
    // Check for 'coverInput' as the name attribute in the file input
    if (!$handled && isset($_FILES['coverInput'])) {
        if ($_FILES['coverInput']['error'] === UPLOAD_ERR_OK) {
            $coverPhotoUrl = uploadToImgBB($_FILES['coverInput']);
            if ($coverPhotoUrl) {
                $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
                $stmt->bind_param("si", $coverPhotoUrl, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['cover_photo'] = $coverPhotoUrl;
                    echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Cover photo updated successfully!"); window.location.reload();</script>';
                } else {
                    echo '<script>alert("Error: Could not save cover photo link to database.");</script>';
                    error_log("DB update error for cover photo: " . $stmt->error);
                }
                $stmt->close();
            } else {
                echo '<script>alert("Error: Failed to upload cover photo to ImgBB. Please try again.");</script>';
            }
        } else {
            // Log and alert specific upload error
            $errorMessage = getUploadErrorMessage($_FILES['coverInput']['error']);
            error_log("Cover Photo Upload Error: " . $errorMessage . " (Code: " . $_FILES['coverInput']['error'] . ")");
            echo '<script>alert("File upload error for cover photo: ' . $errorMessage . '");</script>';
        }
        $handled = true;
    }
    
    // Post image upload
    if (!$handled && isset($_FILES['imageInput'])) {
        if ($_FILES['imageInput']['error'] === UPLOAD_ERR_OK) {
            $postPhotoUrl = uploadToImgBB($_FILES['imageInput']);
            $postDescription = $_POST['descInput'] ?? '';
            if ($postPhotoUrl) {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $postPhotoUrl, $postDescription);
                if ($stmt->execute()) {
                    echo '<script>document.getElementById("upload-loader").style.display = "none"; alert("Post created successfully!"); window.location.reload();</script>';
                } else {
                    echo '<script>alert("Error: Could not save post to database.");</script>';
                    error_log("DB insert error for new post: " . $stmt->error);
                }
                $stmt->close();
            } else {
                echo '<script>alert("Error: Failed to upload post image to ImgBB. Please try again.");</script>';
            }
        } else {
            // Log and alert specific upload error
            $errorMessage = getUploadErrorMessage($_FILES['imageInput']['error']);
            error_log("Post Image Upload Error: " . $errorMessage . " (Code: " . $_FILES['imageInput']['error'] . ")");
            echo '<script>alert("File upload error for post image: ' . $errorMessage . '");</script>';
        }
        $handled = true;
    }
    
    // Edit profile
    if (!$handled && isset($_POST['editUsernameSubmit'])) {
        $newUsername = $_POST['newUsername'];
        $newDescription = substr($_POST['newDescription'], 0, 150); // Truncate description if too long
        
        if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
            echo '<script>alert("Username must be 3-20 characters");</script>';
        } elseif (strlen($_POST['newDescription']) > 150) {
            echo '<script>alert("Description max 150 characters");</script>';
        } else {
            // Check if username already exists for another user
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->bind_param("si", $newUsername, $user_id);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                echo '<script>alert("Username taken");</script>';
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $newUsername, $newDescription, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $newUsername; // Update session username
                    echo '<script>alert("Profile updated!"); window.location.reload();</script>';
                } else {
                    echo '<script>alert("Error updating profile");</script>';
                    error_log("DB update error for profile edit: " . $stmt->error);
                }
            }
            $checkStmt->close();
        }
        $handled = true;
    }
    
    // Delete post
    if (!$handled && isset($_POST['delete_post_id'])) {
        $deletePostId = $_POST['delete_post_id'];
        // Ensure only the owner can delete their post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deletePostId, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo '<script>alert("Post deleted!"); window.location.reload();</script>';
        } else {
            echo '<script>alert("Delete failed or post not found/owned");</script>';
            error_log("DB delete error for post: " . $stmt->error);
        }
        $stmt->close();
        $handled = true;
    }
    
    // If no specific POST action was handled, hide loader if it was shown
    if (!$handled) {
        echo '<script>document.getElementById("upload-loader").style.display = "none";</script>';
    }
}

// Fetch user data
$userData = [];
$stmt = $conn->prepare("SELECT u.username, u.description, u.profile_photo, u.cover_photo, g.is_verified 
                        FROM users u 
                        LEFT JOIN goldentik g ON u.id = g.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
}
$stmt->close();

// Fetch posts
$posts = [];
$stmt = $conn->prepare("SELECT p.id, p.photo_url, p.caption, u.username 
                        FROM posts p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE p.user_id = ? 
                        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();

$conn->close(); // Close the database connection
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
       .sidebg #sidebar button.an-btn {
    color: black !important;
    background-color: white !important;
}

.maingrap{
 width:95%;
 float:right;
}
@media screen (max-width: 768px) {
  .maingrap{
 width:100%;
 float:left;
}
}

@media (max-width: 768px) {
  .maingrap {
 width:100%;
 float:left;
}
}

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

#loadingOverlay {
  display: none;
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
</head>
<body>

<?php include "sidebar.html";?>

<div class="maingrap">
    <center>
        <div class="profile-wrapper">
           <div class="uppertop" id="coverContainer">
                <img src="<?= htmlspecialchars($userData['cover_photo'] ?? 'topimage.jpg') ?>" alt="Cover Image" id="coverPhoto">
            </div>

            <div class="edit-cover" id="editCover">
                <!-- The input is hidden and triggered by clicking the image -->
                <input type="file" id="coverInput" name="coverInput" accept="image/*" style="display: none;">
            </div>

            <div class="profilepic">
                <img src="<?= htmlspecialchars($userData['profile_photo'] ?? 'profile.jpg') ?>" alt="Profile" id="profilePhoto">
                <!-- The input is hidden and triggered by clicking the image -->
                <input type="file" id="profilePicInput" name="profilePhoto" accept="image/*" style="display: none;">
            </div>

            <div class="profile-name">
                <div class="username-line">
                    <span class="username"><?= htmlspecialchars($userData['username'] ?? 'Username') ?></span>
                    <?php if (!empty($userData['is_verified'])): ?>
                        <img src="vf.png" alt="verified" class="verified-icon" />
                    <?php endif; ?>
                </div>
                <span class="desc"><?= htmlspecialchars($userData['description'] ?? 'Description') ?></span>
                <button onclick="openEditModal()" class="editp an-btn">Edit Profile</button>
            </div>
        </div>
    </center>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span onclick="closeEditModal()">&times;</span>
            <h2>Edit Profile</h2>
            <form method="POST">
                <label for="newUsername">New Username:</label>
                <input type="text" name="newUsername" id="newUsername" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" required><br><br>
                <label for="newDescription">New Description:</label>
                <textarea name="newDescription" id="newDescription" required><?= htmlspecialchars($userData['description'] ?? '') ?></textarea><br><br>
                <button type="submit" name="editUsernameSubmit" class="editUsernameSubmit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Add New Post Section -->
    <div class="add-post-section" id="uploadBox" style="display: none;">
        <div class="container">
            <h2>Create New Post</h2>
            <div class="form-container">
                <form id="postForm" method="POST" enctype="multipart/form-data">
                    <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                        <img id="previewImg" alt="" style="max-width: 100%; display: none;" />
                        <p id="placeholder">Choose a file to upload</p>
                        <input type="file" id="fileInput" name="imageInput" style="display:none;" onchange="handleImageUpload(event)">
                    </div>

                    <div class="form-fields">
                        <label for="description">Description</label>
                        <textarea id="description" name="descInput" placeholder="Enter the description"></textarea>
                        <button type="submit" class="post-button">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Posts Grid -->
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
                    <form method="post" onsubmit="return confirm('Delete this post?');">
                        <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
                        <button type="submit" class="delete-button">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Loading Overlay for AJAX calls -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Post Detail Modal -->
    <div id="postModal" style="display: none;">
        <div id="postModalContent">
            <button class="close-btn" onclick="closePostModal()">
                <i class="fa-solid fa-x"></i>
            </button>
        </div>
    </div>
</div>

<!-- Fullscreen Image Modal -->
<div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
    <span class="close-fullscreen">&times;</span>
    <div class="fullscreen-modal-content">
        <img id="fullscreenImage" class="fullscreen-image">
        <div id="fullscreenCaption" class="fullscreen-caption"></div>
    </div>
</div>

<!-- Floating Plus Button to Toggle Upload Box -->
<button class="plus-button" onclick="toggleUpload()">+</button>
<div id="upload-loader" style="display: none;">
    <div class="spinner"></div>
</div>

<script>
// Profile photo click to change: Triggers the hidden file input
document.getElementById('profilePhoto').addEventListener('click', function() {
    document.getElementById('profilePicInput').click();
});

// Cover photo click to change: Triggers the hidden file input
document.getElementById('coverPhoto').addEventListener('click', function() {
    document.getElementById('coverInput').click();
});

// Event listener for profile photo input change
document.getElementById('profilePicInput').addEventListener('change', function() {
    showLoader(); // Show loading spinner
    // Call uploadFile with the input element and 'profile' type
    uploadFile(this, 'profile'); 
});

// Event listener for cover photo input change
document.getElementById('coverInput').addEventListener('change', function() {
    showLoader(); // Show loading spinner
    // Call uploadFile with the input element and 'cover' type
    uploadFile(this, 'cover');
});

/**
 * Handles the file upload to the server via Fetch API.
 * @param {HTMLInputElement} input - The file input element.
 * @param {string} type - The type of upload ('profile' or 'cover').
 */
function uploadFile(input, type) {
    const formData = new FormData();
    // Append the file to FormData with the correct name attribute
    // that the PHP script expects ($_FILES['profilePhoto'] or $_FILES['coverInput'])
    if (type === 'profile') {
        formData.append('profilePhoto', input.files[0]);
    } else if (type === 'cover') {
        formData.append('coverInput', input.files[0]);
    } else {
        console.error('Unknown upload type:', type);
        hideLoader();
        return;
    }
    
    fetch('', { // Sending to the same PHP script
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get response as text (PHP echoes <script> tags)
    .then(data => {
        hideLoader(); // Hide loading spinner
        // Execute the script returned by PHP (e.g., alert and reload)
        // This is a common pattern in older PHP applications, but for modern
        // apps, consider returning JSON and handling success/error messages in JS.
        try {
            // Create a temporary div to parse the HTML/script from the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            const scriptTags = tempDiv.getElementsByTagName('script');
            for (let i = 0; i < scriptTags.length; i++) {
                eval(scriptTags[i].innerHTML); // Execute the script
            }
        } catch (e) {
            console.error('Error executing PHP response script:', e, data);
            alert('An unexpected error occurred after upload. Please check console.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Upload failed: ' + error.message);
        hideLoader(); // Ensure loader is hidden on error
    });
}

/**
 * Handles the image preview for the new post upload section.
 * @param {Event} event - The change event from the file input.
 */
function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file || !file.type.startsWith('image/')) {
        // If no file or not an image, reset preview
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('placeholder').style.display = 'block';
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        const previewImg = document.getElementById('previewImg');
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        document.getElementById('placeholder').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

/**
 * Opens the post detail modal and fetches post content.
 * @param {number} postId - The ID of the post to display.
 */
function openPostModal(postId) {
    const modal = document.getElementById('postModal');
    const content = document.getElementById('postModalContent');
    const loader = document.getElementById('loadingOverlay');

    modal.style.display = 'block';
    loader.style.display = 'flex'; // Show loading overlay

    // Fetch post details from photovs.php
    fetch('photovs.php?post_id=' + postId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.text();
        })
        .then(data => {
            // Parse the response to extract the relevant post content
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const innerContent = doc.querySelector('.outer-card'); // Assuming photovs.php returns content within .outer-card

            if (innerContent) {
                // Set the modal content and add the close button
                content.innerHTML = `
                    <button class="close-btn" onclick="closePostModal()"><i class="fa-solid fa-x"></i></button>
                    ${innerContent.outerHTML}
                `;
                modal.style.display = 'block';
                
                // Add click functionality to images within the modal for fullscreen view
                const images = content.querySelectorAll('img');
                images.forEach(img => {
                    img.onclick = function() {
                        document.getElementById('fullscreenImage').src = this.src;
                        document.getElementById('fullscreenCaption').textContent = this.alt;
                        document.getElementById('fullscreenModal').style.display = 'block';
                    };
                });
            } else {
                alert('Post content not found or invalid response from server.');
                console.error('Invalid response from photovs.php:', data);
            }
        })
        .catch(error => {
            console.error('Error fetching post:', error);
            alert('Failed to load post. Please try again.');
        })
        .finally(() => {
            loader.style.display = 'none'; // Hide loading overlay regardless of success/failure
        });
}

/**
 * Closes the post detail modal and resets its content.
 */
function closePostModal() {
    const modal = document.getElementById('postModal');
    const content = document.getElementById('postModalContent');
    modal.style.display = 'none';
    // Reset content to just the close button to prevent old content from flashing
    content.innerHTML = `<button class="close-btn" onclick="closePostModal()"><i class="fa-solid fa-x"></i></button>`;
}

/**
 * Closes the fullscreen image modal.
 */
function closeFullscreen() {
    document.getElementById('fullscreenModal').style.display = 'none';
}

/**
 * Shows the global upload loader.
 */
function showLoader() {
    document.getElementById('upload-loader').style.display = 'flex';
}

/**
 * Hides the global upload loader.
 */
function hideLoader() {
    document.getElementById('upload-loader').style.display = 'none';
}

/**
 * Toggles the visibility of the new post upload box.
 */
function toggleUpload() {
    const uploadBox = document.getElementById("uploadBox");
    uploadBox.style.display = uploadBox.style.display === "none" ? "block" : "none";
    // Reset preview when toggling off
    if (uploadBox.style.display === "none") {
        document.getElementById('previewImg').src = '';
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('placeholder').style.display = 'block';
        document.getElementById('fileInput').value = ''; // Clear selected file
        document.getElementById('description').value = ''; // Clear description
    }
}

/**
 * Opens the edit profile modal.
 */
function openEditModal() {
    document.getElementById("editModal").style.display = "flex"; // Use flex for centering
}

/**
 * Closes the edit profile modal.
 */
function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

// Close modals when clicking outside of their content
window.onclick = function(event) {
    const editModal = document.getElementById("editModal");
    if (event.target == editModal) {
        closeEditModal();
    }
    
    const postModal = document.getElementById("postModal");
    if (event.target == postModal) {
        closePostModal();
    }
    
    const fullscreenModal = document.getElementById("fullscreenModal");
    if (event.target == fullscreenModal) {
        closeFullscreen();
    }
}
</script>
</body>
</html>
