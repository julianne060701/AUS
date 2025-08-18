<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/conn.php';
?>

<?php include('../includes/header.php'); ?>

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
                                            echo "<button class='btn btn-sm btn-primary' data-toggle='modal' data-target='#editModal_" . $brand_id . "' title='Edit'>";
                                            echo "<i class='fas fa-edit'></i>";
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
                <div class="modal fade" id="addBrandModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="add_brand.php" method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Brand</h5>
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
                    <div class="modal fade" id="viewModal_<?php echo $brand_id; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Brand Details</h5>
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
                    <div class="modal fade" id="editModal_<?php echo $brand_id; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <form action="update_brand.php" method="POST">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Brand</h5>
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
                $conn->close();
                ?>

            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<!-- Load jQuery first (from CDN to ensure it's available) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SB Admin 2 (if available locally, otherwise skip) -->
<script>
// Only load if file exists, otherwise skip
try {
    document.write('<script src="../js/sb-admin-2.min.js"><\/script>');
} catch(e) {
    console.log('SB Admin 2 JS not found, continuing without it');
}
</script>

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    console.log('jQuery version:', $.fn.jquery);
    console.log('Table element found:', $('#dataTable').length > 0);
    console.log('Table HTML structure:');
    console.log($('#dataTable')[0]);
    
    // Count columns in header vs body
    var headerCols = $('#dataTable thead tr:first th').length;
    var bodyCols = $('#dataTable tbody tr:first td').length;
    console.log('Header columns:', headerCols);
    console.log('Body columns:', bodyCols);
    
    // Initialize DataTable with error handling
    try {
        $('#dataTable').DataTable({
            "pageLength": 10,
            "order": [[ 0, "desc" ]],
            "columnDefs": [
                { "orderable": false, "targets": 2 }
            ],
            "responsive": true,
            "destroy": true // Allow reinitialization
        });
        console.log('DataTable initialized successfully');
    } catch (error) {
        console.error('DataTable initialization error:', error);
    }
});
</script>

</body>
</html>