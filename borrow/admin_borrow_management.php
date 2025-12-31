<?php
session_start();
include '../config/conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get all employees for filter
$employees_sql = "SELECT DISTINCT u.id, u.full_name 
                  FROM borrow_requests br
                  JOIN users u ON br.employee_id = u.id
                  ORDER BY u.full_name";
$employees_result = $conn->query($employees_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borrow Management</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include '../includes/topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Borrow Management</h1>
                    </div>

                    <!-- Filters Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterStatus">Status</label>
                                        <select class="form-control" id="filterStatus">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="declined">Declined</option>
                                            <option value="returned">Returned</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterEmployee">Employee</label>
                                        <select class="form-control" id="filterEmployee">
                                            <option value="">All Employees</option>
                                            <?php
                                            while ($emp = $employees_result->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($emp['full_name']) . "'>" . htmlspecialchars($emp['full_name']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterDateFrom">Date From</label>
                                        <input type="date" class="form-control" id="filterDateFrom">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filterDateTo">Date To</label>
                                        <input type="date" class="form-control" id="filterDateTo">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary" id="applyFilters">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="clearFilters">
                                        <i class="fas fa-times"></i> Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Borrow Requests Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Borrow Requests</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="borrowTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Employee</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Expected Return</th>
                                            <th>Action Date</th>
                                            <th>Return Date</th>
                                            <th>Notes</th>
                                            <th>Admin Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $all_sql = "SELECT br.*, COALESCE(p.product_name, br.item_name) as item_name, u.full_name as employee_name, admin_u.full_name as admin_name
                                                    FROM borrow_requests br
                                                    LEFT JOIN products p ON br.product_id = p.id
                                                    JOIN users u ON br.employee_id = u.id
                                                    LEFT JOIN users admin_u ON br.admin_id = admin_u.id
                                                    ORDER BY br.borrow_date DESC";
                                        $result = $conn->query($all_sql);
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            $status_badge = '';
                                            switch($row['status']) {
                                                case 'pending':
                                                    $status_badge = '<span class="badge badge-warning">Pending</span>';
                                                    break;
                                                case 'approved':
                                                    $status_badge = '<span class="badge badge-success">Approved</span>';
                                                    break;
                                                case 'declined':
                                                    $status_badge = '<span class="badge badge-danger">Declined</span>';
                                                    break;
                                                case 'returned':
                                                    $status_badge = '<span class="badge badge-info">Returned</span>';
                                                    break;
                                            }
                                            
                                            $actions = '';
                                            if ($row['status'] == 'pending') {
                                                $actions = '<button class="btn btn-sm btn-success approve-btn" data-id="' . $row['id'] . '" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger decline-btn" data-id="' . $row['id'] . '" title="Decline">
                                                                <i class="fas fa-times"></i>
                                                            </button>';
                                            } elseif ($row['status'] == 'approved') {
                                                $actions = '<button class="btn btn-sm btn-primary return-btn" data-id="' . $row['id'] . '" title="Mark as Returned">
                                                                <i class="fas fa-undo"></i>
                                                            </button>';
                                            } else {
                                                $actions = '—';
                                            }
                                            
                                            echo "<tr>
                                                    <td>#" . $row['id'] . "</td>
                                                    <td>" . htmlspecialchars($row['employee_name']) . "</td>
                                                    <td>" . htmlspecialchars($row['item_name']) . "</td>
                                                    <td>" . $row['quantity'] . "</td>
                                                    <td>" . $status_badge . "</td>
                                                    <td>" . date("M d, Y h:i A", strtotime($row['borrow_date'])) . "</td>
                                                    <td>" . ($row['expected_return_date'] ? date("M d, Y", strtotime($row['expected_return_date'])) : '—') . "</td>
                                                    <td>" . ($row['action_date'] ? date("M d, Y h:i A", strtotime($row['action_date'])) : '—') . "</td>
                                                    <td>" . ($row['return_date'] ? date("M d, Y h:i A", strtotime($row['return_date'])) : '—') . "</td>
                                                    <td>" . ($row['notes'] ? htmlspecialchars(substr($row['notes'], 0, 50)) . (strlen($row['notes']) > 50 ? '...' : '') : '—') . "</td>
                                                    <td>" . ($row['admin_notes'] ? htmlspecialchars(substr($row['admin_notes'], 0, 50)) . (strlen($row['admin_notes']) > 50 ? '...' : '') : '—') . "</td>
                                                    <td>" . $actions . "</td>
                                                  </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../includes/footer.php'; ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalTitle">Action</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="actionForm">
                    <div class="modal-body">
                        <input type="hidden" id="action_request_id" name="request_id">
                        <input type="hidden" id="action_type" name="action_type">
                        <div class="form-group">
                            <label for="admin_notes">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="actionSubmitBtn">Submit</button>
                    </div>
                </form>
            </div>
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
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#borrowTable').DataTable({
            "order": [[5, "desc"]], // Sort by Request Date descending
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
        });

        // Custom filter function
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var status = $('#filterStatus').val();
                var employee = $('#filterEmployee').val();
                var dateFrom = $('#filterDateFrom').val();
                var dateTo = $('#filterDateTo').val();
                
                // Get row data
                var rowStatus = data[4].toLowerCase(); // Status column
                var rowEmployee = data[1]; // Employee column
                var rowDateStr = data[5]; // Request Date column (format: "M d, Y h:i A")
                
                // Status filter - check if status badge contains the filter status
                if (status) {
                    var statusMap = {
                        'pending': 'pending',
                        'approved': 'approved',
                        'declined': 'declined',
                        'returned': 'returned'
                    };
                    if (!rowStatus.includes(statusMap[status])) {
                        return false;
                    }
                }
                
                // Employee filter
                if (employee && rowEmployee !== employee) {
                    return false;
                }
                
                // Date range filter
                if (dateFrom || dateTo) {
                    // Parse the formatted date string (e.g., "Oct 27, 2025 10:00 AM")
                    var rowDateObj = new Date(rowDateStr);
                    
                    if (dateFrom) {
                        var fromDate = new Date(dateFrom);
                        fromDate.setHours(0, 0, 0, 0);
                        if (rowDateObj < fromDate) {
                            return false;
                        }
                    }
                    if (dateTo) {
                        var toDate = new Date(dateTo);
                        toDate.setHours(23, 59, 59, 999); // Include entire end date
                        if (rowDateObj > toDate) {
                            return false;
                        }
                    }
                }
                
                return true;
            }
        );

        // Apply filters
        $('#applyFilters').on('click', function() {
            table.draw();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#filterStatus').val('');
            $('#filterEmployee').val('');
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
            table.draw();
        });

        // Approve button
        $(document).on('click', '.approve-btn', function() {
            var requestId = $(this).data('id');
            $('#action_request_id').val(requestId);
            $('#action_type').val('approve');
            $('#actionModalTitle').text('Approve Borrow Request');
            $('#actionSubmitBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
            $('#admin_notes').val('');
            $('#actionModal').modal('show');
        });

        // Decline button
        $(document).on('click', '.decline-btn', function() {
            var requestId = $(this).data('id');
            $('#action_request_id').val(requestId);
            $('#action_type').val('decline');
            $('#actionModalTitle').text('Decline Borrow Request');
            $('#actionSubmitBtn').removeClass('btn-success').addClass('btn-danger').text('Decline');
            $('#admin_notes').val('');
            $('#actionModal').modal('show');
        });

        // Return button
        $(document).on('click', '.return-btn', function() {
            var requestId = $(this).data('id');
            $('#action_request_id').val(requestId);
            $('#action_type').val('return');
            $('#actionModalTitle').text('Mark as Returned');
            $('#actionSubmitBtn').removeClass('btn-danger btn-success').addClass('btn-primary').text('Mark as Returned');
            $('#admin_notes').val('');
            $('#actionModal').modal('show');
        });

        // Handle form submission
        $('#actionForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                request_id: $('#action_request_id').val(),
                action_type: $('#action_type').val(),
                admin_notes: $('#admin_notes').val()
            };

            $.ajax({
                url: 'process_borrow_action.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred. Please try again.'
                    });
                }
            });
        });
    });
    </script>
</body>
</html>
