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
    <li class="nav-item <?= ($currentPage == 'installer_dashboard.php') ? 'active' : '' ?>">
        <a class="nav-link" href="installer_dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Installer Dashboard</span>
        </a>
    </li>

    <!-- Rice Inventory (View Only) -->
    <!-- <li class="nav-item <?= ($currentPage == 'inventory.php') ? 'active' : '' ?>">
        <a class="nav-link" href="inventory.php">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Product Inventory</span>
        </a>
    </li> -->

    <!-- Record Sale -->
    <!-- <li class="nav-item <?= ($currentPage == 'record_sale.php') ? 'active' : '' ?>">
        <a class="nav-link" href="record_sale.php">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Record Sale</span>
        </a>
    </li> -->

    <!-- Low Stock Alerts -->
    <!-- <li class="nav-item <?= ($currentPage == 'low_stock.php') ? 'active' : '' ?>">
        <a class="nav-link" href="low_stock.php">
            <i class="fas fa-fw fa-exclamation-triangle"></i>
            <span>Low Stock Alerts</span>
        </a>
    </li> -->

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

</ul>
<!-- End of Sidebar -->
