<?php
session_start();

$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$dbUsername = "admin";
$dbPassword = "DBpicshot";
$dbname     = "Photostore";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Set a default user_id if not set in session (for testing purposes)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 5;
}
$userId = $_SESSION['user_id'];

// --- Sightengine API Credentials (Replace with your actual keys) ---
define('SIGHTENGINE_API_USER', '197575865'); // Replace with your Sightengine API User
define('SIGHTENGINE_API_SECRET', 'fEDX6bKrLqRS8GZPHydS8XJQb55Dk9Sr'); // Replace with your Sightengine API Secret
// ------------------------------------------------------------------

// Handle post submission or image upload for AI detection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['imageUrl'])) {
        // This block handles the final post submission after image processing
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
    } elseif (isset($_FILES['imageFile'])) {
        // This block handles the initial image upload for AI detection
        $file = $_FILES['imageFile'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'ERROR', 'message' => 'File upload error: ' . $file['error']]);
            exit;
        }

        // --- START ADDED CODE FOR FILE TYPE VALIDATION ---
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Invalid file type. Only JPG, PNG, and WebP images are allowed.']);
            exit;
        }
        // --- END ADDED CODE ---

        $imageContent = file_get_contents($file['tmp_name']);
        if ($imageContent === false) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Failed to read uploaded image content.']);
            exit;
        }

        // Perform AI generation check using Sightengine
        $is_ai_generated    = false;
        $ai_detection_error = '';

        if (SIGHTENGINE_API_USER === 'YOUR_SIGHTENGINE_API_USER' || SIGHTENGINE_API_SECRET === 'YOUR_SIGHTENGINE_API_SECRET') {
            $ai_detection_error = 'Sightengine API credentials are not set. Please replace placeholders with your actual keys.';
            $is_ai_generated = false;
        } else {
            $cfile = curl_file_create($file['tmp_name'], $file['type'], $file['name']);

            $params = array(
                'media'      => $cfile,
                'models'     => 'genai',
                'api_user'   => SIGHTENGINE_API_USER,
                'api_secret' => SIGHTENGINE_API_SECRET,
            );

            $ch = curl_init('https://api.sightengine.com/1.0/check.json');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            $response   = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curl_error) {
                $ai_detection_error = 'cURL error during Sightengine check: ' . $curl_error;
            } elseif ($http_code !== 200) {
                $ai_detection_error = 'Sightengine API returned HTTP error code: ' . $http_code;
                $output = json_decode($response, true);
                if (isset($output['error']['message'])) {
                    $ai_detection_error .= ' - ' . $output['error']['message'];
                }
            } else {
                $output = json_decode($response, true);

                if (isset($output['status']) && $output['status'] === 'success' && isset($output['type']['ai_generated'])) {
                    if ($output['type']['ai_generated'] > 0.5) { // Adjust confidence threshold as needed
                        $is_ai_generated = true;
                    }
                } else {
                    $ai_detection_error = 'Sightengine API response error: ' . (isset($output['error']['message']) ? $output['error']['message'] : 'Unexpected response format or missing "type.ai_generated" field.');
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'status'          => 'SUCCESS',
            'is_ai_generated' => $is_ai_generated,
            'ai_detection_error' => $ai_detection_error
        ]);
        exit;
    }
}

$conn->close();
?>

<?php include "sidebar.html";?>

<!DOCTYPE html>
<html lang="en">
<head>
     <title>PicShot</title>
  <link rel="icon" type="image/avif" href="icon.avif">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <link rel="stylesheet" href="post.css" />
    <style>
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
        button:not(.an-btn){
            margin-top: 20px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(to right, #3b5323, #ffa500);
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        /* Style for disabled button */
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }
        @media (max-width: 768px) {
            button:not(.an-btn){
                margin-top: 20px;
                padding: 10px 20px;
                border: none;
                border-radius: 10px;
                background: linear-gradient(to right, #3b5323, #ffa500);
                color: white;
                font-size: 16px;
                cursor: pointer;
                margin-bottom:40px
            }
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
                <button id="postButton" onclick="submitPost()">Post</button>
                <p id="aiWarning" style="color: red; display: none; margin-top: 10px; font-weight: bold;">
                    This image appears to be AI-generated. Posting is disabled.
                </p>
                <p id="aiError" style="color: orange; display: none; margin-top: 5px; font-size: 0.9em;">
                    AI detection encountered an issue.
                </p>
            </div>
        </div>
    </div>

    <div id="loader"><div></div></div>

    <script>
        const IMGBB_API_KEY = "8f23d9f5d1b5960647ba5942af8a1523";
        const IMAGGA_KEY    = "acc_7300facc9d3b521";
        const IMAGGA_SECRET = "f127d8a250041a77a10d8c1e2ad78ccc";

        const loader      = document.getElementById('loader');
        const fileInput   = document.getElementById('fileInput');
        const previewImg  = document.getElementById('previewImg');
        const placeholder = document.getElementById('placeholder');
        const postButton  = document.getElementById('postButton');
        const aiWarning   = document.getElementById('aiWarning');
        const aiError     = document.getElementById('aiError');
        let latestImageUrl = "";
        let isAiGenerated = false;

        function resetUI() {
            postButton.disabled = true;
            aiWarning.style.display = 'none';
            aiError.style.display = 'none';
            document.getElementById("title").value = '';
            document.getElementById("description").value = '';
            previewImg.style.display = 'none';
            placeholder.style.display = 'block';
            latestImageUrl = "";
            isAiGenerated = false;
        }

        async function handleImageUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // --- START ADDED CLIENT-SIDE FILE TYPE CHECK ---
            const allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedFileTypes.includes(file.type)) {
                alert('Invalid file type. Only JPG, PNG, and WebP images are allowed.');
                fileInput.value = ''; // Clear the selected file
                return;
            }
            // --- END ADDED CLIENT-SIDE FILE TYPE CHECK ---


            resetUI();

            // Preview
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);

            loader.style.display = 'flex';

            // Step 1: Send image file directly to your PHP for AI detection
            const formDataForPHP = new FormData();
            formDataForPHP.append("imageFile", file);

            try {
                const response = await fetch("", { // Post to the same PHP file
                    method: "POST",
                    body: formDataForPHP
                });
                const result = await response.json(); // Expecting JSON response

                if (result.status === 'SUCCESS') {
                    if (result.is_ai_generated) {
                        isAiGenerated = true;
                        aiWarning.style.display = 'block';
                        postButton.disabled = true;
                    } else {
                        // If not AI generated, proceed with ImgBB upload and Imagga tagging
                        await uploadToImgbb(file);
                    }
                    if (result.ai_detection_error) {
                        aiError.textContent = `AI Detection Error: ${result.ai_detection_error}`;
                        aiError.style.display = 'block';
                        // Keep post button disabled if AI detection itself failed
                        postButton.disabled = true;
                    }
                } else {
                    console.error("Server upload for AI check failed:", result.message);
                    aiError.textContent = `Image processing error: ${result.message}`;
                    aiError.style.display = 'block';
                    postButton.disabled = true;
                }
            } catch (err) {
                console.error("Error during image upload to server for AI check:", err);
                aiError.textContent = `Network error during AI check: ${err.message}`;
                aiError.style.display = 'block';
                postButton.disabled = true;
            } finally {
                loader.style.display = 'none';
            }
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
                if (data.data && data.data.url) {
                    latestImageUrl = data.data.url;
                    await getTagsFromImagga(latestImageUrl);
                    // Only enable if not AI-generated and other steps passed
                    if (!isAiGenerated) {
                        postButton.disabled = false;
                    }
                } else {
                    console.error("ImgBB upload failed: No URL returned", data);
                    alert("Image upload to ImgBB failed. Please try again.");
                    postButton.disabled = true;
                }
            } catch (err) {
                console.error("Upload to imgbb failed:", err);
                alert("Failed to upload image to ImgBB. Please check your connection.");
                postButton.disabled = true;
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
                } else {
                       console.warn("Imagga did not return tags or encountered an error:", result);
                       document.getElementById("title").value = "Could not generate title";
                }
            } catch (err) {
                console.error("Imagga error:", err);
                document.getElementById("title").value = "Error getting title";
            } finally {
                loader.style.display = 'none';
            }
        }

        function submitPost() {
            if (isAiGenerated) {
                alert("Cannot post AI-generated images.");
                return;
            }

            const title       = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const imageUrl    = latestImageUrl;

            if (!title || !description || !imageUrl) {
                alert("Please complete all fields and ensure the image has been processed.");
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
                    alert("Post added successfully!");
                    window.location.reload(); // Reload to clear the form
                } else {
                    alert("Error adding post: " + text);
                }
            })
            .catch(err => {
                loader.style.display = 'none';
                console.error("DB insert error:", err);
                alert("An error occurred while posting: " + err.message);
            });
        }
    </script>
</body>
</html>