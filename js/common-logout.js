/**
 * Common logout functionality for all CORE II modules
 * Requires SweetAlert2 to be loaded
 */

function confirmLogout() {
  Swal.fire({
    title: 'Confirm Logout',
    text: 'Are you sure you want to log out of CORE II System?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-box-arrow-right"></i> Logout',
    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancel',
    reverseButtons: true,
    customClass: {
      confirmButton: 'btn btn-danger me-2',
      cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false,
    backdrop: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
    focusConfirm: false,
    focusCancel: true
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading state during logout
      Swal.fire({
        title: 'Logging out...',
        text: 'Please wait while we securely log you out.',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Redirect to logout after a short delay
      setTimeout(() => {
        window.location.href = 'auth.php?logout=1';
      }, 1500);
    }
  });
}

/**
 * Initialize logout functionality for sidebar logout links
 */
function initializeLogout() {
  // Find all logout links and add click handlers
  const logoutLinks = document.querySelectorAll('a[href*="logout"], a[onclick*="logout"]');
  
  logoutLinks.forEach(link => {
    // Remove existing onclick handlers
    link.removeAttribute('onclick');
    
    // Remove href to prevent default navigation
    if (link.href && link.href.includes('logout')) {
      link.href = '#';
    }
    
    // Add new click handler
    link.addEventListener('click', function(e) {
      e.preventDefault();
      confirmLogout();
    });
  });
  
  // Also handle any existing confirmLogout calls
  window.confirmLogout = confirmLogout;
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Small delay to ensure other scripts have loaded
  setTimeout(() => {
    initializeLogout();
  }, 100);
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { confirmLogout, initializeLogout };
}
