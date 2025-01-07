<div class="topnav">
    <a href="?page=dashboard" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'dashboard') ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        Dashboard
    </a>
    <a href="?page=budget" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'budget') ? 'active' : ''; ?>">
        <i class="fas fa-wallet"></i>
        Budget
    </a>
    <a href="?page=expenses" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'expenses') ? 'active' : ''; ?>">
        <i class="fas fa-receipt"></i>
        Expenses
    </a>
    <a href="?page=saving" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'saving') ? 'active' : ''; ?>">
        <i class="fas fa-piggy-bank"></i>
        Saving
    </a>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <div class="dropdown">
            <button class="dropbtn">
                <i class="fas fa-user"></i><?php echo $_SESSION['name']; ?>
                <span class="arrow"></span>
            </button>
            <div class="dropdown-content">
                <a href="?page=profile" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'profile') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="?action=logout" class="logout-option"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    <?php else: ?>
        <a href="auth/login.php" class="<?php echo ($_SERVER['PHP_SELF'] === '/auth/login.php') ? 'active' : ''; ?>">
            <i class="fas fa-sign-in-alt"></i>
            Login
        </a>
    <?php endif; ?>
</div>