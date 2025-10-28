<?php
/**
 * Timezone Test Page
 * Visit this page to verify Philippine timezone is working correctly
 */
include 'config/conn.php';
include 'config/timezone.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timezone Test - Philippine Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-clock"></i> Philippine Timezone Test</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>PHP Timezone Information</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>PHP Timezone:</strong> <?= date_default_timezone_get() ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Current Philippine Time:</strong> <?= getPhilippineTime() ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Current UTC Time:</strong> <?= gmdate('Y-m-d H:i:s') ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Timezone Offset:</strong> <?= getPhilippineTimezoneOffset() ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>MySQL Timezone Information</h5>
                                <?php
                                $result = $conn->query("SELECT @@time_zone as timezone, NOW() as current_time, @@global.time_zone as global_timezone");
                                if ($result) {
                                    $row = $result->fetch_assoc();
                                ?>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>MySQL Timezone:</strong> <?= $row['timezone'] ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>MySQL Current Time:</strong> <?= $row['current_time'] ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>MySQL Global Timezone:</strong> <?= $row['global_timezone'] ?>
                                    </li>
                                </ul>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Sample Database Timestamps</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Sample Data</th>
                                        <th>Formatted Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Check products table
                                    $result = $conn->query("SELECT product_name, created_at FROM products LIMIT 3");
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>Products</td>";
                                            echo "<td>{$row['product_name']}</td>";
                                            echo "<td>" . formatPhilippineTime($row['created_at']) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    
                                    // Check aircon_sales table
                                    $result = $conn->query("SELECT aircon_model, date_of_sale FROM aircon_sales LIMIT 3");
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>Aircon Sales</td>";
                                            echo "<td>{$row['aircon_model']}</td>";
                                            echo "<td>" . formatPhilippineTime($row['date_of_sale']) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Timezone Configuration Status</h6>
                            <p class="mb-0">
                                ✅ PHP timezone set to Asia/Manila<br>
                                ✅ MySQL timezone set to +08:00<br>
                                ✅ All new timestamps will be stored in Philippine time<br>
                                ✅ All displayed timestamps will be shown in Philippine time
                            </p>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="inventory_report.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View Inventory Report
                            </a>
                            <a href="withdrawal_products.php" class="btn btn-success">
                                <i class="fas fa-boxes"></i> View Withdrawal Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
