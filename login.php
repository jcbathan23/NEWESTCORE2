<?php
session_start();
include 'db.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT id, password, role FROM users WHERE username = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashedPassword, $role);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['login_time'] = time();

            if ($role === "admin") {
                header("Location: admin.php");
                exit();
            } elseif ($role === "provider") {
                header("Location: provider-dashboard.php");
                exit();
            } else {
                header("Location: user-dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that username.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SLATE System</title>
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  
  <style>
    /* Base Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* Modern Loading Screen */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, rgba(44, 62, 80, 0.95) 0%, rgba(52, 73, 94, 0.98) 100%);
      backdrop-filter: blur(20px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .loading-overlay.show {
      opacity: 1;
      visibility: visible;
    }

    .loading-container {
      text-align: center;
      position: relative;
    }

    .loading-logo {
      width: 80px;
      height: 80px;
      margin-bottom: 2rem;
      animation: logoFloat 3s ease-in-out infinite;
    }

    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 3px solid rgba(0, 198, 255, 0.2);
      border-top: 3px solid #00c6ff;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 1.5rem;
      position: relative;
    }

    .loading-spinner::before {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      border: 3px solid transparent;
      border-top: 3px solid rgba(0, 198, 255, 0.4);
      border-radius: 50%;
      animation: spin 1.5s linear infinite reverse;
    }

    .loading-text {
      font-size: 1.2rem;
      font-weight: 600;
      color: #00c6ff;
      margin-bottom: 0.5rem;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.3s forwards;
    }

    .loading-subtext {
      font-size: 0.9rem;
      color: #b0bec5;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.6s forwards;
    }

    .loading-progress {
      width: 200px;
      height: 4px;
      background: rgba(0, 198, 255, 0.2);
      border-radius: 2px;
      margin: 1rem auto 0;
      overflow: hidden;
      position: relative;
    }

    .loading-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #00c6ff, #0072ff);
      border-radius: 2px;
      width: 0%;
      animation: progressFill 2s ease-in-out infinite;
    }

    .loading-dots {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .loading-dot {
      width: 8px;
      height: 8px;
      background: #00c6ff;
      border-radius: 50%;
      animation: dotPulse 1.4s ease-in-out infinite both;
    }

    .loading-dot:nth-child(2) {
      animation-delay: 0.2s;
    }

    .loading-dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    /* Loading Animations */
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    @keyframes textFadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes progressFill {
      0% { width: 0%; }
      50% { width: 70%; }
      100% { width: 100%; }
    }

    @keyframes dotPulse {
      0%, 80%, 100% { 
        transform: scale(0.8);
        opacity: 0.5;
      }
      40% { 
        transform: scale(1);
        opacity: 1;
      }
    }

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
      color: white;
      line-height: 1.6;
    }

    /* Layout Components */
    .main-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    .login-container {
      width: 100%;
      max-width: 75rem;
      display: flex;
      background: rgba(31, 42, 56, 0.8);
      border-radius: 0.75rem;
      overflow: hidden;
      box-shadow: 0 0.625rem 1.875rem rgba(0, 0, 0, 0.3);
    }

    /* Welcome Panel */
    .welcome-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem;
      background: linear-gradient(135deg, rgba(0, 114, 255, 0.2), rgba(0, 198, 255, 0.2));
    }

    .welcome-panel h1 {
      font-size: 2.25rem;
      font-weight: 700;
      color: #ffffff;
      text-shadow: 0.125rem 0.125rem 0.5rem rgba(0, 0, 0, 0.6);
      text-align: center;
    }

    /* Login Panel */
    .login-panel {
      width: 25rem;
      padding: 3.75rem 2.5rem;
      background: rgba(22, 33, 49, 0.95);
    }

    .login-box {
      width: 100%;
      text-align: center;
    }

    .login-box img {
      width: 6.25rem;
      height: auto;
      margin-bottom: 1.25rem;
    }

    .login-box h2 {
      margin-bottom: 1.5625rem;
      color: #ffffff;
      font-size: 1.75rem;
    }

    /* Error Message */
    .error-message {
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid rgba(220, 53, 69, 0.5);
      border-radius: 0.375rem;
      padding: 0.75rem;
      margin-bottom: 1.25rem;
      color: #ff6b6b;
      font-size: 0.875rem;
      display: none;
    }

    .error-message.show {
      display: block;
    }

    /* Success Message */
    .success-message {
      background: rgba(40, 167, 69, 0.2);
      border: 1px solid rgba(40, 167, 69, 0.5);
      border-radius: 0.375rem;
      padding: 0.75rem;
      margin-bottom: 1.25rem;
      color: #28a745;
      font-size: 0.875rem;
      display: none;
      text-align: center;
      font-weight: 600;
    }

    .success-message.show {
      display: block;
    }

    /* Form Elements */
    .login-box form {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .login-box input {
      width: 100%;
      padding: 0.75rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 0.375rem;
      color: white;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .login-box input:focus {
      outline: none;
      border-color: #00c6ff;
      box-shadow: 0 0 0 0.125rem rgba(0, 198, 255, 0.2);
    }

    .login-box input::placeholder {
      color: rgba(160, 160, 160, 0.8);
    }

    .login-box button {
      padding: 0.75rem;
      background: linear-gradient(to right, #0072ff, #00c6ff);
      border: none;
      border-radius: 0.375rem;
      font-weight: 600;
      font-size: 1rem;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .login-box button:hover {
      background: linear-gradient(to right, #0052cc, #009ee3);
      transform: translateY(-0.125rem);
      box-shadow: 0 0.3125rem 0.9375rem rgba(0, 0, 0, 0.2);
    }

    .login-box button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* Login Link */
    .login-link {
      margin-top: 1.5rem;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }

    .login-link a {
      color: #00c6ff;
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    /* Loading spinner */
    .spinner {
      display: none;
      width: 1rem;
      height: 1rem;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top: 2px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 0.5rem;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Footer */
    footer {
      text-align: center;
      padding: 1.25rem;
      background: rgba(0, 0, 0, 0.2);
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.875rem;
    }

    /* Responsive Design */
    @media (max-width: 48rem) {
      .login-container {
        flex-direction: column;
      }

      .welcome-panel, 
      .login-panel {
        width: 100%;
      }

      .welcome-panel {
        padding: 1.875rem 1.25rem;
      }

      .welcome-panel h1 {
        font-size: 1.75rem;
      }

      .login-panel {
        padding: 2.5rem 1.25rem;
      }
    }

    @media (max-width: 30rem) {
      .main-container {
        padding: 1rem;
      }

      .welcome-panel h1 {
        font-size: 1.5rem;
      }

      .login-box h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Modern Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-container">
      <img src="slatelogo.png" alt="SLATE Logo" class="loading-logo">
      <div class="loading-spinner"></div>
      <div class="loading-text" id="loadingText">Loading...</div>
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your login</div>
      <div class="loading-progress">
        <div class="loading-progress-bar"></div>
      </div>
      <div class="loading-dots">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
      </div>
    </div>
  </div>

  <div class="main-container">
    <div class="login-container">
      <div class="welcome-panel">
        <h1>FREIGHT MANAGEMENT SYSTEM</h1>
      </div>

      <div class="login-panel">
        <div class="login-box">
          <img src="slatelogo.png" alt="SLATE Logo">
          <h2>SLATE Login</h2>
          
          <!-- Error Message -->
          <div id="errorMessage" class="error-message">
            <span id="errorText"></span>
          </div>
          
          <!-- Success Message -->
          <div id="successMessage" class="success-message">
            <span id="successText"></span>
          </div>
          
          <form id="loginForm" onsubmit="handleLogin(event)">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" id="username" placeholder="Username" required>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <button type="submit" id="loginButton">
              <span class="spinner" id="spinner"></span>
              <span id="buttonText">Log In</span>
            </button>
          </form>
          
          <div class="login-link">
            Don't have an account? <a href="register.php">Register here</a>
          </div>
          
          
        </div>
      </div>
    </div>
  </div>

  <footer>
    &copy; <span id="currentYear"></span> SLATE Freight Management System. All rights reserved.
  </footer>

  <script>
    // Show initial loading
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing Login...', 'Preparing SLATE system');
      
      // Hide loading after a short delay
      setTimeout(() => {
        hideLoading();
      }, 1500);
    });

    // Add current year to footer
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    
    // Show error message if present
    <?php if (!empty($error)): ?>
    document.getElementById('errorText').textContent = '<?php echo addslashes($error); ?>';
    document.getElementById('errorMessage').classList.add('show');
    <?php endif; ?>
    
    // Show success message if redirected from registration
    <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
    showSuccessMessage('Successful registration! You can now login with your new account.');
    <?php endif; ?>
    
    // Handle login form submission
    async function handleLogin(event) {
      event.preventDefault();
      
      const form = event.target;
      const button = document.getElementById('loginButton');
      const spinner = document.getElementById('spinner');
      const buttonText = document.getElementById('buttonText');
      const errorMessage = document.getElementById('errorMessage');
      const errorText = document.getElementById('errorText');
      
      // Get form data
      const formData = new FormData(form);
      
      // Show loading overlay
      showLoading('Authenticating...', 'Verifying your credentials');
      
      // Show loading state on button
      button.disabled = true;
      spinner.style.display = 'inline-block';
      buttonText.textContent = 'Logging in...';
      errorMessage.classList.remove('show');
      
      try {
        const response = await fetch('auth.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Success - show success loading then redirect
          showLoading('Login Successful!', 'Redirecting to dashboard...');
          setTimeout(() => {
            // Redirect based on user role
            if (result.role === 'admin') {
              window.location.href = 'admin.php';
            } else if (result.role === 'provider') {
              window.location.href = 'provider-dashboard.php';
            } else {
              window.location.href = 'user-dashboard.php';
            }
          }, 1500);
        } else {
          // Show error message
          hideLoading();
          errorText.textContent = result.message || 'Login failed. Please try again.';
          errorMessage.classList.add('show');
          
          // Clear password field
          document.getElementById('password').value = '';
          document.getElementById('password').focus();
        }
      } catch (error) {
        // Network error
        hideLoading();
        errorText.textContent = 'Network error. Please check your connection and try again.';
        errorMessage.classList.add('show');
      } finally {
        // Reset button state
        button.disabled = false;
        spinner.style.display = 'none';
        buttonText.textContent = 'Log In';
      }
    }
    
    // Clear error message when user starts typing
    document.getElementById('username').addEventListener('input', function() {
      document.getElementById('errorMessage').classList.remove('show');
    });
    
    document.getElementById('password').addEventListener('input', function() {
      document.getElementById('errorMessage').classList.remove('show');
    });
    
    // Function to show success message
    function showSuccessMessage(message) {
      const successMessage = document.getElementById('successMessage');
      const successText = document.getElementById('successText');
      successText.textContent = message;
      successMessage.classList.add('show');
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        successMessage.classList.remove('show');
      }, 5000);
    }

    // Loading Utility Functions
    function showLoading(text = 'Loading...', subtext = 'Please wait') {
      const overlay = document.getElementById('loadingOverlay');
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) loadingText.textContent = text;
      if (loadingSubtext) loadingSubtext.textContent = subtext;
      
      overlay.classList.add('show');
    }

    function hideLoading() {
      const overlay = document.getElementById('loadingOverlay');
      overlay.classList.remove('show');
    }
  </script>
</body>
</html>