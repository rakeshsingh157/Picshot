<?php
$host = 'database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com';
$port = '3306';
$dbname = 'Photostore'; 
$username = 'admin';
$password = 'DBpicshot';    

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Connected";
?>
