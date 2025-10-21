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

// Get schedule ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: installer_schedule.php");
    exit();
}

// Fetch existing schedule data
$query = "SELECT * FROM installer_schedules WHERE id = $id";
$result = mysqli_query($conn, $query);
$schedule = mysqli_fetch_assoc($result);

if (!$schedule) {
    header("Location: installer_schedule.php");
    exit();
}

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
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $image_path = $schedule['image_path']; // Keep existing image if no new one uploaded
    
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
                // Delete old image if exists
                if ($schedule['image_path'] && file_exists('../' . $schedule['image_path'])) {
                    unlink('../' . $schedule['image_path']);
                }
                $image_path = 'uploads/installer_images/' . $new_filename;
            }
        }
    }

    $query = "UPDATE installer_schedules SET 
              installer_name = '$installer_name',
              customer_name = '$customer_name',
              contact_number = '$contact_number',
              address = '$address',
              schedule_date = '$schedule_date',
              schedule_time = '$schedule_time',
              service_type = '$service_type',
              products_to_install = '$products_to_install',
              image_path = '$image_path',
              notes = '$notes',
              status = '$status'
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $message = "Installer schedule updated successfully!";
        $message_type = "success";
        // Refresh the schedule data
        $query = "SELECT * FROM installer_schedules WHERE id = $id";
        $result = mysqli_query($conn, $query);
        $schedule = mysqli_fetch_assoc($result);
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
        <h1 class="h3 mb-0 text-gray-800">Edit Installer Schedule</h1>
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
                            <input type="text" class="form-control" id="installer_name" name="installer_name" value="<?php echo htmlspecialchars($schedule['installer_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="customer_name">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($schedule['customer_name']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_number">Contact Number *</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($schedule['contact_number']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="service_type">Service Type *</label>
                            <select class="form-control" id="service_type" name="service_type" required>
                                <option value="">Select Service Type</option>
                                <option value="Installation" <?php echo $schedule['service_type'] == 'Installation' ? 'selected' : ''; ?>>Installation</option>
                                <option value="Repair" <?php echo $schedule['service_type'] == 'Repair' ? 'selected' : ''; ?>>Repair</option>
                                <option value="Maintenance" <?php echo $schedule['service_type'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Inspection" <?php echo $schedule['service_type'] == 'Inspection' ? 'selected' : ''; ?>>Inspection</option>
                                <option value="Other" <?php echo $schedule['service_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($schedule['address']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="products_to_install">Products to Install *</label>
                    <textarea class="form-control" id="products_to_install" name="products_to_install" rows="3" required placeholder="List the products that will be installed (e.g., Water Pump Model ABC-123, Pipes 2-inch diameter, etc.)"><?php echo htmlspecialchars($schedule['products_to_install']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image</label>
                    <?php if ($schedule['image_path']): ?>
                        <div class="mb-2">
                            <img src="../<?php echo $schedule['image_path']; ?>" alt="Current Image" style="max-width: 200px; max-height: 150px;" class="img-thumbnail">
                            <br><small class="text-muted">Current image</small>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                    <small class="form-text text-muted">Upload a new image to replace the current one (optional)</small>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="schedule_date">Schedule Date *</label>
                            <input type="date" class="form-control" id="schedule_date" name="schedule_date" value="<?php echo $schedule['schedule_date']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time *</label>
                            <input type="time" class="form-control" id="schedule_time" name="schedule_time" value="<?php echo date('H:i', strtotime($schedule['schedule_time'])); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Scheduled" <?php echo $schedule['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="In Progress" <?php echo $schedule['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $schedule['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $schedule['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or special instructions..."><?php echo htmlspecialchars($schedule['notes']); ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                    <a href="installer_schedule.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>
