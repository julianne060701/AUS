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

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installer_name = mysqli_real_escape_string($conn, $_POST['installer_name']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $products_to_install = mysqli_real_escape_string($conn, $_POST['products_to_install']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'Scheduled';
    
    $image_path = '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/installer_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'installer_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/installer_images/' . $new_filename;
            }
        }
    }

    $query = "INSERT INTO installer_schedules (installer_name, customer_name, contact_number, address, schedule_date, schedule_time, service_type, products_to_install, image_path, notes, status) 
              VALUES ('$installer_name', '$customer_name', '$contact_number', '$address', '$schedule_date', '$schedule_time', '$service_type', '$products_to_install', '$image_path', '$notes', '$status')";
    
    if (mysqli_query($conn, $query)) {
        $message = "Installer schedule added successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "danger";
    }
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Installer Schedule</h1>
        <a href="installer_schedule.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Schedules
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Schedule Information</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="installer_name">Installer Name *</label>
                            <input type="text" class="form-control" id="installer_name" name="installer_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="customer_name">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_number">Contact Number *</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="service_type">Service Type *</label>
                            <select class="form-control" id="service_type" name="service_type" required>
                                <option value="">Select Service Type</option>
                                <option value="Installation">Installation</option>
                                <option value="Repair">Repair</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Inspection">Inspection</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="products_to_install">Products to Install *</label>
                    <select class="form-control" id="products_to_install" name="products_to_install" required>
                        <option value="">Select Product</option>
                        <?php
                        // Fetch products from database
                        $products_query = "SELECT p.id, p.product_name, p.capacity, c.category_name, b.brand_name 
                                          FROM products p 
                                          LEFT JOIN category c ON p.category_id = c.category_id 
                                          LEFT JOIN brands b ON p.brand_id = b.brand_id 
                                          WHERE p.quantity > 0 
                                          ORDER BY p.product_name ASC";
                        $products_result = mysqli_query($conn, $products_query);
                        
                        if ($products_result && mysqli_num_rows($products_result) > 0) {
                            while ($product = mysqli_fetch_assoc($products_result)) {
                                $product_display = $product['product_name'];
                                if (!empty($product['capacity'])) {
                                    $product_display .= " ({$product['capacity']})";
                                }
                                if (!empty($product['brand_name'])) {
                                    $product_display .= " - {$product['brand_name']}";
                                }
                                if (!empty($product['category_name'])) {
                                    $product_display .= " [{$product['category_name']}]";
                                }
                                echo "<option value='" . htmlspecialchars($product_display) . "'>" . htmlspecialchars($product_display) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No products available</option>";
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted">Select the product to be installed</small>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image</label>
                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                    <small class="form-text text-muted">Upload an image related to the installation (optional)</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_date">Schedule Date *</label>
                            <input type="date" class="form-control" id="schedule_date" name="schedule_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time *</label>
                            <input type="time" class="form-control" id="schedule_time" name="schedule_time" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or special instructions..."></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                    <a href="installer_schedule.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>
