<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$installer_filter = isset($_GET['installer']) ? $_GET['installer'] : '';

// Build query with filters
$where_conditions = ["schedule_date BETWEEN '$date_from' AND '$date_to'"];
if ($status_filter) {
    $where_conditions[] = "status = '$status_filter'";
}
if ($installer_filter) {
    $where_conditions[] = "installer_name LIKE '%$installer_filter%'";
}

$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT * FROM installer_schedules WHERE $where_clause ORDER BY schedule_date DESC";
$result = mysqli_query($conn, $query);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_schedules,
    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM installer_schedules WHERE $where_clause";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Installer Schedule Report</h1>
        <a href="installer_schedule.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Schedules
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Schedules</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_schedules']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Scheduled</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['scheduled']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">In Progress</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['in_progress']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Report</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="Scheduled" <?php echo $status_filter == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="installer">Installer Name</label>
                            <input type="text" class="form-control" id="installer" name="installer" value="<?php echo $installer_filter; ?>" placeholder="Search installer...">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="installer_schedule_report.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Schedule Report</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Installer Name</th>
                            <th>Customer Name</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Products to Install</th>
                            <th>Image</th>
                            <th>Schedule Date</th>
                            <th>Schedule Time</th>
                            <th>Service Type</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>Installer Name</th>
                            <th>Customer Name</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Products to Install</th>
                            <th>Image</th>
                            <th>Schedule Date</th>
                            <th>Schedule Time</th>
                            <th>Service Type</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . $row['installer_name'] . "</td>";
                                echo "<td>" . $row['customer_name'] . "</td>";
                                echo "<td>" . $row['contact_number'] . "</td>";
                                echo "<td>" . $row['address'] . "</td>";
                                echo "<td>" . ($row['products_to_install'] ? $row['products_to_install'] : 'N/A') . "</td>";
                                echo "<td>";
                                if ($row['image_path']) {
                                    echo "<img src='../" . $row['image_path'] . "' alt='Schedule Image' style='max-width: 50px; max-height: 50px;' class='img-thumbnail'>";
                                } else {
                                    echo "No Image";
                                }
                                echo "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['schedule_date'])) . "</td>";
                                echo "<td>" . date('h:i A', strtotime($row['schedule_time'])) . "</td>";
                                echo "<td>" . $row['service_type'] . "</td>";
                                echo "<td>";
                                $status = $row['status'];
                                $badge_class = '';
                                switch($status) {
                                    case 'Scheduled':
                                        $badge_class = 'badge-warning';
                                        break;
                                    case 'In Progress':
                                        $badge_class = 'badge-info';
                                        break;
                                    case 'Completed':
                                        $badge_class = 'badge-success';
                                        break;
                                    case 'Cancelled':
                                        $badge_class = 'badge-danger';
                                        break;
                                    default:
                                        $badge_class = 'badge-secondary';
                                }
                                echo "<span class='badge $badge_class'>$status</span>";
                                echo "</td>";
                                echo "<td>" . ($row['notes'] ? $row['notes'] : 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' class='text-center'>No installer schedules found for the selected criteria.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>
