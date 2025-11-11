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

// Build install query with proper alias handling for date_condition
$install_where = $date_condition;
if (!empty($install_where)) {
    // Replace column references with alias for the joined query
    $install_where = str_replace('schedule_date', 'sched.schedule_date', $install_where);
    $install_where = str_replace('installer_name', 'sched.installer_name', $install_where);
    $install_where = str_replace('status', 'sched.status', $install_where);
}

$install_query = "SELECT 
    sched.id, sched.installer_name, sched.customer_name, sched.contact_number, sched.address, sched.schedule_date,
    sched.schedule_time, sched.service_type, sched.products_to_install, sched.quantity_to_install, sched.notes, sched.status, sched.cancel_note, sched.created_at, sched.updated_at,
    COALESCE(p.product_name, sched.products_to_install) as product_name
FROM installer_schedules sched
LEFT JOIN products p ON sched.products_to_install IS NOT NULL 
    AND sched.products_to_install != '' 
    AND sched.products_to_install REGEXP '^[0-9]+$' 
    AND CAST(sched.products_to_install AS UNSIGNED) = p.id
$install_where 
ORDER BY sched.schedule_date DESC, sched.schedule_time DESC";

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
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Load libraries in correct order -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jsPDF and plugins - FIXED CDN LINKS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    
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
        
        /* Completed Schedules Modal Styles */
        #completedSchedulesModal .modal-content {
            border-radius: 0.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        #completedSchedulesModal .table-info th {
            background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
            color: white;
        }
        
        #completedSchedulesModal .table tbody tr:hover {
            background-color: rgba(155, 89, 182, 0.1);
        }
        
        #completedSchedulesModal .btn-info {
            background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        #completedSchedulesModal .btn-info:hover {
            background: linear-gradient(135deg, #2980B9 0%, #1F618D 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        /* View Button in Installer Table */
        #installerTable .btn-info {
            background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        #installerTable .btn-info:hover {
            background: linear-gradient(135deg, #2980B9 0%, #1F618D 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4"> 
                        <h1 class="h3 mb-0 text-gray-800">Installation Report</h1>
                        <div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pdfOptionsModal">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                        </div>
                    </div>

                    <div class="filter-card">
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
                        <!-- Rest of your HTML content remains the same -->
                        <!-- I'm keeping your existing tab structure here -->


                         <!-- Tab Navigation -->
                         <ul class="nav nav-tabs" id="reportTabs" role="tablist">
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
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if(empty($installer_data)): ?>
                                                                <tr><td colspan="8" class="text-center">No installer data available</td></tr>
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
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewCompletedSchedules('<?= htmlspecialchars($row['installer_name'], ENT_QUOTES) ?>')" title="View Schedules">
                                                                            <i class="fas fa-eye"></i> View
                                                                        </button>
                                                                    </td>
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
                                                    <div class="row mb-3">
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
                                                                    <th onclick="sortTable(10)">Quantity <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(11)">Notes <i class="fas fa-sort"></i></th>
                                                                    <th onclick="sortTable(12)">Created <i class="fas fa-sort"></i></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="detailedTableBody">
                                                                <?php if(empty($install_data)): ?>
                                                                <tr><td colspan="13" class="text-center">No installation data available</td></tr>
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
                                                                    <td><?= htmlspecialchars(substr($install['product_name'] ?? $install['products_to_install'], 0, 30)) ?><?= strlen($install['product_name'] ?? $install['products_to_install']) > 30 ? '...' : '' ?></td>
                                                                    <td><?= htmlspecialchars($install['quantity_to_install'] ?? '1') ?></td>
                                                                    <td><?= htmlspecialchars(substr($install['notes'], 0, 30)) ?><?= strlen($install['notes']) > 30 ? '...' : '' ?></td>
                                                                    <td><?= date('M j, Y', strtotime($install['created_at'])) ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Pagination -->
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
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

    <!-- Completed Schedules Modal -->
    <div class="modal fade" id="completedSchedulesModal" tabindex="-1" aria-labelledby="completedSchedulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%); color: white;">
                    <h5 class="modal-title" id="completedSchedulesModalLabel">
                        <i class="fas fa-check-circle"></i> Installation Schedules
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="text-primary" id="installerNameHeader">Installer: <span id="installerNameDisplay"></span></h6>
                        <p class="text-muted mb-0">Total Schedules: <strong id="totalCompletedCount">0</strong></p>
                    </div>
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover" id="completedSchedulesTable">
                            <thead class="table-info" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer Name</th>
                                    <th>Contact Number</th>
                                    <th>Address</th>
                                    <th>Schedule Date</th>
                                    <th>Schedule Time</th>
                                    <th>Service Type</th>
                                    <th>Products</th>
                                    <th>Quantity to Install</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Cancel Note</th>
                                    <th>Completed At</th>
                                </tr>
                            </thead>
                            <tbody id="completedSchedulesTableBody">
                                <tr>
                                    <td colspan="13" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="exportCompletedSchedulesPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Download Options Modal -->
    <div class="modal fade" id="pdfOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-pdf text-success"></i> PDF Download Options
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-muted">Select which sections to include in your PDF:</h6>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeSummary" checked>
                                <label class="form-check-label" for="includeSummary">
                                    <i class="fas fa-chart-pie text-primary"></i> <strong>Summary</strong>
                                    <small class="text-muted d-block">Key metrics and statistics</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeInstaller">
                                <label class="form-check-label" for="includeInstaller">
                                    <i class="fas fa-users text-warning"></i> <strong>Installer Performance</strong>
                                    <small class="text-muted d-block">Performance metrics by installer</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeService">
                                <label class="form-check-label" for="includeService">
                                    <i class="fas fa-cogs text-secondary"></i> <strong>Service Types</strong>
                                    <small class="text-muted d-block">Breakdown by service type</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeDetailed">
                                <label class="form-check-label" for="includeDetailed">
                                    <i class="fas fa-list text-success"></i> <strong>Detailed Schedules</strong>
                                    <small class="text-muted d-block">Complete installation records</small>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FIXED PDF Generation Function
        function generateSelectedPDF() {
            try {
                console.log('Starting PDF generation...');
                
                // Get selected options
                const selectedSections = {
                    summary: document.getElementById('includeSummary').checked,
                    installer: document.getElementById('includeInstaller').checked,
                    service: document.getElementById('includeService').checked,
                    detailed: document.getElementById('includeDetailed').checked
                };
                
                // Check if at least one section is selected
                const hasSelection = Object.values(selectedSections).some(selected => selected);
                if (!hasSelection) {
                    alert('Please select at least one section to include in the PDF.');
                    return;
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('pdfOptionsModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Generate PDF after a short delay
                setTimeout(() => downloadPDF(selectedSections), 300);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error generating PDF: ' + error.message);
            }
        }

        function downloadPDF(selectedSections) {
            // Load logo image first
            const logoPath = '../img/logo.jpg';
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                try {
                    // Access jsPDF correctly from the UMD module
                    const { jsPDF } = window.jspdf;
                    
                    if (!jsPDF) {
                        throw new Error('jsPDF not loaded properly. Please refresh the page.');
                    }
                    
                    console.log('Creating PDF document...');
                    const doc = new jsPDF('landscape', 'mm', 'letter');
                    
                    // Set up fonts and colors
                    doc.setFont('helvetica');
                    
                    // Add logo to header
                    try {
                        // Calculate logo dimensions (maintaining aspect ratio)
                        const maxLogoWidth = 40; // Maximum width in mm
                        const maxLogoHeight = 20; // Maximum height in mm
                        let logoWidth = this.width * 0.264583; // Convert pixels to mm (1px = 0.264583mm at 96dpi)
                        let logoHeight = this.height * 0.264583;
                        
                        // Scale down if too large
                        if (logoWidth > maxLogoWidth) {
                            const scale = maxLogoWidth / logoWidth;
                            logoWidth = maxLogoWidth;
                            logoHeight = logoHeight * scale;
                        }
                        if (logoHeight > maxLogoHeight) {
                            const scale = maxLogoHeight / logoHeight;
                            logoHeight = maxLogoHeight;
                            logoWidth = logoWidth * scale;
                        }
                        
                        doc.addImage(img, 'JPEG', 20, 10, logoWidth, logoHeight);
                    } catch (logoError) {
                        console.warn('Could not add logo:', logoError);
                        // Continue without logo if there's an error
                    }
                    
                    // Add header text (adjusted position to account for logo)
                    doc.setFontSize(20);
                    doc.setTextColor(40, 40, 40);
                    const logoWidthUsed = 50; // Space reserved for logo
                    doc.text('Installation Report', 20 + logoWidthUsed, 20);
                    
                    doc.setFontSize(12);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Generated on: ' + new Date().toLocaleString(), 20 + logoWidthUsed, 30);
                    doc.text('Period: <?= ucfirst($filter) ?>', 20 + logoWidthUsed, 36);
                    
                    let currentY = 50;
                    
                    // Add Summary section
                    if (selectedSections.summary) {
                        console.log('Adding Summary...');
                        doc.setFontSize(14);
                        doc.setTextColor(40, 40, 40);
                        doc.text('Summary', 20, currentY);
                        
                        const summaryData = [
                            ['Total Schedules', '<?= number_format($summary['total_schedules'] ?? 0) ?>'],
                            ['Completed', '<?= number_format($summary['completed_count'] ?? 0) ?>'],
                            ['In Progress', '<?= number_format($summary['in_progress_count'] ?? 0) ?>'],
                            ['Scheduled', '<?= number_format($summary['scheduled_count'] ?? 0) ?>'],
                            ['Cancelled', '<?= number_format($summary['cancelled_count'] ?? 0) ?>'],
                            ['Active Installers', '<?= number_format($summary['unique_installers'] ?? 0) ?>']
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
                    
                    // Add Installer Performance
                    if (selectedSections.installer && <?= count($installer_data) ?> > 0) {
                        console.log('Adding Installer Performance...');
                        
                        // Check if we need a new page
                        if (currentY > 150) {
                            doc.addPage();
                            currentY = 20;
                        }
                        
                        doc.setFontSize(14);
                        doc.setTextColor(40, 40, 40);
                        doc.text('Installer Performance', 20, currentY);
                        
                        const installerData = <?= json_encode($installer_data) ?>.map(item => [
                            item.installer_name || 'N/A',
                            item.total_schedules || '0',
                            item.completed_count || '0',
                            item.in_progress_count || '0',
                            item.scheduled_count || '0',
                            item.cancelled_count || '0',
                            (item.total_schedules > 0 ? ((item.completed_count / item.total_schedules) * 100).toFixed(1) : '0') + '%'
                        ]);
                        
                        doc.autoTable({
                            startY: currentY + 5,
                            head: [['Installer', 'Total', 'Completed', 'In Progress', 'Scheduled', 'Cancelled', 'Rate']],
                            body: installerData,
                            theme: 'grid',
                            headStyles: { fillColor: [155, 89, 182], textColor: 255 },
                            styles: { fontSize: 8, cellPadding: 2 },
                            columnStyles: {
                                1: { halign: 'center' },
                                2: { halign: 'center' },
                                3: { halign: 'center' },
                                4: { halign: 'center' },
                                5: { halign: 'center' },
                                6: { halign: 'center' }
                            }
                        });
                        
                        currentY = doc.lastAutoTable.finalY + 15;
                    }
                    
                    // Add Service Types
                    if (selectedSections.service && <?= count($service_data) ?> > 0) {
                        console.log('Adding Service Types...');
                        
                        // Check if we need a new page
                        if (currentY > 150) {
                            doc.addPage();
                            currentY = 20;
                        }
                        
                        doc.setFontSize(14);
                        doc.setTextColor(40, 40, 40);
                        doc.text('Service Types Breakdown', 20, currentY);
                        
                        const serviceData = <?= json_encode($service_data) ?>.map(item => [
                            item.service_type || 'N/A',
                            item.total_count || '0',
                            item.scheduled_count || '0',
                            item.in_progress_count || '0',
                            item.completed_count || '0',
                            item.cancelled_count || '0',
                            (item.total_count > 0 ? ((item.completed_count / item.total_count) * 100).toFixed(1) : '0') + '%'
                        ]);
                        
                        doc.autoTable({
                            startY: currentY + 5,
                            head: [['Service Type', 'Total', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Rate']],
                            body: serviceData,
                            theme: 'grid',
                            headStyles: { fillColor: [26, 188, 156], textColor: 255 },
                            styles: { fontSize: 8, cellPadding: 2 },
                            columnStyles: {
                                1: { halign: 'center' },
                                2: { halign: 'center' },
                                3: { halign: 'center' },
                                4: { halign: 'center' },
                                5: { halign: 'center' },
                                6: { halign: 'center' }
                            }
                        });
                        
                        currentY = doc.lastAutoTable.finalY + 15;
                    }
                    
                    // Add Detailed Schedules (limited to first 50)
                    if (selectedSections.detailed && <?= count($install_data) ?> > 0) {
                        console.log('Adding Detailed Schedules...');
                        
                        doc.addPage();
                        currentY = 20;
                        
                        doc.setFontSize(14);
                        doc.setTextColor(40, 40, 40);
                        doc.text('Detailed Installation Schedules (First 50 Records)', 20, currentY);
                        
                        const installData = <?= json_encode(array_slice($install_data, 0, 50)) ?>.map(item => {
                            const productDisplay = item.product_name || item.products_to_install || 'N/A';
                            return [
                                item.id || 'N/A',
                                item.installer_name || 'N/A',
                                item.customer_name || 'N/A',
                                item.contact_number || 'N/A',
                                new Date(item.schedule_date).toLocaleDateString(),
                                item.service_type || 'N/A',
                                productDisplay.length > 25 ? productDisplay.substring(0, 25) + '...' : productDisplay,
                                item.status || 'N/A',
                                item.quantity_to_install || '1'
                            ];
                        });
                        
                        doc.autoTable({
                            startY: currentY + 5,
                            head: [['ID', 'Installer', 'Customer', 'Contact', 'Date', 'Service', 'Product', 'Status', 'Quantity']],
                            body: installData,
                            theme: 'grid',
                            headStyles: { fillColor: [52, 73, 94], textColor: 255 },
                            styles: { fontSize: 7, cellPadding: 1.5 },
                            columnStyles: {
                                0: { halign: 'center', cellWidth: 15 },
                                4: { halign: 'center' },
                                7: { halign: 'center' },
                                8: { halign: 'center', cellWidth: 20 }
                            }
                        });
                    }
                    
                    // Add footer to all pages
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setTextColor(150, 150, 150);
                        doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                        doc.text('Generated by AUS Installation System', 20, doc.internal.pageSize.height - 10);
                    }
                    
                    // Save the PDF
                    const fileName = 'Installation_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
                    doc.save(fileName);
                    
                    console.log('PDF generated successfully!');
                    alert('PDF generated successfully! Check your downloads folder.');
                    
                } catch (error) {
                    console.error('PDF Generation Error:', error);
                    alert('Error generating PDF: ' + error.message + '\n\nTroubleshooting:\n1. Try refreshing the page\n2. Clear your browser cache\n3. Check browser console for details');
                }
            };
            
            img.onerror = function() {
                // If logo fails to load, generate PDF without logo
                console.warn('Logo image could not be loaded. Generating PDF without logo.');
                try {
                    const { jsPDF } = window.jspdf;
                    
                    if (!jsPDF) {
                        throw new Error('jsPDF not loaded properly. Please refresh the page.');
                    }
                    
                    const doc = new jsPDF('landscape', 'mm', 'letter');
                    doc.setFont('helvetica');
                    
                    // Add header without logo
                    doc.setFontSize(20);
                    doc.setTextColor(40, 40, 40);
                    doc.text('Installation Report', 20, 20);
                    
                    doc.setFontSize(12);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Generated on: ' + new Date().toLocaleString(), 20, 30);
                    doc.text('Period: <?= ucfirst($filter) ?>', 20, 36);
                    
                    let currentY = 50;
                    
                    // Continue with PDF generation without logo (same sections as above)
                    // For brevity, we'll just show a basic PDF if logo fails
                    if (selectedSections.summary) {
                        doc.setFontSize(14);
                        doc.setTextColor(40, 40, 40);
                        doc.text('Summary', 20, currentY);
                        
                        const summaryData = [
                            ['Total Schedules', '<?= number_format($summary['total_schedules'] ?? 0) ?>'],
                            ['Completed', '<?= number_format($summary['completed_count'] ?? 0) ?>'],
                            ['In Progress', '<?= number_format($summary['in_progress_count'] ?? 0) ?>'],
                            ['Scheduled', '<?= number_format($summary['scheduled_count'] ?? 0) ?>'],
                            ['Cancelled', '<?= number_format($summary['cancelled_count'] ?? 0) ?>'],
                            ['Active Installers', '<?= number_format($summary['unique_installers'] ?? 0) ?>']
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
                    
                    // Add footer
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setTextColor(150, 150, 150);
                        doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                        doc.text('Generated by AUS Installation System', 20, doc.internal.pageSize.height - 10);
                    }
                    
                    const fileName = 'Installation_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
                    doc.save(fileName);
                    
                    alert('PDF generated successfully! Check your downloads folder.');
                } catch (error) {
                    console.error('PDF Generation Error:', error);
                    alert('Error generating PDF: ' + error.message);
                }
            };
            
            // Set the image source to load it
            img.src = logoPath;
        }

        function toggleCustomDate() {
            document.getElementById('customDateRange').style.display = 'flex';
            document.querySelectorAll('button.filter-btn').forEach(btn => btn.classList.remove('active'));
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

        // Tab persistence
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
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    link.setAttribute('aria-selected', 'false');
                });
                
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                tabButton.classList.add('active');
                tabButton.setAttribute('aria-selected', 'true');
                tabPane.classList.add('show', 'active');
            }
        }

        // Table management for detailed schedules
        let detailedTableData = [];
        let filteredDetailedData = [];
        let currentPage = 1;
        let entriesPerPage = 25;
        let sortColumn = -1;
        let sortDirection = 'asc';

        // Initialize table data
        function initializeDetailedTable() {
            const tableBody = document.getElementById('detailedTableBody');
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            
            detailedTableData = rows.map(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                return {
                    element: row,
                    data: cells.map(cell => cell.textContent.trim())
                };
            }).filter(item => item.data.length > 0);
            
            filteredDetailedData = [...detailedTableData];
            updateDetailedTableDisplay();
        }

        // Search functionality
        function searchDetailedTable(searchTerm) {
            const term = searchTerm.toLowerCase();
            
            filteredDetailedData = detailedTableData.filter(item => {
                return item.data.some(cellData => 
                    cellData.toLowerCase().includes(term)
                );
            });
            
            currentPage = 1;
            updateDetailedTableDisplay();
        }

        // Filter functionality
        function filterDetailedTable(statusFilter, installerFilter, serviceFilter) {
            filteredDetailedData = detailedTableData.filter(item => {
                const statusMatch = !statusFilter || item.data[8].includes(statusFilter);
                const installerMatch = !installerFilter || item.data[1].includes(installerFilter);
                const serviceMatch = !serviceFilter || item.data[7].includes(serviceFilter);
                
                return statusMatch && installerMatch && serviceMatch;
            });
            
            currentPage = 1;
            updateDetailedTableDisplay();
        }

        // Update table display
        function updateDetailedTableDisplay() {
            const tableBody = document.getElementById('detailedTableBody');
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            
            // Clear table body
            tableBody.innerHTML = '';
            
            // Show filtered data
            const dataToShow = filteredDetailedData.slice(startIndex, endIndex);
            
            if (dataToShow.length === 0) {
                const noDataRow = document.createElement('tr');
                noDataRow.innerHTML = '<td colspan="13" class="text-center">No data found</td>';
                tableBody.appendChild(noDataRow);
            } else {
                dataToShow.forEach(item => {
                    tableBody.appendChild(item.element.cloneNode(true));
                });
            }
            
            // Update pagination info
            updateDetailedPaginationInfo();
            generateDetailedPagination();
        }

        // Update pagination info
        function updateDetailedPaginationInfo() {
            const total = filteredDetailedData.length;
            const start = (currentPage - 1) * entriesPerPage + 1;
            const end = Math.min(start + entriesPerPage - 1, total);
            
            document.getElementById('showingStart').textContent = start;
            document.getElementById('showingEnd').textContent = end;
            document.getElementById('totalFiltered').textContent = total;
        }

        // Generate pagination
        function generateDetailedPagination() {
            const pagination = document.getElementById('pagination');
            const totalPages = Math.ceil(filteredDetailedData.length / entriesPerPage);
            
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="changeDetailedPage(${currentPage - 1})">Previous</a>`;
            pagination.appendChild(prevLi);
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="changeDetailedPage(${i})">${i}</a>`;
                pagination.appendChild(li);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="changeDetailedPage(${currentPage + 1})">Next</a>`;
            pagination.appendChild(nextLi);
        }

        // Change page
        function changeDetailedPage(page) {
            const totalPages = Math.ceil(filteredDetailedData.length / entriesPerPage);
            
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                updateDetailedTableDisplay();
            }
        }

        // Change entries per page
        function changeDetailedEntriesPerPage(entries) {
            entriesPerPage = parseInt(entries);
            currentPage = 1;
            updateDetailedTableDisplay();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded. Checking libraries...');
            console.log('jsPDF available:', typeof window.jspdf !== 'undefined');
            console.log('jsPDF.jsPDF available:', typeof window.jspdf?.jsPDF !== 'undefined');
            console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
            
            restoreActiveTab();
            initializeDetailedTable();
            
            // Add event listeners for search and filters
            document.getElementById('searchInput').addEventListener('input', function() {
                searchDetailedTable(this.value);
            });
            
            document.getElementById('statusFilter').addEventListener('change', function() {
                const statusFilter = this.value;
                const installerFilter = document.getElementById('installerFilter').value;
                const serviceFilter = document.getElementById('serviceFilter').value;
                filterDetailedTable(statusFilter, installerFilter, serviceFilter);
            });
            
            document.getElementById('installerFilter').addEventListener('change', function() {
                const statusFilter = document.getElementById('statusFilter').value;
                const installerFilter = this.value;
                const serviceFilter = document.getElementById('serviceFilter').value;
                filterDetailedTable(statusFilter, installerFilter, serviceFilter);
            });
            
            document.getElementById('serviceFilter').addEventListener('change', function() {
                const statusFilter = document.getElementById('statusFilter').value;
                const installerFilter = document.getElementById('installerFilter').value;
                const serviceFilter = this.value;
                filterDetailedTable(statusFilter, installerFilter, serviceFilter);
            });
            
            document.getElementById('rowsPerPage').addEventListener('change', function() {
                changeDetailedEntriesPerPage(this.value);
            });
            
            document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-bs-target').replace('#', '');
                    saveActiveTab(tabId);
                });
            });
        });

        // Chart.js initialization
        const chartLabels = <?php echo json_encode(array_map(function($item) { return date('M j', strtotime($item['install_date'])); }, $chart_data)); ?>;
        const chartData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_schedules'))); ?>;
        const completedData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_completed'))); ?>;
        const serviceLabels = <?php echo json_encode(array_column($service_data, 'service_type')); ?>;
        const serviceData = <?php echo json_encode(array_map('intval', array_column($service_data, 'total_count'))); ?>;

        // Initialize charts when DOM is ready
        window.addEventListener('DOMContentLoaded', function() {
            const trendCtx = document.getElementById('installTrendChart');
            const serviceCtx = document.getElementById('serviceChart');
            
            if (trendCtx) {
                new Chart(trendCtx.getContext('2d'), {
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
                        }
                    }
                });
            }
            
            if (serviceCtx) {
                new Chart(serviceCtx.getContext('2d'), {
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
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        });

        // Table sorting functions
        function sortTable(columnIndex) {
            const table = document.getElementById('detailedTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            if (rows.length === 0) return;
            
            const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
            const currentColumn = parseInt(table.getAttribute('data-sort-column') || -1);
            
            let direction = 'asc';
            if (currentColumn === columnIndex) {
                direction = currentDirection === 'asc' ? 'desc' : 'asc';
            }
            
            table.setAttribute('data-sort-column', columnIndex);
            table.setAttribute('data-sort-direction', direction);
            
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                if (!isNaN(aVal) && !isNaN(bVal)) {
                    aVal = parseFloat(aVal);
                    bVal = parseFloat(bVal);
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                } else {
                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
            
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
            
            if (rows.length === 0 || rows[0].cells.length <= 1) return;
            
            const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
            const currentColumn = parseInt(table.getAttribute('data-sort-column') || -1);
            
            let direction = 'asc';
            if (currentColumn === columnIndex) {
                direction = currentDirection === 'asc' ? 'desc' : 'asc';
            }
            
            table.setAttribute('data-sort-column', columnIndex);
            table.setAttribute('data-sort-direction', direction);
            
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                if (aVal.includes('%')) {
                    aVal = parseFloat(aVal.replace('%', ''));
                    bVal = parseFloat(bVal.replace('%', ''));
                } else if (!isNaN(aVal) && !isNaN(bVal)) {
                    aVal = parseFloat(aVal);
                    bVal = parseFloat(bVal);
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                } else {
                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
            
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

        // Completed Schedules Modal Functions
        let currentInstallerName = '';
        let currentCompletedSchedules = [];

        function viewCompletedSchedules(installerName) {
            currentInstallerName = installerName;
            
            // Update modal header
            document.getElementById('installerNameDisplay').textContent = installerName;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('completedSchedulesModal'));
            modal.show();
            
            // Reset table body with loading spinner
            const tbody = document.getElementById('completedSchedulesTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </td>
                </tr>
            `;
            
            // Get filter values from the page
            const filter = '<?= $filter ?>';
            const startDate = '<?= $start_date ?>';
            const endDate = '<?= $end_date ?>';
            
            // Fetch completed schedules
            fetch(`get_completed_schedules.php?installer_name=${encodeURIComponent(installerName)}&filter=${filter}&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                        return;
                    }
                    
                    currentCompletedSchedules = data.schedules || [];
                    document.getElementById('totalCompletedCount').textContent = data.total || 0;
                    
                    if (currentCompletedSchedules.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-muted">No schedules found for this installer.</td></tr>`;
                        return;
                    }
                    
                    // Populate table
                    tbody.innerHTML = '';
                    currentCompletedSchedules.forEach(schedule => {
                        const row = document.createElement('tr');
                        const scheduleDate = schedule.schedule_date ? new Date(schedule.schedule_date) : null;
                        const scheduleTime = schedule.schedule_time ? schedule.schedule_time.substring(0, 5) : 'N/A';
                        const completedAt = schedule.completed_at ? new Date(schedule.completed_at) : null;
                        
                        const formatDate = (date) => {
                            if (!date || isNaN(date.getTime())) return 'N/A';
                            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        };
                        
                        const formatDateTime = (date) => {
                            if (!date || isNaN(date.getTime())) return 'N/A';
                            return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        };
                        
                        // Determine status badge
                        const status = schedule.status || 'N/A';
                        let statusBadge = '';
                        if (status === 'Completed') {
                            statusBadge = '<span class="badge badge-completed">Completed</span>';
                        } else if (status === 'Cancelled') {
                            statusBadge = '<span class="badge badge-cancelled">Cancelled</span>';
                        } else if (status === 'In Progress') {
                            statusBadge = '<span class="badge badge-in-progress">In Progress</span>';
                        } else if (status === 'Scheduled') {
                            statusBadge = '<span class="badge badge-scheduled">Scheduled</span>';
                        } else {
                            statusBadge = '<span class="badge badge-secondary">' + escapeHtml(status) + '</span>';
                        }
                        
                        // Show cancel_note only if status is Cancelled
                        const cancelNote = (status === 'Cancelled' && schedule.cancel_note) 
                            ? escapeHtml(schedule.cancel_note.length > 50 ? schedule.cancel_note.substring(0, 50) + '...' : schedule.cancel_note)
                            : (status === 'Cancelled' ? '<span class="text-muted">No note</span>' : 'N/A');
                        
                        const productDisplay = schedule.product_name || schedule.products_to_install || 'N/A';
                        row.innerHTML = `
                            <td>${schedule.id}</td>
                            <td>${escapeHtml(schedule.customer_name)}</td>
                            <td>${escapeHtml(schedule.contact_number)}</td>
                            <td>${escapeHtml(schedule.address ? (schedule.address.length > 50 ? schedule.address.substring(0, 50) + '...' : schedule.address) : 'N/A')}</td>
                            <td>${formatDate(scheduleDate)}</td>
                            <td>${scheduleTime}</td>
                            <td>${escapeHtml(schedule.service_type || 'N/A')}</td>
                            <td>${escapeHtml(productDisplay.length > 30 ? productDisplay.substring(0, 30) + '...' : productDisplay)}</td>
                            <td>${schedule.quantity_to_install || '1'}</td>
                            <td>${escapeHtml(schedule.notes ? (schedule.notes.length > 30 ? schedule.notes.substring(0, 30) + '...' : schedule.notes) : 'N/A')}</td>
                            <td>${statusBadge}</td>
                            <td>${cancelNote}</td>
                            <td>${formatDateTime(completedAt)}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `<tr><td colspan="13" class="text-center text-danger">Error loading data: ${error.message}</td></tr>`;
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function exportCompletedSchedulesPDF() {
            if (currentCompletedSchedules.length === 0) {
                alert('No completed schedules to export.');
                return;
            }
            
            // Load logo image first
            const logoPath = '../img/logo.jpg';
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                try {
                    const { jsPDF } = window.jspdf;
                    
                    if (!jsPDF) {
                        throw new Error('jsPDF not loaded properly. Please refresh the page.');
                    }
                    
                    const doc = new jsPDF('landscape', 'mm', 'letter');
                    doc.setFont('helvetica');
                    
                    // Add logo to header
                    try {
                        // Calculate logo dimensions (maintaining aspect ratio)
                        const maxLogoWidth = 40; // Maximum width in mm
                        const maxLogoHeight = 20; // Maximum height in mm
                        let logoWidth = this.width * 0.264583; // Convert pixels to mm (1px = 0.264583mm at 96dpi)
                        let logoHeight = this.height * 0.264583;
                        
                        // Scale down if too large
                        if (logoWidth > maxLogoWidth) {
                            const scale = maxLogoWidth / logoWidth;
                            logoWidth = maxLogoWidth;
                            logoHeight = logoHeight * scale;
                        }
                        if (logoHeight > maxLogoHeight) {
                            const scale = maxLogoHeight / logoHeight;
                            logoHeight = maxLogoHeight;
                            logoWidth = logoWidth * scale;
                        }
                        
                        doc.addImage(img, 'JPEG', 20, 10, logoWidth, logoHeight);
                    } catch (logoError) {
                        console.warn('Could not add logo:', logoError);
                        // Continue without logo if there's an error
                    }
                    
                    // Add header text (adjusted position to account for logo)
                    doc.setFontSize(18);
                    doc.setTextColor(40, 40, 40);
                    const logoWidthUsed = 50; // Space reserved for logo
                    doc.text('Installation Schedules', 20 + logoWidthUsed, 20);
                    
                    doc.setFontSize(12);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Installer: ' + currentInstallerName, 20 + logoWidthUsed, 30);
                    doc.text('Total Schedules: ' + currentCompletedSchedules.length, 20 + logoWidthUsed, 36);
                    doc.text('Generated on: ' + new Date().toLocaleString(), 20 + logoWidthUsed, 42);
                    
                    // Prepare table data
                    const tableData = currentCompletedSchedules.map(schedule => {
                        const scheduleDate = schedule.schedule_date ? new Date(schedule.schedule_date) : null;
                        const scheduleTime = schedule.schedule_time ? schedule.schedule_time.substring(0, 5) : 'N/A';
                        const completedAt = schedule.completed_at ? new Date(schedule.completed_at) : null;
                        const status = schedule.status || 'N/A';
                        
                        const formatDate = (date) => {
                            if (!date || isNaN(date.getTime())) return 'N/A';
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        };
                        
                        // Get cancel note if status is Cancelled
                        const cancelNote = (status === 'Cancelled' && schedule.cancel_note) 
                            ? (schedule.cancel_note.length > 40 ? schedule.cancel_note.substring(0, 40) + '...' : schedule.cancel_note)
                            : (status === 'Cancelled' ? 'No note' : 'N/A');
                        
                        const productDisplay = schedule.product_name || schedule.products_to_install || 'N/A';
                        return [
                            schedule.id.toString(),
                            schedule.customer_name || 'N/A',
                            schedule.contact_number || 'N/A',
                            schedule.address ? (schedule.address.length > 30 ? schedule.address.substring(0, 30) + '...' : schedule.address) : 'N/A',
                            formatDate(scheduleDate),
                            scheduleTime,
                            schedule.service_type || 'N/A',
                            productDisplay.length > 25 ? productDisplay.substring(0, 25) + '...' : productDisplay,
                            schedule.quantity_to_install || '1',
                            status,
                            cancelNote,
                            formatDate(completedAt)
                        ];
                    });
                    
                    doc.autoTable({
                        startY: 50,
                        head: [['ID', 'Customer', 'Contact', 'Address', 'Schedule Date', 'Time', 'Service Type', 'Products', 'Quantity', 'Status', 'Cancel Note', 'Completed Date']],
                        body: tableData,
                        theme: 'grid',
                        headStyles: { fillColor: [155, 89, 182], textColor: 255 },
                        styles: { fontSize: 7, cellPadding: 2 },
                        columnStyles: {
                            0: { halign: 'center', cellWidth: 12 },
                            4: { halign: 'center' },
                            5: { halign: 'center' },
                            8: { halign: 'center', cellWidth: 15 },
                            9: { halign: 'center' },
                            11: { halign: 'center' }
                        },
                        margin: { top: 50 }
                    });
                    
                    // Add footer
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setTextColor(150, 150, 150);
                        doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                        doc.text('Generated by AUS Installation System', 20, doc.internal.pageSize.height - 10);
                    }
                    
                    const fileName = 'Completed_Schedules_' + currentInstallerName.replace(/[^a-z0-9]/gi, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf';
                    doc.save(fileName);
                    
                    alert('PDF exported successfully!');
                } catch (error) {
                    console.error('PDF Export Error:', error);
                    alert('Error exporting PDF: ' + error.message);
                }
            };
            
            img.onerror = function() {
                // If logo fails to load, generate PDF without logo
                console.warn('Logo image could not be loaded. Generating PDF without logo.');
                try {
                    const { jsPDF } = window.jspdf;
                    
                    if (!jsPDF) {
                        throw new Error('jsPDF not loaded properly. Please refresh the page.');
                    }
                    
                    const doc = new jsPDF('landscape', 'mm', 'letter');
                    doc.setFont('helvetica');
                    
                    // Add header without logo
                    doc.setFontSize(18);
                    doc.setTextColor(40, 40, 40);
                    doc.text('Installation Schedules', 20, 20);
                    
                    doc.setFontSize(12);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Installer: ' + currentInstallerName, 20, 30);
                    doc.text('Total Schedules: ' + currentCompletedSchedules.length, 20, 36);
                    doc.text('Generated on: ' + new Date().toLocaleString(), 20, 42);
                    
                    // Prepare table data
                    const tableData = currentCompletedSchedules.map(schedule => {
                        const scheduleDate = schedule.schedule_date ? new Date(schedule.schedule_date) : null;
                        const scheduleTime = schedule.schedule_time ? schedule.schedule_time.substring(0, 5) : 'N/A';
                        const completedAt = schedule.completed_at ? new Date(schedule.completed_at) : null;
                        const status = schedule.status || 'N/A';
                        
                        const formatDate = (date) => {
                            if (!date || isNaN(date.getTime())) return 'N/A';
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        };
                        
                        // Get cancel note if status is Cancelled
                        const cancelNote = (status === 'Cancelled' && schedule.cancel_note) 
                            ? (schedule.cancel_note.length > 40 ? schedule.cancel_note.substring(0, 40) + '...' : schedule.cancel_note)
                            : (status === 'Cancelled' ? 'No note' : 'N/A');
                        
                        const productDisplay = schedule.product_name || schedule.products_to_install || 'N/A';
                        return [
                            schedule.id.toString(),
                            schedule.customer_name || 'N/A',
                            schedule.contact_number || 'N/A',
                            schedule.address ? (schedule.address.length > 30 ? schedule.address.substring(0, 30) + '...' : schedule.address) : 'N/A',
                            formatDate(scheduleDate),
                            scheduleTime,
                            schedule.service_type || 'N/A',
                            productDisplay.length > 25 ? productDisplay.substring(0, 25) + '...' : productDisplay,
                            schedule.quantity_to_install || '1',
                            status,
                            cancelNote,
                            formatDate(completedAt)
                        ];
                    });
                    
                    doc.autoTable({
                        startY: 50,
                        head: [['ID', 'Customer', 'Contact', 'Address', 'Schedule Date', 'Time', 'Service Type', 'Products', 'Quantity', 'Status', 'Cancel Note', 'Completed Date']],
                        body: tableData,
                        theme: 'grid',
                        headStyles: { fillColor: [155, 89, 182], textColor: 255 },
                        styles: { fontSize: 7, cellPadding: 2 },
                        columnStyles: {
                            0: { halign: 'center', cellWidth: 12 },
                            4: { halign: 'center' },
                            5: { halign: 'center' },
                            8: { halign: 'center', cellWidth: 15 },
                            9: { halign: 'center' },
                            11: { halign: 'center' }
                        },
                        margin: { top: 50 }
                    });
                    
                    // Add footer
                    const pageCount = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setTextColor(150, 150, 150);
                        doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                        doc.text('Generated by AUS Installation System', 20, doc.internal.pageSize.height - 10);
                    }
                    
                    const fileName = 'Completed_Schedules_' + currentInstallerName.replace(/[^a-z0-9]/gi, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf';
                    doc.save(fileName);
                    
                    alert('PDF exported successfully!');
                } catch (error) {
                    console.error('PDF Export Error:', error);
                    alert('Error exporting PDF: ' + error.message);
                }
            };
            
            // Set the image source to load it
            img.src = logoPath;
        }
    </script>
</body> 
</html>