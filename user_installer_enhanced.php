<?php
include 'config/conn.php';

$message = '';
$messageType = '';

// Check if extended fields exist in the database
$checkExtendedFields = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
$hasExtendedFields = $checkExtendedFields && $checkExtendedFields->num_rows > 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    // Validation
    if (empty($username) || empty($full_name) || empty($password) || empty($role)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif ($hasExtendedFields && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Username already exists. Please choose a different username.';
            $messageType = 'error';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new user based on available fields
            if ($hasExtendedFields) {
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role, email, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssss", $username, $full_name, $hashedPassword, $role, $email, $phone);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $username, $full_name, $hashedPassword, $role);
            }
            
            if ($stmt->execute()) {
                $message = 'User installed successfully!';
                $messageType = 'success';
                // Clear form data
                $username = $full_name = $email = $phone = '';
            } else {
                $message = 'Error installing user: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enhanced User Installer - AUS Inventory System</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .installer-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .installer-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
        }
        .installer-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .installer-body {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-installer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-installer:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .required {
            color: #dc3545;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .migration-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="installer-container">
        <div class="installer-card">
            <div class="installer-header">
                <h1><i class="fas fa-user-plus"></i> Enhanced User Installer</h1>
                <p>Add a new user to the AUS Inventory System</p>
            </div>
            
            <div class="installer-body">
                <?php if (!$hasExtendedFields): ?>
                    <div class="migration-notice">
                        <i class="fas fa-info-circle"></i>
                        <strong>Database Migration Available:</strong> Run the database_migration.sql script to enable email and phone fields for enhanced user management.
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" 
                               required>
                    </div>

                    <?php if ($hasExtendedFields): ?>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            <option value="employee" <?php echo (isset($role) && $role === 'employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="installer" <?php echo (isset($role) && $role === 'installer') ? 'selected' : ''; ?>>Installer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" 
                               minlength="6" required>
                        <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-installer">
                        <i class="fas fa-user-plus"></i> Install User
                    </button>
                </form>

                <div class="back-link">
                    <a href="user/user.php">
                        <i class="fas fa-arrow-left"></i> Back to User Management
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Real-time password matching
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                if (this.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        });
    </script>
</body>
</html>
