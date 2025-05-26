<?php
// Start session
session_start();

// ✅ Database config
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$dbusername = "admin";
$dbpassword = "DBpicshot";
$dbname = "Photostore";

// ✅ Connect to DB
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ✅ Handle form submission
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "❌ Username or Email already exists.";
    } else {
        // ✅ Insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $name, $email, $username, $hashedPassword);

        if ($insert->execute()) {
            // Get the last inserted user_id to insert into the goldentik table
            $userId = $conn->insert_id;

            // ✅ Insert into goldentik table with false verification status
            $insertGoldentik = $conn->prepare("INSERT INTO goldentik (user_id, is_verified) VALUES (?, ?)");
            $isVerified = false; // Default to false
            $insertGoldentik->bind_param("ii", $userId, $isVerified);

            if ($insertGoldentik->execute()) {
                $success = "✅ Account created successfully! Redirecting to login...";
                header("refresh:2;url=index.php");
            } else {
                $error = "❌ Error adding to Goldentik table.";
            }

            $insertGoldentik->close();
        } else {
            $error = "❌ Error creating account.";
        }
        $insert->close();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/avif" href="icon.avif">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PicShot</title>
  <link rel="stylesheet" href="siup.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <!-- SIGN UP FORM ON LEFT -->
      <div class="card-left">
        <h2>
          <span class="brand">Pic<span class="highlight">Shot</span></span><br>Create your account
        </h2>

        <form method="POST" action="">
          <input type="text" name="name" placeholder="Enter your name" required />
          <input type="email" name="email" placeholder="Enter your email" required />
          <input type="text" name="username" placeholder="Choose a username" required />
          <input type="password" name="password" placeholder="Create a password" required />
          <button type="submit">Sign Up</button>
        </form>

        <!-- ✅ Show messages -->
        <?php if ($success): ?>
          <p style="color:green;"><?= $success ?></p>
        <?php elseif ($error): ?>
          <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>

        <p class="bottom-text">
          Already have an account?
          <button onclick="window.location.href='index.php'" style="background: none; border: none; color: #FE9042; cursor: pointer; font: inherit; text-decoration: underline; padding: 0;">
            Sign in
          </button>
        </p>
      </div>

      <!-- IMAGE ON RIGHT -->
      <div class="card-right">
        <div class="image-overlay">
          <h1>Join the Community!</h1>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
