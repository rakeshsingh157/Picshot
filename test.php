<?php
session_start();

// ✅ Database config
$servername = "sql12.freesqldatabase.com";
$username = "sql12777439";
$password = "nmMjJrQPE9";
$dbname = "sql12777439";

// ✅ Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ✅ imgbb Upload Function
function uploadToImgBB($file) {
    $apiKey = "8f23d9f5d1b5960647ba5942af8a1523";
    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    // Generate a unique filename by appending timestamp or unique string
    $uniqueName = time() . "_" . basename($file['name']);
    
    // Create an image upload API request
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

// ✅ Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: test.php");
    exit();
}

// ✅ Handle Signup
if (isset($_POST['signup'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $username, $password);
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        header("Location: test.php");
        exit();
    } else {
        $error = "Signup failed: " . $stmt->error;
    }
    $stmt->close();
}

// ✅ Handle Login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: test.php");
            exit();
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}

// ✅ Upload Profile / Cover / Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload']) && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $profilePhoto = $coverPhoto = $postPhoto = null;

    // Handle Profile Photo
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $profilePhoto = uploadToImgBB($_FILES['profile_photo']);
        // Insert new profile photo entry (do not update)
        $stmt = $conn->prepare("INSERT INTO user_photos (user_id, photo_type, photo_url) VALUES (?, 'profile', ?)");
        $stmt->bind_param("is", $uid, $profilePhoto);
        $stmt->execute();
        $stmt->close();
    }

    // Handle Cover Photo
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === 0) {
        $coverPhoto = uploadToImgBB($_FILES['cover_photo']);
        // Insert new cover photo entry (do not update)
        $stmt = $conn->prepare("INSERT INTO user_photos (user_id, photo_type, photo_url) VALUES (?, 'cover', ?)");
        $stmt->bind_param("is", $uid, $coverPhoto);
        $stmt->execute();
        $stmt->close();
    }

    // Handle Post Photo (optional)
    if (isset($_FILES['post_photo']) && $_FILES['post_photo']['error'] === 0) {
        $postPhoto = uploadToImgBB($_FILES['post_photo']);
        $caption = $_POST['caption'] ?? "";
        // Insert new post photo with caption
        $stmt = $conn->prepare("INSERT INTO posts (user_id, photo_url, caption) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $uid, $postPhoto, $caption);
        $stmt->execute();
        $stmt->close();
    }
}

// ✅ Fetch User Info
$userData = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $uid");
    if ($result && $result->num_rows > 0) $userData = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>📸 PicShot Web App</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        form { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; }
        img { max-width: 300px; display: block; margin: 10px 0; }
        .error { color: red; }
        .logout { float: right; }
    </style>
</head>
<body>

<h1>📸 PicShot Web App</h1>

<?php if (isset($_SESSION['user_id']) && $userData): ?>
    <div class="logout">
        <a href="?logout=true">Logout</a>
    </div>

    <h2>Welcome, <?= htmlspecialchars($userData['name']) ?>!</h2>

    <h3>Your Profile</h3>
    <?php
    // Fetch all profile photos
    $profilePhotos = [];
    $result = $conn->query("SELECT * FROM user_photos WHERE user_id = $uid AND photo_type = 'profile' ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $profilePhotos[] = $row['photo_url'];
    }

    if (count($profilePhotos) > 0) {
        foreach ($profilePhotos as $photo) {
            echo "<img src=\"$photo\">";
        }
    } else {
        echo "<p>No profile photos yet.</p>";
    }

    // Fetch all cover photos
    $coverPhotos = [];
    $result = $conn->query("SELECT * FROM user_photos WHERE user_id = $uid AND photo_type = 'cover' ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $coverPhotos[] = $row['photo_url'];
    }

    if (count($coverPhotos) > 0) {
        foreach ($coverPhotos as $photo) {
            echo "<img src=\"$photo\">";
        }
    } else {
        echo "<p>No cover photos yet.</p>";
    }
    ?>

    <form method="POST" enctype="multipart/form-data">
        <h3>Upload Photos</h3>
        <input type="file" name="profile_photo"> Profile Photo<br><br>
        <input type="file" name="cover_photo"> Cover Photo<br><br>
        <input type="file" name="post_photo"> Post Photo (optional)<br><br>
        <input type="text" name="caption" placeholder="Post Caption (optional)"><br><br>
        <button type="submit" name="upload">Upload</button>
    </form>

    <h3>Your Posts</h3>
    <?php
    $posts = $conn->query("SELECT * FROM posts WHERE user_id = $uid ORDER BY created_at DESC");
    if ($posts && $posts->num_rows > 0):
        while ($post = $posts->fetch_assoc()):
    ?>
        <div>
            <img src="<?= $post['photo_url'] ?>" alt="Post">
            <p><strong>Caption:</strong> <?= htmlspecialchars($post['caption']) ?></p>
            <hr>
        </div>
    <?php endwhile; else: ?>
        <p>No posts uploaded yet.</p>
    <?php endif; ?>

<?php else: ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <h2>Login</h2>
        Email: <input type="email" name="email" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>

    <form method="POST">
        <h2>Signup</h2>
        Name: <input type="text" name="name" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Username: <input type="text" name="username" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit" name="signup">Signup</button>
    </form>
<?php endif; ?>

</body>
</html>
