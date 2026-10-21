/**
 * PLBN Wini - Main JavaScript File
 * Version: 1.0.0
 * Author: PLBN Wini Team
 * Description: Core functionality for PLBN Wini website
 */

// ========================================
// DOCUMENT READY
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initMobileMenu();
    initBackToTop();
    initSmoothScroll();
    initFormValidation();
    initImagePreview();
    initNotification();
    initCountAnimation();
    initCarousel();
    initTooltips();
    initPasswordToggle();
    initSearchAutoComplete();
    initPrintButton();
    initConfirmDialog();
});

// ========================================
// MOBILE MENU
// ========================================
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('active');
            this.classList.toggle('active');
            
            // Change icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu.classList.contains('active')) {
                const isClickInside = mobileMenu.contains(e.target) || 
                                     menuToggle.contains(e.target);
                if (!isClickInside) {
                    mobileMenu.classList.remove('active');
                    menuToggle.classList.remove('active');
                    const icon = menuToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
}

// ========================================
// BACK TO TOP BUTTON
// ========================================
function initBackToTop() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (backToTopBtn) {
        // Show/hide button on scroll
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.classList.remove('visible');
                backToTopBtn.style.display = 'none';
            }
        });
        
        // Scroll to top on click
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// ========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ========================================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// ========================================
// FORM VALIDATION
// ========================================
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                // Remove existing error
                const errorDiv = input.parentElement.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
                input.classList.remove('error');
                
                // Validate
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    
                    const error = document.createElement('div');
                    error.className = 'error-message';
                    error.style.color = '#dc3545';
                    error.style.fontSize = '0.85rem';
                    error.style.marginTop = '5px';
                    error.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + 
                                     (input.dataset.errorMessage || 'Field ini harus diisi');
                    input.parentElement.appendChild(error);
                }
                
                // Email validation
                if (input.type === 'email' && input.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value.trim())) {
                        isValid = false;
                        input.classList.add('error');
                        const error = document.createElement('div');
                        error.className = 'error-message';
                        error.style.color = '#dc3545';
                        error.style.fontSize = '0.85rem';
                        error.style.marginTop = '5px';
                        error.innerHTML = '<i class="fas fa-exclamation-circle"></i> Format email tidak valid';
                        input.parentElement.appendChild(error);
                    }
                }
                
                // Phone validation
                if (input.type === 'tel' && input.value.trim()) {
                    const phoneRegex = /^[0-9+\-\s()]+$/;
                    if (!phoneRegex.test(input.value.trim())) {
                        isValid = false;
                        input.classList.add('error');
                        const error = document.createElement('div');
                        error.className = 'error-message';
                        error.style.color = '#dc3545';
                        error.style.fontSize = '0.85rem';
                        error.style.marginTop = '5px';
                        error.innerHTML = '<i class="fas fa-exclamation-circle"></i> Format nomor telepon tidak valid';
                        input.parentElement.appendChild(error);
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Remove error on input
        form.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorDiv = this.parentElement.querySelector('.error-message');
                if (errorDiv) errorDiv.remove();
            });
        });
    });
}

// ========================================
// IMAGE PREVIEW
// ========================================
function initImagePreview() {
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

// ========================================
// NOTIFICATION SYSTEM
// ========================================
function initNotification() {
    // Auto dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
        
        // Close button
        const closeBtn = alert.querySelector('.close-alert');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
        }
    });
}

// ========================================
// COUNT ANIMATION
// ========================================
function initCountAnimation() {
    const counters = document.querySelectorAll('.count-animate');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.target);
                    animateCount(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => observer.observe(counter));
    } else {
        // Fallback for older browsers
        counters.forEach(counter => {
            const target = parseInt(counter.dataset.target);
            animateCount(counter, target);
        });
    }
}

function animateCount(element, target) {
    let current = 0;
    const increment = target / 50;
    const duration = 1500;
    const stepTime = duration / 50;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.round(current);
    }, stepTime);
}

// ========================================
// CAROUSEL / SLIDER
// ========================================
function initCarousel() {
    const carousels = document.querySelectorAll('.carousel');
    
    carousels.forEach(carousel => {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        let currentSlide = 0;
        let autoPlay = carousel.dataset.autoplay === 'true';
        let interval = null;
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.style.display = i === index ? 'block' : 'none';
            });
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }
        
        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(currentSlide);
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                resetAutoPlay();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                resetAutoPlay();
            });
        }
        
        function resetAutoPlay() {
            if (autoPlay) {
                clearInterval(interval);
                interval = setInterval(nextSlide, 3000);
            }
        }
        
        // Initialize
        if (slides.length > 0) {
            showSlide(0);
            if (autoPlay) {
                interval = setInterval(nextSlide, 3000);
            }
        }
        
        // Pause on hover
        if (autoPlay) {
            carousel.addEventListener('mouseenter', () => {
                clearInterval(interval);
            });
            carousel.addEventListener('mouseleave', () => {
                interval = setInterval(nextSlide, 3000);
            });
        }
    });
}

// ========================================
// TOOLTIPS
// ========================================
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const text = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-box';
            tooltip.textContent = text;
            tooltip.style.position = 'fixed';
            tooltip.style.background = '#1a3a5c';
            tooltip.style.color = 'white';
            tooltip.style.padding = '8px 15px';
            tooltip.style.borderRadius = '6px';
            tooltip.style.fontSize = '0.85rem';
            tooltip.style.zIndex = '9999';
            tooltip.style.pointerEvents = 'none';
            tooltip.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            tooltip.style.maxWidth = '250px';
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// ========================================
// PASSWORD TOGGLE
// ========================================
function initPasswordToggle() {
    const toggles = document.querySelectorAll('.toggle-password');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });
}

// ========================================
// SEARCH AUTOCOMPLETE
// ========================================
function initSearchAutoComplete() {
    const searchInputs = document.querySelectorAll('.search-autocomplete');
    
    searchInputs.forEach(input => {
        const datalistId = input.dataset.datalist;
        const datalist = document.getElementById(datalistId);
        
        if (datalist) {
            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                const options = datalist.querySelectorAll('option');
                
                options.forEach(option => {
                    const text = option.textContent.toLowerCase();
                    option.style.display = text.includes(value) ? '' : 'none';
                });
            });
        }
    });
}

// ========================================
// PRINT BUTTON
// ========================================
function initPrintButton() {
    const printBtn = document.querySelector('.btn-print');
    
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
}

// ========================================
// CONFIRM DIALOG
// ========================================
function initConfirmDialog() {
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Apakah Anda yakin?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Format currency (Rupiah)
 */
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Format date
 */
function formatDate(date, format = 'dd/mm/yyyy') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return format
        .replace('dd', day)
        .replace('mm', month)
        .replace('yyyy', year);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Get URL parameters
 */
function getUrlParams() {
    const params = {};
    const queryString = window.location.search.substring(1);
    const pairs = queryString.split('&');
    
    pairs.forEach(pair => {
        const [key, value] = pair.split('=');
        if (key) {
            params[decodeURIComponent(key)] = decodeURIComponent(value || '');
        }
    });
    
    return params;
}

/**
 * Set URL parameters
 */
function setUrlParams(params) {
    const url = new URL(window.location.href);
    Object.keys(params).forEach(key => {
        url.searchParams.set(key, params[key]);
    });
    window.history.pushState({}, '', url);
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Teks berhasil disalin!', 'success');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Teks berhasil disalin!', 'success');
    } catch (err) {
        showNotification('Gagal menyalin teks!', 'error');
    }
    
    document.body.removeChild(textarea);
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.background = colors[type] || colors.info;
    notification.style.color = 'white';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '8px';
    notification.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
    notification.style.zIndex = '99999';
    notification.style.animation = 'slideIn 0.3s ease';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .error {
        border-color: #dc3545 !important;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 5px;
    }
    
    .tooltip-box {
        position: fixed;
        background: #1a3a5c;
        color: white;
        padding: 8px 15px;
        border-radius: 6px;
        font-size: 0.85rem;
        z-index: 9999;
        pointer-events: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        max-width: 250px;
    }
    
    #backToTop {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #ffd700;
        color: #1a3a5c;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        transition: all 0.3s;
        z-index: 999;
    }
    
    #backToTop.visible {
        display: block !important;
        animation: fadeIn 0.3s ease;
    }
    
    #backToTop:hover {
        transform: scale(1.1);
        background: #ffe44d;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
`;

document.head.appendChild(style);

// ========================================
// EXPOSE GLOBAL FUNCTIONS
// ========================================
window.formatRupiah = formatRupiah;
window.formatDate = formatDate;
window.debounce = debounce;
window.throttle = throttle;
window.getUrlParams = getUrlParams;
window.setUrlParams = setUrlParams;
window.copyToClipboard = copyToClipboard;
window.showNotification = showNotification;

// ========================================
// CONSOLE LOG BANNER
// ========================================
console.log('%c PLBN Wini ', 'background: #1a3a5c; color: #ffd700; font-size: 20px; font-weight: bold; padding: 10px 20px; border-radius: 5px;');
console.log('%c Website Resmi Pos Lintas Batas Negara Wini ', 'font-size: 14px; color: #666;');
console.log('%c By PLBN Wini Team ', 'font-size: 12px; color: #999;');