// Universal Logout SweetAlert for SLATE System
// This script provides a consistent logout confirmation across all modules

function confirmLogout() {
  Swal.fire({
    title: 'Confirm Logout',
    text: 'Are you sure you want to log out of SLATE system?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-box-arrow-right me-2"></i>Yes, Log Out',
    cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancel',
    reverseButtons: true,
    backdrop: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
    customClass: {
      popup: 'slate-logout-modal',
      title: 'slate-logout-title',
      content: 'slate-logout-content',
      confirmButton: 'slate-logout-confirm-btn',
      cancelButton: 'slate-logout-cancel-btn'
    },
    didOpen: () => {
      // Add custom styling
      document.querySelector('.slate-logout-modal').style.borderRadius = '15px';
      document.querySelector('.slate-logout-modal').style.boxShadow = '0 10px 40px rgba(0,0,0,0.2)';
    }
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading state
      Swal.fire({
        title: 'Logging Out...',
        text: 'Please wait while we securely log you out.',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: {
          popup: 'slate-loading-modal'
        },
        didOpen: () => {
          Swal.showLoading();
          // Add custom loading animation
          const loadingIcon = document.querySelector('.swal2-icon.swal2-info');
          if (loadingIcon) {
            loadingIcon.style.animation = 'spin 1s linear infinite';
          }
        }
      });

      // Simulate logout process (you can adjust the delay)
      setTimeout(() => {
        // Success message
        Swal.fire({
          title: 'Logged Out Successfully!',
          text: 'Thank you for using SLATE. You have been securely logged out.',
          icon: 'success',
          confirmButtonColor: '#10b981',
          confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Continue to Login',
          allowOutsideClick: false,
          customClass: {
            popup: 'slate-success-modal',
            confirmButton: 'slate-success-btn'
          },
          didOpen: () => {
            // Add success styling
            document.querySelector('.slate-success-modal').style.borderRadius = '15px';
            document.querySelector('.slate-success-modal').style.boxShadow = '0 10px 40px rgba(16, 185, 129, 0.2)';
          }
        }).then(() => {
          // Redirect to login page
          window.location.href = 'login.php?logged_out=1';
        });
      }, 1000);
    }
  });
}

// Alternative quick logout function without confirmation (for emergency use)
function quickLogout() {
  Swal.fire({
    title: 'Quick Logout',
    text: 'Logging you out immediately...',
    icon: 'info',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true,
    customClass: {
      popup: 'slate-quick-logout-modal'
    },
    didOpen: () => {
      Swal.showLoading();
    }
  }).then(() => {
    window.location.href = 'login.php?quick_logout=1';
  });
}

// Session timeout warning
function showSessionWarning(minutesLeft = 5) {
  Swal.fire({
    title: 'Session Expiring Soon',
    html: `Your session will expire in <b>${minutesLeft}</b> minutes.<br>Would you like to extend your session?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#dc2626',
    confirmButtonText: '<i class="bi bi-arrow-clockwise me-2"></i>Extend Session',
    cancelButtonText: '<i class="bi bi-box-arrow-right me-2"></i>Logout Now',
    reverseButtons: false,
    allowOutsideClick: false,
    customClass: {
      popup: 'slate-warning-modal',
      confirmButton: 'slate-warning-confirm-btn',
      cancelButton: 'slate-warning-cancel-btn'
    },
    didOpen: () => {
      // Add warning styling
      document.querySelector('.slate-warning-modal').style.borderRadius = '15px';
      document.querySelector('.slate-warning-modal').style.boxShadow = '0 10px 40px rgba(245, 158, 11, 0.2)';
    }
  }).then((result) => {
    if (result.isConfirmed) {
      // Extend session by making an AJAX request
      fetch('auth.php?extend_session=1')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: 'Session Extended!',
              text: 'Your session has been extended successfully.',
              icon: 'success',
              timer: 2000,
              timerProgressBar: true,
              showConfirmButton: false,
              customClass: {
                popup: 'slate-success-modal'
              }
            });
          } else {
            confirmLogout();
          }
        })
        .catch(() => {
          confirmLogout();
        });
    } else if (result.isDismissed) {
      confirmLogout();
    }
  });
}

// Network error logout
function networkErrorLogout(message = 'Network connection lost') {
  Swal.fire({
    title: 'Connection Error',
    text: `${message}. You will be logged out for security.`,
    icon: 'error',
    confirmButtonColor: '#dc2626',
    confirmButtonText: '<i class="bi bi-wifi-off me-2"></i>Logout',
    allowOutsideClick: false,
    allowEscapeKey: false,
    customClass: {
      popup: 'slate-error-modal',
      confirmButton: 'slate-error-btn'
    }
  }).then(() => {
    window.location.href = 'login.php?network_error=1';
  });
}

// Custom CSS for SweetAlert modals
const slateAlertStyles = `
  <style>
    /* Custom SweetAlert Styling for SLATE */
    .slate-logout-modal,
    .slate-loading-modal,
    .slate-success-modal,
    .slate-warning-modal,
    .slate-error-modal,
    .slate-quick-logout-modal {
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif !important;
    }
    
    .slate-logout-title,
    .swal2-title {
      font-weight: 700 !important;
      font-size: 1.5rem !important;
      color: #1f2937 !important;
    }
    
    .slate-logout-content,
    .swal2-html-container {
      font-size: 1rem !important;
      color: #6b7280 !important;
    }
    
    .slate-logout-confirm-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
      transition: all 0.3s ease !important;
    }
    
    .slate-logout-confirm-btn:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4) !important;
    }
    
    .slate-logout-cancel-btn {
      background: #6c757d !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
    }
    
    .slate-success-btn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
    }
    
    .slate-warning-confirm-btn {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
    }
    
    .slate-warning-cancel-btn {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
    }
    
    .slate-error-btn {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0.75rem 1.5rem !important;
      font-weight: 500 !important;
    }
    
    /* Dark mode support */
    .dark-mode .slate-logout-title,
    .dark-mode .swal2-title {
      color: #f9fafb !important;
    }
    
    .dark-mode .slate-logout-content,
    .dark-mode .swal2-html-container {
      color: #d1d5db !important;
    }
    
    .dark-mode .slate-logout-modal,
    .dark-mode .slate-loading-modal,
    .dark-mode .slate-success-modal,
    .dark-mode .slate-warning-modal,
    .dark-mode .slate-error-modal,
    .dark-mode .slate-quick-logout-modal {
      background: #1f2937 !important;
      color: #f9fafb !important;
    }
    
    /* Animations */
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    .swal2-popup {
      animation: swal2-show 0.3s ease-out !important;
    }
  </style>
`;

// Add styles to document
if (typeof document !== 'undefined') {
  document.head.insertAdjacentHTML('beforeend', slateAlertStyles);
}

// Initialize session timeout checker (optional - can be enabled per module)
function initSessionTimeout(warningMinutes = 5, totalMinutes = 30) {
  const warningTime = (totalMinutes - warningMinutes) * 60 * 1000;
  const totalTime = totalMinutes * 60 * 1000;
  
  // Set warning timer
  setTimeout(() => {
    showSessionWarning(warningMinutes);
  }, warningTime);
  
  // Set automatic logout timer
  setTimeout(() => {
    quickLogout();
  }, totalTime);
}

// Export functions for use in modules
if (typeof window !== 'undefined') {
  window.confirmLogout = confirmLogout;
  window.quickLogout = quickLogout;
  window.showSessionWarning = showSessionWarning;
  window.networkErrorLogout = networkErrorLogout;
  window.initSessionTimeout = initSessionTimeout;
}
