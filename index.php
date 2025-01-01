<?php

include 'layouts/header.php';
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet"  href="/css/styles.css">

</head>

<body>
    <div class="card">
        <div class="header">
            <h1><?php echo htmlspecialchars($website_name); ?></h1>
            <p><?php echo htmlspecialchars($website_desc); ?></p>
        </div>
        <div class="topnav">
            <a href="?page=dashboard">Dashboard</a>
            <a href="?page=budget">Your Budget</a>
            <a href="?page=expenses">Your Expenses</a>
            <a href="?page=saving">Saving</a>
            <a href="#" style="float:right">Log in</a>
        </div>

        <div id="content">
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

            switch ($page) {
                case 'dashboard':
                    include 'views/dashboard.php';
                    break;
                case 'budget':
                    include 'views/budget.php';
                    break;
                case 'expenses':
                    include 'views/expenses.php';
                    break;
                case 'saving':
                    include 'views/saving.php';
                    break;
                default:
                    include 'views/dashboard.php';
                    break;
            }
            ?>
        </div>
        <?php include 'layouts/footer.php'; ?>
        <script src="script.js" defer></script>
</body>

</html>