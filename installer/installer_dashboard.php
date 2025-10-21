<?php
session_start();

// Redirect if not logged in as installer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'installer') {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get the logged-in installer's name from session
$installer_name = $_SESSION['username'] ?? 'Unknown';

// Fetch schedules for the logged-in installer
$query = "SELECT * FROM installer_schedules 
          WHERE installer_name = ? 
          ORDER BY schedule_date ASC, schedule_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $installer_name);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);

// Group schedules by date
$grouped_schedules = [];
foreach ($schedules as $schedule) {
    $date = $schedule['schedule_date'];
    if (!isset($grouped_schedules[$date])) {
        $grouped_schedules[$date] = [];
    }
    $grouped_schedules[$date][] = $schedule;
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

    <title>Aircon Dashboard</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/employee.css">
    <style>
        .schedule-card {
            border-left: 4px solid #4e73df;
            transition: all 0.3s ease;
            height: 100%;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
        }
        .schedule-card.completed {
            border-left-color: #1cc88a;
            opacity: 0.8;
        }
        .schedule-card.scheduled {
            border-left-color: #4e73df;
        }
        .schedule-card.in-progress {
            border-left-color: #f6c23e;
        }
        .schedule-card.cancelled {
            border-left-color: #e74a3b;
            opacity: 0.7;
        }
        .date-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .time-badge {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }
        .info-item {
            margin-bottom: 0.5rem;
        }
        .info-item i {
            width: 20px;
            color: #4e73df;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .no-schedule {
            text-align: center;
            padding: 3rem;
            color: #858796;
        }
        .no-schedule i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .product-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            margin: 0.25rem;
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
                    <h1 class="h3 mb-0 text-gray-800">My Installation Schedule</h1>
                    <div class="text-muted">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo date('F j, Y'); ?>
                    </div>
                </div>

                <!-- Schedule Cards -->
                <?php if (empty($schedules)): ?>
                    <div class="card shadow">
                        <div class="card-body no-schedule">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No Scheduled Installations</h4>
                            <p>You don't have any installations scheduled at the moment.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped_schedules as $date => $date_schedules): ?>
                        <div class="date-header">
                            <h4 class="mb-0">
                                <i class="fas fa-calendar-day mr-2"></i>
                                <?php echo date('l, F j, Y', strtotime($date)); ?>
                                <span class="badge badge-light ml-2"><?php echo count($date_schedules); ?> Schedule<?php echo count($date_schedules) > 1 ? 's' : ''; ?></span>
                            </h4>
                        </div>

                        <div class="row mb-4">
                            <?php foreach ($date_schedules as $schedule): ?>
                                <?php 
                                    $status_class = strtolower(str_replace(' ', '-', $schedule['status']));
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
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card schedule-card <?php echo $status_class; ?> shadow h-100">
                                        <div class="card-body">
                                            <!-- Time and Status -->
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="badge badge-primary time-badge">
                                                    <i class="far fa-clock mr-1"></i>
                                                    <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?>
                                                </span>
                                                <span class="badge status-badge <?php echo $badge_class; ?>">
                                                    <?php echo $schedule['status']; ?>
                                                </span>
                                            </div>

                                            <!-- Service Type -->
                                            <div class="mb-2">
                                                <span class="badge badge-info">
                                                    <i class="fas fa-tools mr-1"></i>
                                                    <?php echo htmlspecialchars($schedule['service_type']); ?>
                                                </span>
                                            </div>

                                            <!-- Customer Info -->
                                            <h5 class="card-title text-primary mb-3">
                                                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($schedule['customer_name']); ?>
                                            </h5>

                                            <div class="info-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <small class="text-muted">Address:</small><br>
                                                <span class="ml-4"><?php echo htmlspecialchars($schedule['address']); ?></span>
                                            </div>

                                            <div class="info-item">
                                                <i class="fas fa-phone"></i>
                                                <small class="text-muted">Contact:</small><br>
                                                <span class="ml-4"><?php echo htmlspecialchars($schedule['contact_number']); ?></span>
                                            </div>

                                            <?php if (!empty($schedule['products_to_install'])): ?>
                                            <hr>
                                            <div class="info-item">
                                                <i class="fas fa-box"></i>
                                                <small class="text-muted">Products to Install:</small><br>
                                                <div class="ml-4 mt-2">
                                                    <?php 
                                                    $products = explode(',', $schedule['products_to_install']);
                                                    foreach($products as $product): 
                                                    ?>
                                                        <span class="product-badge"><?php echo htmlspecialchars(trim($product)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <?php if (!empty($schedule['notes'])): ?>
                                            <hr>
                                            <div class="info-item">
                                                <i class="fas fa-sticky-note"></i>
                                                <small class="text-muted">Notes:</small><br>
                                                <span class="ml-4"><?php echo nl2br(htmlspecialchars($schedule['notes'])); ?></span>
                                            </div>
                                            <?php endif; ?>

                                            <?php if (!empty($schedule['image_path'])): ?>
                                            <hr>
                                            <div class="info-item">
                                                <i class="fas fa-image"></i>
                                                <small class="text-muted">Attachment:</small><br>
                                                <div class="ml-4 mt-2">
                                                    <img src="<?php echo htmlspecialchars($schedule['image_path']); ?>" 
                                                         alt="Schedule Image" 
                                                         class="img-fluid rounded" 
                                                         style="max-height: 150px; cursor: pointer;"
                                                         onclick="viewImage('<?php echo htmlspecialchars($schedule['image_path']); ?>')">
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <!-- Action Buttons -->
                                            <div class="mt-3 pt-3 border-top">
                                                <button class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $schedule['id']; ?>)">
                                                    <i class="fas fa-info-circle mr-1"></i> View Details
                                                </button>
                                                <?php if ($schedule['status'] == 'Scheduled'): ?>
                                                <button class="btn btn-sm btn-warning ml-2" onclick="updateStatus(<?php echo $schedule['id']; ?>, 'In Progress')">
                                                    <i class="fas fa-play mr-1"></i> Start
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($schedule['status'] == 'In Progress'): ?>
                                                <button class="btn btn-sm btn-success ml-2" onclick="updateStatus(<?php echo $schedule['id']; ?>, 'Completed')">
                                                    <i class="fas fa-check mr-1"></i> Complete
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
            <!-- End of Main Content -->

        </div>

        <?php include('includes/footer.php'); ?>
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function viewDetails(scheduleId) {
    // You can redirect to a details page or open a modal
    window.location.href = 'schedule_details.php?id=' + scheduleId;
}

function updateStatus(scheduleId, newStatus) {
    if (confirm('Update status to "' + newStatus + '"?')) {
        $.ajax({
            url: 'update_schedule_status.php',
            method: 'POST',
            data: {
                schedule_id: scheduleId,
                status: newStatus
            },
            success: function(response) {
                alert('Status updated successfully!');
                location.reload();
            },
            error: function() {
                alert('Error updating status. Please try again.');
            }
        });
    }
}

function viewImage(imagePath) {
    $('#modalImage').attr('src', imagePath);
    $('#imageModal').modal('show');
}
</script>

</body>
</html>