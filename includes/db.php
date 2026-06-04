<?php
// db.php
// This file connects to our MySQL database.
// We include this file at the top of any page that needs the database.

// Database settings
$host     = 'localhost';    // where MySQL is running
$dbname   = 'art_store';    // our database name
$username = 'root';         // default XAMPP username
$password = '';             // default XAMPP password is empty

try {
    // PDO is PHP's way of talking to a database safely
    // We create a new PDO object with our settings
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Tell PDO to show us errors if something goes wrong
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tell PDO to return results as arrays with named keys like $row['name']
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If connection fails, stop the page and show the error
    die("Connection failed: " . $e->getMessage());
}
?>