<?php
require_once 'config.php';

// Only allow locally (optional)
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '::1') {
    // comment this if hosting
    // die("Disabled on public hosting. Run locally then delete this file.");
}

$username = 'admin';
$password = 'Admin@12345'; // CHANGE THIS

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin (username, password_hash, role) VALUES (?, ?, 'admin')
                        ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)");
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "Admin created/updated! Username: {$username} | Password: {$password} <br><b>DELETE setup_admin.php now.</b>";
} else {
    echo "Error: " . $conn->error;
}

