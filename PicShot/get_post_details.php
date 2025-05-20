<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p>Please log in to view post details.</p>";
    exit;
}

// DB Connection
$host = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$user = "admin";
$pass = "DBpicshot";
$db   = "Photostore";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "<p>Database connection failed: " . $conn->connect_error . "</p>";
    exit;
}

$post_id = $_GET['post_id'] ?? null;
if (!$post_id) {
    echo "<p>Post ID is required!</p>";
    exit;
}

// Fetch post
$sql = "SELECT p.*, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    echo "<p>Post not found!</p>";
    exit;
}

// Fetch comments
$sql_c = "SELECT c.comment, c.created_at, u.username
          FROM comments c
          JOIN users u ON c.user_id = u.id
          WHERE c.post_id = ?
          ORDER BY c.created_at DESC";
$stmt_c = $conn->prepare($sql_c);
$stmt_c->bind_param("i", $post_id);
$stmt_c->execute();
$comments = $stmt_c->get_result();
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

            <form method="POST" class="comment-box" action="post_comment.php">
                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_id); ?>">
                <input type="text" name="comment" placeholder="What do you think?" required>
                <button type="submit">Send</button>
            </form>
        </div>
    </div>
</div>

<?php
$stmt->close();
$stmt_c->close();
$conn->close();
?>