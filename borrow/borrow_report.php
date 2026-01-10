<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
} 
include '../config/conn.php'; 

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'overall';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$date_condition = "";
$params = [];
$param_types = "";

switch($filter) {
    case 'today':
        $date_condition = "WHERE DATE(br.borrow_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "WHERE br.borrow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "WHERE MONTH(br.borrow_date) = MONTH(CURDATE()) AND YEAR(br.borrow_date) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE YEAR(br.borrow_date) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($start_date && $end_date) {
            $date_condition = "WHERE br.borrow_date BETWEEN ? AND ?";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            $param_types = "ss";
        }
        break;
    case 'overall':
    default:
        $date_condition = "";
        break;
}

$summary_query = "SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN br.status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN br.status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN br.status = 'declined' THEN 1 END) as declined_count,
    COUNT(CASE WHEN br.status = 'returned' THEN 1 END) as returned_count,
    SUM(br.quantity) as total_items_borrowed,
    COUNT(DISTINCT br.employee_id) as unique_borrowers,
    COUNT(DISTINCT br.product_id) as unique_items
FROM borrow_requests br
$date_condition";

if(!empty($params)) {
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
} else {
    $result = $conn->query($summary_query);
    $summary = $result->fetch_assoc();
}

$borrow_query = "SELECT 
    br.id, br.quantity, br.borrow_date, br.expected_return_date, br.status,
    br.action_date, br.return_date, br.notes, br.admin_notes,
    COALESCE(p.product_name, br.item_name) as item_name,
    u.full_name as borrower_name,
    u.role as borrower_role,
    admin_u.full_name as admin_name
FROM borrow_requests br
LEFT JOIN products p ON br.product_id = p.id
LEFT JOIN users u ON br.employee_id = u.id
LEFT JOIN users admin_u ON br.admin_id = admin_u.id
$date_condition
ORDER BY br.borrow_date DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($borrow_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrow_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($borrow_query);
    $borrow_data = $result->fetch_all(MYSQLI_ASSOC);
}

$status_query = "SELECT 
    br.status, COUNT(*) as count, SUM(br.quantity) as total_quantity
FROM borrow_requests br
$date_condition
GROUP BY br.status
ORDER BY br.status";

if(!empty($params)) {
    $stmt = $conn->prepare($status_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $status_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($status_query);
    $status_data = $result->fetch_all(MYSQLI_ASSOC);
}

$borrower_query = "SELECT 
    u.full_name, u.role, COUNT(*) as request_count, SUM(br.quantity) as total_items
FROM borrow_requests br
LEFT JOIN users u ON br.employee_id = u.id
$date_condition
GROUP BY u.id, u.full_name, u.role
ORDER BY request_count DESC
LIMIT 10";

if(!empty($params)) {
    $stmt = $conn->prepare($borrower_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrower_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($borrower_query);
    $borrower_data = $result->fetch_all(MYSQLI_ASSOC);
}

$item_query = "SELECT 
    COALESCE(p.product_name, br.item_name) as item_name,
    COUNT(*) as request_count,
    SUM(br.quantity) as total_quantity,
    COUNT(CASE WHEN br.status = 'approved' THEN 1 END) as approved_count
FROM borrow_requests br
LEFT JOIN products p ON br.product_id = p.id
$date_condition
GROUP BY COALESCE(p.product_name, br.item_name)
ORDER BY request_count DESC
LIMIT 10";

if(!empty($params)) {
    $stmt = $conn->prepare($item_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($item_query);
    $item_data = $result->fetch_all(MYSQLI_ASSOC);
}
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <?php include('../includes/header.php'); ?> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filter-card { background: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 0.35rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .filter-btn { margin: 0.25rem; }
        .filter-btn.active { background-color: #4e73df !important; border-color: #4e73df !important; color: white !important; }
        .summary-card { transition: transform 0.2s; }
        .summary-card:hover { transform: translateY(-2px); }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .chart-container { position: relative; height: 300px; margin: 20px 0; }
        .print-header { display: none; }
        
        /* Professional Tab Styles */
        .nav-tabs { border-bottom: 3px solid #e9ecef; margin-bottom: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 0.5rem 0.5rem 0 0; padding: 0.5rem 0.5rem 0 0.5rem; }
        .nav-tabs .nav-link { border: none; border-radius: 0.375rem 0.375rem 0 0; margin-right: 0.25rem; padding: 1rem 1.5rem; color: rgba(255,255,255,0.8); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-tabs .nav-link:hover { border-color: transparent; color: #fff; background-color: rgba(255,255,255,0.1); transform: translateY(-2px); }
        .nav-tabs .nav-link.active { color: #fff; background-color: rgba(255,255,255,0.2); border-color: transparent; border-bottom: 3px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .nav-tabs .nav-link i { margin-right: 0.5rem; font-size: 1.1rem; }
        .tab-content { padding-top: 1.5rem; background: #fff; border-radius: 0 0 0.5rem 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .tab-pane { animation: fadeInUp 0.4s ease-out; }
        
        /* Professional Table Styles */
        .table-hover tbody tr:hover { background-color: rgba(255,255,255,0.1); transform: scale(1.01); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .table th { color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none; }
        .table td { vertical-align: middle; border-color: rgba(255,255,255,0.1); background-color: rgba(255,255,255,0.02); }
        
        /* Status Badge Styles */
        .badge { font-size: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-weight: 600; }
        .badge-warning { background: linear-gradient(135deg, #F39C12 0%, #E67E22 100%); color: #fff; }
        .badge-success { background: linear-gradient(135deg, #2ECC71 0%, #27AE60 100%); }
        .badge-danger { background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%); }
        .badge-info { background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%); }
        
        /* Professional Card Styles */
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 0.75rem; }
        .card-header { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6; border-radius: 0.75rem 0.75rem 0 0 !important; }
        
        @media print {
            body * { visibility: hidden; }
            .printable-section, .printable-section * { visibility: visible; }
            .print-header { visibility: visible !important; display: block !important; }
            #wrapper, #content-wrapper { margin: 0; padding: 0; }
            .sidebar, .topbar, .filter-card, .no-print, .nav-tabs { display: none !important; }
            .card { page-break-inside: avoid; border: 1px solid #ddd; box-shadow: none; }
            .summary-card:hover { transform: none; }
            .table-responsive { max-height: none; overflow: visible; }
            .chart-container { page-break-inside: avoid; }
            .print-header { display: block; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
            .tab-content { padding-top: 0; }
            .tab-pane { display: block !important; opacity: 1 !important; }
        }
    </style>
</head> 
<body id="page-top"> 
    <div id="wrapper"> 
        <?php include('../includes/sidebar.php'); ?> 
        <div id="content-wrapper" class="d-flex flex-column"> 
            <div id="content"> 
                <?php include('../includes/topbar.php'); ?> 
                <div class="container-fluid"> 
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 no-print"> 
                        <h1 class="h3 mb-0 text-gray-800">Borrow Equipment/Items Report</h1>
                        <div class="btn-group">
                            <button class="btn btn-success" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-card no-print">
                        <h5 class="mb-3">Filter Borrow Data</h5>
                        <form method="GET" id="filterForm">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">Time Period:</label><br>
                                    <button type="submit" name="filter" value="today" class="btn btn-outline-primary filter-btn <?php echo $filter == 'today' ? 'active' : '' ?>">Today</button>
                                    <button type="submit" name="filter" value="week" class="btn btn-outline-primary filter-btn <?php echo $filter == 'week' ? 'active' : '' ?>">This Week</button>
                                    <button type="submit" name="filter" value="month" class="btn btn-outline-primary filter-btn <?php echo $filter == 'month' ? 'active' : '' ?>">This Month</button>
                                    <button type="submit" name="filter" value="year" class="btn btn-outline-primary filter-btn <?php echo $filter == 'year' ? 'active' : '' ?>">This Year</button>
                                    <button type="submit" name="filter" value="overall" class="btn btn-outline-primary filter-btn <?php echo $filter == 'overall' ? 'active' : '' ?>">Overall</button>
                                    <button type="button" class="btn btn-outline-secondary filter-btn" onclick="toggleCustomDate()">Custom Range</button>
                                </div>
                            </div>
                            <div id="customDateRange" class="row mt-3" style="display: <?php echo $filter == 'custom' ? 'flex' : 'none' ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date:</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date:</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="filter" value="custom" class="btn btn-primary">Apply Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="printableArea">
                        <div class="print-header">
                            <h2>Borrow Equipment/Items Report</h2>
                            <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                            <p>Period: <?php 
                                switch($filter) {
                                    case 'today': echo 'Today'; break;
                                    case 'week': echo 'This Week'; break;
                                    case 'month': echo 'This Month'; break;
                                    case 'year': echo 'This Year'; break;
                                    case 'custom': echo htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date); break;
                                    default: echo 'Overall'; break;
                                }
                            ?></p>
                        </div>

                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs no-print" id="borrowTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                                    <i class="fas fa-chart-pie"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                                    <i class="fas fa-list"></i> All Requests
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">
                                    <i class="fas fa-chart-line"></i> Analytics
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="borrowTabContent">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                <div class="row mb-4">
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Requests</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_requests'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['pending_count'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['approved_count'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Returned</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['returned_count'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-undo fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-danger shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Declined</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['declined_count'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-times-circle fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-secondary shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Items</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_items_borrowed'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-boxes fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Unique Borrowers</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['unique_borrowers'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2 summary-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Unique Items</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['unique_items'] ?? 0) ?></div>
                                                    </div>
                                                    <div class="col-auto"><i class="fas fa-tags fa-2x text-gray-300"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Chart -->
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Request Status Distribution</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="statusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- All Requests Tab -->
                            <div class="tab-pane fade" id="requests" role="tabpanel">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">All Borrow Requests</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="borrowTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Borrower</th>
                                                        <th>Role</th>
                                                        <th>Item Name</th>
                                                        <th>Quantity</th>
                                                        <th>Request Date</th>
                                                        <th>Expected Return</th>
                                                        <th>Status</th>
                                                        <th>Action Date</th>
                                                        <th>Return Date</th>
                                                        <th>Admin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($borrow_data as $row): 
                                                        $status_badge = '';
                                                        switch($row['status']) {
                                                            case 'pending':
                                                                $status_badge = '<span class="badge badge-warning">Pending</span>';
                                                                break;
                                                            case 'approved':
                                                                $status_badge = '<span class="badge badge-success">Approved</span>';
                                                                break;
                                                            case 'declined':
                                                                $status_badge = '<span class="badge badge-danger">Declined</span>';
                                                                break;
                                                            case 'returned':
                                                                $status_badge = '<span class="badge badge-info">Returned</span>';
                                                                break;
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td>#<?= $row['id'] ?></td>
                                                        <td><?= htmlspecialchars($row['borrower_name']) ?></td>
                                                        <td><?= ucfirst(htmlspecialchars($row['borrower_role'])) ?></td>
                                                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                                                        <td><?= $row['quantity'] ?></td>
                                                        <td><?= date("M d, Y h:i A", strtotime($row['borrow_date'])) ?></td>
                                                        <td><?= $row['expected_return_date'] ? date("M d, Y", strtotime($row['expected_return_date'])) : '—' ?></td>
                                                        <td><?= $status_badge ?></td>
                                                        <td><?= $row['action_date'] ? date("M d, Y h:i A", strtotime($row['action_date'])) : '—' ?></td>
                                                        <td><?= $row['return_date'] ? date("M d, Y h:i A", strtotime($row['return_date'])) : '—' ?></td>
                                                        <td><?= $row['admin_name'] ? htmlspecialchars($row['admin_name']) : '—' ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Analytics Tab -->
                            <div class="tab-pane fade" id="analytics" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Top Borrowers</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Borrower</th>
                                                                <th>Role</th>
                                                                <th>Requests</th>
                                                                <th>Total Items</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach($borrower_data as $borrower): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($borrower['full_name']) ?></td>
                                                                <td><?= ucfirst(htmlspecialchars($borrower['role'])) ?></td>
                                                                <td><?= $borrower['request_count'] ?></td>
                                                                <td><?= $borrower['total_items'] ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Most Borrowed Items</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Item Name</th>
                                                                <th>Requests</th>
                                                                <th>Total Quantity</th>
                                                                <th>Approved</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach($item_data as $item): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                                <td><?= $item['request_count'] ?></td>
                                                                <td><?= $item['total_quantity'] ?></td>
                                                                <td><?= $item['approved_count'] ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#borrowTable').DataTable({
                "order": [[5, "desc"]],
                "pageLength": 25
            });

            // Status Chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                const statusData = <?php echo json_encode($status_data); ?>;
                const labels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
                const counts = statusData.map(item => parseInt(item.count));
                
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: [
                                '#F39C12',
                                '#2ECC71',
                                '#E74C3C',
                                '#3498DB'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });

        function toggleCustomDate() {
            const customDateRange = document.getElementById('customDateRange');
            customDateRange.style.display = customDateRange.style.display === 'none' ? 'flex' : 'none';
        }

        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(18);
            doc.text('Borrow Equipment/Items Report', 14, 15);
            
            // Add date
            doc.setFontSize(10);
            doc.text('Generated on: ' + new Date().toLocaleString(), 14, 22);
            
            // Add period
            doc.text('Period: <?php 
                switch($filter) {
                    case 'today': echo 'Today'; break;
                    case 'week': echo 'This Week'; break;
                    case 'month': echo 'This Month'; break;
                    case 'year': echo 'This Year'; break;
                    case 'custom': echo htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date); break;
                    default: echo 'Overall'; break;
                }
            ?>', 14, 28);
            
            // Summary table
            doc.autoTable({
                startY: 35,
                head: [['Metric', 'Value']],
                body: [
                    ['Total Requests', '<?= $summary['total_requests'] ?? 0 ?>'],
                    ['Pending', '<?= $summary['pending_count'] ?? 0 ?>'],
                    ['Approved', '<?= $summary['approved_count'] ?? 0 ?>'],
                    ['Declined', '<?= $summary['declined_count'] ?? 0 ?>'],
                    ['Returned', '<?= $summary['returned_count'] ?? 0 ?>'],
                    ['Total Items', '<?= $summary['total_items_borrowed'] ?? 0 ?>'],
                    ['Unique Borrowers', '<?= $summary['unique_borrowers'] ?? 0 ?>']
                ]
            });
            
            // Borrow requests table
            const tableData = [
                ['ID', 'Borrower', 'Item', 'Qty', 'Status', 'Request Date']
            ];
            
            <?php foreach(array_slice($borrow_data, 0, 50) as $row): ?>
            tableData.push([
                '#<?= $row['id'] ?>',
                '<?= addslashes($row['borrower_name']) ?>',
                '<?= addslashes($row['item_name']) ?>',
                '<?= $row['quantity'] ?>',
                '<?= ucfirst($row['status']) ?>',
                '<?= date("M d, Y", strtotime($row['borrow_date'])) ?>'
            ]);
            <?php endforeach; ?>
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 10,
                head: [tableData[0]],
                body: tableData.slice(1),
                styles: { fontSize: 8 }
            });
            
            doc.save('borrow_report_<?= date('Y-m-d') ?>.pdf');
        }
    </script>
</body>
</html>
