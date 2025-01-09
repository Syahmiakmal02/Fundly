<?php
session_start();
include 'layouts/header.php';
include 'auth/db_config.php';
// Add this after session_start()
function checkAuth()
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['error'] = "Please login to access this feature";
        header("Location: views/login.php");
        exit();
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

//function check name to dynamically change icon
function checkName($name)
{
    if ($name == 'dashboard') {
        return '<i class="fas fa-home"></i>';
    } elseif ($name == 'budget') {
        return '<i class="fas fa-wallet"></i>';
    } elseif ($name == 'expenses') {
        return '<i class="fas fa-receipt"></i>';
    } elseif ($name == 'saving') {
        return '<i class="fas fa-piggy-bank"></i>';
    } elseif ($name == 'profile') {
        return '<i class="fas fa-user-circle"></i>';
    } elseif (empty($name)) {
        return '';
    }
    return $name;
}

//get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'default';

?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css">

    <?php
    switch ($current_page) {
        case 'expenses':
            echo '<link rel="stylesheet" href="css/expenses.css">';
            break;
        case 'budget':
            echo '<link rel="stylesheet" href="css/budget.css">';
            break;
        case 'dashboard':
            echo '<link rel="stylesheet" href="css/dashboard.css">';
            break;
        case 'profile':
            echo '<link rel="stylesheet" href="css/profile.css">';
            break;
        case 'saving':
            echo '<link rel="stylesheet" href="css/saving.css">';
            break;
    }
    ?>

    <link rel="shortcut icon" href="/imgs/logo.png" type="image/x-icon">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title><?php echo htmlspecialchars($website_name); ?></title>
</head>

<body>
    <div class="card">
        <div class="header">
            <h1> 
                <?php
                    $icon = checkName($page);
                    echo $icon . ' ' . htmlspecialchars($website_name);
                ?>
            </h1>
            <p><?php echo htmlspecialchars($website_desc); ?></p>
        </div>
        <div class="nav-wrapper">
            <?php include 'layouts/top_nav.php'; ?>
        </div>

        <div id="content">
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

            switch ($page) {
                case 'dashboard':
                    include 'views/dashboard.php';
                    break;
                case 'budget':
                    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                        $_SESSION['error'] = "Please login to access Your Budget";
                        header("Location: auth/login.php");
                        exit();
                    }
                    include 'views/budget.php';
                    break;
                case 'expenses':
                    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                        $_SESSION['error'] = "Please login to access Your Expenses";
                        header("Location: auth/login.php");
                        exit();
                    }
                    include 'views/expenses.php';
                    break;
                case 'saving':
                    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                        $_SESSION['error'] = "Please login to access Your Saving";
                        header("Location: auth/login.php");
                        exit();
                    }
                    include 'views/saving.php';
                    break;
                default:
                    include 'views/dashboard.php';
                    break;
            }
            ?>
        </div>
        <?php include 'layouts/footer.php'; ?>
</body>

</html>