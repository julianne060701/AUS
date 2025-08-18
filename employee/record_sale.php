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
    $quantity_input = (int)$_POST['quantity']; // Quantity of products sold
    $payment_method = $_POST['payment_method']; // 'cash' or 'installment'
    $selling_price = (float)$_POST['selling_price'];
    $cashier = $_POST['cashier_name'];
    $date_of_sale = date("Y-m-d H:i:s");
    
    // Calculate discount and final amounts
    $subtotal = $selling_price * $quantity_input;
    $discount_percentage = ($payment_method === 'cash') ? 10 : 0;
    $discount_amount = $subtotal * ($discount_percentage / 100);
    $total_amount = $subtotal - $discount_amount;

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
            // Step 2: Insert into sales table - FIXED TO MATCH YOUR TABLE STRUCTURE
            $insert_sale = $conn->prepare("INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_sale->bind_param("sidiss", $product_name, $quantity_input, $selling_price, $total_amount, $date_of_sale, $cashier);
            $insert_sale->execute();

            // Step 3: Update inventory
            $new_stock = $current_stock - $quantity_input;
            $new_stock = max(0, $new_stock);

            // Update inventory
            $update_inventory = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_inventory->bind_param("ii", $new_stock, $product_id);
            $update_inventory->execute();

            $conn->commit();
            $discount_text = ($payment_method === 'cash') ? " with 10% cash discount (₱" . number_format($discount_amount, 2) . " saved)" : "";
            $success_message = "Sale recorded successfully! Sold: {$quantity_input} unit(s) of {$product_name}{$discount_text}. Total: ₱" . number_format($total_amount, 2);
            
            // Instead of meta refresh, we'll use JavaScript redirect after SweetAlert
            // echo "<meta http-equiv='refresh' content='3'>";
            
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
    <title>Sales Records - Product Inventory</title>
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
        <?php include('includes/sidebar.php'); ?>
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
                            <i class="fas fa-shopping-cart mr-2"></i>Sales Records
                        </h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addSaleModal">
                            <i class="fas fa-plus mr-2"></i>New Sale
                        </button>
                    </div>

                    <!-- Sales Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-table mr-2"></i>Recent Sales Records
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
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Cashier</th>
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
                                                    <td>₱<?php echo number_format($row['selling_price'], 2); ?></td>
                                                    <td><strong>₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-user-circle text-primary mr-1"></i>
                                                        <?php echo htmlspecialchars($row['cashier']); ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y ', strtotime($row['date_of_sale'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSale(<?php echo $row['sale_id']; ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="printReceipt(<?php echo $row['sale_id']; ?>)" title="Print Receipt">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No sales records found.</p>
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
            <?php include('includes/footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="saleForm" method="post" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus mr-2"></i>Record New Sale
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
                                            data-price="<?php echo $product['selling_price']; ?>"
                                            data-stock="<?php echo $product['quantity']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                        <?php if (!empty($product['capacity'])): ?>
                                            (<?php echo htmlspecialchars($product['capacity']); ?>)
                                        <?php endif; ?>
                                        <?php if (!empty($product['category_name'])): ?>
                                            - <?php echo htmlspecialchars($product['category_name']); ?>
                                        <?php endif; ?>
                                        - Stock: <?php echo $product['quantity']; ?> - ₱<?php echo number_format($product['selling_price'], 2); ?>
                                    </option>
                                <?php 
                                    endwhile; 
                                else: 
                                ?>
                                    <option value="" disabled>No products available</option>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>Shows available stock and selling price
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

                        <!-- Selling Price -->
                        <div class="col-md-6 mb-3">
                            <label for="selling_price" class="form-label">
                                <i class="fas fa-peso-sign mr-1"></i>Unit Price
                            </label>
                            <input type="number" class="form-control" name="selling_price" id="selling_price" step="0.01" min="0" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                <i class="fas fa-credit-card mr-1"></i>Payment Method
                            </label>
                            <div class="row">
                                <div class="col-6">
                                    <div class="card border-success payment-option" data-method="cash">
                                        <div class="card-body text-center">
                                            <input type="radio" name="payment_method" value="cash" id="cash" required>
                                            <label for="cash" class="mb-0 d-block cursor-pointer">
                                                <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                                <h6 class="text-success">Cash Payment</h6>
                                                <small class="text-success font-weight-bold">10% Discount</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-warning payment-option" data-method="installment">
                                        <div class="card-body text-center">
                                            <input type="radio" name="payment_method" value="installment" id="installment" required>
                                            <label for="installment" class="mb-0 d-block cursor-pointer">
                                                <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                                <h6 class="text-warning">Installment</h6>
                                                <small class="text-muted">Full Price</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

                    <div class="alert alert-info mt-3" id="saleInfo" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span id="saleDetails"></span>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="card bg-light mt-3" id="priceBreakdown">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Subtotal:</small>
                                    <div id="subtotalDisplay">₱0.00</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Discount:</small>
                                    <div id="discountDisplay" class="text-success">₱0.00 (0%)</div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="text-center">
                                <h5 class="mb-0" id="totalDisplay">Total: ₱0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="submitSale">
                        <i class="fas fa-save mr-1"></i>Record Sale
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

    function calculateTotal() {
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        const price = parseFloat(document.getElementById('selling_price').value) || 0;
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const productName = selectedOption.getAttribute('data-name') || '';
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        const subtotal = quantity * price;
        const isDiscounted = paymentMethod && paymentMethod.value === 'cash';
        const discountPercentage = isDiscounted ? 10 : 0;
        const discountAmount = subtotal * (discountPercentage / 100);
        const total = subtotal - discountAmount;
        
        // Update displays
        document.getElementById('subtotalDisplay').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('discountDisplay').textContent = `₱${discountAmount.toFixed(2)} (${discountPercentage}%)`;
        document.getElementById('discountDisplay').className = isDiscounted ? 'text-success font-weight-bold' : 'text-muted';
        document.getElementById('totalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
        
        // Show sale info
        if (quantity > 0 && productName) {
            const paymentText = paymentMethod ? ` (${paymentMethod.value === 'cash' ? 'Cash - 10% discount' : 'Installment'})` : '';
            document.getElementById('saleDetails').textContent = `Selling ${quantity} unit(s) of ${productName}${paymentText}`;
            document.getElementById('saleInfo').style.display = 'block';
        } else {
            document.getElementById('saleInfo').style.display = 'none';
        }

        // Store calculation data for SweetAlert
        formData = {
            productName: productName,
            quantity: quantity,
            unitPrice: price,
            subtotal: subtotal,
            discount: discountAmount,
            total: total,
            paymentMethod: paymentMethod ? paymentMethod.value : '',
            discountPercentage: discountPercentage
        };
    }

    // Add event listeners for calculation
    document.getElementById('quantity').addEventListener('input', function() {
        updateStockInfo();
        calculateTotal();
    });
    document.getElementById('selling_price').addEventListener('input', calculateTotal);
    
    // Payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Update visual selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary', 'bg-light');
            });
            
            const selectedCard = document.querySelector(`.payment-option[data-method="${this.value}"]`);
            selectedCard.classList.add('border-primary', 'bg-light');
            
            calculateTotal();
        });
    });

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
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
            
            document.getElementById('selling_price').value = price.toFixed(2);
            document.getElementById('quantity').setAttribute('max', stock);
            
            updateStockInfo();
            calculateTotal();
        } else {
            document.getElementById('selling_price').value = '';
            document.getElementById('stockInfo').innerHTML = 'Select product first';
            document.getElementById('quantity').removeAttribute('max');
            document.getElementById('saleInfo').style.display = 'none';
            
            // Reset price breakdown
            document.getElementById('subtotalDisplay').textContent = '₱0.00';
            document.getElementById('discountDisplay').textContent = '₱0.00 (0%)';
            document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
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
        
        if (requestedQuantity > stock) {
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Stock!',
                text: `Requested ${requestedQuantity} units exceeds available stock (${stock} units).`,
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // Create confirmation message with sale details
        const paymentMethodText = formData.paymentMethod === 'cash' ? 'Cash Payment (10% Discount)' : 'Installment Payment';
        const discountText = formData.discount > 0 ? `<br><strong>Discount:</strong> ₱${formData.discount.toFixed(2)}` : '';
        
        Swal.fire({
            title: 'Confirm Sale Transaction',
            html: `
                <div class="text-left">
                    <strong>Product:</strong> ${formData.productName}<br>
                    <strong>Quantity:</strong> ${formData.quantity} unit(s)<br>
                    <strong>Unit Price:</strong> ₱${formData.unitPrice.toFixed(2)}<br>
                    <strong>Payment Method:</strong> ${paymentMethodText}<br>
                    <strong>Subtotal:</strong> ₱${formData.subtotal.toFixed(2)}
                    ${discountText}
                    <hr>
                    <strong class="text-primary">Total Amount:</strong> <span class="text-primary">₱${formData.total.toFixed(2)}</span>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check mr-2"></i>Yes, Record Sale',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
            reverseButtons: true,
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Submit the form
                return submitSaleForm();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Success handled in submitSaleForm function
            }
        });
    });

    // Function to actually submit the form
    function submitSaleForm() {
        return new Promise((resolve, reject) => {
            const form = document.getElementById('saleForm');
            const formDataToSend = new FormData(form);
            
            // Show loading on submit button
            const submitBtn = document.getElementById('submitSale');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...';
            submitBtn.disabled = true;
            
            // Submit via fetch API for better control
            fetch(window.location.href, {
                method: 'POST',
                body: formDataToSend
            })
            .then(response => response.text())
            .then(data => {
                // For now, we'll reload the page to handle PHP response
                // In a more advanced setup, you'd parse JSON response
                window.location.reload();
                resolve();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing the sale. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                reject(error);
            });
        });
    }

    // Enhanced action functions with SweetAlert
    function viewSale(id) {
        Swal.fire({
            title: `Sale Details #${id}`,
            text: 'Sale details would be loaded here...',
            icon: 'info',
            confirmButtonColor: '#007bff'
        });
    }

    function printReceipt(id) {
        Swal.fire({
            title: 'Print Receipt?',
            text: `Do you want to print the receipt for sale #${id}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-print mr-2"></i>Yes, Print',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Here you would implement actual printing logic
                Swal.fire({
                    icon: 'success',
                    title: 'Receipt Sent to Printer!',
                    text: `Receipt for sale #${id} has been sent to the printer.`,
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
            order: [[0, 'desc']]
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
                document.getElementById('subtotalDisplay').textContent = '₱0.00';
                document.getElementById('discountDisplay').textContent = '₱0.00 (0%)';
                document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
                document.getElementById('saleInfo').style.display = 'none';
                document.getElementById('stockInfo').innerHTML = 'Select product first';
                
                // Remove payment method selections
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
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
        document.getElementById('subtotalDisplay').textContent = '₱0.00';
        document.getElementById('discountDisplay').textContent = '₱0.00 (0%)';
        document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
        document.getElementById('saleInfo').style.display = 'none';
        document.getElementById('stockInfo').innerHTML = 'Select product first';
        
        // Remove payment method selections
        document.querySelectorAll('.payment-option').forEach(option => {
            option.classList.remove('border-primary', 'bg-light');
        });
    });
</script>

<?php
// End output buffering and flush content
ob_end_flush();
?>

</body>
</html>