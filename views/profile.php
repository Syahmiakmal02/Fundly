<?php
session_start();
// Assuming you have user authentication in place
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
require_once '../auth/db_config.php';

function getCurrentUserData($conn, $userId)
{
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    return $userData;
}

function updateProfile($conn) {
    $errors = [];
    $success = false;
    $userId = $_SESSION['user_id'];

    // Get current user data
    $currentData = getCurrentUserData($conn, $userId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? $currentData['name']);
        $email = trim($_POST['email'] ?? $currentData['email']);
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Validate name
        if ($name !== $currentData['name'] && (empty($name) || strlen($name) > 100)) {
            $errors['name'] = "Name must be between 1 and 100 characters.";
        }

        // Validate email
        if ($email !== $currentData['email']) {
            if (empty($email)) {
                $errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid email format.";
            } else {
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->bind_param("si", $email, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $errors['email'] = "Email is already in use.";
                }
                $stmt->close();
            }
        }

        // Validate new password
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $errors['new_password'] = "Password must be at least 8 characters.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = "Passwords do not match.";
            }
        }

        // Proceed if no errors
        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                // Update name and email
                if ($name !== $currentData['name'] || $email !== $currentData['email']) {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
                    $stmt->bind_param("ssi", $name, $email, $userId);
                    $stmt->execute();
                }

                // Update password if provided
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashedPassword, $userId);
                    $stmt->execute();
                }

                $conn->commit();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['flash_message'] = "Profile updated successfully!";
                $_SESSION['flash_type'] = "success";

                header("Location: profile.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $errors['general'] = "An error occurred. Please try again.";
                error_log("Profile Update Error: " . $e->getMessage());
            }
        }
    }

    return [
        'errors' => $errors,
        'currentData' => $currentData
    ];
}

// Get the result and current user data
$result = updateProfile($conn);
$userData = $result['currentData'];

// Display flash messages if they exist
if (isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    $flashType = $_SESSION['flash_type'];
    // Clear the flash message
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Edit Your Information</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/landingPage.css">
    <link rel="stylesheet" href="/css/profile.css">
    <link rel="shortcut icon" href="/imgs/logo.png" type="image/x-icon">
</head>

<body>
    <nav class="landing-nav">
        <div class="nav-content">
            <a class="logo">Fundly</a>
            <div class="nav-links">
                <!-- <button id="theme-toggle">🌙</button> -->
                <a href="/" class="secondary-btn"> Back</a>
            </div>
        </div>
    </nav>

    <main class="profile-container">
        <div class="profile-card">
            <?php if (isset($flashMessage)): ?>
                <div class="alert alert-<?php echo $flashType; ?>">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($result['errors']['general'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($result['errors']['general']); ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="/imgs/profile.png" alt="Profile picture" />
                </div>
                <h1>Edit Profile</h1>
            </div>

            <form class="profile-form" method="POST" action="">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your name"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? $userData['name']); ?>">
                    <?php if (!empty($result['errors']['name'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($result['errors']['name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? $userData['email']); ?>">
                    <?php if (!empty($result['errors']['email'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($result['errors']['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password (optional)</label>
                    <input type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Enter new password">
                    <?php if (!empty($result['errors']['new_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($result['errors']['new_password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password">
                    <?php if (!empty($result['errors']['confirm_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($result['errors']['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-btn">Save Changes</button>
                    <a href="/" class="secondary-btn">Cancel</a>
                </div>
            </form>

        </div>

    </main>

</body>

</html>