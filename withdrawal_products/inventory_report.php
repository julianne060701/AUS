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

// Query for inventory summary
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

// Query for detailed inventory data
$inventory_query = "SELECT 
    p.id,
    p.product_name,
    p.capacity,
    p.quantity,
    p.buying_price,
    p.selling_price,
    p.created_at,
    p.updated_at,
    c.category_name,
    b.brand_name,
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

// Query for category breakdown
$category_query = "SELECT 
    c.category_name,
    COUNT(*) as product_count,
    SUM(p.quantity) as total_quantity,
    SUM(p.quantity * p.buying_price) as total_value,
    AVG(p.quantity) as avg_quantity
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

// Query for brand breakdown
$brand_query = "SELECT 
    b.brand_name,
    COUNT(*) as product_count,
    SUM(p.quantity) as total_quantity,
    SUM(p.quantity * p.buying_price) as total_value,
    AVG(p.quantity) as avg_quantity
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

// Query for low stock products
$low_stock_query = "SELECT 
    p.id,
    p.product_name,
    p.capacity,
    p.quantity,
    p.buying_price,
    p.selling_price,
    c.category_name,
    b.brand_name,
    (p.quantity * p.buying_price) as inventory_value
FROM products p 
LEFT JOIN category c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
WHERE p.quantity <= 5
ORDER BY p.quantity ASC";

$result = $conn->query($low_stock_query);
$low_stock_data = $result->fetch_all(MYSQLI_ASSOC);
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
        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .normal-stock {
            color: #28a745;
        }
        .alert-card {
            border-left: 4px solid #dc3545;
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
                        <h1 class="h3 mb-0 text-gray-800">Inventory Report</h1>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-card">
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
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Products</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_products'] ?? 0) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Inventory Value</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($summary['total_inventory_value'] ?? 0, 2) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
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
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Units</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['total_quantity'] ?? 0) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 summary-card alert-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Items</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($summary['low_stock_count'] ?? 0) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Category Breakdown Chart -->
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Inventory by Category</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Brand Breakdown Chart -->
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Inventory by Brand</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="brandChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <?php if(!empty($low_stock_data)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow mb-4 alert-card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Low Stock Alert
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Product Name</th>
                                                    <th>Capacity</th>
                                                    <th>Category</th>
                                                    <th>Brand</th>
                                                    <th>Current Stock</th>
                                                    <th>Buying Price</th>
                                                    <th>Selling Price</th>
                                                    <th>Inventory Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($low_stock_data as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                    <td><?= htmlspecialchars($item['capacity'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                                    <td><?= htmlspecialchars($item['brand_name'] ?? 'No Brand') ?></td>
                                                    <td class="low-stock"><?= number_format($item['quantity']) ?></td>
                                                    <td>₱<?= number_format($item['buying_price'], 2) ?></td>
                                                    <td>₱<?= number_format($item['selling_price'], 2) ?></td>
                                                    <td>₱<?= number_format($item['inventory_value'], 2) ?></td>
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

                    <!-- Category Breakdown Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Inventory by Category</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Product Count</th>
                                                    <th>Total Units</th>
                                                    <th>Total Value</th>
                                                    <th>Average Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($category_data)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No inventory data available for the selected period</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach($category_data as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                                                    <td><?= number_format($row['product_count']) ?></td>
                                                    <td><?= number_format($row['total_quantity']) ?></td>
                                                    <td>₱<?= number_format($row['total_value'], 2) ?></td>
                                                    <td><?= number_format($row['avg_quantity'], 1) ?></td>
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

                    <!-- Detailed Inventory Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Detailed Inventory</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Product ID</th>
                                                    <th>Product Name</th>
                                                    <th>Capacity</th>
                                                    <th>Category</th>
                                                    <th>Brand</th>
                                                    <th>Quantity</th>
                                                    <th>Buying Price</th>
                                                    <th>Selling Price</th>
                                                    <th>Inventory Value</th>
                                                    <th>Profit Margin</th>
                                                    <th>Last Updated</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($inventory_data)): ?>
                                                <tr>
                                                    <td colspan="11" class="text-center">No inventory data available for the selected period</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach($inventory_data as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                    <td><?= htmlspecialchars($item['capacity'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                                    <td><?= htmlspecialchars($item['brand_name'] ?? 'No Brand') ?></td>
                                                    <td class="<?= ($item['quantity'] <= 5) ? 'low-stock' : 'normal-stock' ?>">
                                                        <?= number_format($item['quantity']) ?>
                                                    </td>
                                                    <td>₱<?= number_format($item['buying_price'], 2) ?></td>
                                                    <td>₱<?= number_format($item['selling_price'], 2) ?></td>
                                                    <td>₱<?= number_format($item['inventory_value'], 2) ?></td>
                                                    <td>₱<?= number_format($item['profit_margin'], 2) ?></td>
                                                    <td><?= date('M j, Y', strtotime($item['updated_at'])) ?></td>
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
        const categoryLabels = <?php echo json_encode(array_column($category_data, 'category_name')); ?>;
        const categoryData = <?php echo json_encode(array_map('floatval', array_column($category_data, 'total_value'))); ?>;
        const brandLabels = <?php echo json_encode(array_column($brand_data, 'brand_name')); ?>;
        const brandData = <?php echo json_encode(array_map('floatval', array_column($brand_data, 'total_value'))); ?>;

        // Category Pie Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
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
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Brand Pie Chart
        const brandCtx = document.getElementById('brandChart').getContext('2d');
        const brandChart = new Chart(brandCtx, {
            type: 'doughnut',
            data: {
                labels: brandLabels,
                datasets: [{
                    data: brandData,
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
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
 
</body> 

</html>

