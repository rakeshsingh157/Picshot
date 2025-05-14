<?php
session_start();

// 1. DATABASE CONFIGURATION
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$dbUsername = "admin";
$dbPassword = "DBpicshot";
$dbname     = "Photostore";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// 2. USER SESSION (लॉगिन सिस्टम के हिसाब से बदल सकते हैं)
if (!isset($_SESSION['user_id'])) {
    // उदाहरण के लिए user_id = 5
    $_SESSION['user_id'] = 5;
}
$userId = $_SESSION['user_id'];

// 3. HANDLE FORM SUBMISSION: जब AJAX से डेटा आएगा
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['imageUrl'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $photoUrl    = $_POST['imageUrl'];

    $stmt = $conn->prepare("
        INSERT INTO posts (user_id, title, caption, photo_url)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $userId, $title, $description, $photoUrl);

    if ($stmt->execute()) {
        echo 'SUCCESS';
    } else {
        echo 'ERROR: ' . $stmt->error;
    }
    $stmt->close();
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create New Post</title>
  <link rel="stylesheet" href="post.css" />
  <style>
    /* Loader Styles */
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
    @keyframes spin { 0% {transform: rotate(0deg);} 100% {transform: rotate(360deg);} }
  </style>
</head>
<body>
  <div class="container">
    <h2>Create New Post</h2>
    <div class="form-container">
      <div class="upload-box" onclick="fileInput.click()">
        <img id="previewImg" alt="" style="max-width: 100%; display: none;" />
        <p id="placeholder">Choose a file to upload</p>
        <input type="file" id="fileInput" hidden onchange="handleImageUpload(event)">
      </div>

      <div class="form-fields">
        <label>Title</label>
        <input type="text" id="title" placeholder="Title will appear here" readonly />
        <label>Description</label>
        <textarea id="description" placeholder="Enter the description"></textarea>
        <button onclick="submitPost()">Post</button>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loader"><div></div></div>

  <script>
    const IMGBB_API_KEY = "8f23d9f5d1b5960647ba5942af8a1523";
    const IMAGGA_KEY    = "acc_7300facc9d3b521";
    const IMAGGA_SECRET = "f127d8a250041a77a10d8c1e2ad78ccc";

    const loader     = document.getElementById('loader');
    const fileInput  = document.getElementById('fileInput');
    const previewImg = document.getElementById('previewImg');
    const placeholder= document.getElementById('placeholder');
    let latestImageUrl = "";

    function handleImageUpload(event) {
      const file = event.target.files[0];
      if (!file || !file.type.startsWith('image/')) return;

      // Preview
      const reader = new FileReader();
      reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);

      uploadToImgbb(file);
    }

    async function uploadToImgbb(file) {
      loader.style.display = 'flex';
      const formData = new FormData();
      formData.append("image", file);
      try {
        const res  = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`, {
          method: "POST", body: formData
        });
        const data = await res.json();
        latestImageUrl = data.data.url;
        await getTagsFromImagga(latestImageUrl);
      } catch (err) {
        console.error("Upload to imgbb failed:", err);
      } finally {
        loader.style.display = 'none';
      }
    }

    async function getTagsFromImagga(imageUrl) {
      loader.style.display = 'flex';
      const auth = btoa(IMAGGA_KEY + ":" + IMAGGA_SECRET);
      try {
        const res    = await fetch(`https://api.imagga.com/v2/tags?image_url=${encodeURIComponent(imageUrl)}`, {
          headers: { "Authorization": "Basic " + auth }
        });
        const result = await res.json();
        if (result.result && result.result.tags) {
          const tags = result.result.tags
            .filter(t => t.confidence > 50)
            .map(t => t.tag.en);
          document.getElementById("title").value = tags.join(", ") || "No title found";
        }
      } catch (err) {
        console.error("Imagga error:", err);
      } finally {
        loader.style.display = 'none';
      }
    }

    function submitPost() {
      const title       = document.getElementById('title').value;
      const description = document.getElementById('description').value;
      const imageUrl    = latestImageUrl;

      if (!title || !description || !imageUrl) {
        alert("Please complete all fields and wait for upload/tagging.");
        return;
      }

      loader.style.display = 'flex';
      fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          title: title,
          description: description,
          imageUrl: imageUrl
        })
      })
      .then(res => res.text())
      .then(text => {
        loader.style.display = 'none';
        if (text.trim() === "SUCCESS") {
          alert("Post added!");
          window.location.reload();
        } else {
          alert(text);
        }
      })
      .catch(err => {
        loader.style.display = 'none';
        console.error("DB insert error:", err);
      });
    }
  </script>
</body>
</html>
