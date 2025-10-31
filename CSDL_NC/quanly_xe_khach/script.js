/**
 * Custom JavaScript cho hệ thống quản lý xe khách
 */

document.addEventListener('DOMContentLoaded', function() {

    // Khởi tạo các component Bootstrap
    initializeBootstrapComponents();

    // Thiết lập validation form
    setupFormValidation();

    // Thiết lập xác nhận xóa
    setupDeleteConfirmation();

    // Tự động ẩn thông báo
    autoHideAlerts();

    // Format số điện thoại
    formatPhoneNumbers();

    // Thiết lập date picker
    setupDatePickers();

    console.log('Hệ thống quản lý xe khách đã sẵn sàng!');
});

/**
 * Khởi tạo các component Bootstrap
 */
function initializeBootstrapComponents() {
    // Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popover
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Thiết lập validation cho form
 */
function setupFormValidation() {
    // Bootstrap form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                        showValidationErrors(form);
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Custom validation cho số điện thoại Việt Nam
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateVietnamesePhone(this);
        });
    });

    // Validation cho biển số xe
    const plateInputs = document.querySelectorAll('input[name="ma_xe"]');
    plateInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateLicensePlate(this);
        });
    });
}

/**
 * Validation số điện thoại Việt Nam
 */
function validateVietnamesePhone(input) {
    const phoneRegex = /^(84|0[3|5|7|8|9])+([0-9]{8})$/;
    const value = input.value.replace(/\s+/g, '');

    if (value && !phoneRegex.test(value)) {
        input.setCustomValidity('Số điện thoại không hợp lệ');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
}

/**
 * Validation biển số xe
 */
function validateLicensePlate(input) {
    const plateRegex = /^[0-9]{2}[A-Z]{1,2}-[0-9]{4,5}$/;
    const value = input.value.toUpperCase().replace(/\s+/g, '');

    if (value && !plateRegex.test(value)) {
        input.setCustomValidity('Biển số xe không hợp lệ (VD: 29A-12345)');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
        if (value) {
            input.classList.add('is-valid');
        }
    }
}

/**
 * Hiển thị lỗi validation
 */
function showValidationErrors(form) {
    const invalidFields = form.querySelectorAll(':invalid');
    if (invalidFields.length > 0) {
        const firstInvalid = invalidFields[0];
        firstInvalid.focus();

        // Scroll to first invalid field
        firstInvalid.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

/**
 * Thiết lập xác nhận xóa
 */
function setupDeleteConfirmation() {
    const deleteLinks = document.querySelectorAll('a[href*="delete"], button[data-action="delete"]');

    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm') ||
                'Bạn có chắc chắn muốn xóa không?\nHành động này không thể hoàn tác!';

            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Tự động ẩn thông báo
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Format số điện thoại khi nhập
 */
function formatPhoneNumbers() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');

    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            // Format: 0xxx xxx xxx
            if (value.length > 0) {
                if (value.length <= 4) {
                    value = value;
                } else if (value.length <= 7) {
                    value = value.substring(0, 4) + ' ' + value.substring(4);
                } else {
                    value = value.substring(0, 4) + ' ' + value.substring(4, 7) + ' ' + value.substring(7, 10);
                }
            }

            e.target.value = value;
        });
    });
}

/**
 * Thiết lập date picker
 */
function setupDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');

    dateInputs.forEach(input => {
        // Thiết lập ngày tối thiểu là hôm nay
        if (input.name.includes('ngay_bat_dau') || input.name.includes('gio_di')) {
            const today = new Date().toISOString().split('T')[0];
            input.min = today;
        }

        // Validation ngày kết thúc phải sau ngày bắt đầu
        if (input.name.includes('ngay_ket_thuc')) {
            const startDateInput = document.querySelector('input[name*="ngay_bat_dau"]');
            if (startDateInput) {
                input.addEventListener('change', function() {
                    if (this.value && startDateInput.value) {
                        if (new Date(this.value) <= new Date(startDateInput.value)) {
                            this.setCustomValidity('Ngày kết thúc phải sau ngày bắt đầu');
                        } else {
                            this.setCustomValidity('');
                        }
                    }
                });

                startDateInput.addEventListener('change', function() {
                    input.min = this.value;
                    if (input.value && new Date(input.value) <= new Date(this.value)) {
                        input.value = '';
                    }
                });
            }
        }
    });
}

/**
 * Hiển thị loading spinner
 */
function showLoading(element) {
    const originalText = element.innerHTML;
    element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
    element.disabled = true;

    return function() {
        element.innerHTML = originalText;
        element.disabled = false;
    };
}

/**
 * Hiển thị thông báo toast
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

/**
 * Tạo container cho toast
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

/**
 * AJAX helper functions
 */
const Ajax = {
    get: function(url, callback) {
        fetch(url)
            .then(response => response.json())
            .then(data => callback(null, data))
            .catch(error => callback(error));
    },

    post: function(url, data, callback) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => callback(null, data))
            .catch(error => callback(error));
    }
};

/**
 * Utility functions
 */
const Utils = {
    // Format currency VND
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    },

    // Format date Vietnamese
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    },

    // Format datetime Vietnamese
    formatDateTime: function(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Debounce function
    debounce: function(func, wait) {
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
};

/**
 * Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Có thể gửi lỗi lên server để log
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    // Có thể gửi lỗi lên server để log
});
