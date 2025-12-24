// Main JavaScript for EdTech LMS
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.length > 0) {
                const strength = checkPasswordStrength(this.value);
                updatePasswordStrengthIndicator(this, strength);
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'danger');
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/)) strength++;
    
    return strength;
}

function updatePasswordStrengthIndicator(input, strength) {
    let indicator = input.parentNode.querySelector('.password-strength');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'password-strength mt-1';
        input.parentNode.appendChild(indicator);
    }
    
    let message = '';
    let color = '';
    
    switch(strength) {
        case 0:
        case 1:
        case 2:
            message = 'Weak';
            color = 'danger';
            break;
        case 3:
            message = 'Medium';
            color = 'warning';
            break;
        case 4:
        case 5:
            message = 'Strong';
            color = 'success';
            break;
    }
    
    indicator.innerHTML = `<small class="text-${color}">Password strength: ${message}</small>`;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Course progress tracking
function updateCourseProgress(courseId, progress) {
    fetch('api/update-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            courseId: courseId,
            progress: progress
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Progress updated successfully');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Index page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to elements
    const animatedElements = document.querySelectorAll('.stat-item, .feature-icon, .category-card, .testimonial-card');
    
    animatedElements.forEach((element, index) => {
        element.classList.add('fade-in');
        element.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Counter animation for statistics
    const statNumbers = document.querySelectorAll('.stat-item h2');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalValue = parseInt(target.textContent.replace(/,/g, ''));
                animateCounter(target, finalValue);
                observer.unobserve(target);
            }
        });
    }, observerOptions);
    
    statNumbers.forEach(stat => {
        observer.observe(stat);
    });
    
    // Course card hover effects
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        });
    });
});

function animateCounter(element, finalValue) {
    let currentValue = 0;
    const duration = 2000; // 2 seconds
    const increment = finalValue / (duration / 16); // 60fps
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= finalValue) {
            element.textContent = finalValue.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(currentValue).toLocaleString();
        }
    }, 16);
}

// Newsletter subscription form
function handleNewsletterSubscription(form) {
    const email = form.querySelector('input[type="email"]').value;
    
    if (!email) {
        showAlert('Please enter your email address', 'warning');
        return false;
    }
    
    // Simulate API call
    setTimeout(() => {
        showAlert('Thank you for subscribing to our newsletter!', 'success');
        form.reset();
    }, 1000);
    
    return false;
}