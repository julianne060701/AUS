<?php
session_start();
include '../config/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Incentory</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap4.min.css" rel="stylesheet">

    <style>
    .group-header {
        font-weight: bold;
        background-color: #f8f9fc !important;
    }
    
    .group-header td {
        padding: 12px 8px;
    }
    </style>
</head>
<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include '../includes/topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0 text-gray-800">Product Inventory</h3>
                        <div>
                            <!-- <button class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#manageBrandsModal">
                                <i class="fas fa-tags"></i> Manage Brands
                            </button> -->
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

                    <!-- Filters Row -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label">Filter by Category:</label>
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <?php
                                $categoryQuery = "SELECT DISTINCT c.category_name 
                                                FROM category c 
                                                INNER JOIN products p ON c.category_id = p.category_id 
                                                ORDER BY c.category_name";
                                $categoryResult = $conn->query($categoryQuery);
                                while ($categoryRow = $categoryResult->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($categoryRow['category_name']) . "'>" 
                                         . htmlspecialchars($categoryRow['category_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="brandFilter" class="form-label">Filter by Brand:</label>
                            <select id="brandFilter" class="form-select">
                                <option value="">All Brands</option>
                                <?php
                                $brandQuery = "SELECT DISTINCT b.brand_name 
                                             FROM brands b 
                                             INNER JOIN products p ON b.brand_id = p.brand_id 
                                             ORDER BY b.brand_name";
                                $brandResult = $conn->query($brandQuery);
                                while ($brandRow = $brandResult->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($brandRow['brand_name']) . "'>" 
                                         . htmlspecialchars($brandRow['brand_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button class="btn btn-outline-secondary clear-filter">
                                    <i class="fas fa-times"></i> Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Serial Number</th>
                                        <th>Brand</th>
                                        <th>Capacity</th>
                                        <th>Selling Price</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Date</th>
                                        <th>Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sql = "SELECT p.*, c.category_name, b.brand_name 
                                        FROM products p 
                                        LEFT JOIN category c ON p.category_id = c.category_id 
                                        LEFT JOIN brands b ON p.brand_id = b.brand_id 
                                        ORDER BY c.category_name, b.brand_name, p.product_name";
                                $result = $conn->query($sql);
                            
                                while ($row = $result->fetch_assoc()) {
                                    $stockClass = '';
                                    if ($row['quantity'] <= 5) {
                                        $stockClass = 'text-danger font-weight-bold';
                                    } elseif ($row['quantity'] <= 10) {
                                        $stockClass = 'text-warning font-weight-bold';
                                    }
                            
                                    // Format date with time
                                    $createdDate = !empty($row['created_at']) 
                                        ? date("M d, Y h:i A", strtotime($row['created_at'])) 
                                        : '—';
                            
                                        $serialNumber = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : '—';
                    
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['product_name']) . "</td>
                                                <td><span class='badge badge-info'>" . $serialNumber . "</span></td>
                                                <td><span class='badge badge-secondary'>" . htmlspecialchars($row['brand_name'] ?: 'No Brand') . "</span></td>
                                                <td>" . htmlspecialchars($row['capacity']) . "</td>
                                                <td>₱" . number_format($row['selling_price'], 2) . "</td>
                                                <td>" . htmlspecialchars($row['category_name']) . "</td>
                                                <td class='" . $stockClass . "'>" . $row['quantity'] . "</td>
                                                <td>" . $createdDate . "</td>
                                                <td>" . (!empty($row['updated_at']) ? date("M d, Y h:i A", strtotime($row['updated_at'])) : '—') . "</td>
                                                 <td>
                                                     <button class='btn btn-sm btn-warning edit-btn me-1' 
                                                            data-id='" . $row['id'] . "'
                                                            data-name='" . htmlspecialchars($row['product_name']) . "'
                                                            data-serial='" . htmlspecialchars($row['serial_number']) . "'
                                                            data-capacity='" . htmlspecialchars($row['capacity']) . "'
                                                            data-buying-price='" . $row['buying_price'] . "'
                                                            data-selling-price='" . $row['selling_price'] . "'
                                                            data-quantity='" . $row['quantity'] . "'
                                                            data-category-id='" . $row['category_id'] . "'
                                                            data-brand-id='" . $row['brand_id'] . "'
                                                            data-bs-toggle='modal' 
                                                             data-bs-target='#editProductModal'
                                                             title='Edit' data-bs-toggle='tooltip'>
                                                        <i class='fas fa-edit'></i>
                                                    </button>
                                                     <button class='btn btn-sm btn-info reorder-btn me-1' 
                                                            data-id='" . $row['id'] . "'
                                                            data-name='" . htmlspecialchars($row['product_name']) . "'
                                                            data-current-qty='" . $row['quantity'] . "'
                                                            data-bs-toggle='modal' 
                                                             data-bs-target='#reorderProductModal'
                                                             title='Reorder' data-bs-toggle='tooltip'>
                                                        <i class='fas fa-shopping-basket'></i> 
                                                    </button>
                                                    <button class='btn btn-sm btn-danger delete-btn' 
                                                            data-id='" . $row['id'] . "'
                                                            data-name='" . htmlspecialchars($row['product_name']) . "'
                                                            data-bs-toggle='modal' 
                                                             data-bs-target='#deleteProductModal'
                                                             title='Delete' data-bs-toggle='tooltip'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>


                            </div>
                        </div>
                    </div>

                </div>
                </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../includes/footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Reorder Product Modal -->
    <div class="modal fade" id="reorderProductModal" tabindex="-1" aria-labelledby="reorderProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="reorder_product.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="reorderProductModalLabel">Reorder Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="reorder_product_id" name="product_id">
            <div class="mb-2">
                <label class="form-label">Product</label>
                <input type="text" id="reorder_product_name" class="form-control" readonly>
            </div>
            <div class="mb-2">
                <label class="form-label">Current Stock</label>
                <input type="number" id="reorder_current_qty" class="form-control" readonly>
            </div>
            <div class="mb-2">
                <label class="form-label">Add Quantity <span class="text-danger">*</span></label>
                <input type="number" name="add_quantity" id="reorder_add_qty" class="form-control" min="1" required>
                <small class="text-muted">Enter how many units to add to stock.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Apply</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Manage Brands Modal -->
    <div class="modal fade" id="manageBrandsModal" tabindex="-1" aria-labelledby="manageBrandsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageBrandsModalLabel">Manage Brands</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add Brand Form -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="m-0">Add New Brand</h6>
                        </div>
                        <div class="card-body">
                            <form id="addBrandForm">
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" id="new_brand_name" class="form-control" placeholder="Brand Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add Brand
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Brands -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0">Existing Brands</h6>
                        </div>
                        <div class="card-body">
                            <div id="brandsList">
                                <?php
                                $brandsQuery = "SELECT b.*, COUNT(p.id) as product_count 
                                              FROM brands b 
                                              LEFT JOIN products p ON b.brand_id = p.brand_id 
                                              GROUP BY b.brand_id 
                                              ORDER BY b.brand_name";
                                $brandsResult = $conn->query($brandsQuery);
                                while ($brand = $brandsResult->fetch_assoc()) {
                                    echo "<div class='d-flex justify-content-between align-items-center border-bottom py-2'>
                                            <div>
                                                <strong>" . htmlspecialchars($brand['brand_name']) . "</strong>
                                                <small class='text-muted'>(" . $brand['product_count'] . " products)</small>
                                            </div>
                                            <div>";
                                    if ($brand['product_count'] == 0) {
                                        echo "<button class='btn btn-sm btn-danger delete-brand-btn' data-id='" . $brand['brand_id'] . "' data-name='" . htmlspecialchars($brand['brand_name']) . "'>
                                                <i class='fas fa-trash'></i>
                                              </button>";
                                    }
                                    echo "  </div>
                                          </div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form id="mainAddProductForm" action="add_products.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="main_existing_product_id" name="product_id" value="">
            <div class="row">
              <div class="col-md-12">
                <div class="mb-3">
                  <label class="form-label">Select Existing Product (to reorder)</label>
                  <select id="main_existing_product_select" class="form-select">
                    <option value="">-- New Product --</option>
                    <?php
                    $allProductsRes = $conn->query("
                      SELECT p.id, p.product_name, b.brand_name, c.category_name
                      FROM products p
                      LEFT JOIN brands b ON p.brand_id = b.brand_id
                      LEFT JOIN category c ON p.category_id = c.category_id
                      ORDER BY p.product_name ASC
                    ");
                    while ($p = $allProductsRes->fetch_assoc()) {
                      $label = htmlspecialchars($p['product_name']);
                      if (!empty($p['brand_name'])) $label .= ' — ' . htmlspecialchars($p['brand_name']);
                      if (!empty($p['category_name'])) $label .= ' (' . htmlspecialchars($p['category_name']) . ')';
                      echo "<option value='" . $p['id'] . "'>" . $label . "</option>";
                    }
                    ?>
                  </select>
                  <small class="text-muted">Choose a product to reorder; details will auto-fill and become read-only.</small>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Product Name <span class="text-danger">*</span></label>
                  <input type="text" id="main_product_name" name="product_name" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Serial Number</label>
                  <input type="text" id="main_serial_number" name="serial_number" class="form-control" placeholder="e.g. SN123456 (Optional)">
                  <small class="text-muted">Leave blank if not applicable</small>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Brand</label>
                  <select id="main_brand_id" name="brand_id" class="form-select">
                    <option value="">-- Select Brand (Optional) --</option>
                    <?php
                    $brandRes = $conn->query("SELECT * FROM brands ORDER BY brand_name");
                    while ($brand = $brandRes->fetch_assoc()) {
                        echo "<option value='" . $brand['brand_id'] . "'>" . htmlspecialchars($brand['brand_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Capacity <span class="text-danger">*</span></label>
                  <input type="text" id="main_capacity" name="capacity" class="form-control" placeholder="e.g. 1.5L or 4.0/3tr" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Buying Price <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" id="main_buying_price" name="buying_price" class="form-control" placeholder="0.00" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Selling Price (SRP) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" id="main_selling_price" name="selling_price" class="form-control" placeholder="0.00" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Quantity <span class="text-danger">*</span></label>
                  <input type="number" id="main_quantity" name="quantity" class="form-control" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select id="main_category_id" name="category_id" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <?php
                    $catRes = $conn->query("SELECT * FROM category ORDER BY category_name");
                    while ($cat = $catRes->fetch_assoc()) {
                        echo "<option value='" . $cat['category_id'] . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row d-none" id="main_reorder_row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Add Quantity (Reorder) <span class="text-danger">*</span></label>
                  <input type="number" id="main_add_quantity" name="add_quantity" class="form-control" min="1">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Current Stock</label>
                  <input type="number" id="main_current_qty" class="form-control" readonly>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Product</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form action="edit_product_process.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_product_id" name="product_id">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Product Name <span class="text-danger">*</span></label>
                  <input type="text" id="edit_product_name" name="product_name" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Serial Number</label>
                  <input type="text" id="edit_serial_number" name="serial_number" class="form-control" placeholder="e.g. SN123456 (Optional)">
                  <small class="text-muted">Leave blank if not applicable</small>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Brand</label>
                  <select id="edit_brand_id" name="brand_id" class="form-select">
                    <option value="">-- Select Brand (Optional) --</option>
                    <?php
                    $brandRes = $conn->query("SELECT * FROM brands ORDER BY brand_name");
                    while ($brand = $brandRes->fetch_assoc()) {
                        echo "<option value='" . $brand['brand_id'] . "'>" . htmlspecialchars($brand['brand_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Capacity <span class="text-danger">*</span></label>
                  <input type="text" id="edit_capacity" name="capacity" class="form-control" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Buying Price <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" id="edit_buying_price" name="buying_price" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Selling Price (SRP) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" id="edit_selling_price" name="selling_price" class="form-control" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Quantity <span class="text-danger">*</span></label>
                  <input type="number" id="edit_quantity" name="quantity" class="form-control" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select id="edit_category_id" name="category_id" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <?php
                    $catRes = $conn->query("SELECT * FROM category ORDER BY category_name");
                    while ($cat = $catRes->fetch_assoc()) {
                        echo "<option value='" . $cat['category_id'] . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Product</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete the product "<strong id="delete_product_name"></strong>"?</p>
            <p class="text-danger">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <form action="delete_product_process.php" method="POST" style="display: inline;">
              <input type="hidden" id="delete_product_id" name="product_id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.0/js/dataTables.rowGroup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
     $(document).ready(function () {
         // Check if DataTable is already initialized
         var table;
         if ($.fn.DataTable.isDataTable('#dataTable')) {
             // If already initialized, destroy and reinitialize with custom options
             $('#dataTable').DataTable().destroy();
         }
         
        table = $('#dataTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            "responsive": true,
            "order": [[8, 'desc'], [7, 'desc']], // Latest updated first, then latest created
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ]
        });

        // Add Product modal: toggle between New vs Reorder based on existing product dropdown
        function setMainReorderMode(enabled) {
            var form = $('#mainAddProductForm');
            if (enabled) {
                form.attr('action', 'reorder_product.php');
                $('#main_reorder_row').removeClass('d-none');
                $('#main_add_quantity').attr('required', true);
                $('#main_product_name, #main_serial_number, #main_capacity, #main_buying_price, #main_selling_price').attr('readonly', true);
                $('#main_brand_id, #main_category_id').attr('disabled', true);
                $('#main_quantity').attr('disabled', true).attr('required', false);
            } else {
                form.attr('action', 'add_products.php');
                $('#main_reorder_row').addClass('d-none');
                $('#main_add_quantity').removeAttr('required').val('');
                $('#main_current_qty').val('');
                $('#main_product_name, #main_serial_number, #main_capacity, #main_buying_price, #main_selling_price').attr('readonly', false);
                $('#main_brand_id, #main_category_id').attr('disabled', false);
                $('#main_quantity').attr('disabled', false).attr('required', true);
            }
        }

        // Reset modal on hide
        var mainAddModalEl = document.getElementById('addProductModal');
        if (mainAddModalEl) {
            mainAddModalEl.addEventListener('hidden.bs.modal', function () {
                $('#main_existing_product_select').val('');
                $('#main_existing_product_id').val('');
                setMainReorderMode(false);
                $('#main_product_name').val('');
                $('#main_serial_number').val('');
                $('#main_capacity').val('');
                $('#main_buying_price').val('');
                $('#main_selling_price').val('');
                $('#main_quantity').val('');
                $('#main_brand_id').val('');
                $('#main_category_id').val('');
            });
        }

        // When selecting an existing product, fetch and lock details
        $('#main_existing_product_select').on('change', function () {
            var productId = $(this).val();
            if (!productId) {
                $('#main_existing_product_id').val('');
                setMainReorderMode(false);
                return;
            }
            $('#main_existing_product_id').val(productId);
            setMainReorderMode(true);
            $.ajax({
                url: 'get_product_details.php',
                type: 'GET',
                data: { id: productId },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success && resp.product) {
                        var p = resp.product;
                        $('#main_product_name').val(p.product_name || '');
                        $('#main_serial_number').val(p.serial_number || '');
                        $('#main_capacity').val(p.capacity || '');
                        $('#main_buying_price').val(p.buying_price || '');
                        $('#main_selling_price').val(p.selling_price || '');
                        $('#main_brand_id').val(p.brand_id || '');
                        $('#main_category_id').val(p.category_id || '');
                        $('#main_current_qty').val(p.quantity || '');
                    } else {
                        alert('Could not load product details.');
                    }
                },
                error: function() {
                    alert('Failed to load product details.');
                }
            });
        });
        // Category filter functionality (Category is column index 5)
        $('#categoryFilter').on('change', function() {
            var selectedCategory = $(this).val();
            
            if (selectedCategory === '') {
                table.column(5).search('').draw();
            } else {
                table.column(5).search('^' + selectedCategory + '$', true, false).draw();
            }
        });

        // Brand filter functionality (Brand is column index 2)
        $('#brandFilter').on('change', function() {
            var selectedBrand = $(this).val();
            
            if (selectedBrand === '') {
                table.column(2).search('').draw();
            } else {
                table.column(2).search(selectedBrand, true, false).draw();
            }
        });

        // Clear filters functionality
        $(document).on('click', '.clear-filter', function() {
            $('#categoryFilter').val('');
            $('#brandFilter').val('');
            table.columns().search('').draw();
             table.order([[8, 'desc'], [7, 'desc']]).draw(); // restore latest-first
        });

        // Edit button click handler
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var serial = $(this).data('serial');
            var capacity = $(this).data('capacity');
            var buyingPrice = $(this).data('buying-price');
            var sellingPrice = $(this).data('selling-price');
            var quantity = $(this).data('quantity');
            var categoryId = $(this).data('category-id');
            var brandId = $(this).data('brand-id');
            
            // Populate edit modal fields
            $('#edit_product_id').val(id);
            $('#edit_product_name').val(name);
            $('#edit_serial_number').val(serial || '');
            $('#edit_capacity').val(capacity);
            $('#edit_buying_price').val(buyingPrice);
            $('#edit_selling_price').val(sellingPrice);
            $('#edit_quantity').val(quantity);
            $('#edit_category_id').val(categoryId);
            $('#edit_brand_id').val(brandId || '');
        });

        // Delete button click handler
        $(document).on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            
            // Populate delete modal fields
            $('#delete_product_id').val(id);
            $('#delete_product_name').text(name);
        });

        // Reorder button click handler
        $(document).on('click', '.reorder-btn', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var currentQty = $(this).data('current-qty');

            $('#reorder_product_id').val(id);
            $('#reorder_product_name').val(name);
            $('#reorder_current_qty').val(currentQty);
            $('#reorder_add_qty').val('');
        });

         // Reset Reorder modal on hide
         var reorderModalEl = document.getElementById('reorderProductModal');
         if (reorderModalEl) {
             reorderModalEl.addEventListener('hidden.bs.modal', function () {
                 $('#reorder_product_id').val('');
                 $('#reorder_product_name').val('');
                 $('#reorder_current_qty').val('');
                 $('#reorder_add_qty').val('');
             });
         }

         // Enable Bootstrap tooltips
         var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
         tooltipTriggerList.forEach(function (tooltipTriggerEl) {
             new bootstrap.Tooltip(tooltipTriggerEl);
         });

         // Improve search input placeholder
         var dtFilter = $('#dataTable_filter input[type=\"search\"]');
         if (dtFilter.length) {
             dtFilter.attr('placeholder', 'Search products...');
         }

        // Add brand form handler
        $('#addBrandForm').on('submit', function(e) {
            e.preventDefault();
            var brandName = $('#new_brand_name').val().trim();
            
            if (brandName) {
                $.ajax({
                    url: 'add_brand.php',
                    type: 'POST',
                    data: { brand_name: brandName },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#new_brand_name').val('');
                            location.reload(); // Reload to update the brands list
                        } else {
                            alert('Error: ' + (response.message || 'Failed to add brand'));
                        }
                    },
                    error: function() {
                        alert('Error: Failed to add brand');
                    }
                });
            }
        });

        // Delete brand handler
        $(document).on('click', '.delete-brand-btn', function() {
            var brandId = $(this).data('id');
            var brandName = $(this).data('name');
            
            if (confirm('Are you sure you want to delete the brand "' + brandName + '"?')) {
                $.ajax({
                    url: 'delete_brand.php',
                    type: 'POST',
                    data: { brand_id: brandId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload to update the brands list
                        } else {
                            alert('Error: ' + (response.message || 'Failed to delete brand'));
                        }
                    },
                    error: function() {
                        alert('Error: Failed to delete brand');
                    }
                });
            }
        });

    });
    </script>

    <style>
    .group-header {
        font-weight: bold;
        background-color: #f8f9fc !important;
    }
    
    .group-header td {
        padding: 12px 8px;
    }

    .badge {
        font-size: 0.75em;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .font-weight-bold {
        font-weight: bold !important;
    }
    </style>

</body>
</html>