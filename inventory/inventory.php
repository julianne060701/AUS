<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $rice_type = $_POST['rice_type'];
    $price_per_kg = $_POST['price_per_kg'];
    $sack_weight_kg = $_POST['sack_weight_kg'];
    $quantity_sacks = $_POST['quantity_sacks'];
    $quantity_kg = $_POST['quantity_kg'];
    $unit = $_POST['unit'];
    $category = $_POST['category'];
    $alert_threshold = $_POST['alert_threshold'];

    $sql = "UPDATE rice_inventory SET 
                rice_type = ?, 
                price_per_kg = ?, 
                sack_weight_kg = ?, 
                quantity_sacks = ?, 
                quantity_kg = ?, 
                unit = ?, 
                category_id = ?, 
                alert_threshold = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddiisiii", $rice_type, $price_per_kg, $sack_weight_kg, $quantity_sacks, $quantity_kg, $unit, $category, $alert_threshold, $id);
    
    if ($stmt->execute()) {
        header("Location: inventory.php?success=1");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
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

    <title>Inventory - Dashboard</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../includes/topbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-2 text-gray-800">Rice Inventory</h1>
            <!-- Add Rice Inventory Button -->
            <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addInventoryModal">
                <i class="fas fa-plus"></i> Add Rice Inventory
            </button>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Inventory List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Rice Type</th>
                                    <th>Quantity Sacks</th>
                                    <th>Quantity KG</th>
                                    <th>Unit</th>
                                    <th>Price per KG</th>
                                    <th>Category</th>
                                    <th>Alert Threshold</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                include '../config/conn.php';

                                $query = "SELECT ri.*, c.category_name FROM rice_inventory ri LEFT JOIN category c ON ri.category_id = c.category_id ORDER BY ri.id DESC";
                                $result = $conn->query($query);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $inventoryId = $row['id'];
                                        $modalId = "modal_" . $inventoryId;
                                        $editModalId = "edit_" . $inventoryId;

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['rice_type']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['quantity_sacks'] ?? 0) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['quantity_kg'] ?? 0) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['unit']) . "</td>";
                                        echo "<td>₱" . number_format($row['price_per_kg'], 2) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['alert_threshold']) . "</td>";
                                        echo "<td class='text-center'>
                                                <button class='btn btn-sm btn-info' data-toggle='modal' data-target='#$modalId' title='View'>
                                                    <i class='fas fa-eye'></i>
                                                </button>
                                                <button class='btn btn-sm btn-primary' data-toggle='modal' data-target='#$editModalId' title='Edit'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                              </td>";
                                        echo "</tr>";

                                        // View Modal
                                        echo "<div class='modal fade' id='$modalId' tabindex='-1' role='dialog' aria-labelledby='modalLabel_$modalId' aria-hidden='true'>
                                                <div class='modal-dialog' role='document'>
                                                    <div class='modal-content'>
                                                        <div class='modal-header'>
                                                            <h5 class='modal-title' id='modalLabel_$modalId'>Rice Details</h5>
                                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                <span aria-hidden='true'>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class='modal-body'>
                                                            <p><strong>Rice Type:</strong> " . htmlspecialchars($row['rice_type']) . "</p>
                                                            <p><strong>Quantity Sacks:</strong> " . htmlspecialchars($row['quantity_sacks'] ?? 0) . "</p>
                                                            <p><strong>Quantity KG:</strong> " . htmlspecialchars($row['quantity_kg'] ?? 0) . "</p>
                                                            <p><strong>Unit:</strong> " . htmlspecialchars($row['unit']) . "</p>
                                                            <p><strong>Sack Weight:</strong> " . htmlspecialchars($row['sack_weight_kg']) . " kg</p>
                                                            <p><strong>Price per KG:</strong> ₱" . number_format($row['price_per_kg'], 2) . "</p>
                                                            <p><strong>Category:</strong> " . htmlspecialchars($row['category_name']) . "</p>
                                                            <p><strong>Alert Threshold:</strong> " . htmlspecialchars($row['alert_threshold']) . "</p>
                                                        </div>
                                                        <div class='modal-footer'>
                                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>";

                                        // Edit Modal
                                        echo "<div class='modal fade' id='$editModalId' tabindex='-1' role='dialog' aria-labelledby='editModalLabel_$modalId' aria-hidden='true'>
                                                <div class='modal-dialog' role='document'>
                                                    <form action='update_inventory.php' method='POST'>
                                                        <input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>
                                                        <div class='modal-content'>
                                                            <div class='modal-header'>
                                                                <h5 class='modal-title' id='editModalLabel_$modalId'>Edit Rice Inventory</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                    <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class='modal-body'>
                                                                <div class='form-group'>
                                                                    <label>Rice Type</label>
                                                                    <input type='text' class='form-control' name='rice_type' value='" . htmlspecialchars($row['rice_type']) . "' required>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Price per KG (₱)</label>
                                                                    <input type='number' step='0.01' class='form-control' name='price_per_kg' value='" . htmlspecialchars($row['price_per_kg']) . "' required>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Sack Weight (KG)</label>
                                                                    <input type='number' class='form-control' name='sack_weight_kg' value='" . htmlspecialchars($row['sack_weight_kg'] ?? 50) . "'>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Quantity Sacks</label>
                                                                    <input type='number' class='form-control' name='quantity_sacks' value='" . htmlspecialchars($row['quantity_sacks'] ?? 0) . "' required>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Quantity KG</label>
                                                                    <input type='number' step='0.01' class='form-control' name='quantity_kg' value='" . htmlspecialchars($row['quantity_kg'] ?? 0) . "' required>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Unit</label>
                                                                    <select class='form-control' name='unit' required>
                                                                        <option value='kg' " . ($row['unit'] == 'kg' ? 'selected' : '') . ">KG</option>
                                                                        <option value='sack' " . ($row['unit'] == 'sack' ? 'selected' : '') . ">Sack</option>
                                                                    </select>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Category</label>
                                                                    <select class='form-control' name='category' required>";

                                        $cat_query = "SELECT * FROM category ORDER BY category_name ASC";
                                        $cat_result = mysqli_query($conn, $cat_query);
                                        if ($cat_result && mysqli_num_rows($cat_result) > 0) {
                                            while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                                $selected = $cat_row['category_id'] == $row['category_id'] ? 'selected' : '';
                                                echo "<option value='" . $cat_row['category_id'] . "' $selected>" . htmlspecialchars($cat_row['category_name']) . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No categories found</option>";
                                        }
                                        echo "</select>
                                                                </div>
                                                                <div class='form-group'>
                                                                    <label>Alert Threshold</label>
                                                                    <input type='number' class='form-control' name='alert_threshold' value='" . htmlspecialchars($row['alert_threshold']) . "' required>
                                                                </div>
                                                            </div>
                                                            <div class='modal-footer'>
                                                                <button type='submit' class='btn btn-primary'>Update Inventory</button>
                                                                <button type='button' class='btn btn-secondary' data-dismiss='modal'>Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center'>No inventory records found.</td></tr>";
                                }
                                $conn->close();
                                ?>
                                </tbody>
                            </table>
                            
                            <!-- Add Rice Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" role="dialog" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="add_inventory.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInventoryModalLabel">Add Rice Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="rice_type">Rice Type</label>
                        <input type="text" class="form-control" id="rice_type" name="rice_type" required>
                    </div>
                    <div class="form-group">
                        <label for="price_per_kg">Price per KG (₱)</label>
                        <input type="number" step="0.01" class="form-control" id="price_per_kg" name="price_per_kg" required>
                    </div>
                    <div class="form-group">
                        <label for="sack_weight_kg">Sack Weight (KG)</label>
                        <input type="number" class="form-control" id="sack_weight_kg" name="sack_weight_kg" value="50">
                    </div>
                    <div class="form-group">
                        <label for="quantity_sacks">Quantity Sacks</label>
                        <input type="number" class="form-control" id="quantity_sacks" name="quantity_sacks" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity_kg">Quantity KG</label>
                        <input type="number" step="0.01" class="form-control" id="quantity_kg" name="quantity_kg" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select class="form-control" id="unit" name="unit" required>
                            <option value="kg">KG</option>
                            <option value="sack">Sack</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select class="form-control" id="category" name="category" required>
                        <?php
                        include '../config/conn.php';

                        $cat_query = "SELECT * FROM category ORDER BY category_name ASC";
                        $cat_result = mysqli_query($conn, $cat_query);

                        if ($cat_result && mysqli_num_rows($cat_result) > 0) {
                            while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                echo '<option value="' . $cat_row['category_id'] . '">' . htmlspecialchars($cat_row['category_name']) . '</option>';
                            }
                        } else {
                            echo '<option value="">No categories found</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="alert_threshold">Alert Threshold</label>
                        <input type="number" class="form-control" id="alert_threshold" name="alert_threshold" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Inventory</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </div>
</div>


<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<!-- Auto-calculation script for sacks to KG conversion -->
<script>
$(document).ready(function () {
    $('#dataTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true
    });

    // Auto-calculate KG when sacks or sack weight changes (for add modal)
    function calculateKG() {
        const sacks = parseFloat($('#quantity_sacks').val()) || 0;
        const sackWeight = parseFloat($('#sack_weight_kg').val()) || 50;
        const totalKG = sacks * sackWeight;
        $('#quantity_kg').val(totalKG.toFixed(2));
    }

    $('#quantity_sacks, #sack_weight_kg').on('input', calculateKG);

    // Auto-calculate KG for edit modals
    $('[name="quantity_sacks"], [name="sack_weight_kg"]').on('input', function() {
        const modal = $(this).closest('.modal');
        const sacks = parseFloat(modal.find('[name="quantity_sacks"]').val()) || 0;
        const sackWeight = parseFloat(modal.find('[name="sack_weight_kg"]').val()) || 50;
        const totalKG = sacks * sackWeight;
        modal.find('[name="quantity_kg"]').val(totalKG.toFixed(2));
    });
});
</script>

</body>
</html>