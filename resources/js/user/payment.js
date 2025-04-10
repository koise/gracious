import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    fetchLatestAppointment();
    fetchPaymentHistory();
});

let latestTransaction = 0;

$(document).on('click', '#submitPaymentBtn', function () {
    // Use the transaction ID from the payment form or hidden input
    const transactionId = $('#payment-transaction-id').text() || $('#payment-form-transaction-id').val();
    updatePayment(transactionId);
});

$(document).on('click', '#closePaymentModal', function () {
    $('#paymentModal').hide();
});

$(document).on('click', '#closeDetailbtn, .close-modal-btn', function () {
    $('#appointmentDetailsModal').css('display', 'none');
});

$(document).on('click', '.btn-pay', function () {
    var transactionId = $(this).data('transaction-id');
    $('#appointmentDetailsModal').css('display', 'none');
    showPaymentModal(transactionId);
});

// Specific event handler for the view button in payment history
$(document).on('click', '#paymentHistory .btn-primary', function() {
    var transactionId = $(this).data('transaction-id');
    console.log('Payment history view button clicked for transaction ID:', transactionId);
    viewPaymentDetails(transactionId);
});

// Specific event handler for the details button in latest appointments
$(document).on('click', '#latest-appointment-table .btn-primary', function() {
    var transactionId = $(this).data('transaction-id');
    console.log('Latest appointment details button clicked for transaction ID:', transactionId);
    viewDetails(transactionId);
});

// Separate function to view payment history details
function viewPaymentDetails(transactionId) {
    $('#appointmentDetailsModal').css('display', 'flex');
    
    console.log('Fetching payment history details for transaction ID:', transactionId);
    
    // Try to find the payment in the payment history table
    const paymentData = findPaymentInHistoryData(transactionId);
    
    if (paymentData) {
        populateModalWithAppointmentData(paymentData);
        return;
    }
    
    // If not found in the table, fetch from server
    $.ajax({
        url: '/payment/history',
        method: 'GET',
        success: function(data) {
            console.log('Fetched payment history:', data);
            
            if (!Array.isArray(data) || data.length === 0) {
                console.error('No payment history data available');
                $('#modalDetails').html('<div class="text-center text-danger">Payment details not found</div>');
                return;
            }
            
            // Find the specific payment by ID
            const payment = data.find(p => p.transaction_id == transactionId);
            
            if (payment) {
                populateModalWithAppointmentData(payment);
            } else {
                console.error('Payment not found in history data');
                $('#modalDetails').html('<div class="text-center text-danger">Payment details not found</div>');
            }
        },
        error: function(error) {
            console.error('Error fetching payment history details:', error);
            $('#modalDetails').html('<div class="text-center text-danger">Could not retrieve payment details</div>');
        }
    });
}
function viewDetails(transactionId) {
    $('#appointmentDetailsModal').css('display', 'flex');
    console.log('Fetching details for transaction ID:', transactionId);

    // First, try to find the appointment in the already loaded data
    const cachedAppointment = findAppointmentInExistingData(transactionId);

    if (cachedAppointment) {
        populateModalWithAppointmentData(cachedAppointment);
        return;
    }

    // If not found in cache, fetch from server
    $.ajax({
        url: '/dashboard/payment',
        method: 'GET',
        success: function(data) {
            console.log('Fetched all appointments:', data);

            let appointments = Array.isArray(data) ? data : [data];

            // Find the specific appointment by ID
            const appointment = appointments.find(app =>
                app.transaction_id == transactionId || app.id == transactionId
            );

            if (appointment) {
                populateModalWithAppointmentData(appointment);

                // Hide or show payment section if not paid
                if (appointment.payment_status !== 'paid') {
                    $('#paymentSection').hide();
                } else {
                    $('#paymentSection').show();
                }

                // Hide or show pay button if not pending
                if ((appointment.status || '').toLowerCase() !== 'pending'){
                    $('#modalPayButton').hide();
                } else {
                    $('#modalPayButton').show();
                }
            } else {
                console.error('Appointment not found in response data');
                $('#modalDetails').html('<div class="text-center text-danger">Appointment details not found</div>');
            }
        },
        error: function(error) {
            console.error('Error fetching appointment details:', error);
            $('#modalDetails').html('<div class="text-center text-danger">Could not retrieve appointment details</div>');
        }
    });
}


// Helper function to find a payment in history data
function findPaymentInHistoryData(transactionId) {
    // Try to extract from the payment history table
    const rows = $("#paymentHistory tr");
    for (let i = 0; i < rows.length; i++) {
        const btn = $(rows[i]).find('button[data-transaction-id="' + transactionId + '"]');
        if (btn.length > 0) {
            // Extract data from the row
            const cells = $(rows[i]).find('td');
            return {
                transaction_id: transactionId,
                service_name: $(cells[0]).text(),
                balance: parseFloat($(cells[1]).text().replace('₱', '')),
                status: $(cells[2]).text().toLowerCase(),
                date: new Date($(cells[3]).text()), // Try to parse the date
                payment_recipient: 'N/A' // May not have this info in the payment history table
            };
        }
    }
    return null;
}

// Helper function to find an appointment in existing data
function findAppointmentInExistingData(transactionId) {
    // Try to extract from the latest appointment table
    const rows = $("#latest-appointment-table tr");
    for (let i = 0; i < rows.length; i++) {
        const btn = $(rows[i]).find('button[data-transaction-id="' + transactionId + '"]');
        if (btn.length > 0) {
            // Extract data from the row
            const cells = $(rows[i]).find('td');
            return {
                transaction_id: transactionId,
                service_name: $(cells[0]).text(),
                balance: parseFloat($(cells[1]).text().replace('₱', '')),
                status: $(cells[2]).text().toLowerCase(),
                payment_recipient: $(cells[3]).text(),
                date: new Date() // We may not have the date in the row
            };
        }
    }
    return null;
}

// Helper function to populate modal with appointment data
function populateModalWithAppointmentData(data) {
    $('#modalTransactionId').text(data.transaction_id || data.id || 'N/A');
    $('#modalProcedureType').text(data.procedure_name || 'N/A');
    $('#modalServiceName').text(data.service_name || 'N/A');
    $('#modalBalance').text('₱' + parseFloat(data.balance || 0).toFixed(2));
    $('#modalStatus').text(capitalize(data.status || 'unknown'));
    
    const dateText = data.date ? 
        new Date(data.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 
        'N/A';
    $('#modalDate').text(dateText);
    
    $('#modalPaymentRecipient').text(data.payment_recipient || 'N/A');
    console.log('Successfully populated appointment details modal for ID:', data.transaction_id);
    
    // Add Pay button to modal if status is pending
    const payButtonContainer = $('#modalPayButtonContainer');
    if (payButtonContainer.length > 0) {
        if (data.status && data.status.toLowerCase() === 'pending') {
            payButtonContainer.html(`
                <button class="btn btn-success btn-pay" data-transaction-id="${data.transaction_id || data.id}">
                    Pay Now
                </button>
            `);
        } else {
            payButtonContainer.empty(); // Remove pay button if status is not pending
        }
    }
}

function updatePayment(transactionId) {
    console.log("Update Payment called for transaction ID:", transactionId);
    const qrId = $('#qr-selection').val();
    const referenceNumber = $('#payment-reference').val();
    const paidAmount = $('#payment-paid').val();

    // Check if all required fields are completed
    console.log('Transaction ID:', transactionId);
    console.log('QR ID:', qrId);
    console.log('Reference Number:', referenceNumber);
    console.log('Paid Amount:', paidAmount);

    if (!qrId) {
        alert('Please select a QR to pay.');
        console.log('QR ID missing!');
        return;
    }
    if (!referenceNumber || !paidAmount) {
        alert('Please complete all payment fields.');
        console.log('Reference or Paid Amount missing!');
        return;
    }
    
    // Log the payment data before sending the request
    console.log('Sending payment data:', {
        transaction_id: transactionId,
        status: 'paid',
        paid: paidAmount,
        total: 0, // Replace this with actual total if needed
        qr_id: qrId,
        reference_number: referenceNumber
    });

    axios.post(`/payments/update/${transactionId}`, {
        transaction_id: transactionId,
        status: 'paid',
        paid: paidAmount,
        total: 0, // Replace this with actual total if needed
        qr_id: qrId,
        reference_number: referenceNumber
    })
    .then(function (response) {
        alert('Payment successfully sent!');
        $('#paymentModal').hide(); // Changed from modal('hide') to hide()
        fetchLatestAppointment();  // Refresh the appointment list
        fetchPaymentHistory();     // Refresh the payment history
    })
    .catch(function (error) {
        console.error('Error updating payment:', error);
        alert('Failed to update payment. Please try again.');
    });
}

function qrDetails(qr) {
    $('#qr-details').css('display', 'flex');
    const qrImage = document.getElementById('qr-image');
    const accountName = document.getElementById('qr-account-name');
    const accountNumber = document.getElementById('qr-account-number');
    
    if (qrImage) qrImage.src = `/${qr.image_path}`;
    if (accountName) accountName.textContent = qr.gcash_name;
    if (accountNumber) accountNumber.textContent = qr.number;
    
    console.log('QR details displayed for:', qr);
}

function qrDetailsDropdown(qrData) {
    if (!Array.isArray(qrData) || qrData.length === 0) {
        console.error('No QR data available for dropdown');
        return;
    }

    const qrSelectionDropdown = document.getElementById('qr-selection');
    const qrDetailsContainer = document.getElementById('qr-details');

    if (!qrSelectionDropdown || !qrDetailsContainer) {
        console.error('QR selection dropdown or details container not found in DOM');
        return;
    }

    // Hide QR details by default
    qrDetailsContainer.style.display = 'none';

    // Populate the dropdown
    qrSelectionDropdown.innerHTML = '<option value="">Select a QR to pay</option>';
    qrData.forEach(function (qr) {
        const option = document.createElement('option');
        option.value = qr.id;
        option.textContent = qr.name;
        qrSelectionDropdown.appendChild(option);
    });

    // Handle change event
    qrSelectionDropdown.onchange = function () {
        const selectedQr = qrData.find(q => q.id == this.value);
        if (selectedQr) {
            qrDetails(selectedQr);
        } else {
            // Hide QR details again if "Select a QR" is picked
            qrDetailsContainer.style.display = 'none';
        }
    };
    
    console.log('QR dropdown populated with', qrData.length, 'options');
}

function paymentDetails(transactionId) {
    console.log('Fetching payment details for transaction ID:', transactionId);
    
    // Validate transaction ID before proceeding
    if (!transactionId) {
        console.error('Invalid transaction ID for payment details');
        alert('Error: Transaction ID is missing');
        $('#paymentModal').hide();
        return;
    }
    
    // Update both display and form field with transaction ID
    $('#payment-transaction-id').text(transactionId);
    $('#payment-form-transaction-id').val(transactionId);
    
    axios.get(`/payments/qr-details/${transactionId}`)
        .then(function (response) {
            console.log('QR Details Response:', response.data);
            
            if (!response.data || !response.data.data) {
                console.error('Invalid response format from QR details endpoint');
                return;
            }
            
            const data = response.data.data;
            
            // Check if we have all required data
            if (!data.appointment || !data.payment || !data.qr) {
                console.error('Missing required data in QR details response');
                return;
            }
            
            const appointment = data.appointment;
            const payment = data.payment;
            const qr = data.qr;

            // Format the appointment date
            const appointmentDate = appointment.appointment_date ? 
                new Date(appointment.appointment_date) : new Date();
                
            const formattedDate = appointmentDate.toLocaleDateString('en-US', {
                month: 'long', day: 'numeric', year: 'numeric'
            });

            // Make sure all elements exist before updating
            const serviceElement = document.getElementById('payment-service-name');
            const dateElement = document.getElementById('payment-date');
            const amountElement = document.getElementById('payment-amount');
            
            // Update the page with the formatted details
            if (serviceElement) serviceElement.textContent = appointment.procedures || 'N/A';
            if (dateElement) dateElement.textContent = formattedDate;
            if (amountElement) amountElement.textContent = payment.total;
            
            // Pass the QR data to the dropdown function
            qrDetailsDropdown(qr);
        })
        .catch(function (error) {
            console.log('Error fetching QR details:', error);
            
            // Try to provide a fallback approach
            const txIdElement = document.getElementById('payment-transaction-id');
            if (txIdElement) txIdElement.textContent = transactionId;
            
            // Alert user about the error
            alert('Could not fetch payment details. Please try again later.');
        });
}

function showPaymentModal(transactionId) {
    console.log('Showing payment modal for transaction ID:', transactionId);
    
    // Make sure the transactionId is valid
    if (!transactionId) {
        console.error('Invalid transaction ID for payment modal');
        return;
    }
    
    // Update the transaction ID display in the modal and the hidden form input
    $('#payment-transaction-id').text(transactionId);
    $('#payment-form-transaction-id').val(transactionId);
    
    // Show the modal
    $('#paymentModal').css('display', 'flex');
    
    // Fetch payment details
    paymentDetails(transactionId);
    
    // Store in global for backward compatibility 
    latestTransaction = transactionId;
}

// jQuery click event handler for Pay button
$(document).on('click', '.payButton', function() {
    var transactionId = $(this).data('transaction-id');
    console.log('Pay button clicked for transaction ID:', transactionId);
    showPaymentModal(transactionId); 
});

function fetchLatestAppointment() {
    var tableBody = $("#latest-appointment-table");

    tableBody.html('<tr><td colspan="7" class="text-center">Loading latest appointment...</td></tr>');

    $.ajax({
        url: '/dashboard/payment',
        method: 'GET',
        success: function(data) {
            console.log('Latest appointments data:', data);

            let appointments = Array.isArray(data) ? data : [data];

            if (!appointments.length || appointments[0].transaction_id === undefined) {
                tableBody.html('<tr><td colspan="7" class="text-center">No recent appointments found</td></tr>');
                return;
            }

            const rows = appointments.map(appointment => {
                const formattedDate = new Date(appointment.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });

                // Only show pay button if status is pending
                const payButton = appointment.status && appointment.status.toLowerCase() === 'pending' ? `
                    <button class="btn btn-success btn-sm payButton" data-transaction-id="${appointment.transaction_id}">Pay</button>
                ` : '';

                return `
                    <tr>
                        <td>${appointment.service_name || appointment.procedure_name}</td>
                        <td>₱${parseFloat(appointment.balance).toFixed(2)}</td>
                        <td>${capitalize(appointment.status)}</td>
                        <td>${appointment.payment_recipient || 'N/A'}</td>
                        <td style="display:flex; flex-direction:column; gap:10px;">
                            <button class="btn btn-primary btn-sm" data-transaction-id="${appointment.transaction_id}">Details</button>
                            ${payButton}
                        </td>
                    </tr>
                `;
            }).join('');

            
            tableBody.html(rows);
        },
        error: function(error) {
            console.error('Error fetching latest appointment:', error);
            tableBody.html('<tr><td colspan="7" class="text-center text-danger">Error fetching data</td></tr>');
        }
    });
}

// Fetch Payment History
function fetchPaymentHistory() {
    var tableBody = $("#paymentHistory");

    tableBody.html('<tr><td colspan="5" class="text-center">Loading payment history...</td></tr>');

    $.ajax({
        url: '/payment/history',
        method: 'GET',
        success: function(data) {
            console.log("Fetched Payment History Data:", data);

            if (!Array.isArray(data) || data.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center text-danger">No payment records found</td></tr>');
                return;
            }
            const rows = data.map(payment => {
                const formattedDate = new Date(payment.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });
                return `
                    <tr>
                        <td>${payment.service_name || payment.procedure_name}</td>
                        <td>₱${parseFloat(payment.balance).toFixed(2)}</td>
                        <td>${capitalize(payment.status)}</td>
                        <td>${formattedDate}</td>
                        <td style="display:flex; flex-direction:column; gap:10px;">
                          <button class="btn btn-primary btn-sm" data-transaction-id="${payment.transaction_id}">View</button>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.html(rows);
        },
        error: function(error) {
            console.error('Error fetching payment history:', error);
            tableBody.html('<tr><td colspan="5" class="text-center text-danger">Error fetching data</td></tr>');
        }
    });
}

// Helper: Capitalize status strings
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}