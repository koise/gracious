import $ from 'jquery';
import axios from 'axios';

// Global variable to store QR codes after they're fetched
let cachedQrCodes = [];

// Global variable to store the latest appointment ID
let latestAppointmentId = null;

$(document).ready(function () {
    // Add loading overlay for initial page load
    showPageLoader();
    
    // Set up event handlers
    initializeEventHandlers();
    
    // Fetch appointment data using the appointment/fetch endpoint
    fetchLatestAppointmentFromDashboard();
    fetchPaymentHistory();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Remove loader once everything is ready
    setTimeout(hidePageLoader, 1000);
});

/**
 * Show page loader overlay
 */
function showPageLoader() {
    $('body').append(`
        <div id="page-loader" class="page-loader">
            <div class="loader-content">
                <div class="pulse-loader">
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                <p>Loading payment dashboard...</p>
            </div>
        </div>
    `);
}

/**
 * Hide page loader overlay
 */
function hidePageLoader() {
    $('#page-loader').fadeOut(300, function() {
        $(this).remove();
    });
    
    // Add animation to the cards
    setTimeout(() => {
        $('.latest-payment-card').addClass('animate-in');
    }, 200);
    
    setTimeout(() => {
        $('.payment-history-card').addClass('animate-in');
    }, 400);
}

/**
 * Initialize all event handlers for the payment page
 */
function initializeEventHandlers() {
    
    // Modal close buttons with improved animations
    $(document).on('click', '#closePaymentModal, .close-modal', function () {
        $('#paymentModal').fadeOut(300);
    });

    $(document).on('click', '.close-modal-btn, #closeDetailbtn', function () {
        $('#appointmentDetailsModal').fadeOut(300);
    });

    // View details buttons
    $(document).on('click', '#latest-appointment-table .btn-view', function() {
        const transactionId = $(this).attr('data-transaction-id');
        
        // Add click feedback animation
        $(this).addClass('button-pulse');
        setTimeout(() => {
            $(this).removeClass('button-pulse');
        }, 300);
        
        viewDetails(transactionId);
    });
    
    $(document).on('click', '#paymentHistory .btn-view', function() {
        const transactionId = $(this).attr('data-transaction-id');
        
        // Add click feedback animation
        $(this).addClass('button-pulse');
        setTimeout(() => {
            $(this).removeClass('button-pulse');
        }, 300);
        
        viewDetails(transactionId);
    });
    
    // Payment form submission
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        const transactionId = $('#payment-form-transaction-id').val();
        submitPayment(transactionId);
    });
    
    // QR selection change with animation
    $('#qr-selection').on('change', function() {
        const qrId = $(this).val();
        if (qrId) {
            // Log QR selection
            console.log('QR selected by user - QR ID:', qrId);
            
            // Show loading state with a pulse animation
            $('#qr-details').html(`
                <div class="text-center p-4">
                    <div class="loading-spinner"></div>
                    <p class="mt-3">Loading QR code...</p>
                </div>
            `).fadeIn(300);
            
            // Add highlight effect to the dropdown to show it's processing
            $(this).addClass('active-selection');
            setTimeout(() => $(this).removeClass('active-selection'), 1000);
            
            // Store selected QR ID in the form
            $('#payment-form-qr-id').val(qrId);
            
            // Update QR details with animation
            updateQrDetails(qrId);
        } else {
            console.log('No QR selected - dropdown cleared');
            $('#qr-details').fadeOut(300, function() {
                $(this).html('').show();
            });
            
            // Clear stored QR ID
            $('#payment-form-qr-id').val('');
        }
    });
    
    // Receipt image preview functionality
    $(document).on('change', '#payment-receipt', function() {
        const file = this.files[0];
        const previewContainer = $('#receipt-preview');
        
        if (file) {
            // Clear previous preview
            previewContainer.empty();
            
            // Validate file type
            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                previewContainer.html('<div class="file-error">Invalid file type. Please upload a JPG or PNG image.</div>');
                return;
            }
            
            // Create file reader for preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.html(`
                    <div class="receipt-preview-container">
                        <img src="${e.target.result}" alt="Receipt preview" class="receipt-preview-image">
                        <button type="button" class="remove-receipt-btn" title="Remove receipt">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.empty();
        }
    });
    
    // Remove receipt image
    $(document).on('click', '.remove-receipt-btn', function(e) {
        e.preventDefault();
        $('#payment-receipt').val('');
        $('#receipt-preview').empty();
    });
    
    // Search functionality with debounce for better performance
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        clearTimeout(searchTimeout);
        
        // Show search feedback
        $('#paymentHistory').css('opacity', '0.6');
        
        searchTimeout = setTimeout(() => {
            filterPaymentHistory(searchTerm);
            $('#paymentHistory').css('opacity', '1');
        }, 300);
    });
    
    // Summary cards hover effect 
    $('.summary-card').hover(
        function() {
            $(this).css('transform', 'translateY(-5px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
    
    // Add swiping animation between tabs on mobile
    initializeMobileSwipe();

    // Initialize tooltips
    if (typeof $.fn.tooltip === 'function') {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    // View button click
    $(document).on('click', '.btn-view', function() {
        const transactionId = $(this).attr('data-transaction-id');
        const appointmentId = $(this).attr('data-appointment-id');
        viewDetails(transactionId);
    });
    
    // Search functionality
    $('#searchPayments').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPaymentHistory(searchTerm);
    });
    
    // Responsive menu toggle
    $('#filterToggle').on('click', function() {
        $('.filter-sidebar').toggleClass('show-filters');
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        handleResponsiveLayout();
    });
    
    // Pay now buttons
    $(document).on('click', '.btn-pay, .btn-pay-now, .btn-pay-active', function() {
        const transactionId = $(this).attr('data-transaction-id');
        const appointmentId = $(this).attr('data-appointment-id');
        
        // Add click feedback animation
        $(this).addClass('button-pulse');
        setTimeout(() => {
            $(this).removeClass('button-pulse');
        }, 300);
        
        showPaymentModal(transactionId, appointmentId);
        // Hide the appointment details modal if it's open
        $('#appointmentDetailsModal').fadeOut(300);
    });
}

/**
 * Initialize mobile swipe functionality for better touch experience
 */
function initializeMobileSwipe() {
    // This would require adding a touch gesture library like Hammer.js
    // For demonstration, adding a viewport check
    if (window.innerWidth <= 768) {
        // Add mobile-specific UI enhancements
        $('.dashboard-card').addClass('mobile-optimized');
    }
}

/**
 * Initialize tooltips for better user experience
 */
function initializeTooltips() {
    // Check if Bootstrap's tooltip function exists before calling it
    if (typeof $.fn.tooltip === 'function') {
        $('[data-bs-toggle="tooltip"]').tooltip();
    } else {
        console.log('Bootstrap tooltip functionality not available');
    }
}

/**
 * Filter payment history based on search term
 * @param {string} searchTerm - The search term to filter by
 */
function filterPaymentHistory(searchTerm) {
    $("#paymentHistory tr").each(function() {
        const procedure = $(this).find("td:nth-child(1)").text().toLowerCase();
        const status = $(this).find("td:nth-child(3)").text().toLowerCase();
        const date = $(this).find("td:nth-child(4)").text().toLowerCase();
        
        if (procedure.includes(searchTerm) || status.includes(searchTerm) || date.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

/**
 * View payment details in modal
 * @param {string|number} transactionId - The transaction ID to view
 */
function viewDetails(transactionId) {
    console.log('Viewing details for transaction ID:', transactionId);
    
    // Show modal with improved loading state
    $('#appointmentDetailsModal').css('display', 'flex').hide().fadeIn(300);
    $('#appointmentDetailsModal .modal-content').html(`
        <div class="modal-body text-center p-5">
            <div class="loading-spinner"></div>
            <p class="mt-3">Loading appointment details...</p>
        </div>
    `);
    
    // Try to find the appointment in already loaded data
    const cachedAppointment = findAppointmentInExistingData(transactionId);

    if (cachedAppointment) {
        populateModalWithAppointmentData(cachedAppointment);
        return;
    }

    // Validate the transaction ID to prevent SQL errors
    if (!transactionId || isNaN(parseInt(transactionId))) {
        $('#appointmentDetailsModal .modal-content').html(`
            <div class="modal-header">
                <h3 class="modal-title">Error</h3>
                <button type="button" class="close-modal-btn" id="closeDetailbtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="error-state">
                    <i class="fas fa-exclamation-circle error-icon"></i>
                    <p>Invalid transaction ID. Cannot retrieve appointment details.</p>
                </div>
            </div>
        `);
        return;
    }

    // Use the debug function to test the API response
    debugFetchPaymentDetails(transactionId);
}

/**
 * Debug function to check API responses
 * @param {string|number} id - The ID to fetch details for
 */
function debugFetchPaymentDetails(id) {
    // Testing the API response
    axios.get(`/payments/details-with-joins/${id}`)
        .then(function (response) {
            console.log('DEBUG - API Response:', response);
            console.log('DEBUG - Data structure:', JSON.stringify(response.data, null, 2));
            
            // Display data in console in a readable format
            if (response.data && response.data.appointment) {
                console.table(response.data.appointment);
                
                // Try to populate the modal with the data
                populatePaymentModal(response.data.appointment);
                
                // Add entrance animation to modal content
                $('#appointmentDetailsModal .modal-content').addClass('animate-fade-in');
            } else {
                console.error('DEBUG - No appointment data found in response');
                $('#appointmentDetailsModal .modal-content').html(`
                    <div class="modal-header">
                        <h3 class="modal-title">Error</h3>
                        <button type="button" class="close-modal-btn" id="closeDetailbtn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="error-state">
                            <i class="fas fa-exclamation-circle error-icon"></i>
                            <p>No appointment data found in response.</p>
                            <pre>${JSON.stringify(response.data, null, 2)}</pre>
                        </div>
                    </div>
                `);
            }
        })
        .catch(function (error) {
            console.error('DEBUG - Error fetching payment details:', error);
            console.error('DEBUG - Error details:', error.response ? error.response.data : error.message);
            
            $('#appointmentDetailsModal .modal-content').html(`
                <div class="modal-header">
                    <h3 class="modal-title">Error</h3>
                    <button type="button" class="close-modal-btn" id="closeDetailbtn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="error-state">
                        <i class="fas fa-exclamation-circle error-icon"></i>
                        <p>Error fetching appointment details:</p>
                        <pre>${error.response ? JSON.stringify(error.response.data, null, 2) : error.message}</pre>
                    </div>
                </div>
            `);
        });
}

/**
 * Helper function to find a payment in cached data
 * @param {string|number} transactionId - The transaction ID to find
 * @returns {Object|null} The found appointment or null
 */
function findAppointmentInExistingData(transactionId) {
    // Try from latest appointment table
    const latestRows = $("#latest-appointment-table tr");
    for (let i = 0; i < latestRows.length; i++) {
        const btn = $(latestRows[i]).find('button[data-transaction-id="' + transactionId + '"]');
        if (btn.length > 0) {
            const cells = $(latestRows[i]).find('td');
            const appointmentId = btn.attr('data-appointment-id') || transactionId;
            return {
                id: transactionId,
                appointment_id: appointmentId,
                procedures: $(cells[0]).text().trim(),
                total: parseFloat($(cells[1]).text().replace('₱', '').trim()),
                status: $(cells[2]).text().trim(),
                appointment_date: $(cells[3]).text().trim(),
            };
        }
    }
    
    // Try from payment history table
    const historyRows = $("#paymentHistory tr");
    for (let i = 0; i < historyRows.length; i++) {
        const btn = $(historyRows[i]).find('button[data-transaction-id="' + transactionId + '"]');
        if (btn.length > 0) {
            const cells = $(historyRows[i]).find('td');
            const appointmentId = btn.attr('data-appointment-id') || transactionId;
            return {
                id: transactionId,
                appointment_id: appointmentId,
                procedures: $(cells[0]).text().trim(),
                total: parseFloat($(cells[1]).text().replace('₱', '').trim()),
                status: $(cells[2]).text().trim(),
                appointment_date: $(cells[3]).text().trim(),
            };
        }
    }
    
    return null;
}

/**
 * Populate the details modal with appointment data
 * @param {Object} data - The appointment data
 */
function populateModalWithAppointmentData(data) {
    return populatePaymentModal(data);
}

/**
 * Show payment modal for a transaction
 * @param {string|number} transactionId - The transaction ID to pay for
 * @param {string|number} appointmentId - The appointment ID
 */
function showPaymentModal(transactionId, appointmentId) {
    // Use appointmentId if provided, otherwise use transactionId as appointmentId
    const actualAppointmentId = appointmentId || transactionId;
    
    console.log('Opening payment modal for transaction/appointment ID:', transactionId, 'with appointment ID:', actualAppointmentId);
    
    // Validate the appointment ID to prevent SQL errors
    if (!actualAppointmentId || actualAppointmentId === 'undefined') {
        console.error('Invalid appointment ID. Cannot process payment.', {
            transactionId,
            appointmentId
        });
        showToast('Error', 'Could not process payment. Invalid appointment data.', 'error');
        return;
    }
    
    // Verify this is the latest appointment
    if (actualAppointmentId != latestAppointmentId) {
        console.warn('Payment attempted for non-latest appointment:', {
            requestedAppointmentId: actualAppointmentId,
            latestAppointmentId: latestAppointmentId
        });
        showToast('Payment Restricted', 'Only the latest appointment can be paid.', 'warning');
        return;
    }
    
    // Clear previous form data
    $('#payment-form-transaction-id').val(actualAppointmentId); // Use the appointment ID as transaction ID
    $('#payment-form-appointment-id').val(actualAppointmentId);
    $('#payment-reference').val('');
    $('#payment-paid').val('');
    $('#qr-selection').val('');
    $('#qr-details').hide();
    $('#payment-alerts').empty();
    
    // Show modal with improved loading state
    $('#paymentModal').css('display', 'flex').hide().fadeIn(300);
    $('#paymentModal .modal-content').addClass('loading');
    
    // Add loading overlay
    $('.payment-grid').html(`
        <div class="full-modal-loader">
            <div class="loading-spinner"></div>
            <p>Preparing payment details...</p>
        </div>
    `);
    
    // Use the robust endpoint with appointment ID to fetch payment and QR information
    axios.get(`/payments/details-with-joins/${actualAppointmentId}`)
        .then(function (response) {
            console.log('Payment details from endpoint:', response);
            console.log('Payment and appointment data:', response.data);
            
            // Slight delay for better UX
            setTimeout(() => {
                $('#paymentModal .modal-content').removeClass('loading');
                
                if (response.data && response.data.appointment) {
                    // Get payment and appointment details
                    const appointmentData = response.data.appointment;
                    
                    // Make sure we use the correct IDs
                    $('#payment-form-transaction-id').val(appointmentData.appointment_id || actualAppointmentId);
                    $('#payment-form-appointment-id').val(appointmentData.appointment_id || actualAppointmentId);
                    
                    // Update payment modal with appointment details
                    updatePaymentModalWithAppointmentDetails(appointmentData);
                    
                    // Fetch active QR codes
                    fetchQrCodesForPayment(appointmentData.appointment_id || actualAppointmentId, appointmentData, actualAppointmentId);
                } else {
                    showPaymentError('Could not retrieve payment details');
                }
            }, 500);
        })
        .catch(function (error) {
            console.error('Error fetching payment details:', error);
            console.error('Error details:', error.response ? error.response.data : error.message);
            
            setTimeout(() => {
                $('#paymentModal .modal-content').removeClass('loading');
                showPaymentError('Error loading payment information');
            }, 500);
        });
}

/**
 * Update payment modal with appointment details
 * @param {Object} appointmentData - The appointment data
 */
function updatePaymentModalWithAppointmentDetails(appointmentData) {
    console.log('Setting up payment form with appointment data:', appointmentData);
    
    // Ensure we have valid IDs
    const appointmentId = appointmentData.appointment_id || appointmentData.id;
    const transactionId = appointmentData.payment_id || appointmentData.id;
    
    console.log('Using IDs for form:', { 
        appointmentId: appointmentId,
        transactionId: transactionId
    });
    
    // Create HTML for appointment details section
    const appointmentDetailsHtml = `
        <div class="appointment-details-section">
            <h4 class="section-title">Appointment Details</h4>
            <div class="detail-row">
                <div class="detail-label">Procedure:</div>
                <div class="detail-value">${appointmentData.procedures || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date:</div>
                <div class="detail-value">${formatDate(appointmentData.appointment_date)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Time:</div>
                <div class="detail-value">${appointmentData.appointment_time || appointmentData.preference || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value status-${appointmentData.status?.toLowerCase() || 'pending'}">${appointmentData.status || 'Pending'}</div>
            </div>
            ${appointmentData.remarks ? `
            <div class="detail-row">
                <div class="detail-label">Remarks:</div>
                <div class="detail-value">${appointmentData.remarks}</div>
            </div>
            ` : ''}
        </div>
    `;
    
    // Insert the appointment details at the top of the payment grid
    $('.payment-grid').html(appointmentDetailsHtml);
    
    // Add the payment section after appointment details
    $('.payment-grid').append(`
        <div class="payment-section">
            <h4 class="section-title">Payment Information</h4>
            <div class="payment-amount-info">
                <div class="amount-row">
                    <div class="amount-label">Total Amount:</div>
                    <div class="amount-value">₱${parseFloat(appointmentData.total || 0).toFixed(2)}</div>
                </div>
                <div class="amount-row">
                    <div class="amount-label">Amount Paid:</div>
                    <div class="amount-value">₱${parseFloat(appointmentData.paid || 0).toFixed(2)}</div>
                </div>
                <div class="amount-row highlight">
                    <div class="amount-label">Balance:</div>
                    <div class="amount-value">₱${parseFloat(appointmentData.total - appointmentData.paid || 0).toFixed(2)}</div>
                </div>
            </div>
            
            <form id="paymentForm" enctype="multipart/form-data">
                <input type="hidden" id="payment-form-transaction-id" name="transaction_id" value="${appointmentId}">
                <input type="hidden" id="payment-form-appointment-id" name="appointment_id" value="${appointmentId}">
                <input type="hidden" id="payment-form-qr-id" name="qr_id">
            
            <div id="qr-selection-container" class="form-group mt-4">
                <label for="qr-selection">Select Payment QR Code:</label>
                    <select id="qr-selection" class="form-control" required>
                    <option value="">Select a payment QR code</option>
                </select>
            </div>
            
            <div id="qr-details" class="mt-3" style="display: none;"></div>
            
            <div class="form-group mt-3">
                <label for="payment-paid" class="required">Payment Amount:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">₱</span>
                    </div>
                        <input type="number" id="payment-paid" name="paid_amount" class="form-control" required min="1" step="0.01" placeholder="Enter payment amount" value="${parseFloat(appointmentData.total || 0).toFixed(2)}">
                </div>
            </div>
            
            <div class="form-group mt-3">
                <label for="payment-reference" class="required">Reference Number:</label>
                <input type="text" id="payment-reference" name="reference_number" class="form-control" required placeholder="Enter your payment reference number">
                <small class="form-text text-muted">This is the reference number from your GCash transaction</small>
            </div>
            
            <div class="form-group mt-3">
                <label for="payment-receipt">Receipt Screenshot (Optional):</label>
                <input type="file" id="payment-receipt" name="receipt" class="form-control-file" accept="image/jpeg,image/png,image/jpg">
                <div id="receipt-preview" class="mt-2"></div>
            </div>
            
            <div id="payment-alerts" class="mt-3"></div>
                
                <div class="form-group mt-4 text-center">
                    <button type="submit" id="payment-submit-btn" class="btn-primary-action">
                        <i class="fas fa-check-circle"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    `);
    
    // Log the form ID values after creation
    console.log('Payment form IDs after setup:', {
        'payment-form-transaction-id': $('#payment-form-transaction-id').val(),
        'payment-form-appointment-id': $('#payment-form-appointment-id').val()
    });
    
    // Reinitialize event handlers for the form
    initializePaymentFormHandlers();
}

/**
 * Initialize event handlers specific to the payment form
 */
function initializePaymentFormHandlers() {
    console.log('Initializing payment form handlers');

    // Payment form submission
    $('#paymentForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Get transaction ID from form (more reliable than parameter)
        const transactionId = $('#payment-form-transaction-id').val();
        
        console.log('Form submitted with transaction_id:', transactionId);
        
        submitPayment(transactionId);
    });
    
    // QR selection change with animation
    $('#qr-selection').off('change').on('change', function() {
        const qrId = $(this).val();
        if (qrId) {
            // Log QR selection
            console.log('QR selected by user - QR ID:', qrId);
            
            // Show loading state with a pulse animation
            $('#qr-details').html(`
                <div class="text-center p-4">
                    <div class="loading-spinner"></div>
                    <p class="mt-3">Loading QR code...</p>
                </div>
            `).fadeIn(300);
            
            // Add highlight effect to the dropdown to show it's processing
            $(this).addClass('active-selection');
            setTimeout(() => $(this).removeClass('active-selection'), 1000);
            
            // Store selected QR ID in the form
            $('#payment-form-qr-id').val(qrId);
            
            // Update QR details with animation
            updateQrDetails(qrId);
        } else {
            console.log('No QR selected - dropdown cleared');
            $('#qr-details').fadeOut(300, function() {
                $(this).html('').show();
            });
            
            // Clear stored QR ID
            $('#payment-form-qr-id').val('');
        }
    });
    
    // Receipt image preview functionality
    $(document).off('change', '#payment-receipt').on('change', '#payment-receipt', function() {
        const file = this.files[0];
        const previewContainer = $('#receipt-preview');
        
        if (file) {
            // Clear previous preview
            previewContainer.empty();
            
            // Validate file type
            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                previewContainer.html('<div class="file-error">Invalid file type. Please upload a JPG or PNG image.</div>');
                return;
            }
            
            // Create file reader for preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.html(`
                    <div class="receipt-preview-container">
                        <img src="${e.target.result}" alt="Receipt preview" class="receipt-preview-image">
                        <button type="button" class="remove-receipt-btn" title="Remove receipt">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.empty();
        }
    });
    
    // Remove receipt image
    $(document).off('click', '.remove-receipt-btn').on('click', '.remove-receipt-btn', function(e) {
        e.preventDefault();
        $('#payment-receipt').val('');
        $('#receipt-preview').empty();
    });
}

/**
 * Fetch payment details for a specific appointment
 * @param {Object} appointment - The appointment object
 */
function fetchPaymentForAppointment(appointment) {
    console.log('Setting up payment details for appointment ID:', appointment.id);
    
    // Ensure we have a valid appointment ID before making the API call
    if (!appointment || !appointment.id || isNaN(parseInt(appointment.id))) {
        console.error('Invalid appointment ID for payment details fetch:', appointment);
        
        // Create default payment object
        const payment = {
            id: null,
            total: appointment.fee || 0,
            paid: 0,
            status: appointment.status || 'Pending',
            reference_number: null,
            qr_id: null
        };
        
        updateProgressTracker(appointment.status);
        renderLatestAppointment(appointment, payment);
        return;
    }
    
    // Fetch payment details for this appointment using robust endpoint
    axios.get(`/payments/details-with-joins/${appointment.id}`)
        .then(function (response) {
            console.log('Payment details response:', response);
            console.log('Payment data:', response.data);
            
            if (response.data && response.data.appointment) {
                const paymentDetails = response.data.appointment;
                
                // Create payment object from response
                const payment = {
                    id: paymentDetails.payment_id || null,
                    total: parseFloat(paymentDetails.total || 0),
                    paid: parseFloat(paymentDetails.paid || 0),
                    status: paymentDetails.status,
                    reference_number: paymentDetails.reference_number || null,
                    qr_id: paymentDetails.qr_id
                };
                
                console.log('Using payment details:', payment);
                
                // Update progress tracker based on appointment status
                updateProgressTracker(appointment.status);
                
                // Render appointment in table with payment data
                renderLatestAppointment(appointment, payment);
            } else {
                console.log('No payment details found for appointment, using defaults');
                
                // Create default payment object
                const payment = {
                    id: null,
                    total: appointment.fee || 0,
                    paid: 0,
                    status: appointment.status || 'Pending',
                    reference_number: null,
                    qr_id: null
                };
                
                // Update progress tracker and render with default payment
                updateProgressTracker(appointment.status);
                renderLatestAppointment(appointment, payment);
            }
        })
        .catch(function (error) {
            console.error('Error fetching payment details:', error);
            console.error('Error details:', error.response ? error.response.data : error.message);
            
            // Still render the appointment but with default payment data
            const payment = {
                id: null,
                total: appointment.fee || 0,
                paid: 0,
                status: appointment.status || 'Pending',
                reference_number: null,
                qr_id: null
            };
            
            updateProgressTracker(appointment.status);
            renderLatestAppointment(appointment, payment);
        });
}

/**
 * Format a date string to a more readable format
 * @param {string} dateStr - Date string to format
 * @returns {string} Formatted date string
 */
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Fetch the latest appointment using appointment/fetch endpoint
 */
function fetchLatestAppointmentFromDashboard() {
    console.log('Fetching latest appointment from appointment/fetch endpoint');
    
    // Show loading state in the latest appointment container
    $('#latest-appointment-container').html(`
        <div class="text-center p-4">
            <div class="loading-spinner"></div>
            <p class="mt-3">Loading latest appointment data...</p>
        </div>
    `);
    
    // Use the appointment/fetch endpoint which returns an array of appointments
    axios.post('/appointment/fetch')
        .then(function(response) {
            console.log('Latest appointment response from appointment/fetch endpoint:', response);
            
            const appointments = response.data;
            
            // Check if there are any appointments
            if (Array.isArray(appointments) && appointments.length > 0) {
                // Get the first (latest) appointment
                const appointment = appointments[0];
                const appointmentId = appointment.id;
                
                // Store the latest appointment ID in the global variable
                latestAppointmentId = appointmentId;
                console.log('Found latest appointment, ID:', latestAppointmentId);
                
                // Check if the appointment status is valid for "latest" (not cancelled)
                const status = (appointment.status || '').toLowerCase();
                
                // Only show as latest if status is pending, accepted, ongoing, or completed
                if (status === 'pending' || status === 'accepted' || status === 'ongoing' || status === 'completed') {
                    // Create initial appointment object with data from the endpoint
                    const appointmentData = {
                        id: appointmentId,
                        procedures: appointment.procedures,
                        appointment_date: appointment.appointment_date,
                        formatted_time: appointment.formatted_time,
                        preference: appointment.preference || appointment.formatted_time,
                        status: appointment.status,
                        hours_difference: appointment.hours_difference
                    };
                    
                    // Fetch payment details for this appointment
                    fetchPaymentForAppointment(appointmentData);
                    
                    // If there's an active transaction with pending status, show payment option
                    if (status === 'pending') {
                        showActiveTransactionPaymentButton(appointmentData);
                    }
                    
                    // Update progress tracker based on appointment status
                    updateProgressTracker(status);
                } else {
                    // Status is cancelled, don't show as latest
                    console.log('Appointment status is not valid for latest section:', status);
                    showNoAppointmentsMessage();
                    
                    // Hide the progress tracker
                    $('.payment-status-tracker').hide();
                }
            } else {
                // Handle case with no appointments
                console.log('No appointments found in the response');
                latestAppointmentId = null;
                showNoAppointmentsMessage();
                
                // Hide the progress tracker
                $('.payment-status-tracker').hide();
            }
        })
        .catch(function(error) {
            console.error('Error fetching latest appointment from appointment/fetch endpoint:', error);
            console.error('Error details:', error.response ? error.response.data : error.message);
            
            // Show error state with retry button
            $('#latest-appointment-container').html(`
                <div class="error-state">
                    <i class="fas fa-exclamation-circle error-icon"></i>
                    <p>Error loading appointment information.</p>
                    <p class="text-muted small">${error.response ? error.response.data.message || 'Server error' : error.message}</p>
                    <button class="btn btn-secondary btn-sm mt-3 refresh-btn" id="retry-fetch-appointment">
                        <i class="fas fa-sync-alt"></i> Retry
                    </button>
                </div>
            `);
            
            // Add event listener for retry button
            $('#retry-fetch-appointment').on('click', function() {
                fetchLatestAppointmentFromDashboard();
            });
            
            // Hide the progress tracker
            $('.payment-status-tracker').hide();
        });
}

/**
 * Show no appointments message
 * @param {string} type - Type of message to show ('active' for no active appointments, empty for no appointments at all)
 */
function showNoAppointmentsMessage(type) {
    // Simple bold BOOK NOW! message as requested by the user
    const message = `
        <div class="no-data-state text-center">
            <h3 class="book-now-message" style="font-weight: bold; font-size: 24px; margin: 20px 0;">BOOK NOW!</h3>
          
                <i class="fas fa-plus-circle"></i> Book an Appointment
            </a>
        </div>
    `;
    
    $('#latest-appointment-table').html(`
        <tr>
            <td colspan="5" class="text-center">
                ${message}
            </td>
        </tr>
    `);
    
    // Add event listener to prevent default behavior and handle click with JavaScript
    $(document).off('click', '#bookNowBtn').on('click', '#bookNowBtn', function(e) {
        e.preventDefault();
        window.location.href = '/dashboard';
    });
}

/**
 * Show a prominent button to pay the active transaction
 * @param {Object} appointment - The appointment data
 */
function showActiveTransactionPaymentButton(appointment) {
    console.log('Showing payment button for active transaction:', appointment.id);
    
    // Don't show payment button if payment is already pending or paid
    if (appointment.payment_status && 
        (appointment.payment_status.toLowerCase() === 'pending' || 
         appointment.payment_status.toLowerCase() === 'paid')) {
        console.log('Payment already pending or paid, not showing button');
        return;
    }
    
    // No need to show payment button
    console.log('Not showing payment button per requirements');
}

/**
 * Fetch and display payment history
 */
function fetchPaymentHistory() {
    console.log('Fetching payment history');
    
    // Show loading state
    $('#paymentHistory').html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="loading-spinner"></div>
                <p class="mt-2">Loading payment history...</p>
            </td>
        </tr>
    `);
    
    axios.get('/payments/history-with-joins')
        .then(function(response) {
            console.log('Payment history response:', response);
            
            // Check if we have valid data before rendering
            if (response.data && Array.isArray(response.data)) {
                // Render payment history data
                renderPaymentHistory(response.data);
            } else {
                console.error('Invalid payment history data received:', response.data);
                showPaymentHistoryError('Invalid payment history data received');
            }
        })
        .catch(function(error) {
            console.error('Error fetching payment history:', error);
            console.error('Error details:', error.response ? error.response.data : error.message);
            
            // Show error state
            showPaymentHistoryError(error.response ? error.response.data.message || 'Server error' : error.message);
        });
}

/**
 * Show payment history error message
 * @param {string} message - Error message to display
 */
function showPaymentHistoryError(message) {
    $('.payment-history-card .card-body').html(`
        <div class="error-state">
            <i class="fas fa-exclamation-circle error-icon"></i>
            <p>Error loading payment history.</p>
            <p class="text-muted small">${message}</p>
            <button class="btn btn-secondary btn-sm mt-3 refresh-history-btn" id="retry-fetch-history">
                <i class="fas fa-sync-alt"></i> Retry
            </button>
        </div>
    `);
    
    // Add event listener for retry button
    $('#retry-fetch-history').on('click', function() {
        fetchPaymentHistory();
    });
}

/**
 * Render the payment history table
 * @param {Array} payments - Array of payment objects
 */
function renderPaymentHistory(payments) {
    const tableBody = $('#paymentHistory');
    tableBody.empty();
    
    if (!payments || payments.length === 0) {
        // Show no data state
        $('#payment-history-container .card-body').html(`
            <div class="no-data-state">
                <i class="fas fa-history no-data-icon"></i>
                <p>No payment history found.</p>
            </div>
        `);
        return;
    }
    
    // Sort payments by date (newest first)
    payments.sort((a, b) => {
        const dateA = new Date(a.appointment_date || a.date || 0);
        const dateB = new Date(b.appointment_date || b.date || 0);
        return dateB - dateA;
    });
    
    // Add each payment to the table
    payments.forEach(payment => {
        const procedures = payment.procedures || 'N/A';
        const total = parseFloat(payment.total || payment.balance || 0).toFixed(2);
        
        // Determine payment status based on data
        let paymentStatus;
        if (payment.status) {
            paymentStatus = payment.status;
        } else if (payment.appointment_status === 'completed') {
            paymentStatus = 'Paid';
        } else if (payment.appointment_status === 'cancelled') {
            paymentStatus = 'Cancelled';
        } else {
            paymentStatus = 'Unpaid';
        }
        
        const date = formatDate(payment.appointment_date || payment.date || '');
        const transactionId = payment.transaction_id || payment.id || '';
        const appointmentId = payment.appointment_id || '';
        
        const statusClass = getStatusClass(paymentStatus);
        
        // Determine if Pay Now button should be shown
        const showPayNowButton = 
            paymentStatus.toLowerCase() !== 'paid' && 
            paymentStatus.toLowerCase() !== 'completed' && 
            paymentStatus.toLowerCase() !== 'cancelled';
        
        const row = `
            <tr>
                <td>${procedures}</td>
                <td>₱${total}</td>
                <td><span class="status-badge ${statusClass}">${paymentStatus}</span></td>
                <td>${date}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-view" data-transaction-id="${transactionId}" data-appointment-id="${appointmentId}">
                        <i class="fas fa-eye"></i> View
                    </button>
                    ${showPayNowButton ? `
                    <button class="btn btn-sm btn-pay" data-transaction-id="${transactionId}" data-appointment-id="${appointmentId}">
                        <i class="fas fa-money-bill"></i> Pay Now
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
        
        tableBody.append(row);
    });
    
    // Show the table
    $('#payment-history-table').show();
}

/**
 * Get CSS class for status badge
 * @param {string} status - Status text
 * @returns {string} CSS class name
 */
function getStatusClass(status) {
    const statusLower = (status || '').toLowerCase();
    
    if (statusLower === 'completed' || statusLower === 'paid') {
        return 'status-completed';
    } else if (statusLower === 'pending' || statusLower === 'processing') {
        return 'status-pending';
    } else if (statusLower === 'cancelled' || statusLower === 'unpaid') {
        return 'status-cancelled';
    } else if (statusLower === 'ongoing' || statusLower === 'accepted' || statusLower === 'verified') {
        return 'status-ongoing';
    }
    
    return 'status-default';
}

/**
 * Update the progress tracker based on payment status
 * @param {string} status - Payment or appointment status
 */
function updateProgressTracker(status) {
    // Get status in lowercase for comparison
    const statusLower = (status || '').toLowerCase();
    
    // Default step is 1 (Unpaid)
    let currentStep = 1;
    
    // Map appointment statuses to payment steps
    if (statusLower === 'pending' || statusLower === 'processing') {
        currentStep = 2; // Processing
    } else if (statusLower === 'accepted' || statusLower === 'ongoing' || statusLower === 'verified') {
        currentStep = 3; // Verified
    } else if (statusLower === 'completed' || statusLower === 'paid') {
        currentStep = 4; // Paid
    } else if (statusLower === 'cancelled') {
        // For cancelled, we'll show a special state
        $('.progress-tracker').addClass('cancelled');
        return;
    }
    
    // Reset any special classes
    $('.progress-tracker').removeClass('cancelled');
    
    // Update step classes
    $('.progress-step').removeClass('active completed');
    
    // Mark steps as active or completed
    for (let i = 1; i <= 4; i++) {
        const step = $(`.progress-node#node${i}`);
        
        if (i < currentStep) {
            step.addClass('completed');
        } else if (i === currentStep) {
            step.addClass('active');
        }
    }
    
    // Show the progress tracker
    $('.payment-status-tracker').show();
}

/**
 * Render the latest appointment in the UI
 * @param {Object} appointment - Appointment data
 * @param {Object} payment - Payment data
 */
function renderLatestAppointment(appointment, payment) {
    console.log('Rendering latest appointment:', appointment);
    console.log('With payment details:', payment);
    
    const tableBody = $('#latest-appointment-table');
    tableBody.empty();
    
    if (!appointment) {
        // Show "BOOK NOW!" message when no data is available
        showNoAppointmentsMessage();
        
        // Hide the progress tracker
        $('.payment-status-tracker').hide();
        return;
    }
    
    // Format data for display
    const procedures = appointment.procedures || appointment.service_name || 'N/A';
    const total = parseFloat(payment.total || appointment.fee || 0).toFixed(2);
    
    // Determine payment status based on various sources
    let paymentStatus;
    if (payment && payment.status) {
        paymentStatus = payment.status;
    } else if (appointment.status === 'completed') {
        paymentStatus = 'Paid';
    } else if (appointment.status === 'cancelled') {
        paymentStatus = 'Cancelled';
    } else {
        // Default case - appointment exists but no payment info
        paymentStatus = 'Unpaid';
    }
    
    const date = formatDate(appointment.appointment_date || appointment.date || '');
    const transactionId = payment.id || appointment.id;
    const appointmentId = appointment.id;
    
    const statusClass = getStatusClass(paymentStatus);
    
    // Determine if Pay Now button should be shown
    const showPayNowButton = 
        paymentStatus.toLowerCase() !== 'paid' && 
        paymentStatus.toLowerCase() !== 'completed' && 
        paymentStatus.toLowerCase() !== 'cancelled';
    
    // Create table row
    const row = `
        <tr>
            <td>${procedures}</td>
            <td>₱${total}</td>
            <td><span class="status-badge ${statusClass}">${paymentStatus}</span></td>
            <td>${date}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-view" data-transaction-id="${transactionId}" data-appointment-id="${appointmentId}">
                    <i class="fas fa-eye"></i> View
                </button>
                ${showPayNowButton ? `
                <button class="btn btn-sm btn-pay" data-transaction-id="${transactionId}" data-appointment-id="${appointmentId}">
                    <i class="fas fa-money-bill"></i> Pay Now
                </button>
                ` : ''}
            </td>
        </tr>
    `;
    
    // Add row to table
    tableBody.append(row);
    
    // Show the table
    $('#latest-appointment-table-container').show();
    
    // Update the payment status tracker
    updateProgressTracker(paymentStatus);
}

/**
 * Fetch QR codes for payment
 * @param {string|number} transactionId - The transaction ID
 * @param {Object} paymentData - Payment data object
 * @param {string|number} appointmentId - The appointment ID
 */
function fetchQrCodesForPayment(transactionId, paymentData, appointmentId) {
    console.log('Fetching QR codes for payment:', transactionId);
    console.log('Payment data available:', paymentData);
    
    // Show loading state
        $('#qr-selection-container').show();
    $('#qr-selection').html('<option value="">Loading payment methods...</option>');
        
    // Fetch active QR codes - use the original path that was working
    axios.get('/qr-codes/active')
        .then(function (response) {
            console.log('QR codes response:', response);
            
            const qrCodes = response.data || [];
            // Cache the QR codes for later use
            cachedQrCodes = qrCodes;
            
            if (qrCodes.length === 0) {
                $('#qr-selection-container').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No payment QR codes available. Please contact support.
                    </div>
                `);
                return;
            }
            
            // Populate QR code selection dropdown
            const qrSelection = $('#qr-selection');
            qrSelection.empty();
            
            // Add default option
            qrSelection.append(`<option value="">Select payment method</option>`);
            
            // Add each QR code as an option
            qrCodes.forEach(qr => {
                // Use gcash_name from the schema if available, fallback to name
                const qrName = qr.gcash_name || qr.name || `GCash Account #${qr.id}`;
                qrSelection.append(`<option value="${qr.id}">${qrName}</option>`);
            });
            
            // Select QR ID from payment data if available
            if (paymentData.qr_id) {
                qrSelection.val(paymentData.qr_id);
                
                // Update QR details with this selection
                setTimeout(() => {
                    $('#qr-selection').trigger('change');
                }, 300);
            }
        })
        .catch(function (error) {
            console.error('Error fetching QR codes:', error);
            
            $('#qr-selection-container').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error loading payment methods. Please try again or contact support.
                </div>
            `);
        });
}

/**
 * Update QR details based on selected QR code
 * @param {string|number} qrId - QR code ID
 */
function updateQrDetails(qrId) {
    console.log('Updating QR details for QR ID:', qrId);
    
    // Store selected QR ID in the form
    $('#payment-form-qr-id').val(qrId);
    
    // If no QR ID selected, just hide the details
    if (!qrId) {
        $('#qr-details').hide();
        return;
    }
    
    // Simple HTML structure for displaying QR code
    const qrDetailsHtml = `
        <div class="qr-details-container">
            <div class="qr-image-container text-center">
                <div id="qr-loading" class="mb-2">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
            <p>Loading QR code...</p>
        </div>
                <img id="qrCodeImage" src="/images/loading-qr.gif" 
                    alt="Payment QR Code" class="qr-code-image d-none"
                    onerror="this.onerror=null; handleQrImageError(this, ${qrId});">
                <p class="scan-instruction mt-2">
                    <i class="fas fa-qrcode"></i>
                    Scan this QR code with your GCash app
                </p>
            </div>
            <div class="qr-details mt-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Details</h5>
                        <div class="detail-item">
                            <strong>Account Name:</strong>
                            <span id="qr-account-name">Loading...</span>
                </div>
                        <div class="detail-item">
                            <strong>Phone Number:</strong>
                            <span id="qr-account-number">Loading...</span>
                </div>
            </div>
                </div>
            </div>
        </div>
    `;
    
    // Set up the QR details container
    $('#qr-details').html(qrDetailsHtml).show();
    
    // Try to get the QR name from selection for immediate display
    const selectedQrName = $(`#qr-selection option[value="${qrId}"]`).text();
    if (selectedQrName) {
        $('#qr-account-name').text(selectedQrName);
    }
    
    // First check if we have this QR code in our cached list
    const qrFromCache = cachedQrCodes.find(qr => qr.id == qrId);
    
    if (qrFromCache) {
        console.log('Using QR data from cache:', qrFromCache);
        // Update account details from cache
        updateQrAccountDetails(qrFromCache);
        
        // Load QR image using path from cached data
        if (qrFromCache.image_path) {
            loadQrImageFromPath(qrFromCache.image_path);
        } else {
            // Try direct image loading based on ID and file pattern
            loadQrImageById(qrId);
        }
        
        return; // Skip the API call if we found it in cache
    }
    
    // If not in cache, fetch QR details from API
    axios.get(`/qr-codes/${qrId}`)
        .then(response => {
            console.log('QR details response:', response.data);
            
            let qrData = null;
            
            // Handle different response structures
            if (response.data && response.data.qrCode) {
                qrData = response.data.qrCode;
            } else if (response.data && response.data.data) {
                qrData = response.data.data;
            } else {
                qrData = response.data;
            }
            
            if (qrData) {
                // Update account details
                updateQrAccountDetails(qrData);
                
                // Load QR image using path from database
                if (qrData.image_path) {
                    loadQrImageFromPath(qrData.image_path);
                } else {
                    // Try direct image loading based on ID
                    loadQrImageById(qrId);
                }
            } else {
                // No data in response, show default values and image
                const name = $(`#qr-selection option[value="${qrId}"]`).text();
                $('#qr-account-name').text(name || 'GCash Account');
                $('#qr-account-number').text('Contact support for details');
                loadQrImageById(qrId); // Try to load image by ID pattern
            }
        })
        .catch(error => {
            console.error('Error fetching QR details:', error);
            // Use data from dropdown as fallback
            const name = $(`#qr-selection option[value="${qrId}"]`).text();
            $('#qr-account-name').text(name || 'GCash Account');
            $('#qr-account-number').text('Contact support for details');
            
            // Try to load QR image by ID directly since API failed
            loadQrImageById(qrId);
        });
}

/**
 * Load QR image directly by ID using known file patterns
 * @param {string|number} qrId - QR code ID
 */
function loadQrImageById(qrId) {
    console.log('Trying to load QR image by ID pattern for ID:', qrId);
    
    // Find actual files in file system based on observed pattern
    // Example: qr_images/1744132152_qr-bart.png, qr_images/67e7138078049.png
    const pathsToTry = [
        // Try direct match with ID
        `/qr_images/${qrId}.jpg`,
        `/qr_images/${qrId}.png`,
        
        // Look through all QR files for this ID
        // This is the fallback and will try all images
        ...['/qr_images/10.png',
          '/qr_images/1744132152_qr-bart.png',
          '/qr_images/1744059268_qr-bart.png',
          '/qr_images/1744059265_qr-bart.png',
          '/qr_images/1743563860_timetimetime.png',
          '/qr_images/1743562241_logo.png',
          '/qr_images/1743556504_logo.png',
          '/qr_images/1743555315_logo.png',
          '/qr_images/67e713b2d5b6d.png',
          '/qr_images/67e7138078049.png']
    ];
    
    console.log('Trying these paths to find the QR image:', pathsToTry);
    
    // Create test image to try different paths
    function tryNextPath(index) {
        if (index >= pathsToTry.length) {
            console.error('Failed to load QR image from all paths');
            $('#qr-loading').hide();
            $('#qrCodeImage').attr('src', '/images/default-qr.jpg').removeClass('d-none');
            return;
        }
        
        const testImg = new Image();
        testImg.onload = function() {
            console.log(`QR image loaded successfully from: ${pathsToTry[index]}`);
            $('#qr-loading').hide();
            $('#qrCodeImage').attr('src', pathsToTry[index]).removeClass('d-none');
        };
        testImg.onerror = function() {
            console.log(`Failed to load QR image from: ${pathsToTry[index]}`);
            tryNextPath(index + 1);
        };
        testImg.src = pathsToTry[index];
    }
    
    // Start trying paths
    tryNextPath(0);
}

/**
 * Update QR account details
 * @param {Object} qrData - QR data object
 */
function updateQrAccountDetails(qrData) {
    if (!qrData) {
        console.error('No QR data provided to updateQrAccountDetails');
        return;
    }
    
    // Set account name using values from QR record
    // The schema has 'name' for the account holder name and 'gcash_name' for display name
    const accountName = qrData.gcash_name || qrData.name || 'GCash Account';
    
    // Set account number from the 'number' field
    const accountNumber = qrData.number || 'Contact support for details';
    
    console.log('Updating QR details with name:', accountName, 'and number:', accountNumber);
    
    // Update UI elements
    $('#qr-account-name').text(accountName);
    $('#qr-account-number').text(accountNumber);
}

/**
 * Show QR error message
 * @param {string} message - Error message to display
 */
function showQrError(message) {
    $('#qr-details').html(`
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            ${message}
        </div>
    `);
}

/**
 * Show payment error in modal
 * @param {string} message - Error message to display
 */
function showPaymentError(message) {
    $('.payment-grid').html(`
        <div class="alert alert-danger payment-error">
            <i class="fas fa-exclamation-circle"></i>
            ${message}
        </div>
    `);
}

/**
 * Submit payment form
 * @param {string|number} transactionId - The transaction ID
 */
function submitPayment(transactionId) {
    console.log('submitPayment called with transactionId:', transactionId);
    
    // Get the actual transaction ID from the form first (more reliable)
    const formTransactionId = $('#payment-form-transaction-id').val();
    const formAppointmentId = $('#payment-form-appointment-id').val();
    
    // Use the most reliable ID available
    const actualId = formTransactionId || formAppointmentId || transactionId;
    
    console.log('Payment form IDs:', {
        'Original transactionId parameter': transactionId,
        'Form transaction_id': formTransactionId,
        'Form appointment_id': formAppointmentId,
        'Using ID': actualId
    });
    
    // Validate form before submission
    if (!validatePaymentForm()) {
        return;
    }
    
    // Verify we have a valid transaction ID
    if (!actualId || actualId === 'undefined') {
        $('#payment-alerts').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Invalid transaction ID. Please reload the page and try again.
            </div>
        `);
        return;
    }
    
    // Show loading state
    $('#payment-submit-btn').prop('disabled', true).html(`
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        Processing...
    `);
    
    // Get payment amount
    const paidAmount = $('#payment-paid').val();
    
    // Get form data
    const formData = new FormData();
    formData.append('transaction_id', actualId);
    formData.append('reference_number', $('#payment-reference').val());
    formData.append('paid_amount', paidAmount);
    
    // Use paid amount as total if not specified
    formData.append('total', paidAmount);
    
    formData.append('qr_id', $('#payment-form-qr-id').val());
    
    // Add status field to set it to 'paid'
    formData.append('status', 'paid');
    
    // Append receipt image if provided
    const receiptFile = $('#payment-receipt')[0].files[0];
    if (receiptFile) {
        formData.append('receipt', receiptFile);
    }
    
    // For debugging - log the form data
    console.log('Submitting payment with data:', {
        transaction_id: actualId,
        reference_number: $('#payment-reference').val(),
        paid_amount: paidAmount,
        total: paidAmount,
        qr_id: $('#payment-form-qr-id').val(),
        status: 'paid',
        has_receipt: !!receiptFile
    });
    
    // Submit payment via API
    axios.post('/payments/submit', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then(function (response) {
            console.log('Payment submission response:', response);
            
            // Reset button state
            $('#payment-submit-btn').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit Payment');
            
            // Show success message with paid status
            $('#payment-alerts').html(`
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Payment submitted successfully! Your payment status has been updated to <strong>PAID</strong>. 
                    Your appointment remains <strong>PENDING</strong> for administrative review.
                </div>
            `);
            
            // Update local status in UI immediately
            updatePaymentStatusInUI(actualId, 'paid');
            
            // Close modal and refresh data after delay
            setTimeout(() => {
                $('#paymentModal').fadeOut(300);
                
                // Refresh appointment and payment data
                fetchLatestAppointmentFromDashboard();
                fetchPaymentHistory();
                
                // Show success toast
                showToast('Payment Confirmed', 'Your payment has been processed and marked as PAID!', 'success');
            }, 2000);
        })
        .catch(function (error) {
            console.error('Error submitting payment:', error);
            console.error('Error details:', error.response ? error.response.data : error.message);
            
            // Reset button state
            $('#payment-submit-btn').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit Payment');
            
            // Show error message
            $('#payment-alerts').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ${error.response && error.response.data.message 
                        ? error.response.data.message 
                        : 'Error submitting payment. Please try again.'}
                </div>
            `);
        });
}

/**
 * Update payment status in UI elements
 * @param {string|number} transactionId - The transaction ID
 * @param {string} status - The new status
 */
function updatePaymentStatusInUI(transactionId, status) {
    // Update status in latest appointment table
    const statusClass = getStatusClass(status);
    
    // Find all rows with this transaction ID in both tables
    $(`#latest-appointment-table tr button[data-transaction-id="${transactionId}"]`).each(function() {
        const row = $(this).closest('tr');
        const statusCell = row.find('td:nth-child(3)');
        statusCell.html(`<span class="status-badge ${statusClass}">${status}</span>`);
        
        // Remove pay now button if status is paid
        if (status.toLowerCase() === 'paid') {
            row.find('.btn-pay').remove();
        }
    });
    
    // Also update in payment history table
    $(`#paymentHistory tr button[data-transaction-id="${transactionId}"]`).each(function() {
        const row = $(this).closest('tr');
        const statusCell = row.find('td:nth-child(3)');
        statusCell.html(`<span class="status-badge ${statusClass}">${status}</span>`);
        
        // Remove pay now button if status is paid
        if (status.toLowerCase() === 'paid') {
            row.find('.btn-pay').remove();
        }
    });
    
    // Don't update progress tracker since appointment status is still pending
    // The appointment status doesn't change when payment is made
    // if ($('.progress-tracker').is(':visible')) {
    //     updateProgressTracker('paid');
    // }
}

/**
 * Validate payment form
 * @returns {boolean} True if form is valid
 */
function validatePaymentForm() {
    // Clear previous errors
    $('#payment-alerts').empty();
    let errors = [];
    
    // Check QR selection
    if (!$('#payment-form-qr-id').val()) {
        errors.push('Please select a payment QR code.');
    }
    
    // Check payment amount
    const paymentAmount = $('#payment-paid').val();
    if (!paymentAmount || isNaN(parseFloat(paymentAmount)) || parseFloat(paymentAmount) <= 0) {
        errors.push('Please enter a valid payment amount.');
    }
    
    // Check reference number
    const referenceNumber = $('#payment-reference').val();
    if (!referenceNumber || referenceNumber.trim() === '') {
        errors.push('Please enter a reference number from your GCash transaction.');
    }
    
    // If there are errors, display them and return false
    if (errors.length > 0) {
        let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
        errors.forEach(error => {
            errorHtml += `<li>${error}</li>`;
        });
        errorHtml += '</ul></div>';
        
        $('#payment-alerts').html(errorHtml);
        return false;
    }
    
    return true;
}

/**
 * Show toast notification
 * @param {string} title - Toast title
 * @param {string} message - Toast message
 * @param {string} type - Toast type (success, error, warning, info)
 */
function showToast(title, message, type = 'info') {
    // Check if toast container exists, create if not
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container"></div>');
    }
    
    // Create toast ID
    const toastId = 'toast-' + Date.now();
    
    // Determine toast icon
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    
    // Create toast element
    const toast = `
        <div id="${toastId}" class="toast toast-${type}">
            <div class="toast-header">
                <i class="fas ${icon} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Add toast to container
    $('#toast-container').append(toast);
    
    // Show toast with animation
    $(`#${toastId}`).animate({
        right: '20px',
        opacity: 1
    }, 300);
    
    // Auto-hide after delay
    setTimeout(() => {
        $(`#${toastId}`).animate({
            right: '-320px',
            opacity: 0
        }, 300, function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Directly populate the payment modal fields based on the structure in the screenshot
 * @param {Object} data - The payment/appointment data
 */
function populatePaymentModal(data) {
    console.log('Direct population of payment modal with data:', data);
    
    try {
        // Get key data fields with fallbacks
        const transactionId = data.payment_id || data.transaction_id || data.id || '';
        const appointmentId = data.appointment_id || data.id || '';
        const procedures = data.procedures || data.service_name || 'N/A';
        const remarks = data.remarks || 'N/A';
        const balance = parseFloat(data.total || 0).toFixed(2);
        const paid = data.paid ? parseFloat(data.paid).toFixed(2) : '0.00';
        const refNumber = data.reference_number || 'N/A';
        const appDate = formatDate(data.appointment_date || data.date || '');
        const timePreference = data.preference || data.appointment_time || 'N/A';
        const gcashAccount = data.qr_gcash_name || data.payment_recipient || 'N/A';
        const status = data.status || 'Pending';
        
        // Update modal fields
        $('#modalTransactionId').text(transactionId);
        $('#modalProcedures').text(procedures);
        $('#modalRemarks').text(remarks);
        $('#modalBalance').text('₱' + balance);
        $('#modalPaidStatus').text('₱' + paid);
        $('#modalReferenceNumber').text(refNumber);
        $('#modalAppointmentDate').text(appDate);
        $('#modalPreference').text(timePreference);
        $('#modalQrName').text(gcashAccount);
        $('#modalStatus').text(status).removeClass().addClass('appointment-status status-' + status.toLowerCase());
        
        // Handle the action buttons visibility
        const payButton = $('#modalCompleteButton');
        
        // Hide the button by default
        payButton.hide();
        
        // Determine if Pay Now button should be shown
        const showPayNowButton = 
            status.toLowerCase() !== 'paid' && 
            status.toLowerCase() !== 'completed' && 
            status.toLowerCase() !== 'cancelled';
        
        if (showPayNowButton) {
            // Show the Pay Now button and set data attributes
            payButton.html('<i class="fas fa-money-bill"></i> Pay Now').show()
                .attr('data-transaction-id', transactionId)
                .attr('data-appointment-id', appointmentId)
                .addClass('btn-pay-now');
            
            // Add direct click handler
            payButton.off('click').on('click', function() {
                // Add click feedback animation
                $(this).addClass('button-pulse');
                setTimeout(() => {
                    $(this).removeClass('button-pulse');
                }, 300);
                
                // Close the current modal
                $('#appointmentDetailsModal').fadeOut(300);
                
                // Open payment modal with both IDs
                showPaymentModal(transactionId, appointmentId);
            });
        }
        
        console.log('Payment modal fields populated successfully.');
    } catch (e) {
        console.error('Error populating payment modal:', e);
    }
}

/**
 * Handle QR image loading error with fallbacks
 * @param {HTMLImageElement} img - Image element
 * @param {string|number} qrId - QR code ID
 */
window.handleQrImageError = function(img, qrId) {
    console.error('QR image loading failed:', img.src);
    
    // Use default QR image
    img.src = '/images/default-qr.jpg';
    
    // Log error for debugging
    console.warn('Using default QR image as fallback for QR ID:', qrId);
};

/**
 * Load QR image from a specific path
 * @param {string} imagePath - The image path from database
 */
function loadQrImageFromPath(imagePath) {
    // Check if path exists
    if (!imagePath) {
        console.error('No image path provided');
        $('#qr-loading').hide();
        $('#qrCodeImage').attr('src', '/images/default-qr.jpg').removeClass('d-none');
        return;
    }

    console.log('Loading QR image from path:', imagePath);
    
    // Build paths to try based on the actual file structure we observed
    // Based on files like qr_images/1744132152_qr-bart.png
    const cleanPath = imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;
    
    // Paths to try in order based on what we saw in the file system
    const pathsToTry = [
        // Most likely paths first
        `/${cleanPath}`,                  // With leading slash (most likely)
        cleanPath,                        // Exactly as stored in DB
        
        // Alternative paths
        `/public/${cleanPath}`,           // With public prefix
        `/storage/${cleanPath}`,          // With storage prefix
        
        // Fallback with just the filename
        `/qr_images/${cleanPath.split('/').pop()}`  // Just the filename in qr_images dir
    ];
    
    console.log('Trying these paths in sequence:', pathsToTry);
    
    // Create test image to try different paths
    function tryNextPath(index) {
        if (index >= pathsToTry.length) {
            console.error('Failed to load QR image from all paths');
            $('#qr-loading').hide();
            $('#qrCodeImage').attr('src', '/images/default-qr.jpg').removeClass('d-none');
            return;
        }
        
        const testImg = new Image();
        testImg.onload = function() {
            console.log(`QR image loaded successfully from: ${pathsToTry[index]}`);
            $('#qr-loading').hide();
            $('#qrCodeImage').attr('src', pathsToTry[index]).removeClass('d-none');
        };
        testImg.onerror = function() {
            console.log(`Failed to load QR image from: ${pathsToTry[index]}`);
            tryNextPath(index + 1);
        };
        testImg.src = pathsToTry[index];
    }
    
    // Start trying paths
    tryNextPath(0);
}
