<?php
$host = 'database-1.cav0my0c6v1m.us-east-1.rds.amazonaws.com';
$port = '3306';
$dbname = 'Photostore';  // <-- Replace this with your actual DB name
$username = 'admin';
$password = 'DBpicshot';     // <-- Replace with your actual password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully to AWS RDS MySQL!";
?>
