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
    // Debug: Log all POST data
    error_log("=== FORM SUBMISSION DEBUG ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    $product_id = $_POST['product_id'];
    $quantity_input = (int)$_POST['quantity']; // Quantity of products sold
    // Get payment method with fallback
    $payment_method = 'cash'; // Default to cash
    if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
        $payment_method = $_POST['payment_method'];
    } else {
        // If payment_method is not set, check if installment data exists
        if (isset($_POST['installment_period']) && !empty($_POST['installment_period'])) {
            $payment_method = 'installment';
        }
    }
    $selling_price = (float)$_POST['selling_price'];
    $cashier = $_POST['cashier_name'];
    $date_of_sale = date("Y-m-d H:i:s");
    
    // Debug: Check if payment_method is being received
    if (!isset($_POST['payment_method'])) {
        error_log("WARNING: payment_method not found in POST data!");
        error_log("Available POST keys: " . implode(', ', array_keys($_POST)));
    } else {
        error_log("Payment method received: " . $_POST['payment_method']);
    }
    
    // Debug: Show final payment method determination
    error_log("Final payment method determined: " . $payment_method);
    error_log("Installment period: " . (isset($_POST['installment_period']) ? $_POST['installment_period'] : 'not set'));
    
    // Get installment details if applicable
    $installment_period = isset($_POST['installment_period']) ? (int)$_POST['installment_period'] : 0;
    $interest_rate = 0;
    
    // Debug: Log the values
    error_log("Payment Method: " . $payment_method);
    error_log("Installment Period: " . $installment_period);
    error_log("Installment Period Raw: " . ($_POST['installment_period'] ?? 'NOT SET'));
    if ($payment_method === 'installment' && $installment_period > 0) {
        // Set interest rate based on installment period
        switch($installment_period) {
            case 6:
                $interest_rate = 3;
                break;
            case 12:
                $interest_rate = 5;
                break;
            case 24:
                $interest_rate = 7;
                break;
        }
    }
    
    // Calculate discount and final amounts
    $subtotal = $selling_price * $quantity_input;
    $discount_percentage = ($payment_method === 'cash') ? 10 : 0;
    $discount_amount = $subtotal * ($discount_percentage / 100);
    
    if ($payment_method === 'installment' && $installment_period > 0) {
        // Calculate installment with interest
        $interest_amount = $subtotal * ($interest_rate / 100);
        $total_amount = $subtotal + $interest_amount;
    } else {
        // Cash payment with discount
    $total_amount = $subtotal - $discount_amount;
    }

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
            // Debug: Log the data being inserted
            error_log("Payment Method: " . $payment_method);
            error_log("Installment Period: " . $installment_period);
            error_log("Interest Rate: " . $interest_rate);
            error_log("Interest Amount: " . $interest_amount);
            
            // Step 2: Insert into sales table with installment data
            $insert_sale = $conn->prepare("INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, installment_period, interest_rate, interest_amount, monthly_payment, original_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Prepare installment data
            $payment_method_db = $payment_method;
            $installment_period_db = ($payment_method === 'installment' && $installment_period > 0) ? $installment_period : null;
            $interest_rate_db = ($payment_method === 'installment' && $installment_period > 0) ? $interest_rate : null;
            $interest_amount_db = ($payment_method === 'installment' && $installment_period > 0) ? $interest_amount : null;
            $monthly_payment_db = ($payment_method === 'installment' && $installment_period > 0) ? ($total_amount / $installment_period) : null;
            $original_price_db = $subtotal;
            
            // Debug: Log the prepared data
            error_log("Prepared Data - Payment Method: " . $payment_method_db);
            error_log("Prepared Data - Installment Period: " . ($installment_period_db ?? 'NULL'));
            error_log("Prepared Data - Interest Rate: " . ($interest_rate_db ?? 'NULL'));
            error_log("Prepared Data - Interest Amount: " . ($interest_amount_db ?? 'NULL'));
            error_log("Prepared Data - Monthly Payment: " . ($monthly_payment_db ?? 'NULL'));
            error_log("Prepared Data - Original Price: " . $original_price_db);
            
            $insert_sale->bind_param("sidissisiddd", $product_name, $quantity_input, $selling_price, $total_amount, $date_of_sale, $cashier, $payment_method_db, $installment_period_db, $interest_rate_db, $interest_amount_db, $monthly_payment_db, $original_price_db);
            
            if ($insert_sale->execute()) {
                $sale_id = $conn->insert_id;
                error_log("Sale inserted successfully with ID: " . $sale_id);
                error_log("Payment method saved to database: " . $payment_method_db);
            } else {
                error_log("Error inserting sale: " . $insert_sale->error);
                throw new Exception("Failed to insert sale: " . $insert_sale->error);
            }

            // Step 3: Update inventory
            $new_stock = $current_stock - $quantity_input;
            $new_stock = max(0, $new_stock);

            // Update inventory
            $update_inventory = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_inventory->bind_param("ii", $new_stock, $product_id);
            $update_inventory->execute();

            $conn->commit();
            
            // Create success message based on payment method
            if ($payment_method === 'cash') {
                $success_message = "Sale recorded successfully! Sold: {$quantity_input} unit(s) of {$product_name} with 10% cash discount (₱" . number_format($discount_amount, 2) . " saved). Total: ₱" . number_format($total_amount, 2);
            } else if ($payment_method === 'installment' && $installment_period > 0) {
                $interest_amount = $subtotal * ($interest_rate / 100);
                $monthly_payment = $total_amount / $installment_period;
                $success_message = "Installment sale recorded successfully! Sold: {$quantity_input} unit(s) of {$product_name} for {$installment_period} months at {$interest_rate}% interest. Monthly payment: ₱" . number_format($monthly_payment, 2) . ", Total: ₱" . number_format($total_amount, 2);
            } else {
                $success_message = "Sale recorded successfully! Sold: {$quantity_input} unit(s) of {$product_name}. Total: ₱" . number_format($total_amount, 2);
            }
            
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
                                            <th>Payment Method</th>
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
                                                    <td>
                                                        <?php 
                                                        $payment_method = isset($row['payment_method']) ? $row['payment_method'] : 'cash';
                                                        if ($payment_method === 'installment' && isset($row['installment_period'])) {
                                                            echo '<span class="badge badge-warning">';
                                                            echo '<i class="fas fa-credit-card mr-1"></i>';
                                                            echo $row['installment_period'] . ' months';
                                                            if (isset($row['interest_rate'])) {
                                                                echo ' (' . $row['interest_rate'] . '%)';
                                                            }
                                                            echo '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-success">';
                                                            echo '<i class="fas fa-money-bill-wave mr-1"></i>';
                                                            echo 'Cash';
                                                            echo '</span>';
                                                        }
                                                        ?>
                                                    </td>
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
                                                        
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
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
                                                <small class="text-muted">With Interest</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Installment Period Selection (Hidden by default) -->
                        <div class="col-md-12 mb-3" id="installmentOptions" style="display: none;">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt mr-1"></i>Installment Period
                            </label>
                            <div class="row">
                                <div class="col-4">
                                    <div class="card border-info installment-option" data-period="6" data-rate="3">
                                        <div class="card-body text-center">
                                            <input type="radio" name="installment_period" value="6" id="installment_6">
                                            <label for="installment_6" class="mb-0 d-block cursor-pointer">
                                                <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                                <h6 class="text-info">6 Months</h6>
                                                <div class="badge badge-info mb-1">3% Interest</div>
                                                <small class="text-muted d-block">Lowest Rate</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card border-warning installment-option" data-period="12" data-rate="5">
                                        <div class="card-body text-center">
                                            <input type="radio" name="installment_period" value="12" id="installment_12">
                                            <label for="installment_12" class="mb-0 d-block cursor-pointer">
                                                <i class="fas fa-calendar fa-2x text-warning mb-2"></i>
                                                <h6 class="text-warning">12 Months</h6>
                                                <div class="badge badge-warning mb-1">5% Interest</div>
                                                <small class="text-muted d-block">Most Popular</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card border-danger installment-option" data-period="24" data-rate="7">
                                        <div class="card-body text-center">
                                            <input type="radio" name="installment_period" value="24" id="installment_24">
                                            <label for="installment_24" class="mb-0 d-block cursor-pointer">
                                                <i class="fas fa-calendar fa-2x text-danger mb-2"></i>
                                                <h6 class="text-danger">24 Months</h6>
                                                <div class="badge badge-danger mb-1">7% Interest</div>
                                                <small class="text-muted d-block">Longest Term</small>
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
                                    <small class="text-muted" id="discountLabel">Discount:</small>
                                    <div id="discountDisplay" class="text-success">₱0.00 (0%)</div>
                                </div>
                            </div>
                            <!-- Installment Details (Hidden by default) -->
                            <div id="installmentDetails" style="display: none;">
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Original Price:</small>
                                        <div id="originalPriceDisplay" class="text-info">₱0.00</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Interest Rate:</small>
                                        <div id="interestRateDisplay">0%</div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">Interest Amount:</small>
                                        <div id="interestAmountDisplay" class="text-warning">₱0.00</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Monthly Payment:</small>
                                        <div id="monthlyPaymentDisplay" class="text-primary font-weight-bold">₱0.00</div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <small class="text-muted">Original Price + Interest:</small>
                                        <div id="originalPlusInterestDisplay" class="text-success font-weight-bold">₱0.00 + ₱0.00 = ₱0.00</div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <small class="text-muted">Total Amount:</small>
                                        <div id="totalWithInterestDisplay" class="text-danger font-weight-bold h5">₱0.00</div>
                                    </div>
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

    // Function to get interest rate based on installment period
    function getInterestRate(period) {
        const interestRates = {
            '6': 3,
            '12': 5,
            '24': 7
        };
        return interestRates[period] || 0;
    }

    function calculateTotal() {
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        const price = parseFloat(document.getElementById('selling_price').value) || 0;
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const productName = selectedOption.getAttribute('data-name') || '';
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const installmentPeriod = document.querySelector('input[name="installment_period"]:checked');
        
        const subtotal = quantity * price;
        const isDiscounted = paymentMethod && paymentMethod.value === 'cash';
        const isInstallment = paymentMethod && paymentMethod.value === 'installment';
        
        let discountPercentage = 0;
        let discountAmount = 0;
        let interestRate = 0;
        let interestAmount = 0;
        let monthlyPayment = 0;
        let totalWithInterest = 0;
        let total = subtotal;
        
        // Debug logging
        console.log('Payment Method:', paymentMethod ? paymentMethod.value : 'none');
        console.log('Installment Period:', installmentPeriod ? installmentPeriod.value : 'none');
        console.log('Is Installment:', isInstallment);
        
        if (isDiscounted) {
            discountPercentage = 10;
            discountAmount = subtotal * (discountPercentage / 100);
            total = subtotal - discountAmount;
        } else if (isInstallment && installmentPeriod) {
            // Fetch interest rate from the selected installment period
            interestRate = getInterestRate(installmentPeriod.value);
            interestAmount = subtotal * (interestRate / 100);
            totalWithInterest = subtotal + interestAmount;
            monthlyPayment = totalWithInterest / parseInt(installmentPeriod.value);
            total = totalWithInterest;
            console.log('Selected Period:', installmentPeriod.value);
            console.log('Interest Rate:', interestRate);
            console.log('Interest Amount:', interestAmount);
        }
        
        // Update displays
        document.getElementById('subtotalDisplay').textContent = `₱${subtotal.toFixed(2)}`;
        
        if (isInstallment && installmentPeriod) {
            // Show installment details
            document.getElementById('discountLabel').textContent = 'Interest:';
            document.getElementById('discountDisplay').textContent = `₱${interestAmount.toFixed(2)} (${interestRate}%)`;
            document.getElementById('discountDisplay').className = 'text-warning font-weight-bold';
            
            // Show installment details
            document.getElementById('installmentDetails').style.display = 'block';
            document.getElementById('originalPriceDisplay').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('interestRateDisplay').textContent = `${interestRate}%`;
            document.getElementById('interestAmountDisplay').textContent = `₱${interestAmount.toFixed(2)}`;
            document.getElementById('monthlyPaymentDisplay').textContent = `₱${monthlyPayment.toFixed(2)}`;
            document.getElementById('originalPlusInterestDisplay').textContent = `₱${subtotal.toFixed(2)} + ₱${interestAmount.toFixed(2)} = ₱${totalWithInterest.toFixed(2)}`;
            document.getElementById('totalWithInterestDisplay').textContent = `₱${totalWithInterest.toFixed(2)}`;
            
            document.getElementById('totalDisplay').textContent = `Total: ₱${totalWithInterest.toFixed(2)}`;
        } else {
            // Show discount details
            document.getElementById('discountLabel').textContent = 'Discount:';
        document.getElementById('discountDisplay').textContent = `₱${discountAmount.toFixed(2)} (${discountPercentage}%)`;
        document.getElementById('discountDisplay').className = isDiscounted ? 'text-success font-weight-bold' : 'text-muted';
            
            // Hide installment details
            document.getElementById('installmentDetails').style.display = 'none';
        document.getElementById('totalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
        }
        
        // Show sale info
        if (quantity > 0 && productName) {
            let paymentText = '';
            if (paymentMethod) {
                if (paymentMethod.value === 'cash') {
                    paymentText = ' (Cash - 10% discount)';
                } else if (paymentMethod.value === 'installment' && installmentPeriod) {
                    paymentText = ` (Installment - ${installmentPeriod.value} months, ${interestRate}% interest)`;
                } else {
                    paymentText = ' (Installment)';
                }
            }
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
            interest: interestAmount,
            total: total,
            totalWithInterest: totalWithInterest,
            monthlyPayment: monthlyPayment,
            paymentMethod: paymentMethod ? paymentMethod.value : '',
            installmentPeriod: installmentPeriod ? installmentPeriod.value : '',
            interestRate: interestRate,
            discountPercentage: discountPercentage
        };
    }

    // Function to initialize event listeners
    function initializeEventListeners() {
    // Add event listeners for calculation
    document.getElementById('quantity').addEventListener('input', function() {
        updateStockInfo();
        calculateTotal();
    });
    document.getElementById('selling_price').addEventListener('input', calculateTotal);
    
    // Payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
                console.log('Payment method changed to:', this.value);
                
            // Update visual selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary', 'bg-light');
            });
            
            const selectedCard = document.querySelector(`.payment-option[data-method="${this.value}"]`);
            selectedCard.classList.add('border-primary', 'bg-light');
                
                // Show/hide installment options
                const installmentOptions = document.getElementById('installmentOptions');
                if (this.value === 'installment') {
                    installmentOptions.style.display = 'block';
                    console.log('Installment options shown');
                } else {
                    installmentOptions.style.display = 'none';
                    // Clear installment selections
                    document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
                        radio.checked = false;
                    });
                    document.querySelectorAll('.installment-option').forEach(option => {
                        option.classList.remove('border-primary', 'bg-light');
                    });
                    console.log('Installment options hidden and cleared');
                }
            
            calculateTotal();
        });
    });

        // Installment period change - use event delegation to ensure it works
        document.addEventListener('change', function(e) {
            if (e.target && e.target.name === 'installment_period') {
                // Update visual selection
                document.querySelectorAll('.installment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                const selectedCard = document.querySelector(`.installment-option[data-period="${e.target.value}"]`);
                if (selectedCard) {
                    selectedCard.classList.add('border-primary', 'bg-light');
                }
                
                // Get and display the interest rate for the selected period
                const selectedPeriod = e.target.value;
                const interestRate = getInterestRate(selectedPeriod);
                
                console.log('Installment period changed to:', selectedPeriod);
                console.log('Interest rate for', selectedPeriod, 'months:', interestRate + '%');
                
                // Update the interest rate display immediately
                const interestRateDisplay = document.getElementById('interestRateDisplay');
                if (interestRateDisplay) {
                    interestRateDisplay.textContent = interestRate + '%';
                }
                
                calculateTotal();
            }
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

        // Check if installment is selected but no period is chosen
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        if (paymentMethod && paymentMethod.value === 'installment') {
            const installmentPeriod = document.querySelector('input[name="installment_period"]:checked');
            if (!installmentPeriod) {
                Swal.fire({
                    icon: 'error',
                    title: 'Installment Period Required!',
                    text: 'Please select an installment period (6, 12, or 24 months) for installment payment.',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }
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

        // Debug: Log form data before submission
        console.log('Form Data Before Submission:', formData);
        console.log('Payment Method:', paymentMethod ? paymentMethod.value : 'none');
        console.log('Installment Period:', document.querySelector('input[name="installment_period"]:checked') ? document.querySelector('input[name="installment_period"]:checked').value : 'none');

        // Create confirmation message with sale details
        let paymentMethodText = '';
        let additionalDetails = '';
        
        if (formData.paymentMethod === 'cash') {
            paymentMethodText = 'Cash Payment (10% Discount)';
            additionalDetails = `<br><strong>Discount:</strong> ₱${formData.discount.toFixed(2)}`;
        } else if (formData.paymentMethod === 'installment') {
            paymentMethodText = `Installment Payment (${formData.installmentPeriod} months)`;
            additionalDetails = `
                <br><strong>Original Price:</strong> ₱${formData.subtotal.toFixed(2)}
                <br><strong>Interest Rate:</strong> ${formData.interestRate}%
                <br><strong>Interest Amount:</strong> ₱${formData.interest.toFixed(2)}
                <br><strong>Monthly Payment:</strong> ₱${formData.monthlyPayment.toFixed(2)}
                <br><strong>Total with Interest:</strong> ₱${formData.totalWithInterest.toFixed(2)}`;
        }
        
        Swal.fire({
            title: 'Confirm Sale Transaction',
            html: `
                <div class="text-left">
                    <strong>Product:</strong> ${formData.productName}<br>
                    <strong>Quantity:</strong> ${formData.quantity} unit(s)<br>
                    <strong>Unit Price:</strong> ₱${formData.unitPrice.toFixed(2)}<br>
                    <strong>Payment Method:</strong> ${paymentMethodText}<br>
                    <strong>Subtotal:</strong> ₱${formData.subtotal.toFixed(2)}
                    ${additionalDetails}
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
            
            // Debug: Log what's being sent
            console.log('FormData contents:');
            for (let [key, value] of formDataToSend.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Debug: Check radio button values
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const installmentPeriod = document.querySelector('input[name="installment_period"]:checked');
            console.log('Payment method radio:', paymentMethod ? paymentMethod.value : 'none selected');
            console.log('Installment period radio:', installmentPeriod ? installmentPeriod.value : 'none selected');
            
            // Ensure payment method is included in FormData
            if (paymentMethod) {
                formDataToSend.set('payment_method', paymentMethod.value);
                console.log('Added payment_method to FormData:', paymentMethod.value);
            }
            
            // Ensure installment period is included in FormData if selected
            if (installmentPeriod) {
                formDataToSend.set('installment_period', installmentPeriod.value);
                console.log('Added installment_period to FormData:', installmentPeriod.value);
            }
            
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
        // Show loading state
        Swal.fire({
            title: 'Loading Sale Details...',
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
                    let paymentDetails = '';
                    
                    if (sale.payment_method === 'installment') {
                        paymentDetails = `
                            <div class="row">
                                <div class="col-6">
                                    <strong>Payment Method:</strong><br>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-credit-card mr-1"></i>
                                        Installment (${sale.installment_period} months)
                                    </span>
                                </div>
                                <div class="col-6">
                                    <strong>Interest Rate:</strong><br>
                                    <span class="text-warning">${sale.interest_rate}%</span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Original Price:</strong><br>
                                    <span class="text-info">₱${parseFloat(sale.original_price).toFixed(2)}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Interest Amount:</strong><br>
                                    <span class="text-warning">₱${parseFloat(sale.interest_amount).toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Monthly Payment:</strong><br>
                                    <span class="text-primary font-weight-bold">₱${parseFloat(sale.monthly_payment).toFixed(2)}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Total with Interest:</strong><br>
                                    <span class="text-danger font-weight-bold">₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                                </div>
                            </div>
                        `;
                    } else {
                        paymentDetails = `
                            <div class="row">
                                <div class="col-12">
                                    <strong>Payment Method:</strong><br>
                                    <span class="badge badge-success">
                                        <i class="fas fa-money-bill-wave mr-1"></i>
                                        Cash Payment
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Original Price:</strong><br>
                                    <span class="text-info">₱${parseFloat(sale.original_price).toFixed(2)}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Discount (10%):</strong><br>
                                    <span class="text-success">₱${(parseFloat(sale.original_price) - parseFloat(sale.total_amount)).toFixed(2)}</span>
                                </div>
                            </div>
                        `;
                    }

                    Swal.fire({
                        title: `Sale Details #${sale.sale_id}`,
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
                                        <strong>Unit Price:</strong><br>
                                        ₱${parseFloat(sale.selling_price).toFixed(2)}
                                    </div>
                                    <div class="col-6">
                                        <strong>Cashier:</strong><br>
                                        <i class="fas fa-user-circle text-primary mr-1"></i>
                                        ${sale.cashier}
                                    </div>
                                </div>
                                <hr>
                                ${paymentDetails}
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Date of Sale:</strong><br>
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
                        text: data.message || 'Failed to fetch sale details.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while fetching sale details.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
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
                // Open print window with receipt details
                window.open(`print_receipt.php?sale_id=${id}`, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Receipt Opened!',
                    text: `Receipt for sale #${id} has been opened in a new window.`,
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
                document.getElementById('discountLabel').textContent = 'Discount:';
                document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
                document.getElementById('saleInfo').style.display = 'none';
                document.getElementById('stockInfo').innerHTML = 'Select product first';
                
                // Hide installment details
                document.getElementById('installmentDetails').style.display = 'none';
                document.getElementById('installmentOptions').style.display = 'none';
                
                // Reset installment displays
                document.getElementById('originalPriceDisplay').textContent = '₱0.00';
                document.getElementById('interestRateDisplay').textContent = '0%';
                document.getElementById('interestAmountDisplay').textContent = '₱0.00';
                document.getElementById('monthlyPaymentDisplay').textContent = '₱0.00';
                document.getElementById('originalPlusInterestDisplay').textContent = '₱0.00 + ₱0.00 = ₱0.00';
                document.getElementById('totalWithInterestDisplay').textContent = '₱0.00';
                
                // Remove payment method selections
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                // Remove installment selections
                document.querySelectorAll('.installment-option').forEach(option => {
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
        document.getElementById('discountLabel').textContent = 'Discount:';
        document.getElementById('totalDisplay').textContent = 'Total: ₱0.00';
        document.getElementById('saleInfo').style.display = 'none';
        document.getElementById('stockInfo').innerHTML = 'Select product first';
        
        // Hide installment details
        document.getElementById('installmentDetails').style.display = 'none';
        document.getElementById('installmentOptions').style.display = 'none';
        
        // Reset installment displays
        document.getElementById('originalPriceDisplay').textContent = '₱0.00';
        document.getElementById('interestRateDisplay').textContent = '0%';
        document.getElementById('interestAmountDisplay').textContent = '₱0.00';
        document.getElementById('monthlyPaymentDisplay').textContent = '₱0.00';
        document.getElementById('originalPlusInterestDisplay').textContent = '₱0.00 + ₱0.00 = ₱0.00';
        document.getElementById('totalWithInterestDisplay').textContent = '₱0.00';
        
        // Remove payment method selections
        document.querySelectorAll('.payment-option').forEach(option => {
            option.classList.remove('border-primary', 'bg-light');
        });
        
        // Remove installment selections
        document.querySelectorAll('.installment-option').forEach(option => {
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