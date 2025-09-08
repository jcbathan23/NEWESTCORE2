<?php
session_start();
require_once 'security.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } elseif ($_SESSION['role'] === 'provider') {
        header('Location: provider-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit();
}

// Get error message if any
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'registration_failed':
            $error_message = 'Registration failed. Please try again.';
            break;
        case 'username_exists':
            $error_message = 'Username already exists. Please choose another.';
            break;
        case 'email_exists':
            $error_message = 'Email already exists. Please use another email.';
            break;
        default:
            $error_message = 'An error occurred. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SLATE System</title>
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

    .register-container {
      width: 100%;
      max-width: 90rem;
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

    /* Register Panel */
    .register-panel {
      width: 35rem;
      padding: 3.75rem 2.5rem;
      background: rgba(22, 33, 49, 0.95);
      overflow-y: auto;
      max-height: 90vh;
    }

    .register-box {
      width: 100%;
      text-align: center;
    }

    .register-box img {
      width: 6.25rem;
      height: auto;
      margin-bottom: 1.25rem;
    }

    .register-box h2 {
      margin-bottom: 1.5625rem;
      color: #ffffff;
      font-size: 1.75rem;
    }

    /* Role Selection */
    .role-selector {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      justify-content: center;
    }

    .role-option {
      padding: 0.75rem 1.5rem;
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 0.5rem;
      background: rgba(255, 255, 255, 0.05);
      color: rgba(255, 255, 255, 0.7);
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .role-option:hover {
      border-color: rgba(0, 198, 255, 0.5);
      background: rgba(0, 198, 255, 0.1);
      color: white;
    }

    .role-option.active {
      border-color: #00c6ff;
      background: rgba(0, 198, 255, 0.2);
      color: white;
    }

    /* Form Elements */
    .form-section {
      display: none;
      animation: fadeIn 0.5s ease-in-out;
    }

    .form-section.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .form-group {
      margin-bottom: 1.25rem;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      font-size: 0.9rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 0.375rem;
      color: white;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #00c6ff;
      box-shadow: 0 0 0 0.125rem rgba(0, 198, 255, 0.2);
    }

    .form-group input::placeholder {
      color: rgba(160, 160, 160, 0.8);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .register-box button {
      width: 100%;
      padding: 0.75rem;
      background: linear-gradient(to right, #0072ff, #00c6ff);
      border: none;
      border-radius: 0.375rem;
      font-weight: 600;
      font-size: 1rem;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 1rem;
    }

    .register-box button:hover {
      background: linear-gradient(to right, #0052cc, #009ee3);
      transform: translateY(-0.125rem);
      box-shadow: 0 0.3125rem 0.9375rem rgba(0, 0, 0, 0.2);
    }

    .register-box button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
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
      padding: 1rem;
      margin-bottom: 1.25rem;
      color: #28a745;
      font-size: 1rem;
      font-weight: 600;
      display: none;
      text-align: center;
      animation: successPulse 2s ease-in-out infinite;
    }

    .success-message.show {
      display: block;
    }

    @keyframes successPulse {
      0%, 100% { 
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
      }
      50% { 
        transform: scale(1.02);
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
      }
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
      .register-container {
        flex-direction: column;
      }

      .welcome-panel, 
      .register-panel {
        width: 100%;
      }

      .welcome-panel {
        padding: 1.875rem 1.25rem;
      }

      .welcome-panel h1 {
        font-size: 1.75rem;
      }

      .register-panel {
        padding: 2.5rem 1.25rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 30rem) {
      .main-container {
        padding: 1rem;
      }

      .welcome-panel h1 {
        font-size: 1.5rem;
      }

      .register-box h2 {
        font-size: 1.5rem;
      }

      .role-selector {
        flex-direction: column;
        align-items: center;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your registration</div>
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
    <div class="register-container">
      <div class="welcome-panel">
        <h1>FREIGHT MANAGEMENT SYSTEM</h1>
      </div>

      <div class="register-panel">
        <div class="register-box">
          <img src="slatelogo.png" alt="SLATE Logo">
          <h2>SLATE Registration</h2>
          
          <!-- Error Message -->
          <div id="errorMessage" class="error-message">
            <span id="errorText"></span>
          </div>

          <!-- Success Message -->
          <div id="successMessage" class="success-message">
            <span id="successText"></span>
          </div>
          
          <!-- Role Selection -->
          <div class="role-selector">
            <div class="role-option active" data-role="user">Regular User</div>
            <div class="role-option" data-role="provider">Service Provider</div>
          </div>
          
          <!-- Regular User Registration Form -->
          <form id="userForm" class="form-section active" onsubmit="handleRegistration(event, 'user')">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="user">
            
            <div class="form-row">
              <div class="form-group">
                <label for="userUsername">Username</label>
                <input type="text" name="username" id="userUsername" placeholder="Choose username" required>
              </div>
              <div class="form-group">
                <label for="userEmail">Email</label>
                <input type="email" name="email" id="userEmail" placeholder="Enter email" required>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="userPassword">Password</label>
                <input type="password" name="password" id="userPassword" placeholder="Create password" required>
              </div>
              <div class="form-group">
                <label for="userConfirmPassword">Confirm Password</label>
                <input type="password" name="confirm_password" id="userConfirmPassword" placeholder="Confirm password" required>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="userFirstName">First Name</label>
                <input type="text" name="first_name" id="userFirstName" placeholder="First name" required>
              </div>
              <div class="form-group">
                <label for="userLastName">Last Name</label>
                <input type="text" name="last_name" id="userLastName" placeholder="Last name" required>
              </div>
            </div>
            
            <div class="form-group">
              <label for="userPhone">Phone Number</label>
              <input type="tel" name="phone" id="userPhone" placeholder="Phone number" required>
            </div>
            
            <div class="form-group">
              <label for="userCompany">Company (Optional)</label>
              <input type="text" name="company" id="userCompany" placeholder="Company name">
            </div>
            
            <button type="submit" id="userSubmitButton">
              <span class="spinner" id="userSpinner"></span>
              <span id="userButtonText">Register as User</span>
            </button>
          </form>
          
          <!-- Service Provider Registration Form -->
          <form id="providerForm" class="form-section" onsubmit="handleRegistration(event, 'provider')">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="provider">
            
            <div class="form-row">
              <div class="form-group">
                <label for="providerUsername">Username</label>
                <input type="text" name="username" id="providerUsername" placeholder="Choose username" required>
              </div>
              <div class="form-group">
                <label for="providerEmail">Email</label>
                <input type="email" name="email" id="providerEmail" placeholder="Enter email" required>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="providerPassword">Password</label>
                <input type="password" name="password" id="providerPassword" placeholder="Create password" required>
              </div>
              <div class="form-group">
                <label for="providerConfirmPassword">Confirm Password</label>
                <input type="password" name="confirm_password" id="providerConfirmPassword" placeholder="Confirm password" required>
              </div>
            </div>
            
            <div class="form-group">
              <label for="providerCompanyName">Company Name</label>
              <input type="text" name="company_name" id="providerCompanyName" placeholder="Company name" required>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="providerContactPerson">Contact Person</label>
                <input type="text" name="contact_person" id="providerContactPerson" placeholder="Contact person name" required>
              </div>
              <div class="form-group">
                <label for="providerContactPhone">Contact Phone</label>
                <input type="tel" name="contact_phone" id="providerContactPhone" placeholder="Contact phone" required>
              </div>
            </div>
            
            <div class="form-group">
              <label for="providerServiceArea">Service Area</label>
              <input type="text" name="service_area" id="providerServiceArea" placeholder="Service area coverage" required>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="providerServiceType">Service Type</label>
                <select name="service_type" id="providerServiceType" required>
                  <option value="">Select service type</option>
                  <option value="Freight">Freight</option>
                  <option value="Express">Express</option>
                  <option value="Warehouse">Warehouse</option>
                  <option value="Logistics">Logistics</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label for="providerMonthlyRate">Monthly Rate (₱)</label>
                <input type="number" name="monthly_rate" id="providerMonthlyRate" placeholder="0.00" step="0.01" min="0" required>
              </div>
            </div>
            
            <div class="form-group">
              <label for="providerNotes">Additional Notes</label>
              <textarea name="notes" id="providerNotes" placeholder="Additional information about your services" rows="3"></textarea>
            </div>
            
            <button type="submit" id="providerSubmitButton">
              <span class="spinner" id="providerSpinner"></span>
              <span id="providerButtonText">Register as Provider</span>
            </button>
          </form>
          
          <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
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
      showLoading('Initializing Registration...', 'Preparing SLATE system');
      
      // Test database connection first
      testDatabaseConnection();
      
      // Hide loading after a short delay
      setTimeout(() => {
        hideLoading();
      }, 1500);
    });
    
    // Test database connection
    async function testDatabaseConnection() {
      try {
        const response = await fetch('auth.php?test_db=1');
        const result = await response.json();
        console.log('Database connection test:', result);
        
        if (!result.success) {
          console.error('Database connection failed:', result.message);
          showError('Database connection issue detected. Please contact administrator.');
        }
      } catch (error) {
        console.error('Database test failed:', error);
      }
    }

    // Add current year to footer
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    
    // Show error message if present
    <?php if (!empty($error_message)): ?>
    document.getElementById('errorText').textContent = '<?php echo addslashes($error_message); ?>';
    document.getElementById('errorMessage').classList.add('show');
    <?php endif; ?>
    
    // Role selection functionality
    const roleOptions = document.querySelectorAll('.role-option');
    const userForm = document.getElementById('userForm');
    const providerForm = document.getElementById('providerForm');
    
    roleOptions.forEach(option => {
      option.addEventListener('click', function() {
        const role = this.dataset.role;
        
        // Update active state
        roleOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        
        // Show appropriate form
        if (role === 'user') {
          userForm.classList.add('active');
          providerForm.classList.remove('active');
        } else {
          providerForm.classList.add('active');
          userForm.classList.remove('active');
        }
      });
    });
    
    // Handle registration form submission
    async function handleRegistration(event, role) {
      event.preventDefault();
      
      const form = event.target;
      const button = role === 'user' ? document.getElementById('userSubmitButton') : document.getElementById('providerSubmitButton');
      const spinner = role === 'user' ? document.getElementById('userSpinner') : document.getElementById('providerSpinner');
      const buttonText = role === 'user' ? document.getElementById('userButtonText') : document.getElementById('providerButtonText');
      const errorMessage = document.getElementById('errorMessage');
      const successMessage = document.getElementById('successMessage');
      const errorText = document.getElementById('errorText');
      const successText = document.getElementById('successText');
      
      // Get form data
      const formData = new FormData(form);
      
      // Debug: Log form data
      console.log('Form data being sent:');
      for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
      }
      
      // Validate passwords match
      const password = formData.get('password');
      const confirmPassword = formData.get('confirm_password');
      
      if (password !== confirmPassword) {
        showError('Passwords do not match');
        return;
      }
      
      // Validate password length
      if (password.length < 6) {
        showError('Password must be at least 6 characters long');
        return;
      }
      
      // Show loading overlay
      showLoading('Processing Registration...', 'Creating your account');
      
      // Show loading state on button
      button.disabled = true;
      spinner.style.display = 'inline-block';
      buttonText.textContent = 'Registering...';
      hideMessages();
      
      try {
        console.log('Sending registration request...');
        const response = await fetch('api/register.php', {
          method: 'POST',
          body: formData
        });
        
        console.log('Response status:', response.status);
        const result = await response.json();
        
        console.log('Registration response:', result);
        
        if (result.success) {
          // Success - show success message
          hideLoading();
          // Use the message from backend, or fallback to default
          const successMsg = result.message || 'Successful registration!';
          showSuccess(successMsg + ' Redirecting to login in 3 seconds...');
          
          // Redirect to login after 3 seconds to ensure user sees the message
          setTimeout(() => {
            window.location.href = 'login.php?success=registered';
          }, 3000);
        } else {
          // Show error message
          hideLoading();
          showError(result.message || 'Registration failed. Please try again.');
        }
              } catch (error) {
          // Network error or other error
          hideLoading();
          console.error('Registration error:', error);
          if (error.name === 'TypeError' && error.message.includes('fetch')) {
            showError('Network error. Please check your connection and try again.');
          } else {
            showError('Registration failed: ' + error.message);
          }
        } finally {
        // Reset button state
        button.disabled = false;
        spinner.style.display = 'none';
        buttonText.textContent = role === 'user' ? 'Register as User' : 'Register as Provider';
      }
    }
    
    function showError(message) {
      const errorMessage = document.getElementById('errorMessage');
      const errorText = document.getElementById('errorText');
      errorText.textContent = message;
      errorMessage.classList.add('show');
      successMessage.classList.remove('show');
    }
    
    function showSuccess(message) {
      const successMessage = document.getElementById('successMessage');
      const successText = document.getElementById('successText');
      successText.textContent = message;
      successMessage.classList.add('show');
      errorMessage.classList.remove('show');
    }
    
    function hideMessages() {
      document.getElementById('errorMessage').classList.remove('show');
      document.getElementById('successMessage').classList.remove('show');
    }
    
    // Clear messages when user starts typing
    document.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('input', hideMessages);
    });

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
