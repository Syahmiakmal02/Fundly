<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Fundly - Smart Financial Management for Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/landingPage.css">
    <link rel="shortcut icon" href="/imgs/logo.png" type="image/x-icon">
</head>
<body>
    <nav class="landing-nav">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-wallet"></i>
                <span>Fundly</span>
            </div>
            <div class="nav-links">
                <a href="auth/login.php" class="login-btn">Login</a>
                <a href="auth/register.php" class="register-btn">Get Started</a>
            </div>
        </div>
    </nav>

    <main class="hero-section">
        <div class="hero-content">
            <h1>Take Control of Your Student Finances</h1>
            <p>Track expenses, set budgets, and achieve your savings goals with Fundly - your personal financial companion.</p>
            <div class="cta-buttons">
                <a href="auth/register.php" class="primary-btn">Start For Free</a>
                <a href="#features" class="secondary-btn">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <!-- <img src="imgs/hero-illustration.svg" alt="Financial Management Illustration"> -->
        </div>
    </main>

    <section id="features" class="features-section">
        <h2>Why Choose Fundly?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-receipt"></i>
                <h3>Expense Tracking</h3>
                <p>Easily track your daily expenses and categorize your spending.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-wallet"></i>
                <h3>Budget Management</h3>
                <p>Set and manage budgets to keep your spending in check.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-piggy-bank"></i>
                <h3>Savings Goals</h3>
                <p>Set savings targets and track your progress over time.</p>
            </div>
        </div>
    </section>

    <footer class="landing-footer">
        <div class="footer-content">
            <p>&copy; 2025 Fundly. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>
</body>
</html>