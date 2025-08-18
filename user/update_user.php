<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $full_name, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $full_name, $role, $id);
    }

    if ($stmt->execute()) {
        header("Location: user.php?updated=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
