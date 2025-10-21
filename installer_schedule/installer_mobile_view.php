<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get installer filter
$installer_filter = isset($_GET['installer']) ? $_GET['installer'] : '';

// Get today's schedules
$today = date('Y-m-d');
$where_conditions = ["schedule_date = '$today'"];
if ($installer_filter) {
    $where_conditions[] = "installer_name = '$installer_filter'";
}
$where_clause = implode(' AND ', $where_conditions);

$query = "SELECT * FROM installer_schedules WHERE $where_clause ORDER BY schedule_time";
$result = mysqli_query($conn, $query);

// Get all installers for filter dropdown
$installers_query = "SELECT DISTINCT installer_name FROM installer_schedules ORDER BY installer_name";
$installers_result = mysqli_query($conn, $installers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Installer Mobile View - AUS General Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .schedule-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            transition: transform 0.2s ease;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .schedule-card.completed {
            border-left-color: #28a745;
        }
        .schedule-card.in-progress {
            border-left-color: #17a2b8;
        }
        .schedule-card.cancelled {
            border-left-color: #dc3545;
        }
        .location-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #dee2e6;
        }
        .products-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #90caf9;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .time-badge {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        }
        .mobile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-action {
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card-title {
            color: #2c3e50;
            font-weight: 700;
        }
        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0"><i class="fas fa-tools"></i> Installer Schedule</h4>
                    <small><?php echo date('F d, Y'); ?></small>
                </div>
                <div class="col-auto">
                    <a href="installer_dashboard.php" class="btn btn-light btn-sm">
                        <i class="fas fa-calendar"></i> Calendar View
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="form-group mb-2">
                        <label for="installer" class="form-label">Select Installer</label>
                        <select class="form-control" id="installer" name="installer">
                            <option value="">All Installers</option>
                            <?php
                            while ($installer = mysqli_fetch_assoc($installers_result)) {
                                $selected = ($installer_filter == $installer['installer_name']) ? 'selected' : '';
                                echo "<option value='" . $installer['installer_name'] . "' $selected>" . $installer['installer_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="installer_mobile_view.php" class="btn btn-secondary btn-sm">Clear</a>
                </form>
            </div>
        </div>

        <!-- Today's Schedules -->
        <div class="row">
            <div class="col-12">
                <h5 class="mb-3">Today's Schedules</h5>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($schedule = mysqli_fetch_assoc($result)): ?>
                        <div class="card schedule-card <?php echo strtolower(str_replace(' ', '-', $schedule['status'])); ?>">
                            <div class="card-body">
                                <div class="row align-items-center mb-2">
                                    <div class="col">
                                        <h6 class="card-title mb-0"><?php echo $schedule['customer_name']; ?></h6>
                                        <small class="text-muted"><?php echo $schedule['installer_name']; ?></small>
                                    </div>
                                    <div class="col-auto">
                                        <span class="time-badge"><?php echo date('h:i A', strtotime($schedule['schedule_time'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <span class="badge badge-<?php 
                                            switch($schedule['status']) {
                                                case 'Scheduled': echo 'warning'; break;
                                                case 'In Progress': echo 'info'; break;
                                                case 'Completed': echo 'success'; break;
                                                case 'Cancelled': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?> status-badge"><?php echo $schedule['status']; ?></span>
                                        <span class="badge badge-light status-badge"><?php echo $schedule['service_type']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="location-info">
                                    <h6 class="mb-2 font-weight-bold"><i class="fas fa-map-marker-alt text-danger"></i> Location</h6>
                                    <p class="mb-0 text-dark"><?php echo $schedule['address']; ?></p>
                                </div>
                                
                                <div class="products-info">
                                    <h6 class="mb-2 font-weight-bold"><i class="fas fa-box text-primary"></i> Products to Install</h6>
                                    <p class="mb-0 text-dark"><?php echo $schedule['products_to_install']; ?></p>
                                </div>
                                
                                <?php if ($schedule['image_path']): ?>
                                <div class="mt-2">
                                    <img src="../<?php echo $schedule['image_path']; ?>" alt="Schedule Image" class="img-fluid rounded" style="max-height: 150px;">
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($schedule['notes']): ?>
                                <div class="mt-2">
                                    <h6 class="mb-1"><i class="fas fa-sticky-note text-warning"></i> Notes</h6>
                                    <p class="mb-0"><?php echo $schedule['notes']; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mt-4">
                                    <div class="col-6">
                                        <a href="tel:<?php echo $schedule['contact_number']; ?>" class="btn btn-success btn-action btn-block">
                                            <i class="fas fa-phone"></i> Call Customer
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="edit_installer_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-primary btn-action btn-block">
                                            <i class="fas fa-edit"></i> Update Status
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No schedules for today</h5>
                            <p class="text-muted">Check back later or contact your supervisor.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
