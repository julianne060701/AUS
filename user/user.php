<?php
include '../config/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory - User Management</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../includes/topbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">User Management</h1>

                <!-- Add User Button -->
                <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add User
                </button>

                <!-- Users Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Users</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM users ORDER BY created_at DESC";
                                    $result = $conn->query($query);
                                    $users = []; 

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $userId = htmlspecialchars($row['id']);
                                            $username = htmlspecialchars($row['username']);
                                            $fullName = htmlspecialchars($row['full_name']);
                                            $role = ucfirst(htmlspecialchars($row['role']));

                                            $users[] = [
                                                'id' => $userId,
                                                'username' => $username,
                                                'full_name' => $fullName,
                                                'role' => $role
                                            ];

                                            echo "<tr>";
                                            echo "<td>{$userId}</td>";
                                            echo "<td>{$username}</td>";
                                            echo "<td>{$fullName}</td>";
                                            echo "<td>{$role}</td>";
                                            echo "<td class='text-center'>";
                                            echo "<button class='btn btn-sm btn-info' data-toggle='modal' data-target='#viewUserModal{$userId}' title='View'><i class='fas fa-eye'></i></button> ";
                                            echo "<button class='btn btn-sm btn-primary' data-toggle='modal' data-target='#editUserModal{$userId}' title='Edit'><i class='fas fa-edit'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No users found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Generate View & Edit Modals -->
                <?php
                foreach ($users as $user) {
                    // View Modal
                    echo "
                    <div class='modal fade' id='viewUserModal{$user['id']}' tabindex='-1' role='dialog'>
                        <div class='modal-dialog' role='document'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <h5 class='modal-title'>User Details</h5>
                                    <button type='button' class='close' data-dismiss='modal'><span>&times;</span></button>
                                </div>
                                <div class='modal-body'>
                                    <p><strong>Username:</strong> {$user['username']}</p>
                                    <p><strong>Full Name:</strong> {$user['full_name']}</p>
                                    <p><strong>Role:</strong> {$user['role']}</p>
                                </div>
                            </div>
                        </div>
                    </div>";

                    // Edit Modal
                    echo "
                    <div class='modal fade' id='editUserModal{$user['id']}' tabindex='-1' role='dialog'>
                        <div class='modal-dialog' role='document'>
                            <form action='update_user.php' method='POST'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title'>Edit User</h5>
                                        <button type='button' class='close' data-dismiss='modal'><span>&times;</span></button>
                                    </div>
                                    <div class='modal-body'>
                                        <input type='hidden' name='id' value='{$user['id']}'>
                                        <div class='form-group'>
                                            <label>Username</label>
                                            <input type='text' name='username' class='form-control' value='{$user['username']}' required>
                                        </div>
                                        <div class='form-group'>
                                            <label>Full Name</label>
                                            <input type='text' name='full_name' class='form-control' value='{$user['full_name']}' required>
                                        </div>
                                        <div class='form-group'>
                                            <label>New Password (leave blank to keep current)</label>
                                            <input type='password' name='password' class='form-control'>
                                        </div>
                                        <div class='form-group'>
                                            <label>Role</label>
                                            <select name='role' class='form-control' required>
                                                <option value='admin' ".($user['role'] == 'Admin' ? 'selected' : '').">Admin</option>
                                                <option value='employee' ".($user['role'] == 'Employee' ? 'selected' : '').">Employee</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='submit' class='btn btn-primary'>Save Changes</button>
                                        <button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>";
                }
                ?>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="add_user.php" method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New User</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" class="form-control" required>
                                            <option value="">Select Role</option>
                                            <option value="admin">Admin</option>
                                            <option value="employee">Employee</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Add User</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- End Add User Modal -->
            </div>
        </div>
        </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "pageLength": 10,
            "responsive": true
        });
    });
</script>
</body>
</html>
