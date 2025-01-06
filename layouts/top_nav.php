<div class="topnav">
    <a href="?page=dashboard" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'dashboard') ? 'active' : ''; ?>">
        Dashboard
    </a>
    <a href="?page=budget" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'budget') ? 'active' : ''; ?>">
        Budget
    </a>
    <a href="?page=expenses" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'expenses') ? 'active' : ''; ?>">
        Expenses
    </a>
    <a href="?page=saving" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'saving') ? 'active' : ''; ?>">
        Saving
    </a>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <div class="dropdown">
            <button class="dropbtn">
                <?php echo $_SESSION['name']; ?>
                <span class="arrow"></span>
            </button>
            <div class="dropdown-content">
                <a href="?page=profile" class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'profile') ? 'active' : ''; ?>">Profile</a>
                <a href="?action=logout" class="logout-option">Logout</a>
            </div>
        </div>
    <?php else: ?>
        <a href="auth/login.php" class="<?php echo ($_SERVER['PHP_SELF'] === '/auth/login.php') ? 'active' : ''; ?>">
            Login
        </a>
    <?php endif; ?>
</div>
