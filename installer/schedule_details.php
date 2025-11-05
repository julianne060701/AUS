<?php
session_start();

// Check if user is logged in as installer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'installer') {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get the installer's full name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name FROM users WHERE id = ? AND role = 'installer'";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    header("Location: ../login.php");
    exit();
}

$installer_name = $user_data['full_name'];

// Get schedule ID from URL
$schedule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($schedule_id <= 0) {
    header("Location: installer_dashboard.php");
    exit();
}

// Fetch schedule details with product information
$query = "SELECT s.*, p.product_name, p.capacity, b.brand_name, c.category_name
          FROM installer_schedules s
          LEFT JOIN products p ON s.products_to_install = p.id
          LEFT JOIN brands b ON p.brand_id = b.brand_id
          LEFT JOIN category c ON p.category_id = c.category_id
          WHERE s.id = ? AND s.installer_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $schedule_id, $installer_name);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    header("Location: installer_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Schedule Details - Aircon Dashboard</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/employee.css">
    
    <style>
        .detail-card {
            border-left: 4px solid #4e73df;
            transition: all 0.3s ease;
        }
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .info-section {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-item i {
            width: 25px;
            color: #4e73df;
            margin-right: 10px;
        }
        .product-detail-item {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .product-detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #4e73df;
        }
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
            min-width: 200px;
        }
        .quantity-info {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.3);
            white-space: nowrap;
        }
        .product-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            margin: 0.25rem;
            border: 1px solid #dee2e6;
        }
        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .product-name {
                min-width: auto;
                width: 100%;
            }
            .quantity-info {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('includes/topbar.php'); ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Schedule Details</h1>
                    <a href="installer_dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card detail-card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    Installation Schedule #<?php echo $schedule['id']; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Status and Time -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="info-section">
                                            <h5 class="text-primary mb-3">
                                                <i class="fas fa-clock mr-2"></i>Schedule Information
                                            </h5>
                                            <div class="info-item">
                                                <i class="fas fa-calendar-day"></i>
                                                <strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($schedule['schedule_date'])); ?>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-clock"></i>
                                                <strong>Time:</strong> <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-tools"></i>
                                                <strong>Service Type:</strong> <?php echo htmlspecialchars($schedule['service_type']); ?>
                                            </div>
                                            <div class="info-item">
                                                <?php 
                                                $badge_class = '';
                                                switch($schedule['status']) {
                                                    case 'Completed':
                                                        $badge_class = 'badge-success';
                                                        break;
                                                    case 'In Progress':
                                                        $badge_class = 'badge-warning';
                                                        break;
                                                    case 'Cancelled':
                                                        $badge_class = 'badge-danger';
                                                        break;
                                                    default:
                                                        $badge_class = 'badge-primary';
                                                }
                                                ?>
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Status:</strong> 
                                                <span class="badge status-badge <?php echo $badge_class; ?>">
                                                    <?php echo $schedule['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-section">
                                            <h5 class="text-primary mb-3">
                                                <i class="fas fa-user mr-2"></i>Customer Information
                                            </h5>
                                            <div class="info-item">
                                                <i class="fas fa-user"></i>
                                                <strong>Name:</strong> <?php echo htmlspecialchars($schedule['customer_name']); ?>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-phone"></i>
                                                <strong>Contact:</strong> <?php echo htmlspecialchars($schedule['contact_number']); ?>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <strong>Address:</strong><br>
                                                <span class="ml-4"><?php echo nl2br(htmlspecialchars($schedule['address'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Products to Install -->
                                <?php if (!empty($schedule['product_name'])): ?>
                                <div class="info-section">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-box mr-2"></i>Products to Install
                                    </h5>
                                    <div class="ml-4">
                                        <?php 
                                        $quantity = isset($schedule['quantity_to_install']) ? $schedule['quantity_to_install'] : 1;
                                        $product_display = $schedule['product_name'];
                                        
                                        // Add capacity if available
                                        if (!empty($schedule['capacity'])) {
                                            $product_display .= " ({$schedule['capacity']})";
                                        }
                                        
                                        // Add brand if available
                                        if (!empty($schedule['brand_name'])) {
                                            $product_display .= " - {$schedule['brand_name']}";
                                        }
                                        
                                        // Add category if available
                                        if (!empty($schedule['category_name'])) {
                                            $product_display .= " [{$schedule['category_name']}]";
                                        }
                                        ?>
                                        <div class="product-detail-item">
                                            <div class="product-info">
                                                <span class="product-name"><?php echo htmlspecialchars($product_display); ?></span>
                                                <span class="quantity-info">Quantity: <strong><?php echo $quantity; ?></strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Notes -->
                                <?php if (!empty($schedule['notes'])): ?>
                                <div class="info-section">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-sticky-note mr-2"></i>Additional Notes
                                    </h5>
                                    <div class="ml-4">
                                        <?php echo nl2br(htmlspecialchars($schedule['notes'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Image Attachment -->
                                <?php if (!empty($schedule['image_path'])): ?>
                                <div class="info-section">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-image mr-2"></i>Attachment
                                    </h5>
                                    <div class="ml-4">
                                        <img src="<?php echo htmlspecialchars($schedule['image_path']); ?>" 
                                             alt="Schedule Image" 
                                             class="img-fluid rounded" 
                                             style="max-height: 300px; cursor: pointer;"
                                             onclick="viewImage('<?php echo htmlspecialchars($schedule['image_path']); ?>')">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Completion Image -->
                                <?php if (!empty($schedule['completion_image'])): ?>
                                <div class="info-section">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-camera mr-2"></i>Completion Photo
                                    </h5>
                                    <div class="ml-4">
                                        <img src="../<?php echo htmlspecialchars($schedule['completion_image']); ?>" 
                                             alt="Completion Image" 
                                             class="img-fluid rounded" 
                                             style="max-height: 300px; cursor: pointer;"
                                             onclick="viewImage('../<?php echo htmlspecialchars($schedule['completion_image']); ?>')">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Employee List -->
                                <?php if (!empty($schedule['employee_list'])): ?>
                                <div class="info-section">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-users mr-2"></i>Installation Team Members
                                    </h5>
                                    <div class="ml-4">
                                        <?php 
                                        $employees = preg_split('/[,\n\r]+/', $schedule['employee_list']);
                                        foreach($employees as $employee): 
                                            $employee = trim($employee);
                                            if (!empty($employee)):
                                        ?>
                                            <span class="badge badge-secondary mr-2 mb-2" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($employee); ?>
                                            </span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-cogs mr-2"></i>Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($schedule['status'] == 'Scheduled'): ?>
                                    <button class="btn btn-warning btn-lg" onclick="updateStatus(<?php echo $schedule['id']; ?>, 'In Progress')">
                                        <i class="fas fa-play mr-2"></i>Start Installation
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($schedule['status'] == 'In Progress'): ?>
                                    <button class="btn btn-success btn-lg" onclick="showCompletionModal(<?php echo $schedule['id']; ?>)">
                                        <i class="fas fa-check mr-2"></i>Complete Installation
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($schedule['status'], ['Scheduled', 'In Progress'])): ?>
                                    <button class="btn btn-danger btn-lg" onclick="showCancelModal(<?php echo $schedule['id']; ?>)">
                                        <i class="fas fa-times mr-2"></i>Cancel Installation
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($schedule['status'] == 'Completed'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        This installation has been completed.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <a href="installer_dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->
        </div>

        <?php include('includes/footer.php'); ?>
    </div>
</div>

<!-- Completion Modal -->
<div class="modal fade" id="completionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle mr-2"></i>Complete Installation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="completionForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Please upload a photo of the completed installation before marking it as complete.
                    </div>
                    
                    <div class="form-group">
                        <label for="completion_image" class="font-weight-bold">
                            <i class="fas fa-camera mr-2"></i>Completion Photo *
                        </label>
                        <input type="file" class="form-control-file" id="completion_image" name="completion_image" 
                               accept="image/*" required>
                        <small class="form-text text-muted">
                            Upload a photo showing the completed installation. Supported formats: JPEG, PNG, GIF (Max 5MB)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_list" class="font-weight-bold">
                            <i class="fas fa-users mr-2"></i>Installation Team Members *
                        </label>
                        <textarea class="form-control" id="employee_list" name="employee_list" rows="3" 
                                  placeholder="Enter the names of employees who participated in the installation (one per line or separated by commas)" required></textarea>
                        <small class="form-text text-muted">
                            List all team members who worked on this installation. You can separate names with commas or put each name on a new line.
                        </small>
                    </div>
                    
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <h6>Preview:</h6>
                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="completeBtn">
                        <i class="fas fa-check mr-2"></i>Complete Installation
                    </button>
                </div>
                <input type="hidden" id="completion_schedule_id" name="schedule_id">
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle mr-2"></i>Cancel Installation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="cancelForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Please provide a reason for cancelling this installation. This information will be recorded.
                    </div>
                    
                    <div class="form-group">
                        <label for="cancel_note" class="font-weight-bold">
                            <i class="fas fa-comment-alt mr-2"></i>Reason for Cancellation *
                        </label>
                        <textarea class="form-control" id="cancel_note" name="cancel_note" rows="4" 
                                  placeholder="Please explain why you need to cancel this installation..." required></textarea>
                        <small class="form-text text-muted">
                            Provide a detailed reason for cancelling this installation schedule.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" id="cancelBtn">
                        <i class="fas fa-times mr-2"></i>Cancel Installation
                    </button>
                </div>
                <input type="hidden" id="cancel_schedule_id" name="schedule_id">
            </form>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Image</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Schedule Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function updateStatus(scheduleId, newStatus) {
    if (confirm('Update status to "' + newStatus + '"?')) {
        $.ajax({
            url: 'update_schedule_status.php',
            method: 'POST',
            data: {
                schedule_id: scheduleId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error updating status. Please try again.');
            }
        });
    }
}

function showCompletionModal(scheduleId) {
    $('#completion_schedule_id').val(scheduleId);
    $('#completionForm')[0].reset();
    $('#imagePreview').hide();
    $('#employee_list').val('');
    $('#completionModal').modal('show');
}

function showCancelModal(scheduleId) {
    $('#cancel_schedule_id').val(scheduleId);
    $('#cancelForm')[0].reset();
    $('#cancelModal').modal('show');
}

// Image preview functionality
$('#completion_image').change(function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImg').attr('src', e.target.result);
            $('#imagePreview').show();
        };
        reader.readAsDataURL(file);
    } else {
        $('#imagePreview').hide();
    }
});

// Handle completion form submission
$('#completionForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const completeBtn = $('#completeBtn');
    
    // Disable button and show loading
    completeBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
    
    $.ajax({
        url: 'upload_completion_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Installation completed successfully with image uploaded!');
                $('#completionModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error uploading image. Please try again.');
        },
        complete: function() {
            // Re-enable button
            completeBtn.prop('disabled', false).html('<i class="fas fa-check mr-2"></i>Complete Installation');
        }
    });
});

function viewImage(imagePath) {
    $('#modalImage').attr('src', imagePath);
    $('#imageModal').modal('show');
}

// Handle cancel form submission
$('#cancelForm').submit(function(e) {
    e.preventDefault();
    
    const scheduleId = $('#cancel_schedule_id').val();
    const cancelNote = $('#cancel_note').val().trim();
    
    if (!cancelNote) {
        alert('Please provide a reason for cancellation.');
        return;
    }
    
    const cancelBtn = $('#cancelBtn');
    
    // Disable button and show loading
    cancelBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
    
    $.ajax({
        url: 'update_schedule_status.php',
        method: 'POST',
        data: {
            schedule_id: scheduleId,
            status: 'Cancelled',
            cancel_note: cancelNote
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Installation cancelled successfully!');
                $('#cancelModal').modal('hide');
                // Redirect back to dashboard on cancelled tab
                window.location.href = 'installer_dashboard.php?tab=cancelled';
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error cancelling installation. Please try again.');
        },
        complete: function() {
            // Re-enable button
            cancelBtn.prop('disabled', false).html('<i class="fas fa-times mr-2"></i>Cancel Installation');
        }
    });
});
</script>

</body>
</html>
