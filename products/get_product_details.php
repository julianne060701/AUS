<?php
session_start();
header('Content-Type: application/json');
require_once '../config/conn.php';

try {
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id <= 0) {
		echo json_encode([ 'success' => false, 'message' => 'Invalid product id' ]);
		exit;
	}

	$stmt = $conn->prepare("
		SELECT id, product_name, serial_number, brand_id, category_id, capacity, buying_price, selling_price, quantity
		FROM products
		WHERE id = ?
		LIMIT 1
	");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$product = $result->fetch_assoc();

	if (!$product) {
		echo json_encode([ 'success' => false, 'message' => 'Product not found' ]);
		exit;
	}

	echo json_encode([ 'success' => true, 'product' => $product ]);
} catch (Throwable $e) {
	echo json_encode([ 'success' => false, 'message' => 'Server error' ]);
}

?>

