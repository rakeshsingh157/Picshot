<?php
session_start();
ob_start(); // Start output buffering

// 1. Centralized Database Connection Configuration
$host = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$user = "admin";
$pass = "DBpicshot";
$db   = "Photostore";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Handle database connection error for both AJAX and regular requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(500); // Internal Server Error for AJAX
        echo "Database connection failed for AJAX: " . $conn->connect_error;
        exit();
    } else {
        die("Database connection failed for page load: " . $conn->connect_error);
    }
}

// --- TEMP: For testing if user_id is not set by your login system ---
// REMOVE THIS BLOCK IN PRODUCTION
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use an existing user ID from your 'users' table for testing
}
// --- END TEMP BLOCK ---

$currentUserId = $_SESSION['user_id'] ?? null; // Get logged-in user's ID


// ====================================================================
// --- AJAX ENDPOINTS ---
// These blocks handle requests from JavaScript for popups or comments, then exit.
// ====================================================================

// Handle AJAX request for getting post details (from former photovs.php)
if (isset($_POST['action']) && $_POST['action'] === 'get_post_details') {
    // Clear any previous output buffering to ensure only the desired HTML is sent
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html'); // Explicitly set content type

    $post_id = $_POST['post_id'] ?? null; // Expect POST for post_id

    if (!isset($currentUserId)) {
        http_response_code(401); // Unauthorized
        echo "<p>Please log in to view post details.</p>";
        $conn->close();
        exit;
    }

    if (!$post_id) {
        http_response_code(400); // Bad Request
        echo "<p>Post ID is required!</p>";
        $conn->close();
        exit;
    }

    // Fetch post
    $sql = "SELECT p.*, u.username
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo "<p>Failed to prepare post query: " . $conn->error . "</p>";
        $conn->close();
        exit;
    }
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        http_response_code(404); // Not Found
        echo "<p>Post not found!</p>";
        $conn->close();
        exit;
    }

    // Fetch comments
    $sql_c = "SELECT c.comment, c.created_at, u.username
              FROM comments c
              JOIN users u ON c.user_id = u.id
              WHERE c.post_id = ?
              ORDER BY c.created_at DESC";
    $stmt_c = $conn->prepare($sql_c);
    if (!$stmt_c) {
        http_response_code(500);
        echo "<p>Failed to prepare comments query: " . $conn->error . "</p>";
        $conn->close();
        exit;
    }
    $stmt_c->bind_param("i", $post_id);
    $stmt_c->execute();
    $comments = $stmt_c->get_result();
    $stmt_c->close();

    // Output the HTML for the popup content
    // This will be inserted into the modal by JavaScript
    ?>
    <div class="outer-card"> <div class="post-detail-container">
            <div class="post-detail-inner">
                <div class="image-section">
                    <img class="post-image" src="<?php echo htmlspecialchars($post['photo_url']); ?>" alt="Post Image">
                </div>
                <div class="details-section">
                    <p class="username">@<?php echo htmlspecialchars($post['username']); ?></p>
                    <p class="description"><?php echo htmlspecialchars($post['caption']); ?></p>

                    <h3>Comments:</h3>
                    <div class="comments-container">
                        <?php if ($comments->num_rows): ?>
                            <?php while ($c = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <strong><a href="userview.php?username=<?php echo urlencode(htmlspecialchars($c['username'])); ?>">@<?php echo htmlspecialchars($c['username']); ?></a>:</strong>
                                    <p><?php echo htmlspecialchars($c['comment']); ?></p>
                                    <small><?php echo date('F j, Y, g:i a', strtotime($c['created_at'])); ?></small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No comments yet. Be the first to comment!</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="comment-box">
                        <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_id); ?>">
                        <input type="hidden" name="action" value="post_comment"> <input type="text" name="comment" placeholder="What do you think?" required>
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    $conn->close();
    exit(); // IMPORTANT: Exit after handling the AJAX request
}

// Handle AJAX request for posting a new comment (from former post_comment.php)
if (isset($_POST['action']) && $_POST['action'] === 'post_comment') {
    // Clear any previous output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html'); // Ensure this header for HTML response

    // Authenticate user
    if (!isset($currentUserId)) {
        http_response_code(401); // Unauthorized
        echo "You must be logged in to comment. Please log in and try again.";
        $conn->close();
        exit();
    }

    // Validate inputs
    $post_id = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
    $comment_text = trim($_POST['comment'] ?? '');

    if ($post_id === false || $post_id <= 0) {
        http_response_code(400); // Bad Request
        echo "Invalid Post ID provided.";
        $conn->close();
        exit();
    }
    if (empty($comment_text)) {
        http_response_code(400); // Bad Request
        echo "Comment cannot be empty.";
        $conn->close();
        exit();
    }

    // Insert comment into database
    $sql = "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        http_response_code(500);
        echo "Failed to prepare the comment insertion statement.";
        $conn->close();
        exit();
    }

    $stmt->bind_param("iis", $post_id, $currentUserId, $comment_text);

    if ($stmt->execute()) {
        // Success: Return the HTML for the new comment.
        // Fetch the username of the currently logged-in user to display it.
        $username_placeholder = "Unknown User";
        $user_sql = "SELECT username FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        if ($user_stmt) {
            $user_stmt->bind_param("i", $currentUserId);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_row = $user_result->fetch_assoc()) {
                $username_placeholder = htmlspecialchars($user_row['username']);
            }
            $user_stmt->close();
        } else {
            error_log("Failed to prepare user fetch statement in post_comment: " . $conn->error);
        }

        $display_time = date('F j, Y, g:i a');

        echo '
            <div class="comment">
                <strong><a href="userview.php?username=' . urlencode($username_placeholder) . '">@' . $username_placeholder . '</a></strong>
                <p>' . htmlspecialchars($comment_text) . '</p>
                <small>' . $display_time . '</small>
            </div>';

    } else {
        error_log("Execute failed: " . $stmt->error);
        http_response_code(500);
        echo "Error saving your comment. Please try again: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    exit(); // IMPORTANT: Exit after handling the AJAX request
}

// ====================================================================
// --- REGULAR PAGE LOAD LOGIC ---
// This part runs only if it's not an AJAX request handled above.
// ====================================================================

// Initial redirect logic (should be at the very top of the regular page load)
$username = $_GET['username'] ?? '';
$sessionUsername = $_SESSION['username'] ?? '';

if (strtolower($sessionUsername) === strtolower($username)) {
    header("Location: profile.php");
    exit();
}

// --- Configuration for ImgBB (already present in your code) ---
$imgbbApiKey = "8f23d9f5d1b5960647ba5942af8a1523";

// --- Helper Functions (already present in your code) ---

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

// --- Handle Form Actions (for regular page load) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || ($_POST['action'] !== 'get_post_details' && $_POST['action'] !== 'post_comment'))) {

    if (!isset($currentUserId)) {
        exit("User not logged in."); // Consider redirecting to login page
    }

    // Handle Profile Photo Upload
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === 0) {
        $profilePhotoUrl = uploadToImgBB($_FILES['profilePhoto']);
        if ($profilePhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $profilePhotoUrl, $currentUserId);
            $stmt->execute();
            // Consider adding error handling for database update
        }
    }

    // Handle Cover Photo Upload
    if (isset($_FILES['coverInput']) && $_FILES['coverInput']['error'] === 0) {
        $coverPhotoUrl = uploadToImgBB($_FILES['coverInput']);
        if ($coverPhotoUrl) {
            $stmt = $conn->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $coverPhotoUrl, $currentUserId);
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
            $stmt->bind_param("iss", $currentUserId, $postPhotoUrl, $postDescription);
            $stmt->execute();
            // Consider adding error handling for database insert
        }
    }

    // Handle Delete Post
    if (isset($_POST['delete_post_id'])) {
        $deletePostId = $_POST['delete_post_id'];
        $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?"); // Delete comments first
        $stmt->bind_param("i", $deletePostId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deletePostId, $currentUserId);
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

if (isset($currentUserId)) {
    if ($requestedUsername) {
        $viewingUserId = getUserIdByUsername($conn, $requestedUsername);
        if ($viewingUserId === $currentUserId) {
            $isOwnProfile = true;
            // If viewing own profile by username, redirect to profile.php for consistency
            // header("Location: profile.php");
            // exit();
        }
    } else {
        $viewingUserId = $currentUserId;
        $isOwnProfile = true;
    }
} else {
    // If not logged in and no username requested, redirect to index
    if (!$requestedUsername) {
        header("Location: index.php");
        exit();
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
ob_end_flush(); // End output buffering for the main page load
?>

<!DOCTYPE html>
<html lang="en">
<head>
      <link rel="icon" type="image/avif" href="icon.avif">
      
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PicShot</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="postview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="userview.css">
   <style>
   @media (max-width: 768px)
 {
  .maingrap
{
 width:100%;
 float:right;
}

}
   </style>
</head>
<body>

<?php include "sidebar.html" ?>

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


    <div class="post-grid-box" id="postGrid">
           
 <ul class="nav-posts">
        <li><button onclick="">Posts</button></li>
    </ul>
        <?php foreach ($posts as $post): ?>
            <div class="post-card" onclick="openPostModal(<?= $post['id'] ?>)">
                <div style="width:100%; height:250px; background:#e0e0e0; display:flex; align-items:center; justify-content:center;">
                    <img src="<?= htmlspecialchars($post['photo_url']) ?>" alt="User Post" style="max-height:100%; max-width:100%;">
                </div>
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
            <form id="postForm-<?= $post['id'] ?>" action="photovs.php" method="POST" style="display: none;">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div id="myPostPopup" class="popup-overlay">
    <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <div id="popupContentInner">
            </div>
    </div>
</div>

<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<script>
    //  Popup JS Functions 
    async function openPostModal(postId) {
        document.getElementById("myPostPopup").style.display = "flex";
        const popupContentInner = document.getElementById("popupContentInner");
        popupContentInner.innerHTML = '<p style="text-align: center; color: #555;">Loading post...</p>';

        const loader = document.getElementById('loadingOverlay');
        loader.classList.add('active'); // Show loading

        try {
            // Send AJAX request to userview.php itself
            const response = await fetch('userview.php', {
                method: 'POST', // Use POST as defined in the PHP AJAX endpoint
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_post_details&post_id=${postId}` // Send action and post_id
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            const data = await response.text();
            popupContentInner.innerHTML = data;

            // Reattach event listener for the comment form within the loaded content
            const commentForm = popupContentInner.querySelector('.comment-box');
            if (commentForm) {
                commentForm.addEventListener('submit', async function(event) {
                    event.preventDefault(); // Prevent default form submission
                    const formData = new FormData(this);
                    // The 'action' hidden input is already part of the form, so no need to append it again.

                    const commentText = formData.get('comment');
                    if (!commentText.trim()) {
                        alert("Comment cannot be empty!");
                        return;
                    }

                    // Post comment to userview.php with action=post_comment
                    const commentResponse = await fetch('userview.php', { // Target userview.php directly
                        method: 'POST',
                        body: formData // formData already contains action=post_comment
                    });

                    if (commentResponse.ok) {
                        const newCommentHtml = await commentResponse.text();
                        const commentsContainer = popupContentInner.querySelector('.comments-container');
                        if (commentsContainer) {
                            // Find the <p> for "No comments yet" and remove it if present
                            const noCommentsMessage = commentsContainer.querySelector('p');
                            if (noCommentsMessage && noCommentsMessage.textContent.includes('No comments yet')) {
                                noCommentsMessage.remove();
                            }
                            commentsContainer.insertAdjacentHTML('afterbegin', newCommentHtml);
                            this.querySelector('input[name="comment"]').value = ''; // Clear input
                        }
                    } else {
                        const errorText = await commentResponse.text();
                        console.error('Error submitting comment:', errorText);
                        alert('Error submitting comment. Server said: ' + errorText.substring(0, 200) + '...');
                    }
                });
            }

        } catch (error) {
            console.error('Error loading post details:', error);
            popupContentInner.innerHTML = '<p style="text-align: center; color: red;">Failed to load post details. Please try again. Error: ' + error.message + '</p>';
        } finally {
            loader.classList.remove('active'); // Hide loading regardless of success/failure
        }
    }

    function closePopup() {
        document.getElementById("myPostPopup").style.display = "none";
        document.getElementById("popupContentInner").innerHTML = ''; // Clear content
    }

    // Close popup when clicking outside the content
    window.onclick = function(event) {
        const popupOverlay = document.getElementById("myPostPopup");
        if (event.target === popupOverlay) {
            closePopup();
        }
    }

    // --- Your existing JavaScript functions ---
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

                fetch('', { // Current page for form submission
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

                fetch('', { // Current page for form submission
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
<?php
// Close the main connection only if it hasn't been closed by an AJAX exit
if ($conn->ping()) {
    $conn->close();
}
?>