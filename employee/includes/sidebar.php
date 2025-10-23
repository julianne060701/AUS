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
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Rice Inventory (View Only) -->
    <li class="nav-item <?= ($currentPage == 'inventory.php') ? 'active' : '' ?>">
        <a class="nav-link" href="inventory.php">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Stock Entry</span>
        </a>
    </li>

    <!-- Record Sale -->
    <li class="nav-item <?= ($currentPage == 'withdrawal_products.php') ? 'active' : '' ?>">
        <a class="nav-link" href="withdrawal_products.php">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Withdrawal of Products</span>
        </a>
    </li>

    <!-- installer_schedule -->
    <li class="nav-item <?= ($currentPage == 'installer_schedule.php') ? 'active' : '' ?>">
        <a class="nav-link" href="installer_schedule.php">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Installer Schedule</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

</ul>
<!-- End of Sidebar -->
