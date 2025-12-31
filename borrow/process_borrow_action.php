<?php
session_start();
include '../config/conn.php';

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_SESSION['user_id'];
    $request_id = intval($_POST['request_id']);
    $action_type = $_POST['action_type'];
    $admin_notes = !empty($_POST['admin_notes']) ? mysqli_real_escape_string($conn, $_POST['admin_notes']) : null;
    $action_date = date('Y-m-d H:i:s');

    // Get the borrow request details
    $request_sql = "SELECT * FROM borrow_requests WHERE id = ?";
    $stmt = $conn->prepare($request_sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request_result = $stmt->get_result();
    
    if ($request_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Borrow request not found']);
        exit();
    }
    
    $request = $request_result->fetch_assoc();
    
    // Check if request is already processed
    if ($request['status'] != 'pending' && $action_type != 'return') {
        echo json_encode(['success' => false, 'message' => 'Request has already been processed']);
        exit();
    }
    
    if ($request['status'] != 'approved' && $action_type == 'return') {
        echo json_encode(['success' => false, 'message' => 'Only approved requests can be marked as returned']);
        exit();
    }

    // Process the action
    if ($action_type == 'approve') {
        // Check product availability only if product_id exists
        if (!empty($request['product_id'])) {
            $product_sql = "SELECT quantity FROM products WHERE id = ?";
            $stmt = $conn->prepare($product_sql);
            $stmt->bind_param("i", $request['product_id']);
            $stmt->execute();
            $product_result = $stmt->get_result();
            
            if ($product_result->num_rows > 0) {
                $product = $product_result->fetch_assoc();
                
                // Check how many are currently borrowed
                $borrowed_sql = "SELECT COALESCE(SUM(quantity), 0) as borrowed_qty 
                                 FROM borrow_requests 
                                 WHERE product_id = ? AND status IN ('approved', 'pending') AND id != ?";
                $stmt = $conn->prepare($borrowed_sql);
                $stmt->bind_param("ii", $request['product_id'], $request_id);
                $stmt->execute();
                $borrowed_result = $stmt->get_result();
                $borrowed_data = $borrowed_result->fetch_assoc();
                $available = $product['quantity'] - $borrowed_data['borrowed_qty'];
                
                if ($request['quantity'] > $available) {
                    echo json_encode(['success' => false, 'message' => "Only $available items available"]);
                    exit();
                }
            }
        }
        
        // Update request status
        $update_sql = "UPDATE borrow_requests 
                       SET status = 'approved', 
                           admin_id = ?, 
                           action_date = ?, 
                           admin_notes = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("issi", $admin_id, $action_date, $admin_notes, $request_id);
        
    } elseif ($action_type == 'decline') {
        // Update request status
        $update_sql = "UPDATE borrow_requests 
                       SET status = 'declined', 
                           admin_id = ?, 
                           action_date = ?, 
                           admin_notes = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("issi", $admin_id, $action_date, $admin_notes, $request_id);
        
    } elseif ($action_type == 'return') {
        // Update request status
        $return_date = date('Y-m-d H:i:s');
        $update_sql = "UPDATE borrow_requests 
                       SET status = 'returned', 
                           return_date = ?,
                           admin_notes = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssi", $return_date, $admin_notes, $request_id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action type']);
        exit();
    }
    
    if ($stmt->execute()) {
        $action_text = ucfirst($action_type) . ($action_type == 'return' ? 'ed' : 'd');
        echo json_encode(['success' => true, 'message' => "Request $action_text successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to process action: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

