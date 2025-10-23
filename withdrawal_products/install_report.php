<?php 
session_start();  // Start session before any output 

// Redirect if not logged in as admin 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../login.php"); 
    exit(); 
} 

include '../config/conn.php'; 

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'overall';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build date condition based on filter
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

// Query for install summary
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

// Query for detailed install data
$install_query = "SELECT 
    id,
    installer_name,
    customer_name,
    contact_number,
    address,
    schedule_date,
    schedule_time,
    service_type,
    products_to_install,
    notes,
    status,
    created_at,
    updated_at
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

// Query for installer breakdown
$installer_query = "SELECT 
    installer_name,
    COUNT(*) as total_schedules,
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

// Query for service type breakdown
$service_query = "SELECT 
    service_type,
    COUNT(*) as total_count,
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

// Query for daily install chart (last 30 days for chart)
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
     <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filter-card {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            margin: 0.25rem;
        }
        .filter-btn.active {
            background-color: #4e73df !important;
            border-color: #4e73df !important;
            color: white !important;
        }
        .summary-card {
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .status-scheduled {
            color: #ffc107;
            font-weight: bold;
        }
        .status-in-progress {
            color: #17a2b8;
            font-weight: bold;
        }
        .status-completed {
            color: #28a745;
            font-weight: bold;
        }
        .status-cancelled {
            color: #dc3545;
            font-weight: bold;
        }
        .badge-scheduled {
            background-color: #ffc107;
            color: #000;
        }
        .badge-in-progress {
            background-color: #17a2b8;
            color: #fff;
        }
        .badge-completed {
            background-color: #28a745;
            color: #fff;
        }
        .badge-cancelled {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head> 

<body id="page-top"> 

    <!-- Page Wrapper --> 
    <div id="wrapper"> 

        <!-- Sidebar --> 
        <?php include('../includes/sidebar.php'); ?> 
        <!-- End of Sidebar --> 

        <!-- Content Wrapper --> 
        <div id="content-wrapper" class="d-flex flex-column"> 

            <!-- Main Content --> 
            <div id="content"> 

                <!-- Topbar --> 
                <?php include('../includes/topbar.php'); ?> 
                <!-- End of Topbar --> 

                <!-- Begin Page Content --> 
                <div class="container-fluid"> 

                    <!-- Page Heading --> 
                    <div class="d-sm-flex align-items-center justify-content-between mb-4"> 
                        <h1 class="h3 mb-0 text-gray-800">Installation Report</h1>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>

                    <!-- Filter Section -->
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
                            
                            <!-- Custom Date Range -->
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

                    <!-- Summary Cards Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 summary-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Schedules</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_schedules'] ?? 0) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                        </div>
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
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
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
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
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
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Installation Trend Chart -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Installation Trend (Last 30 Days)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="installTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Type Breakdown -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Service Types</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="serviceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Installer Performance Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Installer Performance</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Installer Name</th>
                                                    <th>Total Schedules</th>
                                                    <th>Scheduled</th>
                                                    <th>In Progress</th>
                                                    <th>Completed</th>
                                                    <th>Cancelled</th>
                                                    <th>Completion Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($installer_data)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No installer data available for the selected period</td>
                                                </tr>
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

                    <!-- Service Type Breakdown Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Service Type Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Service Type</th>
                                                    <th>Total Count</th>
                                                    <th>Scheduled</th>
                                                    <th>In Progress</th>
                                                    <th>Completed</th>
                                                    <th>Cancelled</th>
                                                    <th>Completion Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($service_data)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No service data available for the selected period</td>
                                                </tr>
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

                    <!-- Detailed Installation Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Detailed Installation Schedules</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Schedule ID</th>
                                                    <th>Installer</th>
                                                    <th>Customer</th>
                                                    <th>Contact</th>
                                                    <th>Address</th>
                                                    <th>Schedule Date</th>
                                                    <th>Schedule Time</th>
                                                    <th>Service Type</th>
                                                    <th>Status</th>
                                                    <th>Products</th>
                                                    <th>Notes</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($install_data)): ?>
                                                <tr>
                                                    <td colspan="12" class="text-center">No installation data available for the selected period</td>
                                                </tr>
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
                                </div>
                            </div>
                        </div>
                    </div>
 
                </div> 
                <!-- /.container-fluid --> 

            </div> 
            <!-- End of Main Content --> 

            <!-- Footer --> 
            <?php include('../includes/footer.php'); ?> 
            <!-- End of Footer --> 

        </div> <!-- End of Content Wrapper --> 

    </div> <!-- End of Page Wrapper --> 

    <script>
        function toggleCustomDate() {
            const customRange = document.getElementById('customDateRange');
            customRange.style.display = 'flex';
            // Set filter to custom so form submits correctly
            document.querySelectorAll('button.filter-btn').forEach(btn => btn.classList.remove('active'));
        }

        // Prepare PHP data for JS safely
        const chartLabels = <?php echo json_encode(array_map(function($item) { return date('M j', strtotime($item['install_date'])); }, $chart_data)); ?>;
        const chartData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_schedules'))); ?>;
        const completedData = <?php echo json_encode(array_map('intval', array_column($chart_data, 'daily_completed'))); ?>;
        const serviceLabels = <?php echo json_encode(array_column($service_data, 'service_type')); ?>;
        const serviceData = <?php echo json_encode(array_map('intval', array_column($service_data, 'total_count'))); ?>;

        // Installation Trend Chart
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
                        ticks: {
                            stepSize: 1
                        }
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

        // Service Type Pie Chart
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        const serviceChart = new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: serviceLabels,
                datasets: [{
                    data: serviceData,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
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
