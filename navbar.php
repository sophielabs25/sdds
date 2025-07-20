<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">SDDS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Home Menu Item -->
                <li class="nav-item">
                    <a class="nav-link <?php if ($current_page == 'site_stats.php') echo 'active'; ?>" href="site_stats.php">Home</a>
                </li>
                
                <!-- SU Data Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'dashboard.php') echo 'active'; ?>" href="#" id="suDataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Dashboard
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="suDataDropdown">
                        <li><a class="dropdown-item <?php if ($current_page == 'dashboard.php') echo 'active'; ?>" href="dashboard.php">Real Time Dashboard</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'sudata.php') echo 'active'; ?>" href="sudata.php">View</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'manage_su.php') echo 'active'; ?>" href="manage_su.php">Manage</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'view_moveout.php') echo 'active'; ?>" href="view_moveout.php">Move Out</a></li>
                    </ul>
                </li>

                <!-- SU Data Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'sudata.php') echo 'active'; ?>" href="#" id="suDataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        SU Data
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="suDataDropdown">
                        <li><a class="dropdown-item <?php if ($current_page == 'add_su.php') echo 'active'; ?>" href="add_su.php">Add</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'sudata.php') echo 'active'; ?>" href="sudata.php">View</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'manage_su.php') echo 'active'; ?>" href="manage_su.php">Manage</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'view_moveout.php') echo 'active'; ?>" href="view_moveout.php">Move Out</a></li>
                    </ul>
                </li>
 
               
                <!-- Escalations Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'escalations.php') echo 'active'; ?>" href="#" id="escalationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Escalations
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="escalationsDropdown">
                         <!-- Defects Submenu -->
                         <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Incident Reports</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'new_incident_report.php') echo 'active'; ?>" href="new_incident_report.php">Add New IR</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_incident_reports.php') echo 'active'; ?>" href="manage_incident_reports.php">Manage IR</a></li>
                            </ul>
                        </li>
                        <!-- Defects Submenu -->
                        <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Complaints</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'complaints.php') echo 'active'; ?>" href="complaints.php">Add New</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_complaints.php') echo 'active'; ?>" href="manage_complaints.php">Manage</a></li>
                            </ul>
                        </li>
                        <li><a class="dropdown-item <?php if ($current_page == 'vcs_organisations.php') echo 'active'; ?>" href="#">VCS Organisations</a></li>
                    </ul>
                </li>

                <!-- Safeguarding Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'escalations.php') echo 'active'; ?>" href="#" id="escalationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Safeguarding
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="escalationsDropdown">
                         <!-- Referrals Submenu -->
                         <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Referrals</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'new_sg_referral.php') echo 'active'; ?>" href="new_sg_referral.php">New Referral</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_safeguarding_referrals.php') echo 'active'; ?>" href="manage_safeguarding_referrals.php">Manage Referral</a></li>
                            </ul>
                        </li>
                        <!-- Defects Submenu -->
                        <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Vulnerable</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'add_vulnerable_su.php') echo 'active'; ?>" href="add_vulnerable_su.php">Add Vulnerable SUs</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_vulnerable_sus.php') echo 'active'; ?>" href="manage_vulnerable_sus.php">Manage Vulnerable SUs</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                   <!-- Escalations Dropdown Menu -->
                   <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'escalations.php') echo 'active'; ?>" href="#" id="escalationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        HSE
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="escalationsDropdown">
                         <!-- Defects Submenu -->
                         <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Incident Reports</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'new_incident_report.php') echo 'active'; ?>" href="new_incident_report.php">Add New IR</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_incident_reports.php') echo 'active'; ?>" href="manage_incident_reports.php">Manage IR</a></li>
                            </ul>
                        </li>
                        <!-- Defects Submenu -->
                        <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Complaints</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'complaints.php') echo 'active'; ?>" href="complaints.php">Add New</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_complaints.php') echo 'active'; ?>" href="manage_complaints.php">Manage</a></li>
                            </ul>
                        </li>
                        <li><a class="dropdown-item <?php if ($current_page == 'vcs_organisations.php') echo 'active'; ?>" href="#">VCS Organisations</a></li>
                    </ul>
                </li>

                <!-- Finance Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'finance.php') echo 'active'; ?>" href="#" id="financeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Finance
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="financeDropdown">
                        <li><a class="dropdown-item <?php if ($current_page == 'approved.php') echo 'active'; ?>" href="#">Approved</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'pending.php') echo 'active'; ?>" href="#">Pending</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'attention.php') echo 'active'; ?>" href="#">Attention</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'all_invoices.php') echo 'active'; ?>" href="#">All Invoices</a></li>
                    </ul>
                </li>

               
                <!-- Property Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'property.php') echo 'active'; ?>" href="#" id="propertyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Property
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="propertyDropdown">
                   
                        <!-- Defects Submenu -->
                        <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Defects</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'create_defect.php') echo 'active'; ?>" href="#">Create</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'view_defect.php') echo 'active'; ?>" href="#">View</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_defect.php') echo 'active'; ?>" href="#">Manage</a></li>
                            </ul>
                        </li>

                        <!-- Rooms Submenu -->
                        <li class="dropdown-submenu">
                            <a class="dropdown-item dropdown-toggle" href="#">Properties</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?php if ($current_page == 'add_property.php') echo 'active'; ?>" href="add_property.php">Add Property</a></li>
                                <li><a class="dropdown-item <?php if ($current_page == 'manage_properties.php') echo 'active'; ?>" href="manage_properties.php">Manage Property</a></li>
                            </ul>
                        </li>
                        <li><a class="dropdown-item <?php if ($current_page == 'compliance.php') echo 'active'; ?>" href="#">Compliance</a></li>
                    </ul>
                </li>

                <!-- Employees Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if ($current_page == 'employees.php') echo 'active'; ?>" href="#" id="employeesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Employees
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="employeesDropdown">
                        <li><a class="dropdown-item <?php if ($current_page == 'add_employee.php') echo 'active'; ?>" href="#">Add</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'update_employee.php') echo 'active'; ?>" href="#">Update</a></li>
                        <li><a class="dropdown-item <?php if ($current_page == 'manage_employee.php') echo 'active'; ?>" href="#">Manage</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Right-aligned Search Bar and Welcome Text -->
            <div class="d-flex align-items-center">
                <!-- Welcome Message -->
                
                <div class="dropdown d-flex align-items-center me-2">
    <?php
    // Get user's initials and profile picture
    $name = htmlspecialchars($_SESSION['name']);
    $site_name = htmlspecialchars($_SESSION['site_name']);
    $profile_picture = $_SESSION['profile_picture'] ?? null; // Assume profile picture is stored in session
    $name_parts = explode(' ', $name);
    $initials = strtoupper(substr($name_parts[0], 0, 1)) . strtoupper(substr(end($name_parts), 0, 1));
    ?>

    <!-- User Icon -->
    <div class="user-icon" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <?php if ($profile_picture): ?>
            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" class="profile-img">
        <?php else: ?>
            <span class="user-initials"><?= $initials ?></span>
        <?php endif; ?>
    </div>

    <!-- Dropdown Menu -->
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
        <li class="dropdown-header">
            <strong><?= $name ?></strong>
            <div class="text-muted"><?= $site_name ?></div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
    </ul>
</div>

<!-- Styles -->
<style>
    /* Circle Icon Styles */
    .user-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #6c757d; /* Default background if no picture */
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        overflow: hidden;
    }

    .user-icon img.profile-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .user-initials {
        display: flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
    }

    /* Dropdown Menu Styling */
    .dropdown-menu {
        min-width: 200px;
    }
</style>

                <!-- Search Form -->
                <form class="d-flex">
                    <input class="form-control me-2" type="search" id="searchInput" placeholder="Search" aria-label="Search">
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Custom CSS for Multi-level Dropdown -->
<style>
    /* Positioning for dropdown submenu */
    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu .dropdown-menu {
        top: 0;
        left: 100%;
        margin-top: 0;
        margin-left: 0;
    }

    .dropdown-menu .dropdown-toggle:after {
        transform: rotate(-90deg);
    }
</style>

<script>
// JavaScript for handling multi-level dropdowns in Bootstrap 5
document.addEventListener('DOMContentLoaded', function () {
    // Close all submenus when clicking on another dropdown or outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
            // Close all dropdown menus
            document.querySelectorAll('.dropdown-menu.show').forEach(function (dropdownMenu) {
                dropdownMenu.classList.remove('show');
            });
        }
    });

    document.querySelectorAll('.dropdown-submenu .dropdown-toggle').forEach(function (dropdownToggleEl) {
        dropdownToggleEl.addEventListener('click', function (e) {
            e.stopPropagation();  // Prevent event from bubbling up

            // Close all open submenus
            document.querySelectorAll('.dropdown-submenu .dropdown-menu').forEach(function (dropdownMenu) {
                if (dropdownMenu !== this.nextElementSibling) {
                    dropdownMenu.classList.remove('show');
                }
            }, this);

            // Toggle the current submenu
            if (this.nextElementSibling) {
                this.nextElementSibling.classList.toggle('show');
            }
        });
    });

    // Auto-filter search
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('table tbody tr');

    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        tableRows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
});
</script>
