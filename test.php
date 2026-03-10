<?php
// Run this once to create admin user
$hashed_password = password_hash('12345678', PASSWORD_DEFAULT);
echo "Hashed password: " . $hashed_password;
// Insert this hash into users table
?>