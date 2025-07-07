<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Agar session mein user_id nahi hai, user ko login pe bhejo
    header("Location: index.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);


// 2. DB Connection
$host = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$user = "admin";
$pass = "DBpicshot";
$db   = "Photostore";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    if ($comment !== '') {
        $ins = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $post_id, $user_id, $comment);
        $ins->execute();
    }
}

// 4. Get post ID (URL param or default)
$post_id = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
if (!$post_id) {
    die("Post ID is required!");
}

// 5. Fetch post
$sql = "SELECT p.*, u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    die("Post not found!");
}

// 6. Fetch comments
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Post Detail</title>
    <link rel="icon" type="image/avif" href="icon.avif">
  <style>

    *{box-sizing:border-box;}
    body{margin:0;font-family:'Poppins',sans-serif;background:#eaeaea;color:#333;padding:40px 10px;}
    .outer-card{position:relative;max-width:1100px;margin:auto;background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:30px;}
    .container{display:flex;flex-wrap:wrap;gap:40px;align-items:flex-start;}
    .image-section{flex:1 1 400px;}
    .post-image{width:100%;max-width:500px;height:auto;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,0.15);margin-bottom:10px;}
    .details-section{flex:1 1 350px;}
    .details-section h2{margin-top:0;font-size:24px;}
    .username{color:#666;font-size:14px;margin:8px 0;}
    .description{font-size:16px;margin-top:12px;color:#444;}
      .comment-box {
      position: absolute; 
      bottom: 40px;
      display: flex;
      margin-top: 24px;
      gap: 10px;
    }

    .comment-box input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #ccc;
      border-radius: 30px;
      font-size: 14px;
      width: 400px;
    }

    .comment-box button {
      padding: 12px 20px;
      border: none;
      border-radius: 30px;
      background: linear-gradient(to right, #465A31, #FE9042);
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    .comment-box button:hover {
      background: linear-gradient(to right, #3e4f28, #e2782e);
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
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #ccc;
      border-radius: 30px;
      font-size: 14px;
      width: 280px;
    }

      .comment-box button {
        width: 100%;
      }
    }
    .comment-box button:hover{background:linear-gradient(to right,#3e4f28,#e2782e);}
    .comment{margin-top:15px;padding:10px;background:#f9f9f9;border-radius:5px;border:1px solid #ddd;}
    .comment p{margin:5px 0;color:#444;}
    .comment small{font-size:12px;color:#888;}
    /* Comments container ko fixed height aur scrollable banao */
.comments-container {
  max-height: 300px;       /* height adjust kar sakte ho as per design */
  overflow-y: auto;
  padding-right: 10px;     /* scrollbar ke liye thoda gap */
  margin-bottom: 20px;
}

/* Scrollbar styling optional */
.comments-container::-webkit-scrollbar {
  width: 6px;
}
.comments-container::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.2);
  border-radius: 3px;
}

    @media(max-width:768px){.container{flex-direction:column;align-items:center;} .comment-box{flex-direction:column;} .comment-box input{width:100%;}}
  </style>
</head>
<body>

  <div class="outer-card">
    <div class="container">
      <div class="image-section">
        <img class="post-image" src="<?php echo htmlspecialchars($post['photo_url']); ?>" alt="Post Image">
      </div>
      <div class="details-section">
        
        <p class="username">@<?php echo htmlspecialchars($post['username']); ?></p>
        <p class="description"><?php echo htmlspecialchars($post['caption']); ?></p>

        

        <!-- Comments List -->
       <!-- Comments List -->
<h3>Comments:</h3>
<div class="comments-container">
  <?php if ($comments->num_rows): ?>
    <?php while ($c = $comments->fetch_assoc()): ?>
      <div class="comment">
        <strong>@<?php echo htmlspecialchars($c['username']); ?>:</strong>
        <p><?php echo htmlspecialchars($c['comment']); ?></p>
        <small><?php echo $c['created_at']; ?></small>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No comments yet. Be the first to comment!</p>
  <?php endif; ?>
</div>

        <!-- Comment Form -->
        <form method="POST" class="comment-box">
          <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
          <input type="text" name="comment" placeholder="What do you think?" required>
          <button type="submit">Send</button>
        </form>
      </div>
    </div>
  </div>

</body>
</html>
