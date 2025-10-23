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
<title>AUS Inventory Management</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

/* Animated background elements */
body::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
        radial-gradient(circle at 20% 30%, rgba(135, 206, 235, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(173, 216, 230, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(176, 224, 230, 0.2) 0%, transparent 50%);
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-20px) rotate(1deg); }
    66% { transform: translateY(10px) rotate(-1deg); }
}

.login-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 40px;
    width: 100%;
    max-width: 420px;
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.15),
        0 0 0 1px rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 10;
    transition: all 0.3s ease;
}

.login-container:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 35px 60px rgba(0, 0, 0, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.3);
}

.logo-section {
    text-align: center;
    margin-bottom: 40px;
}

/* .logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    position: relative;
    box-shadow: 0 10px 30px rgba(0, 188, 212, 0.3);
    animation: pulse 2s ease-in-out infinite;
} */

/* .logo::before {
    content: '❄️';
    position: absolute;
} */

/* .logo::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid rgba(0, 188, 212, 0.3);
    border-radius: 50%;
    animation: ripple 2s ease-out infinite;
} */

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.4);
        opacity: 0;
    }
}

.title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.subtitle {
    color: #7f8c8d;
    font-size: 1rem;
    font-weight: 400;
}

.form-group {
    position: relative;
    margin-bottom: 25px;
    transition: transform 0.2s ease;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #34495e;
    font-weight: 600;
    font-size: 0.9rem;
}

.form-group input {
    width: 100%;
    padding: 16px 50px 16px 20px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.form-group input:focus {
    outline: none;
    border-color: #00bcd4;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
    transform: translateY(-2px);
}

.input-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    color: #00bcd4;
    pointer-events: none;
    margin-top: 12px;
}

.login-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
    position: relative;
    overflow: hidden;
}

.login-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 188, 212, 0.4);
}

.login-btn:hover::before {
    left: 100%;
}

.login-btn:active {
    transform: translateY(0);
}

.forgot-password {
    text-align: center;
    margin-top: 25px;
}

.forgot-password a {
    color: #00bcd4;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.forgot-password a:hover {
    color: #0097a7;
    text-decoration: underline;
}

.error-message, .success-message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.error-message {
    background: rgba(244, 67, 54, 0.1);
    color: #d32f2f;
    border: 1px solid rgba(244, 67, 54, 0.2);
}

.success-message {
    background: rgba(76, 175, 80, 0.1);
    color: #388e3c;
    border: 1px solid rgba(76, 175, 80, 0.2);
}

/* Welcome Animation Styles */
.welcome-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
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
    background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
    color: white;
    transform: scale(0.98);
}

/* Responsive design */
@media (max-width: 480px) {
    .login-container {
        padding: 30px 25px;
        margin: 10px;
    }
    
    .title {
        font-size: 1.8rem;
    }
    
    .logo {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
}

/* Temperature indicator decoration */
.temp-indicator {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(0, 188, 212, 0.1);
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #0097a7;
    font-weight: 600;
}

.temp-indicator::before {
    content: '🌡️ ';
    margin-right: 4px;
}
</style>
</head>
<body>
<!-- Welcome Animation Overlay -->
<div class="welcome-overlay" id="welcomeOverlay">
    <div class="welcome-content">
        <div class="welcome-icon">🌨️</div>
        <h1 class="welcome-title">Welcome!</h1>
        <p class="welcome-subtitle">Access Granted - System Online</p>
        <div class="loading-spinner"></div>
        <!-- <p class="redirect-text">Initializing climate control dashboard...</p> -->
    </div>
</div>

<div class="login-container">
  
    
    <div class="logo-section">
        <div class="logo"></div>
        <img src="img/logo.jpg" alt="AUS Logo" style="width: 40px; height: 40px;">
        <h1 class="title">Login</h1>
        <p class="subtitle">AUS Inventory System</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($logout_message)): ?>
        <div class="success-message">✅ <?php echo htmlspecialchars($logout_message); ?></div>
    <?php endif; ?>

    <form method="post" action="" id="loginForm">
        <div class="form-group">
            <label for="username">User Name</label>
            <input type="text" name="username" id="username" required autocomplete="username" placeholder="Enter your ID" />
            <span class="input-icon">👤</span>
        </div>

        <div class="form-group">
            <label for="password">Password</label></label>
            <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter access code" />
            <span class="input-icon">🔐</span>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
            <span>Login</span>
        </button>
    </form>

    <div class="forgot-password">
        <a href="#" onclick="alert('Please contact your system administrator for access code recovery.')">
            🔧 Forgot Access Code?
        </a>
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
    btn.innerHTML = '<span>🔄 Authenticating...</span>';
    
    // Remove loading state after a delay (if form validation fails)
    setTimeout(() => {
        if (!<?php echo $login_success ? 'true' : 'false'; ?>) {
            container.classList.remove('loading');
            btn.classList.remove('loading');
            btn.innerHTML = '<span>🌡️ Access System</span>';
        }
    }, 3000);
});

// Input focus animations
const inputs = document.querySelectorAll('input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
        this.parentElement.style.zIndex = '5';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
        this.parentElement.style.zIndex = '1';
    });
    
    // Add typing effect
    input.addEventListener('input', function() {
        const icon = this.nextElementSibling;
        if (icon) {
            icon.style.transform = 'translateY(-50%) scale(1.1)';
            setTimeout(() => {
                icon.style.transform = 'translateY(-50%) scale(1)';
            }, 150);
        }
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
    <?php if (isset($_SESSION['role'])): ?>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            window.location.href = 'dashboard/index.php';
        <?php elseif ($_SESSION['role'] == 'employee'): ?>
            window.location.href = 'employee/index.php';
        <?php elseif ($_SESSION['role'] == 'installer'): ?>
            window.location.href = 'installer/installer_dashboard.php';
        <?php endif; ?>
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

// Add some interactive climate effects
function createSnowflake() {
    const snowflake = document.createElement('div');
    snowflake.innerHTML = '❄️';
    snowflake.style.position = 'fixed';
    snowflake.style.top = '-20px';
    snowflake.style.left = Math.random() * 100 + 'vw';
    snowflake.style.fontSize = (Math.random() * 10 + 10) + 'px';
    snowflake.style.opacity = '0.7';
    snowflake.style.pointerEvents = 'none';
    snowflake.style.zIndex = '1';
    snowflake.style.animation = `fall ${Math.random() * 3 + 2}s linear infinite`;
    
    document.body.appendChild(snowflake);
    
    setTimeout(() => {
        snowflake.remove();
    }, 5000);
}

// Add CSS for falling animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fall {
        to {
            transform: translateY(100vh) rotate(360deg);
        }
    }
`;
document.head.appendChild(style);

// Create snowflakes periodically
setInterval(createSnowflake, 3000);

// Temperature update simulation
let currentTemp = 22;
function updateTemperature() {
    const tempElement = document.querySelector('.temp-indicator');
    currentTemp += (Math.random() - 0.5) * 0.2;
    currentTemp = Math.max(18, Math.min(26, currentTemp));
    if (tempElement) {
        tempElement.textContent = `${Math.round(currentTemp * 10) / 10}°C Optimal`;
    }
}

setInterval(updateTemperature, 5000);
</script>
</body>
</html>