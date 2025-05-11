<?php
session_start();

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?=htmlspecialchars($userData['username'] ?? 'User Profile')?></title>
    <link rel="stylesheet" href="style.css">
    <style>
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
        <?php if ($isOwnProfile): ?>
            <li><button onclick="">Drafts</button></li>
        <?php endif; ?>
    </ul>

    <?php if ($isOwnProfile): ?>
        <div class="add-post-section" id="uploadBox">
            <form method="POST" enctype="multipart/form-data">
                <label for="imageInput">Upload an image:</label>
                <input type="file" name="imageInput" id="imageInput" accept="image/*" required><br>
                <input type="text" name="descInput" id="descInput" placeholder="Enter description" required><br>
                <button type="submit" name="upload">Add Post</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="post-grid-box" id="postGrid">
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <img src="<?= htmlspecialchars($post['photo_url']) ?>" alt="User Post">
                <div class="post-overlay">
                    <div class="post-desc"><?= htmlspecialchars($post['caption'] ?? 'No Caption') ?></div>
                    <div class="post-username">@<?= htmlspecialchars($post['username']) ?></div>
                    <?php if ($isOwnProfile): ?>
                        <form method="post">
                            <input type="hidden" name="delete_post_id" value="<?= htmlspecialchars($post['id']) ?>">
                            <button type="submit" class="delete-button">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isOwnProfile): ?>
        <button class="plus-button" onclick="toggleUpload()">+</button>
    <?php endif; ?>

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
    </script>
</body>
</html>