<?php
session_start();
include '../config/conn.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borrow Equipment/Items</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../employee/includes/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include '../employee/includes/header.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Borrow Equipment/Items</h1>
                    </div>

                    <!-- New Borrow Request Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">New Borrow Request</h6>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#borrowModal">
                                <i class="fas fa-plus"></i> Request to Borrow Item
                            </button>
                        </div>
                    </div>

                    <!-- My Borrow Requests Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">My Borrow Requests</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="requestsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Request Date</th>
                                            <th>Expected Return</th>
                                            <th>Status</th>
                                            <th>Admin Notes</th>
                                            <th>Action Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $requests_sql = "SELECT br.*, COALESCE(p.product_name, br.item_name) as item_name, u.full_name as admin_name
                                                        FROM borrow_requests br
                                                        LEFT JOIN products p ON br.product_id = p.id
                                                        LEFT JOIN users u ON br.admin_id = u.id
                                                        WHERE br.employee_id = ?
                                                        ORDER BY br.borrow_date DESC";
                                        $stmt = $conn->prepare($requests_sql);
                                        $stmt->bind_param("i", $employee_id);
                                        $stmt->execute();
                                        $requests_result = $stmt->get_result();
                                        
                                        while ($request = $requests_result->fetch_assoc()) {
                                            $status_badge = '';
                                            switch($request['status']) {
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
                                            
                                            echo "<tr>
                                                    <td>" . htmlspecialchars($request['item_name']) . "</td>
                                                    <td>" . $request['quantity'] . "</td>
                                                    <td>" . date("M d, Y h:i A", strtotime($request['borrow_date'])) . "</td>
                                                    <td>" . ($request['expected_return_date'] ? date("M d, Y", strtotime($request['expected_return_date'])) : '—') . "</td>
                                                    <td>" . $status_badge . "</td>
                                                    <td>" . ($request['admin_notes'] ? htmlspecialchars($request['admin_notes']) : '—') . "</td>
                                                    <td>" . ($request['action_date'] ? date("M d, Y h:i A", strtotime($request['action_date'])) : '—') . "</td>
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
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; AUS General Services 2025</span>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Borrow Equipment/Item</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="borrowForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="item_name">Item/Equipment Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Enter the item you want to borrow" required>
                        </div>
                        <div class="form-group">
                            <label for="borrow_quantity">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="borrow_quantity" name="quantity" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label for="expected_return_date">Expected Return Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expected_return_date" name="expected_return_date" required>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional information about your request"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
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
        $('#requestsTable').DataTable();

        // Reset form when modal is opened
        $('#borrowModal').on('show.bs.modal', function() {
            $('#borrowForm')[0].reset();
            $('#borrow_quantity').val(1);
        });

        // Handle form submission
        $('#borrowForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                item_name: $('#item_name').val(),
                quantity: $('#borrow_quantity').val(),
                expected_return_date: $('#expected_return_date').val(),
                notes: $('#notes').val()
            };

            $.ajax({
                url: 'process_borrow_request.php',
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

