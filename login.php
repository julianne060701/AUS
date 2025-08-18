<?php
session_start();
include 'config/conn.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: dashboard/index.php");
        exit();
    } elseif ($_SESSION['role'] == 'employee') {
        header("Location: employee/index.php");
        exit();
    }
}

$login_success = false;
$logout_message = '';

// Check for logout message
if (isset($_SESSION['logout_message'])) {
    $logout_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']); // Remove it after displaying
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Set session variables - FIXED: Use consistent session variable names
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id'] = $user['id'];  // Added this for consistency with topbar.php
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Set login success flag for animation
            $login_success = true;
            
            // Redirect will happen via JavaScript after animation
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Rice Inventory - Login</title>
<link rel="stylesheet" href="css/login.css" />
<style>
/* Welcome Animation Styles */
.welcome-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.welcome-overlay.show {
    opacity: 1;
    visibility: visible;
}

.welcome-content {
    text-align: center;
    color: white;
    transform: translateY(50px);
    transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.welcome-overlay.show .welcome-content {
    transform: translateY(0);
}

.welcome-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    animation: bounceIn 1s ease-out 0.3s both;
}

.welcome-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    opacity: 0;
    animation: slideUp 0.8s ease-out 0.6s both;
}

.welcome-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    opacity: 0;
    animation: slideUp 0.8s ease-out 0.9s both;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
    opacity: 0;
    animation: fadeIn 0.5s ease-out 1.2s both, spin 1s linear 1.2s infinite;
}

.redirect-text {
    margin-top: 1rem;
    font-size: 0.9rem;
    opacity: 0;
    animation: fadeIn 0.5s ease-out 1.5s both;
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes slideUp {
    0% {
        transform: translateY(30px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeIn {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Loading state for form */
.login-container.loading {
    opacity: 0.7;
    pointer-events: none;
}

.login-btn.loading {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: scale(0.98);
}
</style>
</head>
<body>
<!-- Welcome Animation Overlay -->
<div class="welcome-overlay" id="welcomeOverlay">
    <div class="welcome-content">
        <div class="welcome-icon">🎉</div>
        <h1 class="welcome-title">Welcome!</h1>
        <p class="welcome-subtitle">Login Successful</p>
        <div class="loading-spinner"></div>
        <p class="redirect-text">Redirecting to dashboard...</p>
    </div>
</div>

<div class="login-container">
    <div class="logo-section">
        <div class="logo"></div>
        <h1 class="title">Rice Inventory</h1>
        <p class="subtitle">Management System</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($logout_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($logout_message); ?></div>
    <?php endif; ?>

    <form method="post" action="" id="loginForm">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required autocomplete="username" />
            <span class="input-icon">👤</span>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password" />
            <span class="input-icon">🔒</span>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
    </form>

    <div class="forgot-password">
        <a href="#" onclick="alert('Please contact your administrator for password recovery.')">Forgot Password?</a>
    </div>
</div>

<script>
// Enhanced form interactions
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const form = this;
    const btn = document.getElementById('loginBtn');
    const container = document.querySelector('.login-container');
    
    // Add loading state
    container.classList.add('loading');
    btn.classList.add('loading');
    btn.innerHTML = 'Signing In...';
    
    // Remove loading state after a delay (if form validation fails)
    setTimeout(() => {
        if (!<?php echo $login_success ? 'true' : 'false'; ?>) {
            container.classList.remove('loading');
            btn.classList.remove('loading');
            btn.innerHTML = 'Sign In';
        }
    }, 3000);
});

// Input focus animations
const inputs = document.querySelectorAll('input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// Welcome animation function
function showWelcomeAnimation() {
    const overlay = document.getElementById('welcomeOverlay');
    if (overlay) {
        overlay.classList.add('show');
    }
    
    // Redirect after animation completes
    setTimeout(() => {
        <?php if ($login_success): ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                window.location.href = 'dashboard/index.php';
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'employee'): ?>
                window.location.href = 'employee/index.php';
            <?php endif; ?>
        <?php endif; ?>
    }, 3500); // 3.5 seconds to show the full animation
}

// Check if login was successful
<?php if ($login_success): ?>
    // Show welcome animation after a short delay
    setTimeout(showWelcomeAnimation, 800);
<?php endif; ?>

// Keyboard accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && document.activeElement.tagName !== 'BUTTON') {
        document.getElementById('loginBtn').focus();
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
</body>
</html>