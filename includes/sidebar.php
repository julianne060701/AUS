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
    <!-- <li class="nav-item <?= ($currentPage == 'categories.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../category/categories.php">
            <i class="fas fa-fw fa-tags"></i>
            <span>Categories</span>
        </a>
    </li> -->

    <!-- Brand -->
    <li class="nav-item <?= ($currentPage == 'brands.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../brands/brand.php">
            <i class="fas fa-fw fa-industry"></i>
            <span>Brands</span>
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
    <li class="nav-item <?= ($currentPage == 'withdrawal_products.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../withdrawal_products/withdrawal_products.php">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Withdrawal of Products</span>
        </a>
    </li>

    <!-- Installer Schedule -->
    <li class="nav-item <?= ($currentPage == 'installer_schedule.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../installer_schedule/installer_schedule.php">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Installer Schedule</span>
        </a>
    </li>

      <!-- Reports Dropdown -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports" aria-expanded="true" aria-controls="collapseReports">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Reports</span>
        </a>
        <div id="collapseReports" class="collapse" aria-labelledby="headingReports" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Report Types:</h6>
                <a class="collapse-item <?= ($currentPage == 'inventory_report.php') ? 'active' : '' ?>" href="../withdrawal_products/inventory_report.php">
                    <i class="fas fa-fw fa-boxes"></i>
                    Inventory Report
                </a>
                <a class="collapse-item <?= ($currentPage == 'install_report.php') ? 'active' : '' ?>" href="../withdrawal_products/install_report.php">
                    <i class="fas fa-fw fa-tools"></i>
                    Installation Report
                </a>
            </div>
        </div>
    </li>

    <!-- Installer Schedule Report -->
    <!-- <li class="nav-item <?= ($currentPage == 'installer_schedule_report.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../installer_schedule/installer_schedule_report.php">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Schedule Report</span>
        </a>
    </li> -->

    <!-- Installer Dashboard -->
    <!-- <li class="nav-item <?= ($currentPage == 'installer_dashboard.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../installer_schedule/installer_dashboard.php">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Installer Dashboard</span>
        </a>
    </li> -->

    <!-- Installer Mobile View -->
    <!-- <li class="nav-item <?= ($currentPage == 'installer_mobile_view.php') ? 'active' : '' ?>">
        <a class="nav-link" href="../installer_schedule/installer_mobile_view.php">
            <i class="fas fa-fw fa-mobile-alt"></i>
            <span>Mobile View</span>
        </a>
    </li> -->


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
