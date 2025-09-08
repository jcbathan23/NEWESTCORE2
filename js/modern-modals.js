/**
 * Modern Modal Enhancement System
 * Provides advanced modal functionality with animations, validation, and responsive features
 */

class ModernModal {
    constructor() {
        this.modals = [];
        this.validationRules = {};
        this.init();
    }

    init() {
        this.setupModalListeners();
        this.setupFormValidation();
        this.setupKeyboardNavigation();
        this.setupTouchGestures();
        this.addDynamicCSS();
    }

    /**
     * Setup modal event listeners for enhanced animations
     */
    setupModalListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            const modernModals = document.querySelectorAll('.modern-modal');
            
            modernModals.forEach(modal => {
                // Enhanced show animation
                modal.addEventListener('show.bs.modal', (e) => {
                    this.handleModalShow(modal);
                });

                // Enhanced hide animation
                modal.addEventListener('hide.bs.modal', (e) => {
                    this.handleModalHide(modal);
                });

                // Reset form when modal is hidden
                modal.addEventListener('hidden.bs.modal', (e) => {
                    this.resetModalForm(modal);
                });
            });
        });
    }

    /**
     * Handle modal show with enhanced animations
     */
    handleModalShow(modal) {
        const dialog = modal.querySelector('.modal-dialog');
        
        // Add entrance animation
        dialog.style.transform = 'scale(0.8) translateY(-50px)';
        dialog.style.opacity = '0';
        
        // Force reflow
        dialog.offsetHeight;
        
        // Apply show animation
        dialog.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        dialog.style.transform = 'scale(1) translateY(0)';
        dialog.style.opacity = '1';

        // Add shimmer effect to header
        this.addShimmerEffect(modal);

        // Focus first input
        setTimeout(() => {
            const firstInput = modal.querySelector('.modern-form-control');
            if (firstInput) {
                firstInput.focus();
            }
        }, 300);
    }

    /**
     * Handle modal hide with exit animations
     */
    handleModalHide(modal) {
        const dialog = modal.querySelector('.modal-dialog');
        
        // Apply exit animation
        dialog.style.transition = 'all 0.2s ease-out';
        dialog.style.transform = 'scale(0.9) translateY(-20px)';
        dialog.style.opacity = '0';
    }

    /**
     * Add shimmer effect to modal header
     */
    addShimmerEffect(modal) {
        const header = modal.querySelector('.modern-modal-header');
        if (header) {
            header.style.position = 'relative';
            header.style.overflow = 'hidden';
            
            // Create shimmer element if it doesn't exist
            if (!header.querySelector('.shimmer-effect')) {
                const shimmer = document.createElement('div');
                shimmer.className = 'shimmer-effect';
                shimmer.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
                    animation: shimmer 3s infinite;
                    pointer-events: none;
                `;
                header.appendChild(shimmer);
            }
        }
    }

    /**
     * Reset form validation and clear inputs
     */
    resetModalForm(modal) {
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            
            // Clear validation states
            const inputs = form.querySelectorAll('.modern-form-control, .modern-form-select, .modern-form-textarea');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
                const feedback = input.parentElement.querySelector('.validation-feedback');
                if (feedback) {
                    feedback.className = 'validation-feedback';
                    feedback.textContent = '';
                }
            });

            // Reset password strength indicator
            const strengthBar = form.querySelector('.strength-bar');
            if (strengthBar) {
                strengthBar.style.setProperty('--width', '0%');
            }
            
            const strengthText = form.querySelector('.strength-text');
            if (strengthText) {
                strengthText.textContent = 'Password strength';
            }
        }
    }

    /**
     * Setup advanced form validation
     */
    setupFormValidation() {
        document.addEventListener('input', (e) => {
            if (e.target.matches('.modern-form-control, .modern-form-select, .modern-form-textarea')) {
                this.validateField(e.target);
                
                // Special handling for password field
                if (e.target.type === 'password' && e.target.id.includes('Password')) {
                    this.updatePasswordStrength(e.target);
                }
            }
        });

        document.addEventListener('blur', (e) => {
            if (e.target.matches('.modern-form-control, .modern-form-select, .modern-form-textarea')) {
                this.validateField(e.target, true);
            }
        });

        // Form submission handling
        document.addEventListener('submit', (e) => {
            if (e.target.closest('.modern-modal')) {
                this.handleFormSubmission(e);
            }
        });
    }

    /**
     * Validate individual field
     */
    validateField(field, isBlur = false) {
        const feedback = field.parentElement.querySelector('.validation-feedback');
        if (!feedback) return;

        let isValid = true;
        let message = '';

        // Required field validation
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            message = 'This field is required';
        }

        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }

        // Number validation
        if (field.type === 'number' && field.value) {
            const num = parseFloat(field.value);
            if (isNaN(num) || num < 0) {
                isValid = false;
                message = 'Please enter a valid number';
            }
        }

        // Password validation
        if (field.type === 'password' && field.value) {
            if (field.value.length < 6) {
                isValid = false;
                message = 'Password must be at least 6 characters long';
            }
        }

        // Phone number validation (basic)
        if (field.name === 'contactNumber' && field.value) {
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(field.value) || field.value.length < 7) {
                isValid = false;
                message = 'Please enter a valid contact number';
            }
        }

        // Update UI based on validation
        this.updateFieldValidation(field, feedback, isValid, message, isBlur);

        return isValid;
    }

    /**
     * Update field validation UI
     */
    updateFieldValidation(field, feedback, isValid, message, isBlur) {
        if (isValid && field.value.trim()) {
            feedback.className = 'validation-feedback valid';
            feedback.innerHTML = '<i class="bi bi-check-circle"></i> Looks good!';
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else if (!isValid && (field.value.trim() || isBlur)) {
            feedback.className = 'validation-feedback invalid';
            feedback.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${message}`;
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        } else {
            feedback.className = 'validation-feedback';
            feedback.textContent = '';
            field.classList.remove('is-valid', 'is-invalid');
        }
    }

    /**
     * Update password strength indicator
     */
    updatePasswordStrength(passwordField) {
        const password = passwordField.value;
        const strengthBar = passwordField.parentElement.querySelector('.strength-bar');
        const strengthText = passwordField.parentElement.querySelector('.strength-text');
        
        if (!strengthBar || !strengthText) return;

        let strength = 0;
        let feedback = 'Enter password';
        
        if (password.length >= 6) strength += 1;
        if (password.length >= 10) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        const strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
        const strengthColors = ['#dc3545', '#dc3545', '#ffc107', '#ffc107', '#28a745', '#28a745'];
        
        const strengthPercent = (strength / 6) * 100;
        feedback = password ? strengthTexts[Math.min(strength, 5)] : 'Enter password';
        
        strengthBar.style.setProperty('--width', strengthPercent + '%');
        strengthBar.style.background = strengthColors[Math.min(strength, 5)];
        strengthText.textContent = feedback;
        strengthText.style.color = strengthColors[Math.min(strength, 5)];
    }

    /**
     * Handle form submission with loading states
     */
    handleFormSubmission(e) {
        const form = e.target;
        const modal = form.closest('.modern-modal');
        const submitButton = form.querySelector('.modern-btn-primary');
        
        // Validate all fields
        const inputs = form.querySelectorAll('.modern-form-control, .modern-form-select, .modern-form-textarea');
        let isFormValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input, true)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            e.preventDefault();
            this.showValidationError(modal);
            return;
        }

        // Show loading state
        this.showLoadingState(submitButton);
        
        // The actual form submission logic should be handled by the existing JavaScript
        // This just provides the UI feedback
    }

    /**
     * Show loading state on submit button
     */
    showLoadingState(button) {
        if (!button) return;

        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'flex';
        
        button.disabled = true;
        button.style.cursor = 'not-allowed';
    }

    /**
     * Hide loading state
     */
    hideLoadingState(button) {
        if (!button) return;

        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (btnText) btnText.style.display = 'inline';
        if (btnLoading) btnLoading.style.display = 'none';
        
        button.disabled = false;
        button.style.cursor = 'pointer';
    }

    /**
     * Show validation error message
     */
    showValidationError(modal) {
        // Create toast notification for validation errors
        this.showToast('Please correct the highlighted fields before submitting', 'error');
        
        // Scroll to first invalid field
        const firstInvalid = modal.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
    }

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            const activeModal = document.querySelector('.modal.show .modern-modal-content');
            if (!activeModal) return;

            // Tab navigation enhancement
            if (e.key === 'Tab') {
                this.handleTabNavigation(e, activeModal);
            }

            // Enter key to submit
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                const submitButton = activeModal.querySelector('.modern-btn-primary');
                if (submitButton && !submitButton.disabled) {
                    e.preventDefault();
                    submitButton.click();
                }
            }

            // Escape to close
            if (e.key === 'Escape') {
                const closeButton = activeModal.querySelector('.modern-btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }
        });
    }

    /**
     * Enhanced tab navigation
     */
    handleTabNavigation(e, modalContent) {
        const focusableElements = modalContent.querySelectorAll(
            'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
        );
        
        const visibleElements = Array.from(focusableElements).filter(el => {
            return el.offsetParent !== null && !el.disabled;
        });

        if (visibleElements.length === 0) return;

        const firstElement = visibleElements[0];
        const lastElement = visibleElements[visibleElements.length - 1];

        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }

    /**
     * Setup touch gestures for mobile
     */
    setupTouchGestures() {
        let startY = 0;
        let currentY = 0;
        let isDragging = false;

        document.addEventListener('touchstart', (e) => {
            const modal = e.target.closest('.modern-modal-content');
            if (modal && window.innerWidth <= 768) {
                startY = e.touches[0].clientY;
                isDragging = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;
            
            if (deltaY > 50) {
                // Swipe down to close on mobile
                const modal = e.target.closest('.modal');
                if (modal) {
                    const closeButton = modal.querySelector('.modern-btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            }
        });

        document.addEventListener('touchend', () => {
            isDragging = false;
        });
    }

    /**
     * Add dynamic CSS for animations and responsive features
     */
    addDynamicCSS() {
        const style = document.createElement('style');
        style.textContent = `
            .strength-bar::before {
                width: var(--width, 0%);
                background: var(--color, #e9ecef);
                transition: width 0.3s ease, background 0.3s ease;
            }
            
            .modern-form-control.is-valid,
            .modern-form-select.is-valid,
            .modern-form-textarea.is-valid {
                border-color: #28a745;
                box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
            }
            
            .modern-form-control.is-invalid,
            .modern-form-select.is-invalid,
            .modern-form-textarea.is-invalid {
                border-color: #dc3545;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            }
            
            @keyframes shimmer {
                0% { left: -100%; }
                100% { left: 100%; }
            }
            
            .modern-modal .modal-dialog {
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
            }
            
            @media (max-width: 768px) {
                .modern-modal-content {
                    margin: 1rem;
                    border-radius: 15px;
                }
                
                .modern-modal-header,
                .modern-modal-body,
                .modern-modal-footer {
                    padding: 1.5rem;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'error' ? '#dc3545' : '#28a745'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Show animation
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Hide and remove
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Password visibility toggle
     */
    static togglePasswordVisibility(passwordFieldId) {
        const passwordField = document.getElementById(passwordFieldId);
        if (!passwordField) return;

        const toggleButton = passwordField.parentElement.querySelector('.password-toggle i');
        if (!toggleButton) return;
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleButton.className = 'bi bi-eye-slash';
        } else {
            passwordField.type = 'password';
            toggleButton.className = 'bi bi-eye';
        }
    }

    /**
     * Enhanced modal show function
     */
    static showModal(modalId, config = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Update modal title if provided
        if (config.title) {
            const titleElement = modal.querySelector('.modern-modal-title');
            if (titleElement) {
                titleElement.textContent = config.title;
            }
        }

        // Update modal icon if provided
        if (config.icon) {
            const iconElement = modal.querySelector('.modal-icon i');
            if (iconElement) {
                iconElement.className = config.icon;
            }
        }

        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Global functions for backward compatibility
window.togglePasswordVisibility = ModernModal.togglePasswordVisibility;
window.showModal = ModernModal.showModal;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.modernModal = new ModernModal();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernModal;
}
