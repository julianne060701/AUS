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
        $date_condition = "WHERE DATE(schedule_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "WHERE schedule_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "WHERE MONTH(schedule_date) = MONTH(CURDATE()) AND YEAR(schedule_date) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE YEAR(schedule_date) = YEAR(CURDATE())";
        break;
    case 'custom':
        if($start_date && $end_date) {
            $date_condition = "WHERE schedule_date BETWEEN ? AND ?";
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
    COUNT(*) as total_schedules,
    COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count,
    COUNT(DISTINCT installer_name) as unique_installers
FROM installer_schedules 
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

$install_query = "SELECT 
    id, installer_name, customer_name, contact_number, address, schedule_date,
    schedule_time, service_type, products_to_install, notes, status, created_at, updated_at
FROM installer_schedules 
$date_condition 
ORDER BY schedule_date DESC, schedule_time DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($install_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $install_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($install_query);
    $install_data = $result->fetch_all(MYSQLI_ASSOC);
}

$installer_query = "SELECT 
    installer_name, COUNT(*) as total_schedules,
    COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count
FROM installer_schedules 
$date_condition 
GROUP BY installer_name 
ORDER BY total_schedules DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($installer_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $installer_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($installer_query);
    $installer_data = $result->fetch_all(MYSQLI_ASSOC);
}

$service_query = "SELECT 
    service_type, COUNT(*) as total_count,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled_count,
    COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count
FROM installer_schedules 
$date_condition 
GROUP BY service_type 
ORDER BY total_count DESC";

if(!empty($params)) {
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $service_data = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query($service_query);
    $service_data = $result->fetch_all(MYSQLI_ASSOC);
}

$chart_query = "SELECT 
    DATE(schedule_date) as install_date,
    COUNT(*) as daily_schedules,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as daily_completed
FROM installer_schedules
WHERE schedule_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(schedule_date) 
ORDER BY install_date ASC";

$result = $conn->query($chart_query);
$chart_data = $result->fetch_all(MYSQLI_ASSOC);
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <?php include('../includes/header.php'); ?> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        .status-scheduled { color: #ffc107; font-weight: bold; }
        .status-in-progress { color: #17a2b8; font-weight: bold; }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .badge-scheduled { background-color: #ffc107; color: #000; }
        .badge-in-progress { background-color: #17a2b8; color: #fff; }
        .badge-completed { background-color: #28a745; color: #fff; }
        .badge-cancelled { background-color: #dc3545; color: #fff; }
        .print-header { display: none; }
        .section-checkbox { margin-right: 15px; }
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
        
        /* Installer Performance Table - Purple Theme */
        .table-info th { background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%); }
        .table-info th:hover { background: linear-gradient(135deg, #8E44AD 0%, #7D3C98 100%); }
        .table-info tbody tr:hover { background-color: rgba(155, 89, 182, 0.1); }
        
        /* Service Types Table - Teal Theme */
        .table-secondary th { background: linear-gradient(135deg, #1ABC9C 0%, #16A085 100%); }
        .table-secondary th:hover { background: linear-gradient(135deg, #16A085 0%, #138D75 100%); }
        .table-secondary tbody tr:hover { background-color: rgba(26, 188, 156, 0.1); }
        
        /* Detailed Schedules Table - Dark Theme */
        .table-dark th { background: linear-gradient(135deg, #34495E 0%, #2C3E50 100%); }
        .table-dark th:hover { background: linear-gradient(135deg, #2C3E50 0%, #1B2631 100%); }
        .table-dark tbody tr:hover { background-color: rgba(52, 73, 94, 0.1); }
        
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
        .badge-scheduled { background: linear-gradient(135deg, #F39C12 0%, #E67E22 100%); color: #fff; }
        .badge-in-progress { background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%); color: #fff; }
        .badge-completed { background: linear-gradient(135deg, #2ECC71 0%, #27AE60 100%); color: #fff; }
        .badge-cancelled { background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%); color: #fff; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media print {
            body * { visibility: hidden; }
            .printable-section, .printable-section * { visibility: visible; }
            .print-header { visibility: visible !important; display: block !important; }
            #wrapper, #content-wrapper { margin: 0; padding: 0; }
            .sidebar, .topbar, .filter-card, .no-print, .nav-tabs { display: none !important; }
            /* Hide search controls and pagination in print */
            .input-group, .form-select, #pagination, .dataTables_info, .btn-outline-secondary { display: none !important; }
            .card-header .text-muted { display: none !important; }
            .card { page-break-inside: avoid; border: 1px solid #ddd; box-shadow: none; }
            .summary-card:hover { transform: none; }
            .table-responsive { max-height: none; overflow: visible; }
            .chart-container { page-break-inside: avoid; }
            .print-header { display: block; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
            .tab-content { padding-top: 0; }
            .tab-pane { display: block !important; opacity: 1 !important; }
            /* Ensure all table rows are visible */
            #detailedTableBody tr { display: table-row !important; }
            /* Remove onclick cursors */
            th { cursor: default !important; }
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
                        <h1 class="h3 mb-0 text-gray-800">Installation Report</h1>
                        <div>
                            <button class="btn btn-primary" onclick="showPrintModal()"><i class="fas fa-print"></i> Print Report</button>
                            <button class="btn btn-success" onclick="showDownloadModal()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                        </div>
                    </div>

                    <!-- Print/Download Selection Modal -->
                    <div class="modal fade" id="selectionModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTitle">Select Sections to Print</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <div class="section-checkbox">
                                            <input type="checkbox" id="selectAll" checked onchange="toggleAllSections()">
                                            <label for="selectAll"><strong>Select All</strong></label>
                                        </div>
                                        <hr>
                                        <div class="section-checkbox">
                                            <input type="checkbox" class="section-check" id="check-summary" checked>
                                            <label for="check-summary">Summary Cards</label>
                                        </div>
                                        <div class="section-checkbox">
                                            <input type="checkbox" class="section-check" id="check-charts" checked>
                                            <label for="check-charts">Installation Trend & Service Types</label>
                                        </div>
                                        <div class="section-checkbox">
                                            <input type="checkbox" class="section-check" id="check-installer" checked>
                                            <label for="check-installer">Installer Performance</label>
                                        </div>
                                        <div class="section-checkbox">
                                            <input type="checkbox" class="section-check" id="check-service" checked>
                                            <label for="check-service">Service Type Breakdown</label>
                                        </div>
                                        <div class="section-checkbox">
                                            <input type="checkbox" class="section-check" id="check-detailed" checked>
                                            <label for="check-detailed">Detailed Installation Schedules (Complete Table)</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmBtn" onclick="confirmAction()">Print</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-card no-print">
                        <h5 class="mb-3">Filter Installation Data</h5>
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
                            <h2>Installation Report</h2>
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
                        <ul class="nav nav-tabs no-print" id="reportTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">
                                    <i class="fas fa-chart-pie"></i> Summary
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab" aria-controls="trends" aria-selected="false">
                                    <i class="fas fa-chart-line"></i> Installation Trends
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="installer-tab" data-bs-toggle="tab" data-bs-target="#installer" type="button" role="tab" aria-controls="installer" aria-selected="false">
                                    <i class="fas fa-users"></i> Installer Performance
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab" aria-controls="service" aria-selected="false">
                                    <i class="fas fa-cogs"></i> Service Types
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="detailed-tab" data-bs-toggle="tab" data-bs-target="#detailed" type="button" role="tab" aria-controls="detailed" aria-selected="false">
                                    <i class="fas fa-list"></i> Detailed Schedules
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="reportTabContent">
                            <!-- Summary Tab -->
                            <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                                <div id="section-summary" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Schedules</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_schedules'] ?? 0) ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-calendar-alt fa-2x text-gray-300"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-success shadow h-100 py-2 summary-card">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['completed_count'] ?? 0) ?></div>
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
                                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">In Progress</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['in_progress_count'] ?? 0) ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-warning shadow h-100 py-2 summary-card">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Installers</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['unique_installers'] ?? 0) ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Installation Trends Tab -->
                            <div class="tab-pane fade" id="trends" role="tabpanel" aria-labelledby="trends-tab">
                                <div id="section-charts" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-lg-8">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Installation Trend (Last 30 Days)</h6></div>
                                                <div class="card-body"><div class="chart-container"><canvas id="installTrendChart"></canvas></div></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Service Types Distribution</h6></div>
                                                <div class="card-body"><div class="chart-container"><canvas id="serviceChart"></canvas></div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Installer Performance Tab -->
                            <div class="tab-pane fade" id="installer" role="tabpanel" aria-labelledby="installer-tab">
                                <div id="section-installer" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Installer Performance</h6></div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="installerTable">
                                                            <thead class="table-info">
                                                                <tr>
                                                                    <th onclick="sortInstallerTable(0)">Installer Name <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(1)">Total Schedules <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(2)">Scheduled <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(3)">In Progress <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(4)">Completed <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(5)">Cancelled <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortInstallerTable(6)">Completion Rate <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if(empty($installer_data)): ?>
                                                                <tr><td colspan="7" class="text-center">No installer data available</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($installer_data as $row): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row['installer_name']) ?></td>
                                                                    <td><?= number_format($row['total_schedules']) ?></td>
                                                                    <td><span class="badge badge-scheduled"><?= number_format($row['scheduled_count']) ?></span></td>
                                                                    <td><span class="badge badge-in-progress"><?= number_format($row['in_progress_count']) ?></span></td>
                                                                    <td><span class="badge badge-completed"><?= number_format($row['completed_count']) ?></span></td>
                                                                    <td><span class="badge badge-cancelled"><?= number_format($row['cancelled_count']) ?></span></td>
                                                                    <td><?= $row['total_schedules'] > 0 ? number_format(($row['completed_count'] / $row['total_schedules']) * 100, 1) : 0 ?>%</td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Types Tab -->
                            <div class="tab-pane fade" id="service" role="tabpanel" aria-labelledby="service-tab">
                                <div id="section-service" class="section-content">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Service Type Breakdown</h6></div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="serviceTable">
                                                            <thead class="table-secondary">
                                                                <tr>
                                                                    <th onclick="sortServiceTable(0)">Service Type <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(1)">Total Count <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(2)">Scheduled <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(3)">In Progress <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(4)">Completed <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(5)">Cancelled <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortServiceTable(6)">Completion Rate <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if(empty($service_data)): ?>
                                                                <tr><td colspan="7" class="text-center">No service data available</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($service_data as $row): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                                                                    <td><?= number_format($row['total_count']) ?></td>
                                                                    <td><span class="badge badge-scheduled"><?= number_format($row['scheduled_count']) ?></span></td>
                                                                    <td><span class="badge badge-in-progress"><?= number_format($row['in_progress_count']) ?></span></td>
                                                                    <td><span class="badge badge-completed"><?= number_format($row['completed_count']) ?></span></td>
                                                                    <td><span class="badge badge-cancelled"><?= number_format($row['cancelled_count']) ?></span></td>
                                                                    <td><?= $row['total_count'] > 0 ? number_format(($row['completed_count'] / $row['total_count']) * 100, 1) : 0 ?>%</td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detailed Schedules Tab -->
                            <div class="tab-pane fade" id="detailed" role="tabpanel" aria-labelledby="detailed-tab">
                                <div id="section-detailed" class="section-content">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card shadow mb-4">
                                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                    <h6 class="m-0 font-weight-bold text-primary">Detailed Installation Schedules</h6>
                                                    <div class="text-muted">
                                                        <span id="totalRecords"><?= count($install_data) ?></span> records found
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Search and Filter Controls -->
                                                    <div class="row mb-3 no-print">
                                                        <div class="col-md-4">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by customer, installer, or address...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select" id="statusFilter">
                                                                <option value="">All Status</option>
                                                                <option value="Scheduled">Scheduled</option>
                                                                <option value="In Progress">In Progress</option>
                                                                <option value="Completed">Completed</option>
                                                                <option value="Cancelled">Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select" id="installerFilter">
                                                                <option value="">All Installers</option>
                                                                <?php 
                                                                $unique_installers = array_unique(array_column($install_data, 'installer_name'));
                                                                foreach($unique_installers as $installer): ?>
                                                                <option value="<?= htmlspecialchars($installer) ?>"><?= htmlspecialchars($installer) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select" id="serviceFilter">
                                                                <option value="">All Services</option>
                                                                <?php 
                                                                $unique_services = array_unique(array_column($install_data, 'service_type'));
                                                                foreach($unique_services as $service): ?>
                                                                <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select" id="rowsPerPage">
                                                                <option value="10">10 per page</option>
                                                                <option value="25" selected>25 per page</option>
                                                                <option value="50">50 per page</option>
                                                                <option value="100">100 per page</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Table -->
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover" id="detailedTable">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th onclick="sortTable(0)">ID <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(1)">Installer <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(2)">Customer <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(3)">Contact <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(4)">Address <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(5)">Schedule Date <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(6)">Time <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(7)">Service Type <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(8)">Status <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(9)">Products <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(10)">Notes <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(11)">Created <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="detailedTableBody">
                                                                <?php if(empty($install_data)): ?>
                                                                <tr><td colspan="12" class="text-center">No installation data available</td></tr>
                                                                <?php else: ?>
                                                                <?php foreach($install_data as $install): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($install['id']) ?></td>
                                                                    <td><?= htmlspecialchars($install['installer_name']) ?></td>
                                                                    <td><?= htmlspecialchars($install['customer_name']) ?></td>
                                                                    <td><?= htmlspecialchars($install['contact_number']) ?></td>
                                                                    <td><?= htmlspecialchars(substr($install['address'], 0, 50)) ?><?= strlen($install['address']) > 50 ? '...' : '' ?></td>
                                                                    <td><?= date('M j, Y', strtotime($install['schedule_date'])) ?></td>
                                                                    <td><?= date('g:i A', strtotime($install['schedule_time'])) ?></td>
                                                                    <td><?= htmlspecialchars($install['service_type']) ?></td>
                                                                    <td>
                                                                        <span class="badge badge-<?= strtolower(str_replace(' ', '-', $install['status'])) ?>">
                                                                            <?= htmlspecialchars($install['status']) ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?= htmlspecialchars(substr($install['products_to_install'], 0, 30)) ?><?= strlen($install['products_to_install']) > 30 ? '...' : '' ?></td>
                                                                    <td><?= htmlspecialchars(substr($install['notes'], 0, 30)) ?><?= strlen($install['notes']) > 30 ? '...' : '' ?></td>
                                                                    <td><?= date('M j, Y', strtotime($install['created_at'])) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Pagination -->
                                                    <div class="d-flex justify-content-between align-items-center mt-3 no-print">
                                                        <div class="text-muted">
                                                            Showing <span id="showingStart">1</span> to <span id="showingEnd">25</span> of <span id="totalFiltered"><?= count($install_data) ?></span> entries
                                                        </div>
                                                        <nav>
                                                            <ul class="pagination pagination-sm mb-0" id="pagination">
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
                    </div>
                </div> 
            </div> 
            <?php include('../includes/footer.php'); ?> 
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentAction = 'print';

        function showPrintModal() {
            currentAction = 'print';
            document.getElementById('modalTitle').textContent = 'Select Sections to Print';
            document.getElementById('confirmBtn').textContent = 'Print';
            $('#selectionModal').modal('show');
        }

        function showDownloadModal() {
            currentAction = 'download';
            document.getElementById('modalTitle').textContent = 'Download Installation Report';
            document.getElementById('confirmBtn').textContent = 'Download PDF';
            
            // Set default to only detailed table
            document.getElementById('selectAll').checked = false;
            document.getElementById('check-summary').checked = false;
            document.getElementById('check-charts').checked = false;
            document.getElementById('check-installer').checked = false;
            document.getElementById('check-service').checked = false;
            document.getElementById('check-detailed').checked = true;
            
            $('#selectionModal').modal('show');
        }

        function toggleAllSections() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.section-check');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        document.querySelectorAll('.section-check').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(document.querySelectorAll('.section-check')).every(cb => cb.checked);
                document.getElementById('selectAll').checked = allChecked;
            });
        });

        function confirmAction() {
            const sections = {
                summary: document.getElementById('check-summary').checked,
                charts: document.getElementById('check-charts').checked,
                installer: document.getElementById('check-installer').checked,
                service: document.getElementById('check-service').checked,
                detailed: document.getElementById('check-detailed').checked
            };

            // Hide all sections first
            document.querySelectorAll('.section-content').forEach(section => {
                section.classList.remove('printable-section');
            });

            // Show only selected sections
            if(sections.summary) document.getElementById('section-summary').classList.add('printable-section');
            if(sections.charts) document.getElementById('section-charts').classList.add('printable-section');
            if(sections.installer) document.getElementById('section-installer').classList.add('printable-section');
            if(sections.service) document.getElementById('section-service').classList.add('printable-section');
            if(sections.detailed) {
                document.getElementById('section-detailed').classList.add('printable-section');
                // Show all rows in the detailed table for print/PDF
                // Make sure we use allTableData if filteredData is empty
                if (filteredData.length === 0 && allTableData.length > 0) {
                    filteredData = [...allTableData];
                }
                showAllTableRowsForPrint();
            }

            $('#selectionModal').modal('hide');

            if(currentAction === 'print') {
                setTimeout(() => {
                    window.print();
                    // Restore pagination after print
                    setTimeout(() => restoreTablePagination(), 500);
                }, 300);
            } else {
                setTimeout(() => {
                    downloadSelectedPDF();
                    // Restore pagination after download
                    setTimeout(() => restoreTablePagination(), 500);
                }, 300);
            }

            // Reset after action
            setTimeout(() => {
                document.querySelectorAll('.section-content').forEach(section => {
                    section.classList.add('printable-section');
                });
            }, 2000);
        }

        function downloadSelectedPDF() {
            // Store original table state
            const originalTableHTML = document.getElementById('detailedTableBody').innerHTML;
            
            // Show all rows for PDF
            showAllTableRowsForPrint();
            
            // Create a clean version for PDF with only table content
            const cleanContent = createCleanPDFContent();
            
            // Create temporary container - no padding for edge-to-edge
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = cleanContent;
            tempDiv.style.padding = '0';
            tempDiv.style.margin = '0';
            tempDiv.style.backgroundColor = 'white';
            tempDiv.style.width = '100%';
            tempDiv.style.maxWidth = '100%';
            tempDiv.style.minWidth = '100%';
            tempDiv.style.boxSizing = 'border-box';
            tempDiv.style.position = 'relative';
            tempDiv.style.textAlign = 'center';
            tempDiv.style.overflow = 'visible';
            tempDiv.style.fontFamily = 'Arial, sans-serif';
            document.body.appendChild(tempDiv);
            
            const opt = {
                margin: [0.2, 0.2, 0.2, 0.2],
                filename: 'installation_report_' + new Date().toISOString().slice(0,10) + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2.2, 
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    width: 1800,
                    height: 1200,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: { 
                    unit: 'in', 
                    format: 'letter', 
                    orientation: 'landscape',
                    compress: true
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(tempDiv).save().then(() => {
                // Remove temporary div
                document.body.removeChild(tempDiv);
                // Restore original table state
                document.getElementById('detailedTableBody').innerHTML = originalTableHTML;
            });
        }

        function createCleanPDFContent() {
            const sections = {
                summary: document.getElementById('check-summary').checked,
                charts: document.getElementById('check-charts').checked,
                installer: document.getElementById('check-installer').checked,
                service: document.getElementById('check-service').checked,
                detailed: document.getElementById('check-detailed').checked
            };

            let content = `
                <div class="print-header" style="display: block !important; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333;">
                    <h2 style="margin: 0; color: #333;">Installation Report</h2>
                    <p style="margin: 5px 0; color: #666;">Generated on: ${new Date().toLocaleString()}</p>
                    <p style="margin: 5px 0; color: #666;">Period: ${getCurrentFilterText()}</p>
                </div>
            `;

            if (sections.summary) {
                content += document.getElementById('section-summary').outerHTML;
            }
            
            if (sections.charts) {
                content += document.getElementById('section-charts').outerHTML;
            }
            
            if (sections.installer) {
                content += createCleanTableContent('installer');
            }
            
            if (sections.service) {
                content += createCleanTableContent('service');
            }
            
            if (sections.detailed) {
                content += createCleanDetailedTableContent();
            }

            return content;
        }

        function createCleanTableContent(tableType) {
            const tableId = tableType === 'installer' ? 'installerTable' : 'serviceTable';
            const table = document.querySelector(`#section-${tableType} table`);
            
            if (!table) return '';
            
            // Clone the table
            const cleanTable = table.cloneNode(true);
            
            // Remove sort icons and onclick
            cleanTable.querySelectorAll('th').forEach(th => {
                th.removeAttribute('onclick');
                th.style.cursor = 'default';
                const icon = th.querySelector('i');
                if (icon) icon.remove();
            });
            
            // Create clean card wrapper
            const cardTitle = getTableTitle(tableType);
            return `
                <div class="card shadow mb-4" style="page-break-inside: avoid;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">${cardTitle}</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            ${cleanTable.outerHTML}
                        </div>
                    </div>
                </div>
            `;
        }

        function createCleanDetailedTableContent() {
            // Get all filtered data (not just current page)
            const table = document.getElementById('detailedTable');
            
            if (!table) return '';
            
            // Use allTableData if filteredData is empty or not initialized
            const dataToUse = (filteredData && filteredData.length > 0) ? filteredData : allTableData;
            
            // Create a completely new table structure - fully extended to edges
            const cleanTable = document.createElement('table');
            cleanTable.style.fontSize = '10px';
            cleanTable.style.width = '100%';
            cleanTable.style.minWidth = '100%';
            cleanTable.style.tableLayout = 'fixed';
            cleanTable.style.margin = '0';
            cleanTable.style.padding = '0';
            cleanTable.style.borderCollapse = 'collapse';
            cleanTable.style.position = 'relative';
            cleanTable.style.border = '1px solid #333';
            cleanTable.style.fontFamily = 'Arial, sans-serif';
            
            // Copy the header
            const originalHeader = table.querySelector('thead');
            if (originalHeader) {
                const cleanHeader = originalHeader.cloneNode(true);
                // Remove sort icons from header
                cleanHeader.querySelectorAll('i').forEach(icon => icon.remove());
                // Style header for landscape single column layout
                const columnWidths = ['4%', '9%', '9%', '6%', '18%', '6%', '4%', '8%', '6%', '11%', '11%', '8%'];
                cleanHeader.querySelectorAll('th').forEach((th, index) => {
                    th.removeAttribute('onclick');
                    th.style.cursor = 'default';
                    th.style.fontSize = '10px';
                    th.style.padding = '8px 4px';
                    th.style.width = columnWidths[index] || 'auto';
                    th.style.textAlign = 'center';
                    th.style.fontWeight = 'bold';
                    th.style.margin = '0';
                    th.style.border = '1px solid #333';
                    th.style.backgroundColor = '#1f4e79';
                    th.style.color = 'white';
                    th.style.textTransform = 'uppercase';
                    th.style.fontFamily = 'Arial, sans-serif';
                    th.style.whiteSpace = 'nowrap';
                });
                cleanTable.appendChild(cleanHeader);
            }
            
            // Create new tbody with ALL data
            const cleanTableBody = document.createElement('tbody');
            
            // Use the appropriate data source
            if (dataToUse && dataToUse.length > 0) {
                dataToUse.forEach((row, rowIndex) => {
                    const tr = document.createElement('tr');
                    
                    // Create cells with complete data (no truncation)
                    const cells = [
                        row.id,
                        row.installer,
                        row.customer,
                        row.contact,
                        row.address, // Full address, no truncation
                        row.scheduleDate,
                        row.time,
                        row.serviceType,
                        row.status,
                        row.products, // Full products, no truncation
                        row.notes, // Full notes, no truncation
                        row.created
                    ];
                    
                    const columnWidths = ['4%', '9%', '9%', '6%', '18%', '6%', '4%', '8%', '6%', '11%', '11%', '8%'];
                    cells.forEach((cellData, index) => {
                        const td = document.createElement('td');
                        td.textContent = cellData || ''; // Handle null/undefined values
                        td.style.fontSize = '10px';
                        td.style.padding = '6px 4px';
                        td.style.wordWrap = 'break-word';
                        td.style.width = columnWidths[index] || 'auto';
                        td.style.verticalAlign = 'middle';
                        td.style.lineHeight = '1.2';
                        td.style.margin = '0';
                        td.style.border = '1px solid #333';
                        td.style.fontFamily = 'Arial, sans-serif';
                        
                        // Alternating row colors - light blue and white
                        if (rowIndex % 2 === 0) {
                            td.style.backgroundColor = '#e6f3ff'; // Light blue
                        } else {
                            td.style.backgroundColor = 'white'; // White
                        }
                        
                        // Special handling for specific columns
                        if (index === 0) { // ID column
                            td.style.textAlign = 'center';
                            td.style.fontWeight = 'bold';
                        } else if (index === 4 || index === 9 || index === 10) { // Address, Products, Notes
                            td.style.textAlign = 'left';
                            td.style.whiteSpace = 'normal';
                        } else if (index === 5 || index === 6) { // Date and Time
                            td.style.textAlign = 'center';
                        } else {
                            td.style.textAlign = 'center'; // Center align all other columns
                        }
                        
                        tr.appendChild(td);
                    });
                    
                    cleanTableBody.appendChild(tr);
                });
            } else {
                const noDataRow = document.createElement('tr');
                const colCount = table.querySelectorAll('th').length;
                noDataRow.innerHTML = `<td colspan="${colCount}" class="text-center">No data available</td>`;
                cleanTableBody.appendChild(noDataRow);
            }
            
            cleanTable.appendChild(cleanTableBody);
            
            const recordCount = dataToUse ? dataToUse.length : 0;
            
            return `
                <div style="page-break-inside: avoid; margin: 0; padding: 0; width: 100%; position: relative; columns: 1; column-count: 1;">
                    <div style="padding: 8px 0; margin: 0; text-align: center;">
                        <h2 style="font-size: 14px; margin: 0; font-weight: bold; color: #333; font-family: Arial, sans-serif;">Installation Report - Complete Details (${recordCount} records)</h2>
                    </div>
                    <div style="padding: 0; margin: 0; width: 100%; position: relative; columns: 1; column-count: 1;">
                        <div style="margin: 0; padding: 0; width: 100%; min-width: 100%; position: relative; columns: 1; column-count: 1;">
                            ${cleanTable.outerHTML}
                        </div>
                    </div>
                </div>
            `;
        }

        // Show all rows in table for printing
        function showAllTableRowsForPrint() {
            const tableBody = document.getElementById('detailedTableBody');
            if (!tableBody) return;
            
            // Use allTableData if filteredData is empty or not initialized
            const dataToShow = (filteredData && filteredData.length > 0) ? filteredData : allTableData;
            
            // Clear current table
            tableBody.innerHTML = '';
            
            // Add all data rows
            if (!dataToShow || dataToShow.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="12" class="text-center">No data available</td></tr>';
            } else {
                dataToShow.forEach(row => {
                    tableBody.appendChild(row.originalRow.cloneNode(true));
                });
            }
        }

        // Restore pagination after print
        function restoreTablePagination() {
            updateTable();
        }

        function getTableTitle(tableType) {
            const titles = {
                installer: 'Installer Performance',
                service: 'Service Type Breakdown'
            };
            return titles[tableType] || 'Table Report';
        }

        function getCurrentFilterText() {
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter') || 'overall';
            const startDate = urlParams.get('start_date') || '';
            const endDate = urlParams.get('end_date') || '';
            
            switch(filter) {
                case 'today': return 'Today';
                case 'week': return 'This Week';
                case 'month': return 'This Month';
                case 'year': return 'This Year';
                case 'custom': return `${startDate} to ${endDate}`;
                default: return 'Overall';
            }
        }

        function toggleCustomDate() {
            document.getElementById('customDateRange').style.display = 'flex';
            document.querySelectorAll('button.filter-btn').forEach(btn => btn.classList.remove('active'));
        }

        // Tab persistence functions
        function saveActiveTab(tabId) {
            localStorage.setItem('installReportActiveTab', tabId);
        }

        function getActiveTab() {
            return localStorage.getItem('installReportActiveTab') || 'summary';
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

        // Initialize all sections as printable
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
            
            document.querySelectorAll('.section-content').forEach(section => {
                section.classList.add('printable-section');
            });
            
            // Initialize detailed table functionality
            initializeDetailedTable();
        });

        // Detailed Table Management
        let allTableData = [];
        let filteredData = [];
        let currentPage = 1;
        let rowsPerPage = 25;
        let sortColumn = -1;
        let sortDirection = 'asc';

        function initializeDetailedTable() {
            // Store original data
            const tableBody = document.getElementById('detailedTableBody');
            if (!tableBody) return;
            
            const rows = tableBody.querySelectorAll('tr');
            allTableData = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0 && cells.length >= 12) {
                    // Skip "no data" rows
                    const firstCellText = cells[0].textContent.trim();
                    if (firstCellText && !firstCellText.includes('No installation') && !firstCellText.includes('No data')) {
                        // Get the original data from PHP variables to avoid truncation
                        const rowId = parseInt(cells[0].textContent.trim());
                        const originalData = <?php echo json_encode($install_data); ?>;
                        const originalRow = originalData.find(item => item.id == rowId);
                        
                        allTableData.push({
                            id: cells[0].textContent.trim(),
                            installer: cells[1].textContent.trim(),
                            customer: cells[2].textContent.trim(),
                            contact: cells[3].textContent.trim(),
                            address: originalRow ? originalRow.address : cells[4].textContent.trim(),
                            scheduleDate: cells[5].textContent.trim(),
                            time: cells[6].textContent.trim(),
                            serviceType: cells[7].textContent.trim(),
                            status: cells[8].textContent.trim(),
                            products: originalRow ? originalRow.products_to_install : cells[9].textContent.trim(),
                            notes: originalRow ? originalRow.notes : cells[10].textContent.trim(),
                            created: cells[11].textContent.trim(),
                            originalRow: row.cloneNode(true)
                        });
                    }
                }
            });
            
            filteredData = [...allTableData];
            
            // Only update table if we have data
            if (allTableData.length > 0) {
                updateTable();
            }
            
            // Add event listeners
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const installerFilter = document.getElementById('installerFilter');
            const serviceFilter = document.getElementById('serviceFilter');
            const rowsPerPageSelect = document.getElementById('rowsPerPage');
            
            if (searchInput) searchInput.addEventListener('input', filterTable);
            if (statusFilter) statusFilter.addEventListener('change', filterTable);
            if (installerFilter) installerFilter.addEventListener('change', filterTable);
            if (serviceFilter) serviceFilter.addEventListener('change', filterTable);
            if (rowsPerPageSelect) {
                rowsPerPageSelect.addEventListener('change', function() {
                    rowsPerPage = parseInt(this.value);
                    currentPage = 1;
                    updateTable();
                });
            }
        }

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const installerFilter = document.getElementById('installerFilter').value;
            const serviceFilter = document.getElementById('serviceFilter').value;
            
            filteredData = allTableData.filter(row => {
                const matchesSearch = !searchTerm || 
                    row.customer.toLowerCase().includes(searchTerm) ||
                    row.installer.toLowerCase().includes(searchTerm) ||
                    row.address.toLowerCase().includes(searchTerm) ||
                    row.products.toLowerCase().includes(searchTerm) ||
                    row.notes.toLowerCase().includes(searchTerm);
                
                const matchesStatus = !statusFilter || row.status.includes(statusFilter);
                const matchesInstaller = !installerFilter || row.installer === installerFilter;
                const matchesService = !serviceFilter || row.serviceType === serviceFilter;
                
                return matchesSearch && matchesStatus && matchesInstaller && matchesService;
            });
            
            currentPage = 1;
            updateTable();
        }

        function sortTable(columnIndex) {
            if (sortColumn === columnIndex) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = columnIndex;
                sortDirection = 'asc';
            }
            
            const columnNames = ['id', 'installer', 'customer', 'contact', 'address', 'scheduleDate', 'time', 'serviceType', 'status', 'products', 'notes', 'created'];
            const columnName = columnNames[columnIndex];
            
            filteredData.sort((a, b) => {
                let aVal = a[columnName];
                let bVal = b[columnName];
                
                // Handle numeric sorting for ID
                if (columnName === 'id') {
                    aVal = parseInt(aVal);
                    bVal = parseInt(bVal);
                }
                
                if (sortDirection === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            updateTable();
            updateSortIcons();
        }

        function updateSortIcons() {
            const headers = document.querySelectorAll('#detailedTable th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort';
                    if (index === sortColumn) {
                        icon.className = sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                    }
                }
            });
        }

        function updateTable() {
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const pageData = filteredData.slice(startIndex, endIndex);
            
            const tableBody = document.getElementById('detailedTableBody');
            tableBody.innerHTML = '';
            
            if (pageData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="12" class="text-center">No data found</td></tr>';
            } else {
                pageData.forEach(row => {
                    tableBody.appendChild(row.originalRow.cloneNode(true));
                });
            }
            
            updatePagination();
            updateRecordCounts();
        }

        function updatePagination() {
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>`;
            pagination.appendChild(prevLi);
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>`;
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
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
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
                lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>`;
                pagination.appendChild(lastLi);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>`;
            pagination.appendChild(nextLi);
        }

        function changePage(page) {
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                updateTable();
            }
        }

        function updateRecordCounts() {
            const startIndex = (currentPage - 1) * rowsPerPage + 1;
            const endIndex = Math.min(currentPage * rowsPerPage, filteredData.length);
            
            document.getElementById('showingStart').textContent = filteredData.length > 0 ? startIndex : 0;
            document.getElementById('showingEnd').textContent = endIndex;
            document.getElementById('totalFiltered').textContent = filteredData.length;
            document.getElementById('totalRecords').textContent = allTableData.length;
        }

        // Sorting functions for other tables
        function sortInstallerTable(columnIndex) {
            sortGenericTable('installerTable', columnIndex);
        }

        function sortServiceTable(columnIndex) {
            sortGenericTable('serviceTable', columnIndex);
        }

        function sortGenericTable(tableId, columnIndex) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Skip if no data
            if (rows.length === 0 || rows[0].cells.length <= 1) return;
            
            // Determine sort direction
            const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
            const currentColumn = parseInt(table.getAttribute('data-sort-column') || -1);
            
            let direction = 'asc';
            if (currentColumn === columnIndex) {
                direction = currentDirection === 'asc' ? 'desc' : 'asc';
            }
            
            table.setAttribute('data-sort-column', columnIndex);
            table.setAttribute('data-sort-direction', direction);
            
            // Sort rows
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                // Remove % sign for percentage comparisons
                if (aVal.includes('%')) {
                    aVal = parseFloat(aVal.replace('%', ''));
                    bVal = parseFloat(bVal.replace('%', ''));
                }
                // Try to parse as number
                else if (!isNaN(aVal) && !isNaN(bVal)) {
                    aVal = parseFloat(aVal);
                    bVal = parseFloat(bVal);
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                } else {
                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort icons
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort';
                    if (index === columnIndex) {
                        icon.className = direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                    }
                }
            });
        }

        // Chart.js data and initialization
        const chartLabels = <?php echo json_encode(array_map(function($item) { return date('M j', strtotime($item['install_date'])); }, $chart_data)); ?>;
        const chartData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_schedules'))); ?>;
        const completedData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_completed'))); ?>;
        const serviceLabels = <?php echo json_encode(array_column($service_data, 'service_type')); ?>;
        const serviceData = <?php echo json_encode(array_map('intval', array_column($service_data, 'total_count'))); ?>;

        const trendCtx = document.getElementById('installTrendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Total Schedules',
                    data: chartData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Completed',
                    data: completedData,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });

        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        const serviceChart = new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: serviceLabels,
                datasets: [{
                    data: serviceData,
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
                                return context.label + ': ' + context.parsed;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body> 
</html>