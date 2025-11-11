<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit(); 
}

include '../config/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['installer_name'])) {
    echo json_encode(['error' => 'Installer name is required']);
    exit();
}

$installer_name = $conn->real_escape_string($_GET['installer_name']);

// Apply date filter if provided
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'overall';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$date_condition = "";
$params = [$installer_name];
$param_types = "s";

switch($filter) {
    case 'today':
        $date_condition = "AND DATE(schedule_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "AND schedule_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "AND MONTH(schedule_date) = MONTH(CURDATE()) AND YEAR(schedule_date) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "AND YEAR(schedule_date) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($start_date && $end_date) {
            $date_condition = "AND schedule_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $param_types .= "ss";
        }
        break;
    case 'overall':
    default:
        $date_condition = "";
        break;
}

$query = "SELECT 
    sched.id, sched.installer_name, sched.customer_name, sched.contact_number, sched.address, 
    sched.schedule_date, sched.schedule_time, sched.service_type, sched.products_to_install, sched.quantity_to_install,
    sched.notes, sched.status, sched.cancel_note, sched.completed_at, sched.created_at,
    COALESCE(p.product_name, sched.products_to_install) as product_name
FROM installer_schedules sched
LEFT JOIN products p ON sched.products_to_install IS NOT NULL 
    AND sched.products_to_install != '' 
    AND sched.products_to_install REGEXP '^[0-9]+$' 
    AND CAST(sched.products_to_install AS UNSIGNED) = p.id
WHERE sched.installer_name = ? AND (sched.status = 'Completed' OR sched.status = 'Cancelled') 
$date_condition
ORDER BY sched.completed_at DESC, sched.schedule_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param($param_types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = [
        'id' => $row['id'],
        'installer_name' => $row['installer_name'],
        'customer_name' => $row['customer_name'],
        'contact_number' => $row['contact_number'],
        'address' => $row['address'],
        'schedule_date' => $row['schedule_date'],
        'schedule_time' => $row['schedule_time'],
        'service_type' => $row['service_type'],
        'products_to_install' => $row['products_to_install'],
        'product_name' => $row['product_name'],
        'quantity_to_install' => $row['quantity_to_install'],
        'notes' => $row['notes'],
        'status' => $row['status'],
        'cancel_note' => $row['cancel_note'],
        'completed_at' => $row['completed_at'],
        'created_at' => $row['created_at']
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'installer_name' => $installer_name,
    'total' => count($schedules),
    'schedules' => $schedules
]);
?>

