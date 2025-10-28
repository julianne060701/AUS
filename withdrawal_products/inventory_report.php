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
        $date_condition = "WHERE DATE(p.created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = "WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "WHERE MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE YEAR(p.created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($start_date && $end_date) {
            $date_condition = "WHERE p.created_at BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
            $param_types = "ss";
        }
        break;
    case 'overall':
    default:
        $date_condition = "";
        break;
}

$summary_query = "SELECT 
    COUNT(*) as total_products,
    SUM(p.quantity) as total_quantity,
    SUM(p.quantity * p.buying_price) as total_inventory_value,
    AVG(p.quantity) as avg_quantity_per_product,
    COUNT(CASE WHEN p.quantity <= 5 THEN 1 END) as low_stock_count
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
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

$inventory_query = "SELECT 
    p.id, p.product_name, COALESCE(p.serial_number, p.capacity) as serial_number, p.quantity, p.buying_price, p.selling_price,
    p.created_at, p.updated_at, c.category_name, b.brand_name,
    (p.quantity * p.buying_price) as inventory_value,
    (p.selling_price - p.buying_price) as profit_margin
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
$date_condition 
ORDER BY p.quantity ASC, p.product_name ASC";

if(!empty($params)) {
    $stmt = $conn->prepare($inventory_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $inventory_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($inventory_query);
    $inventory_data = $result->fetch_all(MYSQLI_ASSOC);
}

$category_query = "SELECT 
    c.category_name, COUNT(*) as product_count, SUM(p.quantity) as total_quantity,
    SUM(p.quantity * p.buying_price) as total_value, AVG(p.quantity) as avg_quantity
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
$date_condition 
GROUP BY c.category_name 
ORDER BY total_value DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $category_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($category_query);
    $category_data = $result->fetch_all(MYSQLI_ASSOC);
}

$brand_query = "SELECT 
    b.brand_name, COUNT(*) as product_count, SUM(p.quantity) as total_quantity,
    SUM(p.quantity * p.buying_price) as total_value, AVG(p.quantity) as avg_quantity
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
$date_condition 
GROUP BY b.brand_name 
ORDER BY total_value DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($brand_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $brand_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($brand_query);
    $brand_data = $result->fetch_all(MYSQLI_ASSOC);
}

$low_stock_query = "SELECT 
    p.id, p.product_name, COALESCE(p.serial_number, p.capacity) as serial_number, p.quantity, p.buying_price, p.selling_price,
    c.category_name, b.brand_name, (p.quantity * p.buying_price) as inventory_value
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
WHERE p.quantity <= 5
ORDER BY p.quantity ASC";

$result = $conn->query($low_stock_query);
$low_stock_data = $result->fetch_all(MYSQLI_ASSOC);

// Stock In Query (products added to inventory)
$stock_in_query = "SELECT 
    p.id, p.product_name, COALESCE(p.serial_number, p.capacity) as serial_number, p.quantity, p.buying_price,
    p.created_at, c.category_name, b.brand_name,
    'Stock In' as movement_type
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
$date_condition 
ORDER BY p.created_at DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($stock_in_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_in_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($stock_in_query);
    $stock_in_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Stock Out Query (from aircon_sales table with foreign key to products table)
// Stock out records are identified by selling_price = 0 and total_amount = 0
$stock_out_query = "SELECT 
    s.sale_id, s.aircon_model as product_name, s.quantity_sold as quantity,
    s.date_of_sale as created_at, s.cashier,
    'Stock Out' as movement_type,
    COALESCE(p.serial_number, p.capacity) as serial_number, p.buying_price, c.category_name, b.brand_name
FROM aircon_sales s 
LEFT JOIN products p ON s.product_id = p.id
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
WHERE s.selling_price = 0 AND s.total_amount = 0";

// Apply date filter for stock out
$stock_out_date_condition = "";
if($filter == 'today') {
    $stock_out_date_condition = " AND DATE(s.date_of_sale) = CURDATE()";
} elseif($filter == 'week') {
    $stock_out_date_condition = " AND s.date_of_sale >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif($filter == 'month') {
    $stock_out_date_condition = " AND MONTH(s.date_of_sale) = MONTH(CURDATE()) AND YEAR(s.date_of_sale) = YEAR(CURDATE())";
} elseif($filter == 'year') {
    $stock_out_date_condition = " AND YEAR(s.date_of_sale) = YEAR(CURDATE())";
} elseif($filter == 'custom' && $start_date && $end_date) {
    $stock_out_date_condition = " AND s.date_of_sale BETWEEN '$start_date' AND '$end_date'";
}

$stock_out_query .= $stock_out_date_condition . " ORDER BY s.date_of_sale DESC";
$result = $conn->query($stock_out_query);
$stock_out_data = $result->fetch_all(MYSQLI_ASSOC);
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
        .low-stock { color: #dc3545; font-weight: bold; }
        .normal-stock { color: #28a745; }
        .alert-card { border-left: 4px solid #dc3545; }
        .print-header { display: none; }
        .section-checkbox label { font-weight: 500; cursor: pointer; }
        
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
        .table th { color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none; cursor: pointer; transition: all 0.3s ease; }
        .table th:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .table th i { opacity: 0.7; transition: opacity 0.3s ease; }
        .table th:hover i { opacity: 1; }
        .table td { vertical-align: middle; border-color: rgba(255,255,255,0.1); background-color: rgba(255,255,255,0.02); }
        
        /* Stock In Table - Green Theme */
        .table-success th { background: linear-gradient(135deg, #2ECC71 0%, #27AE60 100%); }
        .table-success th:hover { background: linear-gradient(135deg, #27AE60 0%, #229954 100%); }
        .table-success tbody tr:hover { background-color: rgba(46, 204, 113, 0.1); }
        
        /* Stock Out Table - Orange Theme */
        .table-warning th { background: linear-gradient(135deg, #E67E22 0%, #D35400 100%); }
        .table-warning th:hover { background: linear-gradient(135deg, #D35400 0%, #BA4A00 100%); }
        .table-warning tbody tr:hover { background-color: rgba(230, 126, 34, 0.1); }
        
        /* Current Stock Table - Blue Theme */
        .table-primary th { background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%); }
        .table-primary th:hover { background: linear-gradient(135deg, #2980B9 0%, #21618C 100%); }
        .table-primary tbody tr:hover { background-color: rgba(52, 152, 219, 0.1); }
        
        /* Low Stock Alert Table - Red Theme */
        .table-danger th { background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%); }
        .table-danger th:hover { background: linear-gradient(135deg, #C0392B 0%, #A93226 100%); }
        .table-danger tbody tr:hover { background-color: rgba(231, 76, 60, 0.1); }
        
        /* Search and Filter Controls */
        .input-group-text { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        
        /* Pagination Styles */
        .pagination .page-link { color: #667eea; border-color: #dee2e6; transition: all 0.3s ease; }
        .pagination .page-link:hover { color: #fff; background-color: #667eea; border-color: #667eea; transform: translateY(-1px); }
        .pagination .page-item.active .page-link { background-color: #667eea; border-color: #667eea; }
        .pagination .page-item.disabled .page-link { color: #6c757d; background-color: #fff; border-color: #dee2e6; }
        
        /* Data Tables Info */
        .dataTables_info { color: #6c757d; font-size: 0.875rem; }
        
        /* Enhanced Button Styles */
        .btn-outline-secondary:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        
        /* Professional Badge Styles */
        .badge { font-size: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-weight: 600; }
        .badge-danger { background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%); }
        .badge-warning { background: linear-gradient(135deg, #E67E22 0%, #D35400 100%); color: #fff; }
        .badge-success { background: linear-gradient(135deg, #2ECC71 0%, #27AE60 100%); }
        
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
                        <h1 class="h3 mb-0 text-gray-800">Inventory Report</h1>
                        <div class="btn-group">
                            <button class="btn btn-success" onclick="showPDFOptions()">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-card no-print">
                        <h5 class="mb-3">Filter Inventory Data</h5>
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
                            <h2>Professional Inventory Report</h2>
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
                        <ul class="nav nav-tabs no-print" id="inventoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                                    <i class="fas fa-chart-pie"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="stockin-tab" data-bs-toggle="tab" data-bs-target="#stockin" type="button" role="tab" aria-controls="stockin" aria-selected="false">
                                    <i class="fas fa-arrow-down"></i> Stock In
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="stockout-tab" data-bs-toggle="tab" data-bs-target="#stockout" type="button" role="tab" aria-controls="stockout" aria-selected="false">
                                    <i class="fas fa-arrow-up"></i> Stock Out
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab" aria-controls="current" aria-selected="false">
                                    <i class="fas fa-boxes"></i> Current Stock
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics" aria-selected="false">
                                    <i class="fas fa-chart-line"></i> Analytics
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="inventoryTabContent">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div id="section-summary" class="section-content">
                            <div class="row mb-4">
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Products</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_products'] ?? 0) ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-box fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-info shadow h-100 py-2 summary-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Units</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_quantity'] ?? 0) ?></div>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-cubes fa-2x text-gray-300"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-success shadow h-100 py-2 summary-card">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Stock In</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format(count($stock_in_data)) ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-gray-300"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-warning shadow h-100 py-2 summary-card alert-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stock Out</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format(count($stock_out_data)) ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-gray-300"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock In Tab -->
                            <div class="tab-pane fade" id="stockin" role="tabpanel" aria-labelledby="stockin-tab">
                                <div id="section-stockin" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                    <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-arrow-down"></i> Stock In Report</h6>
                                                    <div class="text-muted">
                                                        <span id="stockInCount"><?= count($stock_in_data) ?></span> records
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Search and Filter Controls -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                <input type="text" class="form-control" id="stockInSearch" placeholder="Search products, categories, brands...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <select class="form-select" id="stockInShowEntries">
                                                                <option value="10">Show 10 entries</option>
                                                                <option value="25" selected>Show 25 entries</option>
                                                                <option value="50">Show 50 entries</option>
                                                                <option value="100">Show 100 entries</option>
                                                                <option value="-1">Show all</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button class="btn btn-outline-secondary" onclick="resetStockInFilters()">
                                                                <i class="fas fa-refresh"></i> Reset
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="stockInTable">
                                                            <thead class="table-success">
                                                                <tr>
                                                                    <th onclick="sortTable('stockInTable', 0)">Product Name <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 1)">Model/Serial <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 2)">Category <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 3)">Brand <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 4)">Quantity <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 5)">Unit Price <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockInTable', 6)">Date Added <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="stockInTableBody">
                                                                <?php if(empty($stock_in_data)): ?>
                                                                <tr><td colspan="7" class="text-center">No stock in records found</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($stock_in_data as $item): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                                                    <td><?= htmlspecialchars($item['brand_name'] ?? 'No Brand') ?></td>
                                                                    <td class="text-success font-weight-bold"><?= number_format($item['quantity']) ?></td>
                                                                    <td>₱<?= number_format($item['buying_price'], 2) ?></td>
                                                                    <td><?= date('M j, Y g:i A', strtotime($item['created_at'])) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <!-- Pagination -->
                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="dataTables_info" id="stockInInfo">
                                                                Showing 1 to <span id="stockInShowing">25</span> of <span id="stockInTotal"><?= count($stock_in_data) ?></span> entries
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <nav aria-label="Stock In pagination">
                                                                <ul class="pagination justify-content-end" id="stockInPagination">
                                                                    <!-- Pagination will be generated by JavaScript -->
                                                                </ul>
                                                            </nav>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Out Tab -->
                            <div class="tab-pane fade" id="stockout" role="tabpanel" aria-labelledby="stockout-tab">
                                <div id="section-stockout" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                    <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-arrow-up"></i> Stock Out Report</h6>
                                                    <div class="text-muted">
                                                        <span id="stockOutCount"><?= count($stock_out_data) ?></span> records
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Search and Filter Controls -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                <input type="text" class="form-control" id="stockOutSearch" placeholder="Search products, cashier, reference ID...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <select class="form-select" id="stockOutShowEntries">
                                                                <option value="10">Show 10 entries</option>
                                                                <option value="25" selected>Show 25 entries</option>
                                                                <option value="50">Show 50 entries</option>
                                                                <option value="100">Show 100 entries</option>
                                                                <option value="-1">Show all</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button class="btn btn-outline-secondary" onclick="resetStockOutFilters()">
                                                                <i class="fas fa-refresh"></i> Reset
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="stockOutTable">
                                                            <thead class="table-warning">
                                                                <tr>
                                                                    <th onclick="sortTable('stockOutTable', 0)">Product Name <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockOutTable', 1)">Model/Serial <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockOutTable', 2)">Quantity <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockOutTable', 3)">Processed By <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockOutTable', 4)">Date Out <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('stockOutTable', 5)">Reference ID <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="stockOutTableBody">
                                                                <?php if(empty($stock_out_data)): ?>
                                                                <tr><td colspan="6" class="text-center">No stock out records found</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($stock_out_data as $item): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                                                                    <td class="text-warning font-weight-bold"><?= number_format($item['quantity']) ?></td>
                                                                    <td><?= htmlspecialchars($item['cashier'] ?? 'System') ?></td>
                                                                    <td><?= date('M j, Y g:i A', strtotime($item['created_at'])) ?></td>
                                                                    <td>#<?= htmlspecialchars($item['sale_id']) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <!-- Pagination -->
                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="dataTables_info" id="stockOutInfo">
                                                                Showing 1 to <span id="stockOutShowing">25</span> of <span id="stockOutTotal"><?= count($stock_out_data) ?></span> entries
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <nav aria-label="Stock Out pagination">
                                                                <ul class="pagination justify-content-end" id="stockOutPagination">
                                                                    <!-- Pagination will be generated by JavaScript -->
                                                                </ul>
                                                            </nav>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Stock Tab -->
                            <div class="tab-pane fade" id="current" role="tabpanel" aria-labelledby="current-tab">
                                <div id="section-current" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-boxes"></i> Current Stock Levels</h6>
                                                    <div class="text-muted">
                                                        <span id="currentStockCount"><?= count($inventory_data) ?></span> products
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Search and Filter Controls -->
                                                    <div class="row mb-3">
                                                        <div class="col-md-4">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                <input type="text" class="form-control" id="currentStockSearch" placeholder="Search products, categories, brands...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select" id="currentStockFilter">
                                                                <option value="">All Status</option>
                                                                <option value="low">Low Stock (≤5)</option>
                                                                <option value="medium">Medium Stock (6-20)</option>
                                                                <option value="good">Good Stock (>20)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <select class="form-select" id="currentStockShowEntries">
                                                                <option value="10">Show 10 entries</option>
                                                                <option value="25" selected>Show 25 entries</option>
                                                                <option value="50">Show 50 entries</option>
                                                                <option value="100">Show 100 entries</option>
                                                                <option value="-1">Show all</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button class="btn btn-outline-secondary" onclick="resetCurrentStockFilters()">
                                                                <i class="fas fa-refresh"></i> Reset
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="currentStockTable">
                                                            <thead class="table-primary">
                                                                <tr>
                                                                    <th onclick="sortTable('currentStockTable', 0)">Product Name <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 1)">Model/Serial <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 2)">Category <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 3)">Brand <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 4)">Current Stock <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 5)">Status <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable('currentStockTable', 6)">Last Updated <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="currentStockTableBody">
                                                                <?php if(empty($inventory_data)): ?>
                                                                <tr><td colspan="7" class="text-center">No inventory data available</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($inventory_data as $item): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                                                    <td><?= htmlspecialchars($item['brand_name'] ?? 'No Brand') ?></td>
                                                                    <td class="<?= ($item['quantity'] <= 5) ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' ?>">
                                                                        <?= number_format($item['quantity']) ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if($item['quantity'] <= 5): ?>
                                                                            <span class="badge badge-danger">Low Stock</span>
                                                                        <?php elseif($item['quantity'] <= 20): ?>
                                                                            <span class="badge badge-warning">Medium Stock</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-success">Good Stock</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= date('M j, Y', strtotime($item['updated_at'])) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <!-- Pagination -->
                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="dataTables_info" id="currentStockInfo">
                                                                Showing 1 to <span id="currentStockShowing">25</span> of <span id="currentStockTotal"><?= count($inventory_data) ?></span> entries
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <nav aria-label="Current Stock pagination">
                                                                <ul class="pagination justify-content-end" id="currentStockPagination">
                                                                    <!-- Pagination will be generated by JavaScript -->
                                                                </ul>
                                                            </nav>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Analytics Tab -->
                            <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                                <div id="section-analytics" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-lg-6">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Inventory by Category</h6></div>
                                                <div class="card-body"><div class="chart-container"><canvas id="categoryChart"></canvas></div></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Inventory by Brand</h6></div>
                                                <div class="card-body"><div class="chart-container"><canvas id="brandChart"></canvas></div></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Low Stock Alert -->
                                    <?php if(!empty($low_stock_data)): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4 alert-card">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h6></div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover">
                                                            <thead class="table-danger">
                                                                <tr>
                                                                    <th>Product Name</th><th>Model/Serial</th><th>Category</th><th>Brand</th>
                                                                    <th>Current Stock</th><th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($low_stock_data as $item): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                                                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                                                    <td><?= htmlspecialchars($item['brand_name'] ?? 'No Brand') ?></td>
                                                                    <td class="text-danger font-weight-bold"><?= number_format($item['quantity']) ?></td>
                                                                    <td><span class="badge badge-danger">Critical</span></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div> 
            </div> 
            <?php include('../includes/footer.php'); ?> 
        </div>
    <!-- PDF Download Options Modal -->
    <div class="modal fade" id="pdfOptionsModal" tabindex="-1" aria-labelledby="pdfOptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfOptionsModalLabel">
                        <i class="fas fa-file-pdf text-success"></i> PDF Download Options
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-muted">Select which sections to include in your PDF:</h6>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeOverview" checked>
                                <label class="form-check-label" for="includeOverview">
                                    <i class="fas fa-chart-pie text-primary"></i> <strong>Overview</strong>
                                    <small class="text-muted d-block">Summary statistics and key metrics</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeStockIn">
                                <label class="form-check-label" for="includeStockIn">
                                    <i class="fas fa-arrow-down text-success"></i> <strong>Stock In Report</strong>
                                    <small class="text-muted d-block">Products added to inventory (<?= count($stock_in_data) ?> records)</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeStockOut">
                                <label class="form-check-label" for="includeStockOut">
                                    <i class="fas fa-arrow-up text-warning"></i> <strong>Stock Out Report</strong>
                                    <small class="text-muted d-block">Products removed from inventory (<?= count($stock_out_data) ?> records)</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeCurrentStock">
                                <label class="form-check-label" for="includeCurrentStock">
                                    <i class="fas fa-boxes text-info"></i> <strong>Current Stock Levels</strong>
                                    <small class="text-muted d-block">Current inventory status (<?= count($inventory_data) ?> products)</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeAnalytics">
                                <label class="form-check-label" for="includeAnalytics">
                                    <i class="fas fa-chart-line text-secondary"></i> <strong>Analytics & Charts</strong>
                                    <small class="text-muted d-block">Category and brand analysis</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeLowStock" <?= !empty($low_stock_data) ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="includeLowStock">
                                    <i class="fas fa-exclamation-triangle text-danger"></i> <strong>Low Stock Alert</strong>
                                    <small class="text-muted d-block">Critical stock levels (<?= count($low_stock_data) ?> items)</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> The PDF will be generated in landscape orientation on letter/short bond paper size for optimal table viewing.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="generateSelectedPDF()">
                        <i class="fas fa-download"></i> Generate PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        // Table management objects
        const tableManagers = {
            stockIn: {
                searchInput: 'stockInSearch',
                showEntries: 'stockInShowEntries',
                table: 'stockInTable',
                tableBody: 'stockInTableBody',
                pagination: 'stockInPagination',
                info: 'stockInInfo',
                showing: 'stockInShowing',
                total: 'stockInTotal',
                filter: null,
                currentPage: 1,
                entriesPerPage: 25,
                allData: [],
                filteredData: []
            },
            stockOut: {
                searchInput: 'stockOutSearch',
                showEntries: 'stockOutShowEntries',
                table: 'stockOutTable',
                tableBody: 'stockOutTableBody',
                pagination: 'stockOutPagination',
                info: 'stockOutInfo',
                showing: 'stockOutShowing',
                total: 'stockOutTotal',
                filter: null,
                currentPage: 1,
                entriesPerPage: 25,
                allData: [],
                filteredData: []
            },
            currentStock: {
                searchInput: 'currentStockSearch',
                showEntries: 'currentStockShowEntries',
                table: 'currentStockTable',
                tableBody: 'currentStockTableBody',
                pagination: 'currentStockPagination',
                info: 'currentStockInfo',
                showing: 'currentStockShowing',
                total: 'currentStockTotal',
                filter: 'currentStockFilter',
                currentPage: 1,
                entriesPerPage: 25,
                allData: [],
                filteredData: []
            }
        };

        // Initialize table data
        function initializeTableData() {
            Object.keys(tableManagers).forEach(key => {
                const manager = tableManagers[key];
                const tableBody = document.getElementById(manager.tableBody);
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                
                manager.allData = rows.map(row => {
                    const cells = Array.from(row.querySelectorAll('td'));
                    return {
                        element: row,
                        data: cells.map(cell => cell.textContent.trim())
                    };
                }).filter(item => item.data.length > 0); // Filter out empty rows
                
                manager.filteredData = [...manager.allData];
                updateTableDisplay(key);
            });
        }

        // Search functionality
        function searchTable(tableKey, searchTerm) {
            const manager = tableManagers[tableKey];
            const term = searchTerm.toLowerCase();
            
            manager.filteredData = manager.allData.filter(item => {
                return item.data.some(cellData => 
                    cellData.toLowerCase().includes(term)
                );
            });
            
            manager.currentPage = 1;
            updateTableDisplay(tableKey);
        }

        // Filter functionality (for current stock)
        function filterTable(tableKey, filterValue) {
            const manager = tableManagers[tableKey];
            
            if (!filterValue) {
                manager.filteredData = [...manager.allData];
            } else {
                manager.filteredData = manager.allData.filter(item => {
                    const statusCell = item.data[5]; // Status column
                    switch(filterValue) {
                        case 'low':
                            return statusCell.includes('Low Stock');
                        case 'medium':
                            return statusCell.includes('Medium Stock');
                        case 'good':
                            return statusCell.includes('Good Stock');
                        default:
                            return true;
                    }
                });
            }
            
            manager.currentPage = 1;
            updateTableDisplay(tableKey);
        }

        // Update table display
        function updateTableDisplay(tableKey) {
            const manager = tableManagers[tableKey];
            const tableBody = document.getElementById(manager.tableBody);
            const startIndex = (manager.currentPage - 1) * manager.entriesPerPage;
            const endIndex = manager.entriesPerPage === -1 ? 
                manager.filteredData.length : 
                startIndex + manager.entriesPerPage;
            
            // Clear table body
            tableBody.innerHTML = '';
            
            // Show filtered data
            const dataToShow = manager.entriesPerPage === -1 ? 
                manager.filteredData : 
                manager.filteredData.slice(startIndex, endIndex);
            
            if (dataToShow.length === 0) {
                const noDataRow = document.createElement('tr');
                noDataRow.innerHTML = '<td colspan="7" class="text-center">No data found</td>';
                tableBody.appendChild(noDataRow);
            } else {
                dataToShow.forEach(item => {
                    tableBody.appendChild(item.element.cloneNode(true));
                });
            }
            
            // Update pagination info
            updatePaginationInfo(tableKey);
            generatePagination(tableKey);
        }

        // Update pagination info
        function updatePaginationInfo(tableKey) {
            const manager = tableManagers[tableKey];
            const total = manager.filteredData.length;
            const start = manager.entriesPerPage === -1 ? 1 : (manager.currentPage - 1) * manager.entriesPerPage + 1;
            const end = manager.entriesPerPage === -1 ? total : Math.min(start + manager.entriesPerPage - 1, total);
            
            document.getElementById(manager.showing).textContent = end;
            document.getElementById(manager.total).textContent = total;
        }

        // Generate pagination
        function generatePagination(tableKey) {
            const manager = tableManagers[tableKey];
            const pagination = document.getElementById(manager.pagination);
            const totalPages = manager.entriesPerPage === -1 ? 1 : Math.ceil(manager.filteredData.length / manager.entriesPerPage);
            
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${manager.currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage('${tableKey}', ${manager.currentPage - 1})">Previous</a>`;
            pagination.appendChild(prevLi);
            
            // Page numbers
            const startPage = Math.max(1, manager.currentPage - 2);
            const endPage = Math.min(totalPages, manager.currentPage + 2);
            
            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage('${tableKey}', 1)">1</a>`;
                pagination.appendChild(firstLi);
                
                if (startPage > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsisLi);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === manager.currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="changePage('${tableKey}', ${i})">${i}</a>`;
                pagination.appendChild(li);
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsisLi);
                }
                
                const lastLi = document.createElement('li');
                lastLi.className = 'page-item';
                lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage('${tableKey}', ${totalPages})">${totalPages}</a>`;
                pagination.appendChild(lastLi);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${manager.currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage('${tableKey}', ${manager.currentPage + 1})">Next</a>`;
            pagination.appendChild(nextLi);
        }

        // Change page
        function changePage(tableKey, page) {
            const manager = tableManagers[tableKey];
            const totalPages = manager.entriesPerPage === -1 ? 1 : Math.ceil(manager.filteredData.length / manager.entriesPerPage);
            
            if (page >= 1 && page <= totalPages) {
                manager.currentPage = page;
                updateTableDisplay(tableKey);
            }
        }

        // Change entries per page
        function changeEntriesPerPage(tableKey, entries) {
            const manager = tableManagers[tableKey];
            manager.entriesPerPage = parseInt(entries);
            manager.currentPage = 1;
            updateTableDisplay(tableKey);
        }

        // Sort table
        function sortTable(tableId, columnIndex) {
            const manager = Object.values(tableManagers).find(m => m.table === tableId);
            if (!manager) return;
            
            const tableKey = Object.keys(tableManagers).find(key => tableManagers[key].table === tableId);
            
            manager.filteredData.sort((a, b) => {
                const aVal = a.data[columnIndex] || '';
                const bVal = b.data[columnIndex] || '';
                
                // Try to parse as numbers first
                const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
                const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return aNum - bNum;
                }
                
                return aVal.localeCompare(bVal);
            });
            
            manager.currentPage = 1;
            updateTableDisplay(tableKey);
        }

        // Reset filters
        function resetStockInFilters() {
            document.getElementById('stockInSearch').value = '';
            document.getElementById('stockInShowEntries').value = '25';
            tableManagers.stockIn.currentPage = 1;
            tableManagers.stockIn.entriesPerPage = 25;
            tableManagers.stockIn.filteredData = [...tableManagers.stockIn.allData];
            updateTableDisplay('stockIn');
        }

        function resetStockOutFilters() {
            document.getElementById('stockOutSearch').value = '';
            document.getElementById('stockOutShowEntries').value = '25';
            tableManagers.stockOut.currentPage = 1;
            tableManagers.stockOut.entriesPerPage = 25;
            tableManagers.stockOut.filteredData = [...tableManagers.stockOut.allData];
            updateTableDisplay('stockOut');
        }

        function resetCurrentStockFilters() {
            document.getElementById('currentStockSearch').value = '';
            document.getElementById('currentStockFilter').value = '';
            document.getElementById('currentStockShowEntries').value = '25';
            tableManagers.currentStock.currentPage = 1;
            tableManagers.currentStock.entriesPerPage = 25;
            tableManagers.currentStock.filteredData = [...tableManagers.currentStock.allData];
            updateTableDisplay('currentStock');
        }

        // Tab persistence functions
        function saveActiveTab(tabId) {
            localStorage.setItem('inventoryReportActiveTab', tabId);
        }

        function getActiveTab() {
            return localStorage.getItem('inventoryReportActiveTab') || 'overview';
        }

        function restoreActiveTab() {
            const activeTabId = getActiveTab();
            const tabButton = document.getElementById(activeTabId + '-tab');
            const tabPane = document.getElementById(activeTabId);
            
            if (tabButton && tabPane) {
                // Remove active class from all tabs
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    link.setAttribute('aria-selected', 'false');
                });
                
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Activate the saved tab
                tabButton.classList.add('active');
                tabButton.setAttribute('aria-selected', 'true');
                tabPane.classList.add('show', 'active');
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Restore active tab first
            restoreActiveTab();
            
            // Add click listeners to all tab buttons
            document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-bs-target').replace('#', '');
                    saveActiveTab(tabId);
                });
            });
            
            
            // Initialize all sections as printable
            document.querySelectorAll('.section-content').forEach(section => {
                section.classList.add('printable-section');
            });
            
            // Stock In event listeners
            document.getElementById('stockInSearch').addEventListener('input', function() {
                searchTable('stockIn', this.value);
            });
            
            document.getElementById('stockInShowEntries').addEventListener('change', function() {
                changeEntriesPerPage('stockIn', this.value);
            });
            
            // Stock Out event listeners
            document.getElementById('stockOutSearch').addEventListener('input', function() {
                searchTable('stockOut', this.value);
            });
            
            document.getElementById('stockOutShowEntries').addEventListener('change', function() {
                changeEntriesPerPage('stockOut', this.value);
            });
            
            // Current Stock event listeners
            document.getElementById('currentStockSearch').addEventListener('input', function() {
                searchTable('currentStock', this.value);
            });
            
            document.getElementById('currentStockFilter').addEventListener('change', function() {
                filterTable('currentStock', this.value);
            });
            
            document.getElementById('currentStockShowEntries').addEventListener('change', function() {
                changeEntriesPerPage('currentStock', this.value);
            });
        });


        function toggleCustomDate() {
            document.getElementById('customDateRange').style.display = 'flex';
            document.querySelectorAll('button.filter-btn').forEach(btn => btn.classList.remove('active'));
        }

        const categoryLabels = <?php echo json_encode(array_column($category_data, 'category_name')); ?>;
        const categoryData = <?php echo json_encode(array_map('floatval', array_column($category_data, 'total_value'))); ?>;
        const brandLabels = <?php echo json_encode(array_column($brand_data, 'brand_name')); ?>;
        const brandData = <?php echo json_encode(array_map('floatval', array_column($brand_data, 'total_value'))); ?>;

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        const brandCtx = document.getElementById('brandChart').getContext('2d');
        const brandChart = new Chart(brandCtx, {
            type: 'doughnut',
            data: {
                labels: brandLabels,
                datasets: [{
                    data: brandData,
                    backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // PDF Download Functionality
        function showPDFOptions() {
            const modal = new bootstrap.Modal(document.getElementById('pdfOptionsModal'));
            modal.show();
        }
        
        function generateSelectedPDF() {
            // Get selected options
            const selectedSections = {
                overview: document.getElementById('includeOverview').checked,
                stockIn: document.getElementById('includeStockIn').checked,
                stockOut: document.getElementById('includeStockOut').checked,
                currentStock: document.getElementById('includeCurrentStock').checked,
                analytics: document.getElementById('includeAnalytics').checked,
                lowStock: document.getElementById('includeLowStock').checked
            };
            
            // Check if at least one section is selected
            const hasSelection = Object.values(selectedSections).some(selected => selected);
            if (!hasSelection) {
                alert('Please select at least one section to include in the PDF.');
                return;
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('pdfOptionsModal'));
            modal.hide();
            
            // Generate PDF with selected sections
            downloadPDF(selectedSections);
        }
        
        function downloadPDF(selectedSections = null) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'mm', 'letter'); // Landscape orientation, letter size
            
            // Set up fonts and colors
            doc.setFont('helvetica');
            
            // Add header
            doc.setFontSize(20);
            doc.setTextColor(40, 40, 40);
            doc.text('Professional Inventory Report', 20, 20);
            
            // Add report details
            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);
            doc.text('Generated on: ' + new Date().toLocaleString(), 20, 30);
            
            // Add period information
            const periodText = getPeriodText();
            doc.text('Period: ' + periodText, 20, 36);
            
            let currentY = 50;
            
            // Add Overview section if selected
            if (!selectedSections || selectedSections.overview) {
                doc.setFontSize(14);
                doc.setTextColor(40, 40, 40);
                doc.text('Summary', 20, currentY);
                
                // Summary data
                doc.setFontSize(10);
                doc.setTextColor(60, 60, 60);
                const summaryData = [
                    ['Total Products', '<?= number_format($summary['total_products'] ?? 0) ?>'],
                    ['Total Units', '<?= number_format($summary['total_quantity'] ?? 0) ?>'],
                    ['Stock In Records', '<?= number_format(count($stock_in_data)) ?>'],
                    ['Stock Out Records', '<?= number_format(count($stock_out_data)) ?>']
                ];
                
                doc.autoTable({
                    startY: currentY + 5,
                    head: [['Metric', 'Value']],
                    body: summaryData,
                    theme: 'grid',
                    headStyles: { fillColor: [102, 126, 234], textColor: 255 },
                    styles: { fontSize: 10, cellPadding: 3 }
                });
                
                currentY = doc.lastAutoTable.finalY + 15;
            }
            
            // Add Stock In table if selected
            if ((!selectedSections || selectedSections.stockIn) && <?= count($stock_in_data) ?> > 0) {
                doc.setFontSize(14);
                doc.setTextColor(40, 40, 40);
                doc.text('Stock In Report', 20, currentY);
                
                const stockInData = <?= json_encode($stock_in_data) ?>.map(item => [
                    item.product_name || 'N/A',
                    item.serial_number || 'N/A',
                    item.category_name || 'Uncategorized',
                    item.brand_name || 'No Brand',
                    item.quantity || '0',
                    'PHP ' + (parseFloat(item.buying_price) || 0).toFixed(2),
                    new Date(item.created_at).toLocaleDateString()
                ]);
                
                doc.autoTable({
                    startY: currentY + 5,
                    head: [['Product Name', 'Model/Serial', 'Category', 'Brand', 'Quantity', 'Unit Price', 'Date Added']],
                    body: stockInData,
                    theme: 'grid',
                    headStyles: { fillColor: [46, 204, 113], textColor: 255 },
                    styles: { fontSize: 8, cellPadding: 2 },
                    columnStyles: {
                        4: { halign: 'center' },
                        5: { halign: 'right' },
                        6: { halign: 'center' }
                    }
                });
                
                currentY = doc.lastAutoTable.finalY + 15;
            }
            
            // Add Stock Out table if selected
            if ((!selectedSections || selectedSections.stockOut) && <?= count($stock_out_data) ?> > 0) {
                doc.setFontSize(14);
                doc.setTextColor(40, 40, 40);
                doc.text('Stock Out Report', 20, currentY);
                
                const stockOutData = <?= json_encode($stock_out_data) ?>.map(item => [
                    item.product_name || 'N/A',
                    item.serial_number || 'N/A',
                    item.quantity || '0',
                    item.cashier || 'System',
                    new Date(item.created_at).toLocaleDateString(),
                    '#' + (item.sale_id || 'N/A')
                ]);
                
                doc.autoTable({
                    startY: currentY + 5,
                    head: [['Product Name', 'Model/Serial', 'Quantity', 'Processed By', 'Date Out', 'Reference ID']],
                    body: stockOutData,
                    theme: 'grid',
                    headStyles: { fillColor: [230, 126, 34], textColor: 255 },
                    styles: { fontSize: 8, cellPadding: 2 },
                    columnStyles: {
                        2: { halign: 'center' },
                        4: { halign: 'center' },
                        5: { halign: 'center' }
                    }
                });
                
                currentY = doc.lastAutoTable.finalY + 15;
            }
            
            // Add Current Stock table if selected
            if ((!selectedSections || selectedSections.currentStock) && <?= count($inventory_data) ?> > 0) {
                doc.setFontSize(14);
                doc.setTextColor(40, 40, 40);
                doc.text('Current Stock Levels', 20, currentY);
                
                const currentStockData = <?= json_encode($inventory_data) ?>.map(item => [
                    item.product_name || 'N/A',
                    item.serial_number || 'N/A',
                    item.category_name || 'Uncategorized',
                    item.brand_name || 'No Brand',
                    item.quantity || '0',
                    item.quantity <= 5 ? 'Low Stock' : item.quantity <= 20 ? 'Medium Stock' : 'Good Stock',
                    new Date(item.updated_at).toLocaleDateString()
                ]);
                
                doc.autoTable({
                    startY: currentY + 5,
                    head: [['Product Name', 'Model/Serial', 'Category', 'Brand', 'Current Stock', 'Status', 'Last Updated']],
                    body: currentStockData,
                    theme: 'grid',
                    headStyles: { fillColor: [52, 152, 219], textColor: 255 },
                    styles: { fontSize: 8, cellPadding: 2 },
                    columnStyles: {
                        4: { halign: 'center' },
                        5: { halign: 'center' },
                        6: { halign: 'center' }
                    }
                });
                
                currentY = doc.lastAutoTable.finalY + 15;
            }
            
            // Add Analytics section if selected
            if (!selectedSections || selectedSections.analytics) {
                doc.setFontSize(14);
                doc.setTextColor(40, 40, 40);
                doc.text('Analytics Summary', 20, currentY);
                
                // Category analysis
                const categoryData = <?= json_encode($category_data) ?>;
                if (categoryData.length > 0) {
                    doc.setFontSize(12);
                    doc.text('Inventory by Category', 20, currentY + 10);
                    
                    const categoryTableData = categoryData.map(item => [
                        item.category_name || 'Uncategorized',
                        item.product_count || '0',
                        item.total_quantity || '0',
                        'PHP ' + (parseFloat(item.total_value) || 0).toFixed(2)
                    ]);
                    
                    doc.autoTable({
                        startY: currentY + 15,
                        head: [['Category', 'Products', 'Total Quantity', 'Total Value']],
                        body: categoryTableData,
                        theme: 'grid',
                        headStyles: { fillColor: [155, 89, 182], textColor: 255 },
                        styles: { fontSize: 9, cellPadding: 2 },
                        columnStyles: {
                            1: { halign: 'center' },
                            2: { halign: 'center' },
                            3: { halign: 'right' }
                        }
                    });
                    
                    currentY = doc.lastAutoTable.finalY + 10;
                }
                
                // Brand analysis
                const brandData = <?= json_encode($brand_data) ?>;
                if (brandData.length > 0) {
                    doc.setFontSize(12);
                    doc.text('Inventory by Brand', 20, currentY);
                    
                    const brandTableData = brandData.map(item => [
                        item.brand_name || 'No Brand',
                        item.product_count || '0',
                        item.total_quantity || '0',
                        'PHP ' + (parseFloat(item.total_value) || 0).toFixed(2)
                    ]);
                    
                    doc.autoTable({
                        startY: currentY + 5,
                        head: [['Brand', 'Products', 'Total Quantity', 'Total Value']],
                        body: brandTableData,
                        theme: 'grid',
                        headStyles: { fillColor: [52, 152, 219], textColor: 255 },
                        styles: { fontSize: 9, cellPadding: 2 },
                        columnStyles: {
                            1: { halign: 'center' },
                            2: { halign: 'center' },
                            3: { halign: 'right' }
                        }
                    });
                    
                    currentY = doc.lastAutoTable.finalY + 15;
                }
            }
            
            // Add Low Stock Alert if selected and applicable
            const lowStockData = <?= json_encode($low_stock_data) ?>;
            if ((!selectedSections || selectedSections.lowStock) && lowStockData.length > 0) {
                doc.setFontSize(14);
                doc.setTextColor(231, 76, 60);
                doc.text('Low Stock Alert', 20, currentY);
                
                const lowStockTableData = lowStockData.map(item => [
                    item.product_name || 'N/A',
                    item.serial_number || 'N/A',
                    item.category_name || 'Uncategorized',
                    item.brand_name || 'No Brand',
                    item.quantity || '0',
                    'Critical'
                ]);
                
                doc.autoTable({
                    startY: currentY + 5,
                    head: [['Product Name', 'Model/Serial', 'Category', 'Brand', 'Current Stock', 'Status']],
                    body: lowStockTableData,
                    theme: 'grid',
                    headStyles: { fillColor: [231, 76, 60], textColor: 255 },
                    styles: { fontSize: 8, cellPadding: 2 },
                    columnStyles: {
                        4: { halign: 'center' },
                        5: { halign: 'center' }
                    }
                });
            }
            
            // Add footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150, 150, 150);
                doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                doc.text('Generated by AUS Inventory System', 20, doc.internal.pageSize.height - 10);
            }
            
            // Download the PDF
            const selectedSectionsText = selectedSections ? 
                Object.keys(selectedSections).filter(key => selectedSections[key]).join('_') : 'all';
            const fileName = 'Inventory_Report_' + selectedSectionsText + '_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(fileName);
        }
        
        function getPeriodText() {
            const filter = '<?= $filter ?>';
            const startDate = '<?= $start_date ?>';
            const endDate = '<?= $end_date ?>';
            
            switch(filter) {
                case 'today': return 'Today';
                case 'week': return 'This Week';
                case 'month': return 'This Month';
                case 'year': return 'This Year';
                case 'custom': return startDate + ' to ' + endDate;
                default: return 'Overall';
            }
        }
    </script>
</body> 
</html>