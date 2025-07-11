<?php
session_start();

// Set default timezone to Indian/Kolkata
date_default_timezone_set('Asia/Kolkata');

$host = 'database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com';
$port = '3306';
$dbname = 'Photostore';
$dbuser = 'admin';
$dbpass = 'DBpicshot';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname, $port);

if ($conn->connect_error) {
    // For AJAX requests, output a specific error. For initial page load, exit gracefully.
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(500);
        echo "Database connection failed for AJAX: " . $conn->connect_error;
        exit();
    } else {
        die("Database connection failed for page load: " . $conn->connect_error);
    }
}

// --- AJAX ENDPOINTS ---
// These blocks handle requests from JavaScript and then exit.

// Handle AJAX request for getting post details
if (isset($_GET['action']) && $_GET['action'] === 'get_post_details') {
    $post_id = $_GET['post_id'] ?? null;

    if (!$post_id) {
        http_response_code(400); // Bad Request
        echo "<p>Post ID is required!</p>";
        $conn->close();
        exit;
    }

    // Check if user is logged in for viewing post details (if required)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo "<p>Please log in to view post details.</p>";
        $conn->close();
        exit;
    }

    // Fetch post details
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

    // Fetch comments for the post
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
    ?>
    <div class="post-detail-container">
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

                <form method="POST" class="comment-box" action=""> <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_id); ?>">
                    <input type="hidden" name="action" value="post_comment"> <input type="text" name="comment" placeholder="What do you think?" required>
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    $conn->close();
    exit; // IMPORTANT: Exit after handling the AJAX request
}

// Handle AJAX request for posting a new comment
if (isset($_POST['action']) && $_POST['action'] === 'post_comment') {
    header('Content-Type: text/html'); // Ensure this header for HTML response

    // Authenticate user
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo "You must be logged in to comment. Please log in and try again.";
        $conn->close();
        exit();
    }
    $user_id = $_SESSION['user_id'];

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

    // Insert comment into database with Indian/Kolkata time
    $sql = "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Kolkata'))";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        http_response_code(500);
        echo "Failed to prepare the comment insertion statement.";
        $conn->close();
        exit();
    }

    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);

    if ($stmt->execute()) {
        // Success: Return the HTML for the new comment.
        // Fetch the username of the currently logged-in user to display it.
        $username_placeholder = "Unknown User";
        $user_sql = "SELECT username FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
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
    exit();
}

// REGULAR PAGE LOAD (HTML Content Below)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PicShot</title>
    <link rel="icon" type="image/avif" href="icon.avif">
    <link rel="stylesheet" href="home_page.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
    <link rel="stylesheet" href="home_pagef.css">
</head>
<body>
    <?php include "sidebar.html";?>
    
    <p class="pagename">Home</p>
    
    <form method="GET" action="">
        <div class="search-container">
            <div class="search">
                <span class="search-icon material-symbols-outlined">search</span>
                <input class="search-input" type="text" placeholder="Search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
        </div>
    </form>

    <div class="featured-slider" id="slider">
        <div class="featured-slide active" style="background-image: url('demo.jpg');">
            <div class="overlay">
                <h2>Dazzling Night</h2>
                <p>@AnotherUser</p>
            </div>
        </div>
        <div class="featured-slide" style="background-image: url('photo.jpg');">
            <div class="overlay">
                <h2>Sunny Beach</h2>
                <p>@BeachLover</p>
            </div>
        </div>
        <div class="featured-slide" style="background-image: url('topimage.jpg');">
            <div class="overlay">
                <h2>Peaceful Hills</h2>
                <p>@NatureFan</p>
            </div>
        </div>
        <div class="slider-controls">
            <button onclick="prevSlide()">&#10094;</button>
            <button onclick="nextSlide()">&#10095;</button>
        </div>
        <div class="slider-dots" id="dots"></div>
    </div>

    <div class="container">
        <div class="box">
            <div class="image-grid<?php
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                echo ($search !== '' && $search[0] === '@') ? ' user-list' : '';
            ?>">
                <?php
                if ($search !== '' && $search[0] === '@') {
                    // Username search: show only user info
                    $username = substr($search, 1);
                    $sql = "SELECT id, username, name, profile_photo FROM users WHERE username LIKE CONCAT('%', ?, '%')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        while($user = $result->fetch_assoc()) {
                            $profile_photo = htmlspecialchars($user['profile_photo'] ?: 'profile.jpg');
                            $username = htmlspecialchars($user['username']);
                            $name = htmlspecialchars($user['name']);
                            ?>
                            <div class="user-card">
                                <img src="<?php echo $profile_photo; ?>" alt="Profile" class="profile-photo">
                                <div class="user-info">
                                    <span class="username">
                                        <a href="userview.php?username=<?php echo urlencode($username); ?>">@<?php echo $username; ?></a>
                                    </span>
                                    <div class="real-name"><?php echo $name; ?></div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p>No users found.</p>";
                    }
                    if (isset($stmt)) $stmt->close();
                } else {
                    // Title or caption search or default: show posts
                    if ($search !== '') {
                        $sql = "SELECT posts.*, users.username 
                                 FROM posts 
                                 JOIN users ON posts.user_id = users.id 
                                 WHERE posts.title LIKE CONCAT('%', ?, '%') 
                                   OR posts.caption LIKE CONCAT('%', ?, '%')
                                 ORDER BY posts.created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $search, $search);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        // For default view (no search), fetch posts in random order
                        $sql = "SELECT posts.*, users.username 
                                 FROM posts 
                                 JOIN users ON posts.user_id = users.id 
                                 ORDER BY RAND()";
                        $result = $conn->query($sql);
                    }

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $photo_url = htmlspecialchars($row['photo_url'] ?: 'demo.jpg');
                            $caption = htmlspecialchars($row['caption']);
                            $title = htmlspecialchars($row['title']);
                            $username = htmlspecialchars($row['username']);
                            $post_id_current_grid = htmlspecialchars($row['id']);
                            ?>
                            <div class="image-card" onclick="openPostPopup(<?php echo $post_id_current_grid; ?>)" style="padding-bottom:30px">
                                <div style="width:100%; height:200px; border-radius :20px; background:#e0e0e0; display:flex; align-items:center; justify-content:center;">
                                    <img src="<?php echo $photo_url; ?>" alt="" style="max-height:100%; max-width:100%;">
                                </div>
                                <div class="caption" style="margin-top: 10px;">
                                    <?php if ($caption) { echo "<div>$caption</div>"; } ?>
                                    <span class="username">
                                        <a href="userview.php?username=<?php echo urlencode($username); ?>">@<?php echo $username; ?></a>
                                    </span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p>No photos found.</p>";
                    }
                    if (isset($stmt)) $stmt->close();
                }
                ?>
            </div>
        </div>
    </div>

    <div id="myPostPopup" class="popup-overlay">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup()">&times;</span>
            <div id="popupContentInner">
                </div>
        </div>
    </div>
    
</body>
<script>
    const slides = document.querySelectorAll(".featured-slide");
    const dotsContainer = document.getElementById("dots");
    let current = 0, interval;

    slides.forEach((_, i) => {
      const dot = document.createElement("span");
      dot.addEventListener("click", () => goTo(i));
      dotsContainer.appendChild(dot);
    });
    const dots = dotsContainer.children;

    function show(i) {
      slides.forEach((s, idx) => {
        s.classList.toggle("active", idx===i);
        dots[idx].classList.toggle("active", idx===i);
      });
      current = i;
    }
    function nextSlide() { show((current+1)%slides.length) }
    function prevSlide() { show((current-1+slides.length)%slides.length) }
    function goTo(i) { show(i) }
    function startAuto() { interval = setInterval(nextSlide, 2000) }
    function stopAuto() { clearInterval(interval) }

    show(0);
    startAuto();
    document.getElementById("slider")
                .addEventListener("mouseenter", stopAuto);
    document.getElementById("slider")
                .addEventListener("mouseleave", startAuto);

    async function openPostPopup(postId) {
        document.getElementById("myPostPopup").style.display = "flex";
        const popupContentInner = document.getElementById("popupContentInner");
        popupContentInner.innerHTML = '<p style="text-align: center; color: #555;">Loading post...</p>';

        try {
            const response = await fetch(`home_page.php?action=get_post_details&post_id=${postId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            const data = await response.text();
            popupContentInner.innerHTML = data;

            const commentForm = popupContentInner.querySelector('.comment-box');
            if (commentForm) {
                commentForm.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const formData = new FormData(this);

                    const commentText = formData.get('comment');
                    if (!commentText.trim()) {
                        alert("Comment cannot be empty!");
                        return;
                    }

                    const commentResponse = await fetch('home_page.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (commentResponse.ok) {
                        const newCommentHtml = await commentResponse.text();
                        const commentsContainer = popupContentInner.querySelector('.comments-container');
                        if (commentsContainer) {
                            const noCommentsMessage = commentsContainer.querySelector('p');
                            if (noCommentsMessage && noCommentsMessage.textContent.includes('No comments yet')) {
                                noCommentsMessage.remove();
                            }
                            commentsContainer.insertAdjacentHTML('afterbegin', newCommentHtml);
                            this.querySelector('input[name="comment"]').value = '';
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
        }
    }

    function closePopup() {
        document.getElementById("myPostPopup").style.display = "none";
        document.getElementById("popupContentInner").innerHTML = ''; 
    }

    window.onclick = function(event) {
        const popupOverlay = document.getElementById("myPostPopup");
        if (event.target == popupOverlay) {
            closePopup();
        }
    }
</script>
</html>
<?php
// Close the main connection only if it hasn't been closed by an AJAX exit
if ($conn->ping()) {
    $conn->close();
}
?>