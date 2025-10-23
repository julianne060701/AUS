<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get all installers
$installers_query = "SELECT DISTINCT installer_name FROM installer_schedules ORDER BY installer_name";
$installers_result = mysqli_query($conn, $installers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Installer Selector - AUS General Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .installer-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .installer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .installer-icon {
            font-size: 3.5rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        .quick-access {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-quick {
            border-radius: 25px;
            padding: 15px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-quick:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        .card-title {
            color: #2c3e50;
            font-weight: 700;
        }
        .card-text {
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="quick-access p-4 rounded">
                    <h2 class="mb-0"><i class="fas fa-users"></i> Installer Dashboard</h2>
                    <p class="mb-0">Select an installer to view their schedules</p>
                </div>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <a href="installer_mobile_view.php" class="btn btn-primary btn-quick btn-block">
                    <i class="fas fa-mobile-alt"></i> Mobile View (All)
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="installer_dashboard.php" class="btn btn-info btn-quick btn-block">
                    <i class="fas fa-calendar-alt"></i> Calendar View
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="installer_schedule.php" class="btn btn-secondary btn-quick btn-block">
                    <i class="fas fa-list"></i> List View
                </a>
            </div>
        </div>

        <!-- Installer Selection -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">Select Installer</h4>
            </div>
        </div>

        <div class="row">
            <?php while ($installer = mysqli_fetch_assoc($installers_result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card installer-card h-100" onclick="selectInstaller('<?php echo $installer['installer_name']; ?>')">
                        <div class="card-body text-center">
                            <div class="installer-icon mb-3">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h5 class="card-title"><?php echo $installer['installer_name']; ?></h5>
                            <p class="card-text text-muted">View schedules and assignments</p>
                        </div>
                        <div class="card-footer bg-light">
                            <small class="text-muted">Click to view schedules</small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- All Installers View -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">View All Installers</h5>
                        <p class="card-text">See schedules for all installers in one view</p>
                        <a href="installer_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-calendar-alt"></i> View All Schedules
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectInstaller(installerName) {
            // Redirect to mobile view with installer filter
            window.location.href = 'installer_mobile_view.php?installer=' + encodeURIComponent(installerName);
        }
    </script>
</body>
</html>
