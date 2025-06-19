<?php
session_start();

// Database configuration
$servername = "database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "DBpicshot";
$dbname = "Photostore";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
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
            header("Location: profile.php");
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Username not found.";
    }
    $stmt->close();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Primary Meta Tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PicShot - Authentic Photo Sharing Platform | No AI-Generated Images</title>
    <meta name="description" content="PicShot is a clean, simple photo-sharing platform where original creators share real moments. We do not allow AI-generated images - our mission is to support authentic creativity." />
    <meta name="keywords" content="PicShot, photo sharing, photography platform, authentic photos, no AI images, sell photos online, Imagga API, ImgBB, ChatAnywhere, photography community" />
    <meta name="author" content="Rakesh Kumar Singh, Hrushita Mane, Adarsh Maurya, Sania Patil, Shumaila Khan" />
    <meta name="robots" content="index, follow" />
    <link rel="icon" type="image/avif" href="icon.avif">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://picshot.example.com/" />
    <meta property="og:title" content="PicShot - Authentic Photo Sharing Platform | No AI-Generated Images" />
    <meta property="og:description" content="Share real moments on PicShot - a photo-sharing platform that supports original creators by disallowing AI-generated images." />
    <meta property="og:image" content="https://picshot.example.com/images/picshot-social.jpg" />
    <meta property="og:image:alt" content="PicShot - Share Authentic Photos" />

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="PicShot - Authentic Photo Sharing Platform | No AI-Generated Images" />
    <meta name="twitter:description" content="Share real moments on PicShot - a photo-sharing platform that supports original creators by disallowing AI-generated images." />
    <meta name="twitter:image" content="https://picshot.example.com/images/picshot-social.jpg" />

    <!-- Canonical URL -->
    <link rel="canonical" href="https://picshot.example.com/" />

    <!-- Structured Data for Search Engines -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "PicShot",
        "url": "https://picshot.example.com",
        "description": "Photo-sharing platform for authentic creators with no AI-generated images",
        "applicationCategory": "PhotographyApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "creator": [
            {
                "@type": "Person",
                "name": "Rakesh Kumar Singh",
                "sameAs": "https://example.com/rakesh"
            },
            {
                "@type": "Person",
                "name": "Hrushita Mane",
                "sameAs": "https://example.com/hrushita"
            },
            {
                "@type": "Person",
                "name": "Adarsh Maurya",
                "sameAs": "https://example.com/adarsh"
            },
            {
                "@type": "Person",
                "name": "Sania Patil",
                "sameAs": "https://example.com/sania"
            },
            {
                "@type": "Person",
                "name": "Shumaila Khan",
                "sameAs": "https://example.com/shumaila"
            }
        ],
        "keywords": ["photo sharing", "photography", "authentic photos", "no AI images", "photography community"],
        "sameAs": [
            "https://twitter.com/picshot",
            "https://instagram.com/picshot",
            "https://facebook.com/picshot"
        ]
    }
    </script>

    <link rel="stylesheet" href="si.css" />
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-left">
                <div class="image-overlay">
                    <h1>Welcome Back!</h1>
                </div>
            </div>
            <div class="card-right">
                <h2><span class="brand">Pic<span class="highlight">Shot</span></span><br>Access your account</h2>

                <!-- Login form -->
                <form method="POST" action="">
                    <input type="text" name="username" placeholder="Enter your username" required />
                    <input type="password" name="password" placeholder="Enter your password" required />
                    <button type="submit">Sign In</button>
                </form>

                <?php if ($error): ?>
                    <p class="error-message"><?= htmlspecialchars($error) ?></p>
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

    <!-- Hidden SEO content for search engines only -->
    <div class="seo-content" aria-hidden="true" style="display: none;">
        <h1>PicShot Photo Sharing Platform</h1>
        <p>PicShot is a clean, simple photo-sharing platform where original creators share real moments. We do not allow AI-generated images — our mission is to support authentic creativity.</p>
        
        <h2>About Our Platform</h2>
        <p>Photographers can upload profile, cover, and post photos, comment on others' posts, and chat with the community. Each uploaded photo gets an AI-generated title using the Imagga API to boost search visibility.</p>
        
        <p>We are working on a feature that allows creators to sell their photos directly. Uploaders will earn 90% of the payment, and PicShot will keep just 10% as commission.</p>
        
        <h2>Technologies Used</h2>
        <ul>
            <li>HTML, CSS, JavaScript, PHP, MySQL (hosted on AWS)</li>
            <li>ImgBB – for secure image storage</li>
            <li>ChatAnywhere – for real-time chatbot support</li>
            <li>Imagga – for automatic photo tagging and titles</li>
        </ul>
        
        <h2>Our Team</h2>
        <p>PicShot was created by a dedicated team of developers and designers:</p>
        <ul>
            <li>Rakesh Kumar Singh - Lead Developer</li>
            <li>Hrushita Mane - UI/UX Designer</li>
            <li>Adarsh Maurya - Backend Developer</li>
            <li>Sania Patil - Frontend Developer</li>
            <li>Shumaila Khan - Marketing Specialist</li>
        </ul>
        
        <p>For business inquiries, partnership opportunities, or verification requests, please contact us at: <a href="mailto:kumarpatelrakakeh222@gmail.com">kumarpatelrakakeh222@gmail.com</a></p>
    </div>
</body>
</html>