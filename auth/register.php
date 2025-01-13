<?php
session_start();
require_once('../auth/db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: register.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $check_email_sql = "SELECT email FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $email_result = $check_email_stmt->get_result();

    if ($email_result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists!";
        header("Location: register.php");
        exit();
    }

    // Insert new user with default role as 'admin'
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please log in to continue";
        } else {
            $_SESSION['error'] = "Registration failed!";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error!";
    }
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../css/auth.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="shortcut icon" href="/imgs/logo.png" type="image/x-icon">

</head>

<body>
    <div class="card">
        <div class="login-container">
            <div class="login-header">
                <h2>Register Account</h2>
            </div>

            <form action="register.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="login-button">Register</button>
                <div class="register-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
            <hr>
            <a href="../index.php" class="back-link">Back to Home</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show error message if it exists
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error']; ?>'
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Show success message and redirect
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $_SESSION['success']; ?>'
                }).then((result) => {
                    if (result.isConfirmed || result.isDismissed) {
                        window.location.href = '../auth/login.php';
                    }
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>
