<?php
session_start();
include('../auth/db_config.php');

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Prepare statement to prevent SQL injection
    $sql = "SELECT email, name, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($result->num_rows > 0 && password_verify($password, $user['password'])) {
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['success'] = "Hello, " . $user['name']; // Store success message
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Database error";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/auth.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="card">
        <div class="login-container">
            <div class="login-header">
                <h2>Login</h2>
            </div>
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-button">Login</button>
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
            <hr>
            <a href="../index.php" class="back-link">Back</a>
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
                    title: '<?php echo $_SESSION['success']; ?>',
                    text: 'You are now logged in',
                }).then((result) => {
                    if (result.isConfirmed || result.isDismissed) {
                        window.location.href = '../index.php'; // Redirect after confirmation
                    }
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>