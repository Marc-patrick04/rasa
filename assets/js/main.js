// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });
    });
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const email = this.value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regex.test(email)) {
                this.style.borderColor = 'red';
            } else {
                this.style.borderColor = '';
            }
        });
    });
    
    // Alert messages auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
});

// Mobile menu toggle
function toggleMenu() {
    const nav = document.querySelector('nav ul');
    nav.classList.toggle('show');
}