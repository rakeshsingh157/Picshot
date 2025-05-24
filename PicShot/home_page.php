<?php
session_start();

// 1. Centralized Database Connection Configuration (VERIFY THESE CREDENTIALS)
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

// --- TEMP: For testing if user_id is not set by your login system ---
// REMOVE THIS BLOCK IN PRODUCTION
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use an existing user ID from your 'users' table for testing
}
// --- END TEMP BLOCK ---

// ====================================================================
// --- AJAX ENDPOINTS ---
// These blocks handle requests from JavaScript and then exit.
// ====================================================================

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

    // Insert comment into database
    // The column name for the comment text in your 'comments' table is 'comment', NOT 'comment_text'.
    $sql = "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
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
    exit(); // IMPORTANT: Exit after handling the AJAX request
}

// ====================================================================
// --- REGULAR PAGE LOAD (HTML Content Below) ---
// This part runs only if it's not an AJAX request handled above.
// ====================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PicShot</title>
    <link rel="stylesheet" href="home_page.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
    <style>
        /* User card styling (from your original home_page.php) */
        .user-card {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Added subtle shadow */
            transition: transform 0.2s ease-in-out;
        }
        .user-card:hover {
            transform: translateY(-2px); /* Slight lift on hover */
        }
        .user-card .profile-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #FE9042; /* Highlight profile photo */
        }
        .user-card .user-info .username a {
            font-weight: bold;
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }
        .user-card .user-info .username a:hover {
            color: #FE9042; /* Hover color */
        }
        .user-card .user-info .real-name {
            font-size: 0.9em;
            color: #666;
        }

        /* --- Popup Styles --- */
        .popup-overlay {
            display: none; /* Hidden by default - This is the correct one */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8); /* Darker, more prominent dimmed background */
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px); /* Soft blur effect */
        }
        .popup-content {
            background: #fff;
            padding: 25px; /* Slightly more padding */
            border-radius: 12px; /* Smoother corners */
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); /* Stronger shadow */
            position: relative;
            max-width: 95%; /* Even larger for content */
            max-height: 95%;
            display: flex; /* Use flexbox for internal layout */
            flex-direction: column; /* Stack content vertically */
            overflow: hidden; /* Crucial: Prevents whole popup from scrolling */
        }

        .close-btn {
            position: absolute;
            top: 15px; /* Adjusted position */
            right: 15px; /* Adjusted position */
            font-size: 30px; /* Larger close button */
            cursor: pointer;
            color: #888;
            transition: color 0.2s;
            z-index: 1001; /* Ensure it's above content */
        }
        .close-btn:hover {
            color: #333;
        }

        /* --- Post Detail Specific Styles (from your provided code) --- */
        .post-detail-container {
            flex-grow: 1; /* Allow content to grow */
            display: flex;
            flex-direction: column; /* Stack inner content vertically */
            overflow: hidden; /* Hide internal overflow of this container */
        }
        .post-detail-inner {
            display: flex;
            flex-grow: 1; /* Allow main content to grow */
            flex-wrap: nowrap; /* Prevent wrapping to keep side-by-side on larger screens */
            gap: 30px; /* Slightly less gap */
            align-items: stretch; /* Stretch items to fill height */
            overflow: hidden; /* Prevents overflow of inner flex container */
        }
        .image-section {
            flex: 2 1 500px; /* Stronger flex basis for image */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%; /* Take full height of parent */
            max-height: 600px; /* Max height for the image section */
            min-height: 300px; /* Minimum height for image section */
            overflow: hidden; /* Hide overflow for image container */
            border-radius: 12px; /* Match popup border-radius */
            background-color: #f0f0f0; /* Placeholder background */
        }
        .post-image {
            width: 100%;
            height: 100%;
            object-fit: cover; /* This is the key: covers the entire area */
            border-radius: 12px; /* Match container border-radius */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); /* Refined shadow */
        }
        .details-section {
            flex: 1 1 350px; /* Adjust flex basis as needed */
            display: flex;
            flex-direction: column; /* Stack content vertically */
            overflow: hidden; /* Prevents this section from scrolling if content overflows */
        }
        .details-section h2 {
            margin-top: 0;
            font-size: 26px; /* Slightly larger heading */
            color: #222;
            margin-bottom: 5px;
        }
        .username {
            color: #666;
            font-size: 15px;
            margin: 0 0 15px 0;
            font-weight: 500;
        }
        .description {
            font-size: 16px;
            margin-top: 10px;
            color: #444;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        h3 {
            margin-top: 0px; /* Adjust as needed */
            margin-bottom: 10px;
            color: #333;
            font-size: 18px;
        }

        .comments-container {
            max-height: 300px; /* Fixed height for comments, allowing *only this section* to scroll */
            overflow-y: auto; /* Enable vertical scrolling */
            padding-right: 15px; /* Space for scrollbar */
            margin-bottom: 20px;
            border-top: 1px solid #eee; /* Separator for comments section */
            padding-top: 15px;
            flex-grow: 1; /* Allow comments container to take up remaining vertical space */
        }

        /* Scrollbar styling optional */
        .comments-container::-webkit-scrollbar {
            width: 8px;
        }
        .comments-container::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.15);
            border-radius: 4px;
        }
        .comments-container::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }

        .comment {
            margin-bottom: 15px; /* More space between comments */
            padding: 12px; /* More padding */
            background: #fdfdfd; /* Lighter background */
            border-radius: 8px; /* Smoother corners */
            border: 1px solid #eee; /* Light border */
            box-shadow: 0 1px 3px rgba(0,0,0,0.03); /* Subtle shadow */
        }
        .comment:last-child {
            margin-bottom: 0; /* No margin for the last comment */
        }
        .comment strong {
            color: #333;
            font-size: 14.5px;
        }
        .comment p {
            margin: 5px 0;
            color: #444;
            line-height: 1.5;
        }
        .comment small {
            font-size: 11px;
            color: #999;
            display: block; /* New line for timestamp */
            text-align: right;
        }

        .comment-box {
            display: flex;
            gap: 10px;
            margin-top: auto; /* Push comment box to the bottom */
            padding-top: 15px; /* Space above comment box */
            border-top: 1px solid #eee; /* Separator */
            position: sticky; /* Make it stick to the bottom */
            bottom: -25px; /* Offset to align with popup bottom edge (compensate popup padding) */
            background-color: #fff; /* Match popup background */
            z-index: 10; /* Ensure it's above scrolling comments */
            padding-bottom: 25px; /* Compensate for sticky bottom */
        }
        /* Adjust sticky position for smaller screens if needed to avoid being hidden */
        @media (max-width: 768px) {
            .comment-box {
                bottom: -20px; /* Adjust for smaller popup padding */
                padding-bottom: 20px;
            }
        }


        .comment-box input {
            
            padding: 12px 18px; /* More padding */
            border: 1px solid #ccc;
            border-radius: 30px;
            font-size: 15px; /* Slightly larger font */
            background-color: #f9f9f9; /* Light input background */
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .comment-box input:focus {
            outline: none;
            border-color: #FE9042;
            box-shadow: 0 0 0 2px rgba(254, 144, 66, 0.2);
        }

        .comment-box button {
            padding: 12px 25px; /* More padding */
            border: none;
            border-radius: 30px;
            background: linear-gradient(to right, #465A31, #FE9042);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .comment-box button:hover {
            background: linear-gradient(to right, #3e4f28, #e2782e);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .popup-content {
                max-width: 98%;
                max-height: 98%;
                padding: 15px; /* Less padding on small screens */
                overflow:scroll;
            }
            .post-detail-inner {
                flex-direction: column;
                gap: 20px; /* Less gap on small screens */
                align-items: center; /* Center items when stacked */
                overflow-y: auto; /* Allow inner scrolling if content is too tall */
                padding-bottom: 60px; /* Make space for sticky comment box */
            }
            .image-section {
                width: 100%;
                height: 250px; /* Fixed height for image on small screens */
                min-height: unset;
            }
            .details-section {
                width: 100%;
                padding-bottom: 0; /* Reset */
            }
            .comments-container {
                max-height: 200px; /* Smaller max-height for comments on small screens */
                margin-bottom: 10px;
            }
            .comment-box {
                flex-direction: column;
                bottom: -15px; /* Adjust sticky position */
                padding-bottom: 15px;
                        width: 320px;
    
            }
            .comment-box button {
                width: 100%;
            }
        }
    </style>
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
                // Use $search from above
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
                            $profile_photo = htmlspecialchars($user['profile_photo'] ?: 'default_profile.png');
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
                    if (isset($stmt)) $stmt->close(); // Close statement if it was prepared
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
                        $sql = "SELECT posts.*, users.username 
                                 FROM posts 
                                 JOIN users ON posts.user_id = users.id 
                                 ORDER BY posts.created_at DESC";
                        $result = $conn->query($sql);
                    }

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $photo_url = htmlspecialchars($row['photo_url'] ?: 'demo.jpg');
                            $caption = htmlspecialchars($row['caption']);
                            $title = htmlspecialchars($row['title']);
                            $username = htmlspecialchars($row['username']);
                            $post_id_current_grid = htmlspecialchars($row['id']); // Get post ID for the current grid item
                            ?>
                            <div class="image-card" onclick="openPostPopup(<?php echo $post_id_current_grid; ?>)" style="padding-bottom:300px">
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
                    if (isset($stmt)) $stmt->close(); // Close statement if it was prepared
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

    // --- Popup JavaScript Functions (MODIFIED FOR SAME-PAGE AJAX) ---
    async function openPostPopup(postId) {
        document.getElementById("myPostPopup").style.display = "flex";
        const popupContentInner = document.getElementById("popupContentInner");
        // ONLY show "Loading post..." when the popup is being opened and content is fetched
        popupContentInner.innerHTML = '<p style="text-align: center; color: #555;">Loading post...</p>';

        try {
            // Fetch post details from home_page.php with action=get_post_details
            const response = await fetch(`home_page.php?action=get_post_details&post_id=${postId}`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            const data = await response.text();
            popupContentInner.innerHTML = data;

            // Reattach event listeners for the comment form if it's dynamically loaded
            const commentForm = popupContentInner.querySelector('.comment-box');
            if (commentForm) {
                commentForm.addEventListener('submit', async function(event) {
                    event.preventDefault(); // Prevent default form submission
                    const formData = new FormData(this);
                    // The 'action' hidden input is already part of the form, so no need to append it again.

                    const commentText = formData.get('comment'); // Get the comment text for client-side validation
                    if (!commentText.trim()) {
                        alert("Comment cannot be empty!");
                        return;
                    }

                    // Post comment to home_page.php with action=post_comment
                    const commentResponse = await fetch('home_page.php', { // Target home_page.php directly
                        method: 'POST',
                        body: formData
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
                        const errorText = await commentResponse.text(); // Get the detailed error message from PHP
                        console.error('Error submitting comment:', errorText);
                        alert('Error submitting comment. Server said: ' + errorText.substring(0, 200) + '...'); // Show detailed error
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
        // When closing, clear the content to prevent old content from showing briefly next time
        document.getElementById("popupContentInner").innerHTML = ''; 
    }

    // Optional: Close popup when clicking outside the content
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