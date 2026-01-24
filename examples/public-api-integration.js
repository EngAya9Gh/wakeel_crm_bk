/**
 * CRM Wakeel - Public API Integration Example
 * 
 * This file demonstrates how to integrate website forms with CRM Wakeel API
 * 
 * @version 1.0
 * @date 2026-01-24
 */

// ============================================================================
// Configuration
// ============================================================================

const CRM_CONFIG = {
    apiUrl: 'https://your-crm-domain.com/api/public/v1/leads',
    apiKey: 'YOUR_API_KEY_HERE', // Replace with your actual API key
};

// ============================================================================
// Example 1: Contact Form Integration
// ============================================================================

/**
 * Submit contact form data to CRM
 * 
 * @param {HTMLFormElement} form - The contact form element
 * @returns {Promise<Object>} API response
 */
async function submitContactForm(form) {
    // Prevent default form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Get form data
        const formData = {
            name: form.querySelector('#name').value,
            phone: form.querySelector('#phone').value,
            email: form.querySelector('#email').value,
            subject: form.querySelector('#subject').value,
            message: form.querySelector('#message').value,
            source: 'contact_form'
        };

        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'جاري الإرسال...';

        try {
            const response = await fetch(CRM_CONFIG.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': CRM_CONFIG.apiKey
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Success
                showSuccessMessage('شكراً لتواصلك معنا! سنتواصل معك قريباً');
                form.reset();
            } else {
                // Validation errors
                showErrorMessage(result.message || 'حدث خطأ، يرجى المحاولة مرة أخرى');
                displayValidationErrors(result.errors);
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorMessage('حدث خطأ في الاتصال، يرجى المحاولة مرة أخرى');
        } finally {
            // Restore button state
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

// ============================================================================
// Example 2: Landing Page Form Integration
// ============================================================================

/**
 * Submit landing page form data to CRM
 * 
 * @param {Object} data - Form data object
 * @returns {Promise<Object>} API response
 */
async function submitLandingPageForm(data) {
    const payload = {
        name: data.name,
        phone: data.phone,
        email: data.email || null,
        company: data.company || null,
        address: data.address || null,
        source: 'landing_page'
    };

    try {
        const response = await fetch(CRM_CONFIG.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': CRM_CONFIG.apiKey
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            return {
                success: true,
                leadId: result.data.lead_id,
                message: result.message
            };
        } else {
            return {
                success: false,
                errors: result.errors,
                message: result.message
            };
        }
    } catch (error) {
        console.error('API Error:', error);
        return {
            success: false,
            message: 'حدث خطأ في الاتصال'
        };
    }
}

// ============================================================================
// Example 3: jQuery Integration
// ============================================================================

$(document).ready(function () {
    $('#contactForm').on('submit', function (e) {
        e.preventDefault();

        const formData = {
            name: $('#name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            subject: $('#subject').val(),
            message: $('#message').val(),
            source: 'contact_form'
        };

        $.ajax({
            url: CRM_CONFIG.apiUrl,
            type: 'POST',
            headers: {
                'X-API-Key': CRM_CONFIG.apiKey
            },
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function () {
                $('#submitBtn').prop('disabled', true).text('جاري الإرسال...');
            },
            success: function (response) {
                if (response.success) {
                    alert('تم إرسال رسالتك بنجاح!');
                    $('#contactForm')[0].reset();
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    displayErrors(response.errors);
                } else {
                    alert('حدث خطأ، يرجى المحاولة مرة أخرى');
                }
            },
            complete: function () {
                $('#submitBtn').prop('disabled', false).text('إرسال');
            }
        });
    });
});

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Display success message
 */
function showSuccessMessage(message) {
    // Example using a simple alert (replace with your UI library)
    alert(message);

    // Or use a toast notification library like Toastify
    // Toastify({
    //     text: message,
    //     duration: 3000,
    //     backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
    // }).showToast();
}

/**
 * Display error message
 */
function showErrorMessage(message) {
    alert(message);
}

/**
 * Display validation errors
 */
function displayValidationErrors(errors) {
    if (!errors) return;

    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(el => el.remove());

    // Display new errors
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message text-red-500 text-sm mt-1';
            errorDiv.textContent = errors[field][0];
            input.parentElement.appendChild(errorDiv);
        }
    });
}

/**
 * Validate phone number format (Saudi)
 */
function validateSaudiPhone(phone) {
    const regex = /^(\+966|00966|966|05)[0-9]{8,9}$/;
    return regex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

/**
 * Format phone number to international format
 */
function formatPhoneNumber(phone) {
    // Remove spaces, dashes, parentheses
    phone = phone.replace(/[\s\-\(\)]/g, '');

    // Convert to +966 format
    if (phone.startsWith('00966')) {
        return '+966' + phone.substring(5);
    } else if (phone.startsWith('966')) {
        return '+966' + phone.substring(3);
    } else if (phone.startsWith('05')) {
        return '+966' + phone.substring(1);
    }

    return phone;
}

// ============================================================================
// Example 4: React Hook
// ============================================================================

/**
 * React hook for CRM API integration
 */
function useCRMSubmit() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const submitLead = async (formData) => {
        setLoading(true);
        setError(null);
        setSuccess(false);

        try {
            const response = await fetch(CRM_CONFIG.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': CRM_CONFIG.apiKey
                },
                body: JSON.stringify({
                    ...formData,
                    source: formData.source || 'website_form'
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                setSuccess(true);
                return result.data;
            } else {
                setError(result.message || 'حدث خطأ');
                throw new Error(result.message);
            }
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return { submitLead, loading, error, success };
}

// ============================================================================
// Example 5: Vue.js Integration
// ============================================================================

const ContactFormComponent = {
    data() {
        return {
            form: {
                name: '',
                phone: '',
                email: '',
                subject: '',
                message: '',
                source: 'contact_form'
            },
            loading: false,
            errors: {},
            successMessage: ''
        };
    },
    methods: {
        async submitForm() {
            this.loading = true;
            this.errors = {};
            this.successMessage = '';

            try {
                const response = await fetch(CRM_CONFIG.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': CRM_CONFIG.apiKey
                    },
                    body: JSON.stringify(this.form)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.successMessage = 'تم إرسال رسالتك بنجاح!';
                    this.resetForm();
                } else {
                    this.errors = result.errors || {};
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.form = {
                name: '',
                phone: '',
                email: '',
                subject: '',
                message: '',
                source: 'contact_form'
            };
        }
    }
};

// ============================================================================
// Usage Examples
// ============================================================================

// Example 1: Vanilla JavaScript
// const form = document.querySelector('#contactForm');
// submitContactForm(form);

// Example 2: Direct function call
// submitLandingPageForm({
//     name: 'أحمد محمد',
//     phone: '0501234567',
//     email: 'ahmed@example.com'
// }).then(result => {
//     if (result.success) {
//         console.log('Lead ID:', result.leadId);
//     }
// });

// Example 3: With phone validation
// const phone = document.querySelector('#phone').value;
// if (validateSaudiPhone(phone)) {
//     const formattedPhone = formatPhoneNumber(phone);
//     // Submit with formatted phone
// }
