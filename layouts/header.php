<?php
// header.php

// Include the database configuration file
require_once 'auth/db_config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if($page === 'dashboard') {
    $numPage = 1;
}
elseif($page === 'budget') {
    $numPage = 2;
}
elseif($page === 'expenses') {
    $numPage = 3;
}
elseif($page === 'saving') {
    $numPage = 4;
}
else {
    $numPage = 1;
}

// Fetch data from the database
$sql = "SELECT name, description FROM website_desc WHERE id = " . $numPage;
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $website_name = $row['name'];
    $website_desc = $row['description'];
} else {
    $website_name = "Default Name";
    $website_desc = "Default Description";
}

// Close the database connection
$conn->close();
