<?php
session_start();
include '../config/conn.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Low Stock Rice Inventory</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        .stock-critical {
            background-color: #ffeaea !important;
            border-left: 4px solid #e74a3b !important;
        }

        .stock-low {
            background-color: #fff8e1 !important;
            border-left: 4px solid #f6c23e !important;
        }

        .badge-critical {
            background-color: #e74a3b;
            color: white;
        }

        .badge-low {
            background-color: #f6c23e;
            color: #333;
        }

        .badge-out-of-stock {
            background-color: #6c757d;
            color: white;
        }

        .quantity-display {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
            transform: scale(1.001);
            transition: all 0.2s ease;
        }

        .btn-reorder {
            background: linear-gradient(45deg, #4e73df, #36b9cc);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-reorder:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
            color: white;
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-input {
            padding-left: 40px;
        }

        .filter-badge {
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 5px;
        }

        .filter-badge:hover {
            transform: scale(1.05);
        }

        .filter-badge.active {
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .stats-card {
            border-radius: 10px;
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('includes/topbar.php'); ?>

            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Low Stock Rice Inventory
                    </h1>
                    <button class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>

                <?php
                // Get statistics
                $statsQuery = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN quantity_sacks = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN quantity_sacks > 0 AND quantity_sacks < alert_threshold THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN quantity_sacks < (alert_threshold * 0.3) AND quantity_sacks > 0 THEN 1 ELSE 0 END) as critical_stock,
                    SUM(quantity_sacks * price_per_kg * sack_weight_kg) as total_value
                    FROM rice_inventory 
                    WHERE quantity_sacks <= alert_threshold";
                
                $statsResult = $conn->query($statsQuery);
                $stats = $statsResult->fetch_assoc();
                ?>

                <!-- Low Stock Rice Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-warehouse me-2"></i>Rice Inventory Status
                        </h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">Options:</div>
                                <a class="dropdown-item" href="#"><i class="fas fa-print me-2"></i>Print List</a>
                                <a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Export Excel</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Rice Type</th>
                                        <th>Current Stock</th>
                                        <th>Alert Threshold</th>
                                        <th>Status</th>
                                        <th>Unit</th>
                                        <th>Price per KG</th>
                                        <th>Sack Weight (KG)</th>
                                        <th>Total KG</th>
                                        <th>Total Value</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <?php
                                    // Query to get low stock items (quantity_sacks <= alert_threshold)
                                    $query = "SELECT ri.*, c.category_name 
                                    FROM rice_inventory ri 
                                    LEFT JOIN category c ON ri.category_id = c.category_id 
                                    WHERE ri.quantity_sacks <= ri.alert_threshold 
                                    ORDER BY (ri.quantity_sacks/ri.alert_threshold) ASC, ri.quantity_sacks ASC";
                                    
                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $id = htmlspecialchars($row['id']);
                                            $riceType = htmlspecialchars($row['rice_type']);
                                            $quantitySacks = (int)$row['quantity_sacks'];
                                            $alertThreshold = (int)$row['alert_threshold'];
                                            $unit = htmlspecialchars($row['unit']);
                                            $pricePerKg = (float)$row['price_per_kg'];
                                            $sackWeightKg = (float)$row['sack_weight_kg'];
                                            $quantityKg = (float)$row['quantity_kg'];
                                            $categoryName = htmlspecialchars($row['category_name'] ?? 'N/A');

                                            // Calculate total value
                                            $totalValue = $quantitySacks * $pricePerKg * $sackWeightKg;

                                            // Determine status and styling
                                            if ($quantitySacks == 0) {
                                                $status = 'Out of Stock';
                                                $statusClass = 'badge-out-of-stock';
                                                $rowClass = 'stock-critical';
                                                $quantityClass = 'text-danger';
                                            } else if ($quantitySacks < ($alertThreshold * 0.3)) {
                                                $status = 'Critical';
                                                $statusClass = 'badge-critical';
                                                $rowClass = 'stock-critical';
                                                $quantityClass = 'text-danger';
                                            } else {
                                                $status = 'Low';
                                                $statusClass = 'badge-low';
                                                $rowClass = 'stock-low';
                                                $quantityClass = 'text-warning';
                                            }

                                            echo "<tr class='{$rowClass}' data-status='" . strtolower(str_replace(' ', '-', $status)) . "'>";
                                            echo "<td class='fw-bold'>{$id}</td>";
                                            echo "<td>{$riceType}</td>";
                                            echo "<td class='quantity-display {$quantityClass}'>{$quantitySacks} {$unit}</td>";
                                            echo "<td>{$alertThreshold} {$unit}</td>";
                                            echo "<td><span class='badge {$statusClass}'>{$status}</span></td>";
                                            echo "<td>{$unit}</td>";
                                            echo "<td>₱" . number_format($pricePerKg, 2) . "</td>";
                                            echo "<td>{$sackWeightKg} kg</td>";
                                            echo "<td>" . number_format($quantityKg, 2) . " kg</td>";
                                            echo "<td>₱" . number_format($totalValue, 2) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='11' class='text-center'>No low stock items found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
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

<!-- Scripts -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
 $(document).ready(function () {
    // Initialize DataTable only once
    let table;
    if (!$.fn.DataTable.isDataTable('#dataTable')) {
        table = $('#dataTable').DataTable({
            "pageLength": 10,
            "responsive": true,
            "order": [[2, "asc"]],
            "columnDefs": [
                { "orderable": false, "targets": 10 }
            ]
        });
    } else {
        table = $('#dataTable').DataTable();
    }

    // Search functionality
    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    // Filter functionality
    $('.filter-badge').on('click', function () {
        $('.filter-badge').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');
        let searchTerm = '';

        if (filter === 'out-of-stock') searchTerm = 'Out of Stock';
        else if (filter === 'critical') searchTerm = 'Critical';
        else if (filter === 'low') searchTerm = 'Low';

        table.search(searchTerm).draw();
    });
});

</script>
</body>
</html>