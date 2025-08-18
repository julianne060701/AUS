<?php
include '../config/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Brands</title>
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
                <h1 class="h3 mb-2 text-gray-800">Brands</h1>

                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#addBrandModal">
                    <i class="fas fa-plus"></i> Add Brand
                </button>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Brand List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Brand Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM brands ORDER BY brand_id DESC";
                                    $result = $conn->query($query);
                                    $brands_data = []; // Store data for modals

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $brand_id = $row['brand_id'];
                                            $brand_name = htmlspecialchars($row['brand_name']);
                                            $brands_data[] = $row; // Store for later use
                                            
                                            echo "<tr>";
                                            echo "<td>" . $brand_id . "</td>";
                                            echo "<td>" . $brand_name . "</td>";
                                            echo "<td class='text-center'>";
                                            echo "<button class='btn btn-sm btn-info mr-1' data-toggle='modal' data-target='#viewModal_" . $brand_id . "' title='View'>";
                                            echo "<i class='fas fa-eye'></i>";
                                            echo "</button>";
                                            echo "<button class='btn btn-sm btn-primary mr-1' data-toggle='modal' data-target='#editModal_" . $brand_id . "' title='Edit'>";
                                            echo "<i class='fas fa-edit'></i>";
                                            echo "</button>";
                                            echo "<button class='btn btn-sm btn-danger' onclick='deleteBrand(" . $brand_id . ")' title='Delete'>";
                                            echo "<i class='fas fa-trash'></i>";
                                            echo "</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>No brand records found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add Brand Modal -->
                <div class="modal fade" id="addBrandModal" tabindex="-1" role="dialog" aria-labelledby="addBrandModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <form action="add_brand.php" method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addBrandModalLabel">Add Brand</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="brand_name">Brand Name</label>
                                        <input type="text" class="form-control" name="brand_name" id="brand_name" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Add Brand</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                // Generate modals for each brand
                foreach ($brands_data as $brand) {
                    $brand_id = $brand['brand_id'];
                    $brand_name = htmlspecialchars($brand['brand_name']);
                ?>
                    <!-- View Modal for Brand <?php echo $brand_id; ?> -->
                    <div class="modal fade" id="viewModal_<?php echo $brand_id; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel_<?php echo $brand_id; ?>" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewModalLabel_<?php echo $brand_id; ?>">Brand Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>ID:</strong> <?php echo $brand_id; ?></p>
                                    <p><strong>Brand Name:</strong> <?php echo $brand_name; ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal for Brand <?php echo $brand_id; ?> -->
                    <div class="modal fade" id="editModal_<?php echo $brand_id; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel_<?php echo $brand_id; ?>" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form action="update_brand.php" method="POST">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel_<?php echo $brand_id; ?>">Edit Brand</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="brand_id" value="<?php echo $brand_id; ?>">
                                        <div class="form-group">
                                            <label for="edit_brand_name_<?php echo $brand_id; ?>">Brand Name</label>
                                            <input type="text" class="form-control" name="brand_name" id="edit_brand_name_<?php echo $brand_id; ?>" value="<?php echo $brand_name; ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php
                }
                ?>

            </div> <!-- container-fluid -->
        </div> <!-- content -->

        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<!-- Include jQuery first, then Bootstrap, then DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    console.log('jQuery version:', $.fn.jquery);
    console.log('Table element found:', $('#dataTable').length > 0);
    
    // Initialize DataTable with error handling
    try {
        $('#dataTable').DataTable({
            "pageLength": 10,
            "order": [[ 0, "desc" ]],
            "columnDefs": [
                { "orderable": false, "targets": 2 }
            ],
            "responsive": true,
            "language": {
                "emptyTable": "No brand records found.",
                "zeroRecords": "No matching records found"
            }
        });
        console.log('DataTable initialized successfully');
    } catch (error) {
        console.error('DataTable initialization error:', error);
    }
});

// Function to handle brand deletion
function deleteBrand(brandId) {
    if (confirm('Are you sure you want to delete this brand?')) {
        window.location.href = 'delete_brand.php?id=' + brandId;
    }
}

// Clear form when add modal is closed
$('#addBrandModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
});
</script>

<?php $conn->close(); ?>
</body>
</html>