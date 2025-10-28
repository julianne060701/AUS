<?php
session_start();
include 'config/conn.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get logged-in user data
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT full_name, username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Test Page</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Logout Test Page</h4>
                    </div>
                    <div class="card-body">
                        <p>Welcome, <?= htmlspecialchars($user['full_name']); ?>!</p>
                        <p>This is a test page to debug logout functionality.</p>
                        
                        <!-- Test Logout Button -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            Test Logout Modal
                        </button>
                        
                        <!-- Direct Logout Link -->
                        <a href="logout.php" class="btn btn-danger ms-2">
                            Direct Logout (No Modal)
                        </a>
                        
                        <!-- Debug Info -->
                        <div class="mt-3">
                            <h6>Debug Information:</h6>
                            <ul>
                                <li>Session ID: <?= session_id(); ?></li>
                                <li>User ID: <?= $_SESSION['user_id']; ?></li>
                                <li>Username: <?= $_SESSION['username']; ?></li>
                                <li>Role: <?= $_SESSION['role']; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        console.log('Test page loaded');
        console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Bootstrap 5' : 'Bootstrap 4');
        
        // Test modal functionality
        $('#logoutModal').on('show.bs.modal', function (e) {
            console.log('Modal is opening');
        });
        
        $('#logoutModal').on('shown.bs.modal', function (e) {
            console.log('Modal is now visible');
        });
        
        // Test logout button
        $('a[href="logout.php"]').click(function(e) {
            console.log('Logout button clicked');
        });
    });
    </script>
</body>
</html>
