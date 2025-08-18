<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../index.php">
        <div class="sidebar-brand-icon">
            <img src="../img/logo.jpg" alt="Reyze Bigasan Logo" style="width: 40px; height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">AUS General Services </div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../dashboard/index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

     <!-- User -->
     <li class="nav-item <?= ($currentPage == 'user.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../user/user.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>User Management</span>
        </a>
    </li>

    <!-- Rice Inventory -->
    <!-- <li class="nav-item <?= ($currentPage == 'inventory.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../inventory/inventory.php">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Rice Inventory</span>
        </a>
    </li> -->

    <!-- Category -->
    <li class="nav-item <?= ($currentPage == 'categories.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../category/categories.php">
            <i class="fas fa-fw fa-tags"></i>
            <span>Categories</span>
        </a>
    </li>

    <!-- Products -->
    <li class="nav-item <?= ($currentPage == 'product.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../products/product.php">
            <i class="fas fa-fw fa-box"></i>
            <span>Products</span>
        </a>
    </li> 

    <!-- Add Sale -->
    <li class="nav-item <?= ($currentPage == 'sales.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../sales/sales.php">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Record Sale</span>
        </a>
    </li>

    <!-- Sales Report -->
    <li class="nav-item <?= ($currentPage == 'sales_report.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../sales/sales_report.php">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Sales Report</span>
        </a>
    </li>

    <!-- Suppliers -->
    <!--<li class="nav-item <?= ($currentPage == 'suppliers.php') ? 'active' : '' ?>">-->
    <!--    <a class="nav-link" href="../suppliers.php">-->
    <!--        <i class="fas fa-fw fa-truck"></i>-->
    <!--        <span>Suppliers</span>-->
    <!--    </a>-->
    <!--</li>-->

    <!-- Low Stock Alerts -->
    <!-- <li class="nav-item <?= ($currentPage == 'alerts.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../alerts/alerts.php">
            <i class="fas fa-fw fa-exclamation-triangle"></i>
            <span>Stock Alerts</span>
        </a>
    </li> -->

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

</ul>
<!-- End of Sidebar -->
