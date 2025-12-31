<?php
session_start();
require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: inventory.php');
	exit();
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$addQuantity = isset($_POST['add_quantity']) ? intval($_POST['add_quantity']) : 0;

if ($productId <= 0 || $addQuantity <= 0) {
	$_SESSION['error'] = 'Invalid product or quantity.';
	header('Location: inventory.php');
	exit();
}

$stmt = $conn->prepare("UPDATE products SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('ii', $addQuantity, $productId);

if ($stmt->execute()) {
	if ($stmt->affected_rows > 0) {
		$_SESSION['success'] = 'Stock updated successfully.';
	} else {
		$_SESSION['info'] = 'No changes were made.';
	}
} else {
	$_SESSION['error'] = 'Failed to update stock: ' . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: inventory.php');
exit();
?>
<?php
session_start();
require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: product.php');
	exit();
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$addQuantity = isset($_POST['add_quantity']) ? intval($_POST['add_quantity']) : 0;

if ($productId <= 0 || $addQuantity <= 0) {
	$_SESSION['error'] = 'Invalid product or quantity.';
	header('Location: product.php');
	exit();
}

// Update quantity and updated_at
$stmt = $conn->prepare("UPDATE products SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('ii', $addQuantity, $productId);

if ($stmt->execute()) {
	if ($stmt->affected_rows > 0) {
		$_SESSION['success'] = 'Stock updated successfully.';
	} else {
		$_SESSION['info'] = 'No changes were made.';
	}
} else {
	$_SESSION['error'] = 'Failed to update stock: ' . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: product.php');
exit();
?>

