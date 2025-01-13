<?php
session_start();
// Check if user is visiting for the first time or not logged in
if (!isset($_GET['page']) && (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)) {
    include 'views/landingPage.php';
    exit();
}

include 'layouts/header.php';
include 'auth/db_config.php';

// Add this after session_start()
function checkAuth() {
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
function checkName($name) {
    $icons = [
        'dashboard' => '<i class="fas fa-home"></i>',
        'budget' => '<i class="fas fa-wallet"></i>',
        'expenses' => '<i class="fas fa-receipt"></i>',
        'saving' => '<i class="fas fa-piggy-bank"></i>',
        'profile' => '<i class="fas fa-user-circle"></i>'
    ];
    
    return isset($icons[$name]) ? $icons[$name] : $name;
}

//get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <!-- Load Chart.js from CDN with specific version -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <!-- Load SweetAlert with specific version -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.css">

    <!-- Load Bootstrap5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>    


    <?php
    // Page specific CSS
    switch ($current_page) {
        case 'dashboard':
        default:
            echo '<link rel="stylesheet" href="css/dashboard.css">';
            break;
        case 'budget':
            echo '<link rel="stylesheet" href="css/budget.css">';
            break;
        case 'expenses':
            echo '<link rel="stylesheet" href="css/expenses.css">';
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
    <title><?php echo htmlspecialchars($website_name); ?></title>

    <!-- Prevent CORS and quota issues -->
    <script>
        // Disable any automatic API calls
        window.addEventListener('load', function() {
            // Configure Chart.js defaults
            if (typeof Chart !== 'undefined') {
                Chart.defaults.font.family = "'Poppins', sans-serif";
                Chart.defaults.responsive = true;
                Chart.defaults.maintainAspectRatio = false;
            }
        });
    </script>
</head>

<body>
    <div class="card">
        <div class="header">
            <h1>
                <?php
                $icon = checkName($page);
                echo $icon . ' ' . $website_name;
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
                    checkAuth();
                    include 'views/budget.php';
                    break;
                case 'expenses':
                    checkAuth();
                    include 'views/expenses.php';
                    break;
                case 'saving':
                    checkAuth();
                    include 'views/saving.php';
                    break;
                default:
                    include 'views/dashboard.php';
                    break;
            }
            ?>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</body>
</html>