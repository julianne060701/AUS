<?php
// Start output buffering to prevent header issues
ob_start();

// Start session FIRST before any output
session_start();

// Include database connection
include '../config/conn.php';

$success_message = '';
$error_message = '';

// Fetch products from specific categories (modify the category IDs or names as needed)
$aircon_sql = "SELECT p.*, c.category_name 
               FROM products p 
               LEFT JOIN category c ON p.category_id = c.category_id 
               ORDER BY p.product_name";

$aircon_result = $conn->query($aircon_sql);

// Debug: Check if products are being fetched
if (!$aircon_result) {
    $error_message = "Error fetching products: " . $conn->error;
} else if ($aircon_result->num_rows == 0) {
    $error_message = "No products found. Please check your categories and products.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $quantity_input = (int)$_POST['quantity']; // Quantity of products to stock out
    $cashier = $_POST['cashier_name'];
    $date_of_sale = date("Y-m-d H:i:s");
    
    // Step 1: Get current stock and product details from DB
    $stmt_fetch = $conn->prepare("SELECT product_name, quantity FROM products WHERE id = ?");
    $stmt_fetch->bind_param("i", $product_id);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($product_name, $current_stock);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // Check stock availability
    if ($quantity_input > $current_stock) {
        $error_message = "Insufficient stock. Available: {$current_stock} units, Requested: {$quantity_input} units.";
    }

    if (empty($error_message)) {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Step 2: Insert into sales table (simplified for stock out)
            // Note: Using 'cash' as payment_method since 'stock_out' may not be in ENUM yet
            // Stock out records are identified by selling_price = 0 and total_amount = 0
            // Now includes product_id foreign key for proper relationship
            $insert_sale = $conn->prepare("INSERT INTO aircon_sales (product_id, aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method) VALUES (?, ?, ?, 0, 0, ?, ?, 'cash')");
            $insert_sale->bind_param("isiss", $product_id, $product_name, $quantity_input, $date_of_sale, $cashier);
            
            if ($insert_sale->execute()) {
                $sale_id = $conn->insert_id;
            } else {
                throw new Exception("Failed to insert stock out record: " . $insert_sale->error);
            }

            // Step 3: Update inventory
            $new_stock = $current_stock - $quantity_input;
            $new_stock = max(0, $new_stock);

            // Update inventory
            $update_inventory = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_inventory->bind_param("ii", $new_stock, $product_id);
            $update_inventory->execute();

            $conn->commit();
            
            // Create success message for stock out
            $success_message = "Stock out recorded successfully! Removed: {$quantity_input} unit(s) of {$product_name}. Remaining stock: {$new_stock} units";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Fetch recent sales for display - UPDATED TO MATCH YOUR TABLE STRUCTURE
$sales_query = "SELECT * FROM aircon_sales ORDER BY sale_id DESC LIMIT 50";
$sales_result = $conn->query($sales_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Out Records - Product Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS (v4.6.2 - consistent with your theme) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- SB Admin 2 Custom styles -->
    <link href="https://cdn.jsdelivr.net/gh/StartBootstrap/startbootstrap-sb-admin-2/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <!-- Custom Employee CSS -->
    <link rel="stylesheet" href="../employee/css/employee.css">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
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

                    <!-- Success/Error Messages (Hidden - we'll use SweetAlert instead) -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show d-none" role="alert" id="success-alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Success!</strong> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-none" role="alert" id="error-alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Error!</strong> <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-box-open mr-2"></i>Stock Out Records
                        </h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addSaleModal">
                            <i class="fas fa-plus mr-2"></i>New Stock Out
                        </button>
                    </div>

                    <!-- Stock Out Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-table mr-2"></i>Recent Stock Out Records
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="salesTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Model</th>
                                            <th>Quantity</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($sales_result && $sales_result->num_rows > 0): ?>
                                            <?php while($row = $sales_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td>#<?php echo str_pad($row['sale_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                                    <td>
                                                        <i class="fas fa-cube text-info mr-1"></i>
                                                        <?php echo htmlspecialchars($row['aircon_model']); ?>
                                                    </td>
                                                    <td><span class="badge badge-info"><?php echo $row['quantity_sold']; ?></span></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y g:i A', strtotime($row['date_of_sale'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSale(<?php echo $row['sale_id']; ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No stock out records found.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->
                                        </div>
                                        </div>
            <!-- Footer -->
            <?php include('../includes/footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="saleForm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus mr-2"></i>Record New Stock Out
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Product Model -->
                        <div class="col-md-12 mb-3">
                            <label for="product_id" class="form-label">
                                <i class="fas fa-cube mr-1"></i>Product
                            </label>
                            <select class="form-control" name="product_id" id="product_id" required>
                                <option value="">Select Product</option>
                                <?php 
                                if ($aircon_result && $aircon_result->num_rows > 0): 
                                    $aircon_result->data_seek(0); // Reset pointer
                                    while($product = $aircon_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['id']; ?>"
                                            data-stock="<?php echo $product['quantity']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                        <?php if (!empty($product['capacity'])): ?>
                                            (<?php echo htmlspecialchars($product['capacity']); ?>)
                                        <?php endif; ?>
                                        <?php if (!empty($product['category_name'])): ?>
                                            - <?php echo htmlspecialchars($product['category_name']); ?>
                                        <?php endif; ?>
                                        - Stock: <?php echo $product['quantity']; ?>
                                    </option>
                                <?php 
                                    endwhile; 
                                else: 
                                ?>
                                    <option value="" disabled>No products available</option>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>Shows available stock
                            </small>
                        </div>

                        <!-- Quantity -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-sort-numeric-up mr-1"></i> Quantity
                            </label>
                            <input type="number" class="form-control" name="quantity" id="quantity" min="1" step="1" placeholder="e.g., 2" required>
                            <small class="form-text" id="stockInfo">Select product first</small>
                        </div>


                        <!-- Cashier Name -->
                        <div class="col-md-12 mb-3">
                            <label for="cashier_name" class="form-label">
                                <i class="fas fa-user mr-1"></i>Cashier Name
                            </label>
                            <input type="text" class="form-control" name="cashier_name" id="cashier_name"
                                value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3" id="stockOutInfo" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span id="stockOutDetails"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="submitSale">
                        <i class="fas fa-save mr-1"></i>Record Stock Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

  <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap Bundle -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <!-- SB Admin 2 scripts -->
    <script src="https://cdn.jsdelivr.net/gh/StartBootstrap/startbootstrap-sb-admin-2/js/sb-admin-2.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    // Global variables for form data
    let formData = {};

    // Function to update stock info
    function updateStockInfo() {
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const requestedQuantity = parseInt(document.getElementById('quantity').value) || 0;
        
        const stockInfo = document.getElementById('stockInfo');
        const quantityInput = document.getElementById('quantity');
        
        if (selectedOption.value) {
            stockInfo.innerHTML = `Available: <span class="text-success">${stock} units</span>`;
            quantityInput.setAttribute('max', stock);
            
            // Show warning if requested quantity exceeds stock
            if (requestedQuantity > stock) {
                stockInfo.innerHTML = `Available: <span class="text-danger">${stock} units</span> - <span class="text-danger">Exceeds stock!</span>`;
            }
            
            // Show stock out info
            if (requestedQuantity > 0) {
                const productName = selectedOption.getAttribute('data-name') || '';
                document.getElementById('stockOutDetails').textContent = `Stocking out ${requestedQuantity} unit(s) of ${productName}`;
                document.getElementById('stockOutInfo').style.display = 'block';
            } else {
                document.getElementById('stockOutInfo').style.display = 'none';
            }
        }
    }

    // Function to initialize event listeners
    function initializeEventListeners() {
        // Add event listeners for stock info updates
        document.getElementById('quantity').addEventListener('input', function() {
            updateStockInfo();
        });
    }

    // Initialize event listeners when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeEventListeners);
    } else {
        initializeEventListeners();
    }

    // Function to update stock info
    function updateStockInfo() {
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const requestedQuantity = parseInt(document.getElementById('quantity').value) || 0;
        
        const stockInfo = document.getElementById('stockInfo');
        const quantityInput = document.getElementById('quantity');
        
        if (selectedOption.value) {
            stockInfo.innerHTML = `Available: <span class="text-success">${stock} units</span>`;
            quantityInput.setAttribute('max', stock);
            
            // Show warning if requested quantity exceeds stock
            if (requestedQuantity > stock) {
                stockInfo.innerHTML = `Available: <span class="text-danger">${stock} units</span> - <span class="text-danger">Exceeds stock!</span>`;
            }
        }
    }

    // Set preset prices when product is selected
    document.getElementById('product_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
            
            document.getElementById('quantity').setAttribute('max', stock);
            
            updateStockInfo();
        } else {
            document.getElementById('stockInfo').innerHTML = 'Select product first';
            document.getElementById('quantity').removeAttribute('max');
            document.getElementById('stockOutInfo').style.display = 'none';
        }
    });

    // SweetAlert confirmation before form submission
    document.getElementById('submitSale').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Validate form first
        const form = document.getElementById('saleForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Check stock availability
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const requestedQuantity = parseInt(document.getElementById('quantity').value) || 0;
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const productName = selectedOption.getAttribute('data-name') || '';
        
        if (requestedQuantity > stock) {
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Stock!',
                text: `Requested ${requestedQuantity} units exceeds available stock (${stock} units).`,
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // Create confirmation message with stock out details
        Swal.fire({
            title: 'Confirm Stock Out',
            html: `
                <div class="text-left">
                    <strong>Product:</strong> ${productName}<br>
                    <strong>Quantity:</strong> ${requestedQuantity} unit(s)<br>
                    <strong>Available Stock:</strong> ${stock} units<br>
                    <strong>Remaining Stock:</strong> ${stock - requestedQuantity} units
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check mr-2"></i>Yes, Process Stock Out',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
            reverseButtons: true,
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Submit the form
                return submitStockOutForm();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Success handled in submitStockOutForm function
            }
        });
    });

    // Function to actually submit the form
    function submitStockOutForm() {
        return new Promise((resolve, reject) => {
            const form = document.getElementById('saleForm');
            
            // Show loading on submit button
            const submitBtn = document.getElementById('submitSale');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...';
            submitBtn.disabled = true;
            
            // Submit the form directly
            form.submit();
            
            // Since we're doing a direct form submission, we'll resolve immediately
            resolve();
        });
    }

    // Enhanced action functions with SweetAlert
    function viewSale(id) {
        // Show loading state
        Swal.fire({
            title: 'Loading Stock Out Details...',
            text: 'Please wait while we fetch the transaction details.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch sale details via AJAX
        fetch(`view_sale_details.php?sale_id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const sale = data.sale;
                    
                    Swal.fire({
                        title: `Stock Out Details #${sale.sale_id}`,
                        html: `
                            <div class="text-left">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Product:</strong><br>
                                        <i class="fas fa-cube text-info mr-1"></i>
                                        ${sale.aircon_model}
                                    </div>
                                    <div class="col-6">
                                        <strong>Quantity:</strong><br>
                                        <span class="badge badge-info">${sale.quantity_sold} unit(s)</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Processed By:</strong><br>
                                        <i class="fas fa-user-circle text-primary mr-1"></i>
                                        ${sale.cashier}
                                    </div>
                                    <div class="col-6">
                                        <strong>Type:</strong><br>
                                        <span class="badge badge-secondary">Stock Out</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Date:</strong><br>
                                        <i class="fas fa-calendar text-muted mr-1"></i>
                                        ${new Date(sale.date_of_sale).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </div>
                                    <div class="col-6">
                                        <strong>Transaction ID:</strong><br>
                                        <code>#${sale.sale_id.toString().padStart(3, '0')}</code>
                                    </div>
                                </div>
                            </div>
                        `,
            icon: 'info',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: '<i class="fas fa-check mr-1"></i>Close',
                        width: '600px'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to fetch stock out details.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while fetching stock out details.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
    }

    function printReceipt(id) {
        Swal.fire({
            title: 'Print Stock Out Receipt?',
            text: `Do you want to print the receipt for stock out #${id}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-print mr-2"></i>Yes, Print',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Open print window with receipt details
                window.open(`print_receipt.php?sale_id=${id}`, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Receipt Opened!',
                    text: `Receipt for stock out #${id} has been opened in a new window.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }

    // Initialize DataTable
    $(document).ready(function () {
        $('#salesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: true,
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: [4] } // Disable sorting on Actions column
            ]
        });

        // Show success/error messages with SweetAlert if they exist
        <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($success_message); ?>',
                confirmButtonColor: '#28a745'
            }).then(() => {
                // Reset form after success
                $('#addSaleModal').modal('hide');
                document.getElementById('saleForm').reset();
                
                // Reset displays
                document.getElementById('stockOutInfo').style.display = 'none';
                document.getElementById('stockInfo').innerHTML = 'Select product first';
            });
        <?php endif; ?>

        <?php if ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo addslashes($error_message); ?>',
                confirmButtonColor: '#dc3545'
            });
        <?php endif; ?>
    });
    
    // Style for cursor pointer
    $('<style>.cursor-pointer { cursor: pointer; }</style>').appendTo('head');

    // Reset form when modal is closed
    $('#addSaleModal').on('hidden.bs.modal', function () {
        document.getElementById('saleForm').reset();
        document.getElementById('stockOutInfo').style.display = 'none';
        document.getElementById('stockInfo').innerHTML = 'Select product first';
    });
</script>

<?php
// End output buffering and flush content
ob_end_flush();
?>

</body>
</html>