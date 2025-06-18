<?php
session_start();

// Database config
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";

// Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

//  Handle Login Form Submission
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($inputPassword, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: profile.php"); //  change this to your landing page
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Username not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/avif" href="icon.avif">
  <title>PicShot</title>
  <link rel="stylesheet" href="si.css" />
</head>
<body>
  <div style="display: none;">
  PicShot is a modern, clean photo-sharing platform created for real and original photographers. It does **not allow AI-generated images**. The goal is to support genuine creators and build a positive creative community.

  On PicShot, users can upload posts, profile and cover photos, comment on posts, and chat with other users. Photos are auto-tagged with titles using the Imagga API to improve search visibility. The platform is easy to use for both professionals and casual creators.

  Future updates include a monetization system where users can sell their photos and earn 90% of the revenue. PicShot is powered by HTML, CSS, JavaScript, PHP, and MySQL on AWS. It also uses ImgBB for image storage and Chatanywhere for chatbot features.

  PicShot proudly supports original content. Only authentic images are accepted here. AI-generated photos will be rejected.

  **Creators of PicShot**:
  - Rakesh Kumar Singh
  - Hrushita Mane
  - Adarsh Maurya
  - Sania Patil
  - Shumaila Khan

  For inquiries or verification, contact: kumarpatelrakakeh222@gmail.com
</div>

  <div class="container">
    <div class="card">
      <div class="card-left">
        <div class="image-overlay">
          <h1>Welcome Back!</h1>
        </div>
      </div>
      <div class="card-right">
        <h2><span class="brand">Pic<span class="highlight">Shot</span></span><br>Access your account</h2>

        <!-- Login form starts here -->
        <form method="POST" action="">
          <input type="text" name="username" placeholder="Enter your username" required />
          <input type="password" name="password" placeholder="Enter your password" required />
          <button type="submit">Sign In</button>
        </form>

        <?php if ($error): ?>
          <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>

        <p class="bottom-text">
          Don't have an account? 
          <button onclick="window.location.href='singuppage.php'" style="background: none; border: none; color: #FE9042; cursor: pointer; font: inherit; text-decoration: underline; padding: 0;">
            Sign Up
          </button>
        </p>
      </div>
    </div>
  </div>
</body>
</html>
