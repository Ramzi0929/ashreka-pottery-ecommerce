// Universal validation functions for Ethiopian phone and email
class ValidationSystem {
    static validateEthiopianPhone(phone) {
        // Remove all spaces and special characters except +
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        
        // Ethiopian phone patterns - accept all 09X and 9X formats:
        // +25190XXXXXXX, +25191XXXXXXX, etc. (any digit after 9)
        // 090XXXXXXX, 091XXXXXXX, etc. (any digit after 09)
        // 90XXXXXXX, 91XXXXXXX, etc. (any digit after 9)
        const patterns = [
            /^\+251[9][0-9]{8}$/, // +251901234567 format (90-99)
            /^0[9][0-9]{8}$/,     // 0901234567 format (090-099)
            /^[9][0-9]{8}$/       // 901234567 format (90-99)
        ];
        
        return patterns.some(pattern => pattern.test(cleanPhone));
    }
    
    static validateEmail(email) {
        // Basic email format validation
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        
        // Check basic format
        if (!emailRegex.test(email)) {
            return false;
        }
        
        // Additional checks for real email format
        const parts = email.split('@');
        if (parts.length !== 2) return false;
        
        const [local, domain] = parts;
        
        // Local part checks
        if (local.length === 0 || local.length > 64) return false;
        if (local.startsWith('.') || local.endsWith('.')) return false;
        if (local.includes('..')) return false;
        
        // Domain part checks
        if (domain.length === 0 || domain.length > 255) return false;
        if (domain.startsWith('.') || domain.endsWith('.')) return false;
        if (domain.includes('..')) return false;
        
        // Must have at least one dot in domain
        if (!domain.includes('.')) return false;
        
        return true;
    }
    
    static formatEthiopianPhone(phone) {
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        
        // Convert to +251 format
        if (cleanPhone.startsWith('0')) {
            return '+251' + cleanPhone.substring(1);
        } else if (cleanPhone.startsWith('9')) {
            return '+251' + cleanPhone;
        } else if (cleanPhone.startsWith('+251')) {
            return cleanPhone;
        }
        
        return phone; // Return original if no pattern matches
    }
    
    static showError(input, message) {
        // Remove existing error
        this.clearError(input);
        
        // Add highly visible red error styling
        input.classList.add('is-invalid');
        input.style.borderColor = '#dc3545';
        input.style.borderWidth = '2px';
        input.style.boxShadow = '0 0 0 0.25rem rgba(220, 53, 69, 0.4)';
        input.style.backgroundColor = 'rgba(220, 53, 69, 0.05)';
        
        // Create highly visible red error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-danger fw-bold mt-1';
        errorDiv.style.fontSize = '14px';
        errorDiv.style.display = 'block';
        errorDiv.style.padding = '5px 10px';
        errorDiv.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
        errorDiv.style.borderRadius = '4px';
        errorDiv.style.border = '1px solid rgba(220, 53, 69, 0.3)';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${message}`;
        
        // Insert after input with proper spacing
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
        
        // Scroll to error if not visible
        setTimeout(() => {
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }
    
    static clearError(input) {
        input.classList.remove('is-invalid');
        input.style.borderColor = '';
        input.style.borderWidth = '';
        input.style.boxShadow = '';
        input.style.backgroundColor = '';
        const errorMsg = input.parentNode.querySelector('.text-danger');
        if (errorMsg) {
            errorMsg.remove();
        }
    }
    
    static validatePhoneInput(input) {
        const phone = input.value.trim();
        
        if (!phone) {
            this.showError(input, 'Phone required');
            return false;
        }
        
        if (!this.validateEthiopianPhone(phone)) {
            this.showError(input, '10 digit Ethiopian number start by (+251, 09, 9)');
            return false;
        }
        
        this.clearError(input);
        input.value = this.formatEthiopianPhone(phone);
        return true;
    }
    
    static validateEmailInput(input) {
        const email = input.value.trim().toLowerCase();
        
        if (!email) {
            this.showError(input, 'Email required');
            return false;
        }
        
        if (!this.validateEmail(email)) {
            this.showError(input, 'Use: name@example.com');
            return false;
        }
        
        this.clearError(input);
        input.value = email;
        return true;
    }
    
    static attachValidation() {
        // Auto-attach validation to all phone and email inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Phone inputs
            const phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"], input[id="phone"]');
            phoneInputs.forEach(input => {
                input.addEventListener('blur', () => ValidationSystem.validatePhoneInput(input));
                input.addEventListener('input', () => ValidationSystem.clearError(input));
            });
            
            // Email inputs
            const emailInputs = document.querySelectorAll('input[type="email"], input[name="email"], input[id="email"]');
            emailInputs.forEach(input => {
                input.addEventListener('blur', () => ValidationSystem.validateEmailInput(input));
                input.addEventListener('input', () => ValidationSystem.clearError(input));
            });
        });
    }
    
    static validateForm(form) {
        let isValid = true;
        
        // Validate all phone inputs
        const phoneInputs = form.querySelectorAll('input[type="tel"], input[name="phone"], input[id="phone"]');
        phoneInputs.forEach(input => {
            if (!this.validatePhoneInput(input)) {
                isValid = false;
            }
        });
        
        // Validate all email inputs
        const emailInputs = form.querySelectorAll('input[type="email"], input[name="email"], input[id="email"]');
        emailInputs.forEach(input => {
            if (!this.validateEmailInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
}

// Auto-initialize validation
ValidationSystem.attachValidation();

// Make it globally available
window.ValidationSystem = ValidationSystem;