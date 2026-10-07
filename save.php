<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$price = trim($_POST['price'] ?? '0');
$stock = (int)($_POST['stock'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($name === '' || $category === '') {
    header('Location: add.php?error=1');
    exit;
}

$stmt = $conn->prepare('INSERT INTO medicines (name, category, price, stock, description) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('ssdis', $name, $category, $price, $stock, $description);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: afficher.php?success=1');
exit;
