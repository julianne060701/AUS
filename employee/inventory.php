<?php
session_start();
include '../config/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory - Products with Brands</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'includes/topbar.php'; ?>
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
                        <th>Brand</th>
                        <th>Capacity</th>
                        <th>Selling Price</th>
                        <th>Category</th>
                        <th>Stock</th>
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
                    
                    echo "<tr>
                            <td>" . htmlspecialchars($row['product_name']) . "</td>
                            <td><span class='badge badge-secondary'>" . htmlspecialchars($row['brand_name'] ?: 'No Brand') . "</span></td>
                            <td>" . htmlspecialchars($row['capacity']) . "</td>
                            <td>₱" . number_format($row['selling_price'], 2) . "</td>
                            <td>" . htmlspecialchars($row['category_name']) . "</td>
                            <td class='" . $stockClass . "'>" . $row['quantity'] . "</td>
                            <td>
                                <button class='btn btn-sm btn-warning edit-btn' 
                                        data-id='" . $row['id'] . "'
                                        data-name='" . htmlspecialchars($row['product_name']) . "'
                                        data-capacity='" . htmlspecialchars($row['capacity']) . "'
                                        data-buying-price='" . $row['buying_price'] . "'
                                        data-selling-price='" . $row['selling_price'] . "'
                                        data-quantity='" . $row['quantity'] . "'
                                        data-category-id='" . $row['category_id'] . "'
                                        data-brand-id='" . $row['brand_id'] . "'
                                        data-bs-toggle='modal' 
                                        data-bs-target='#editProductModal'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button class='btn btn-sm btn-danger delete-btn' 
                                        data-id='" . $row['id'] . "'
                                        data-name='" . htmlspecialchars($row['product_name']) . "'
                                        data-bs-toggle='modal' 
                                        data-bs-target='#deleteProductModal'>
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
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->
            </div>
            </div>
            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

     <!-- Add Product Modal -->
     <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="add_products.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Product Name</label>
              <input type="text" name="product_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">-- Select Brand (Optional) --</option>
                <?php
                $brandRes = $conn->query("SELECT * FROM brands ORDER BY brand_name");
                while ($brand = $brandRes->fetch_assoc()) {
                    echo "<option value='" . $brand['brand_id'] . "'>" . htmlspecialchars($brand['brand_name']) . "</option>";
                }
                ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Capacity</label>
              <input type="text" name="capacity" class="form-control" placeholder="e.g. 1.5 or 4.0/3tr" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Buying Price</label>
              <input type="number" step="0.01" name="buying_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Selling Price (SRP)</label>
              <input type="number" step="0.01" name="selling_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
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
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="edit_product_process.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_product_id" name="product_id">
            <div class="mb-3">
              <label class="form-label">Product Name</label>
              <input type="text" id="edit_product_name" name="product_name" class="form-control" required>
            </div>
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
            <div class="mb-3">
              <label class="form-label">Capacity</label>
              <input type="text" id="edit_capacity" name="capacity" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Buying Price</label>
              <input type="number" step="0.01" id="edit_buying_price" name="buying_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Selling Price (SRP)</label>
              <input type="number" step="0.01" id="edit_selling_price" name="selling_price" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" id="edit_quantity" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
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
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Update</button>
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
    // Check if DataTable is already initialized and destroy it first
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }
    var table = $('#dataTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            "responsive": true,
            "order": [[4, 'asc'], [1, 'asc'], [0, 'asc']], // Sort by category, then brand, then product name
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ]
        });

    // Category filter functionality
    $('#categoryFilter').on('change', function() {
        var selectedCategory = $(this).val();
        
        if (selectedCategory === '') {
            // Show all categories
            table.column(4).search('').draw();
        } else {
            // Filter by selected category
            table.column(4).search('^' + selectedCategory + '$', true, false).draw();
        }
    });

    // Brand filter functionality
    $('#brandFilter').on('change', function() {
            var selectedBrand = $(this).val();
            
            if (selectedBrand === '') {
                table.column(1).search('').draw();
            } else {
                table.column(1).search(selectedBrand, true, false).draw();
            }
        });

        // Clear filters functionality
        $(document).on('click', '.clear-filter', function() {
            $('#categoryFilter').val('');
            $('#brandFilter').val('');
            table.columns().search('').draw();
        });

   // Edit button click handler
   $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var capacity = $(this).data('capacity');
            var buyingPrice = $(this).data('buying-price');
            var sellingPrice = $(this).data('selling-price');
            var quantity = $(this).data('quantity');
            var categoryId = $(this).data('category-id');
            var brandId = $(this).data('brand-id');
            
            // Populate edit modal fields
            $('#edit_product_id').val(id);
            $('#edit_product_name').val(name);
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


    // Optional: Add clear filter button functionality
    $(document).on('click', '.clear-filter', function() {
        $('#categoryFilter').val('');
        table.column(3).search('').draw();
    });
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
    
    </script>

    <style>
    .group-header {
        font-weight: bold;
        background-color: #f8f9fc !important;
    }
    
    .group-header td {
        padding: 12px 8px;
    }
    </style>

</body>
</html>