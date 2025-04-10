import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    fetchLatestAppointment();
    fetchPaymentHistory();
});

$(document).on('click', '#closePaymentModal', function () {
    $('#paymentModal').hide();
});

$(document).on('click', '#closeDetailbtn, .close-modal-btn', function () {
    $('#appointmentDetailsModal').css('display', 'none');
});

// Specific event handler for the details button in latest appointments
$(document).on('click', '#latest-appointment-table .btn-primary', function() {
    var transactionId = $(this).data('transaction-id');
    console.log('Latest appointment details button clicked for transaction ID:', transactionId);
    viewDetails(transactionId);
});

function viewPaymentDetails(transactionId) {
    $('#appointmentDetailsModal').css('display', 'flex');
    
    console.log('Fetching payment history details for transaction ID:', transactionId);
    
    if(!transactionId){
        $('#appointmentDetailsModal').css('display', 'none');
        return;
    }

    const paymentData = findPaymentInHistoryData(transactionId);
    
    if (paymentData) {
        populateModalWithAppointmentData(paymentData);
        return;
    }
    
    // If not found in the table, fetch from server using Axios
    axios.get(`/appointments/payment-details/${transactionId}`)
        .then(function (response) {
            console.log('Fetched payment history:', response.data);

            // Check if data exists and if the appointment details are valid
            if (!response.data.appointment) {
                console.error('No payment details available for this appointment');
                $('#modalDetails').html('<div class="text-center text-danger">Payment details not found</div>');
                return;
            }

            // If payment details are found, populate the modal
            populateModalWithAppointmentData(response.data.appointment);
        })
        .catch(function (error) {
            console.error('Error fetching payment history details:', error);
            $('#modalDetails').html('<div class="text-center text-danger">Could not retrieve payment details</div>');
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
function populateModalWithAppointmentData(data) {
    // Appointment Details
    $('#modalTransactionId').text(data.appointment_id || 'N/A');
    $('#modalPatientId').text(data.patient_id || 'N/A');
    $('#modalAppointmentDate').text(data.appointment_date || 'N/A');
    $('#modalPreference').text(data.preference || 'N/A');
    $('#modalAppointmentTime').text(data.appointment_time || 'N/A');
    $('#modalStatus').text(data.status ? capitalize(data.status) : 'N/A');
    $('#modalProcedures').text(data.procedures || 'N/A');
    $('#modalRemarks').text(data.remarks || 'N/A');
    
    // Payment Details
    $('#modalPaymentId').text(data.payment_id || 'N/A');
    $('#modalPaidStatus').text(data.paid);
    $('#modalReferenceNumber').text(data.reference_number || 'N/A');
    $('#modalTotalAmount').text('₱' + parseFloat(data.total || 0).toFixed(2));
    
    // QR Code Details
    $('#modalQrId').text(data.qr_id || 'N/A');
    $('#modalQrName').text(data.qr_gcash_name || 'N/A');
    $('#modalQrImagePath').attr('src', data.qr_image_path || 'default-image-path.png');
    
    console.log('Successfully populated appointment details modal for ID:', data.appointment_id);

    // Add Pay button to modal if status is pending
    const payButtonContainer = $('#modalPayButtonContainer');
    if (payButtonContainer.length > 0) {
        if (data.status && data.status.toLowerCase() === 'pending') {
            payButtonContainer.html(`
                <button class="btn btn-success btn-pay" data-transaction-id="${data.appointment_id}">
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
        total: 0,
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
        $('#paymentModal').hide();
        fetchLatestAppointment();
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
        option.textContent = qr.gcash_name;
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

    axios.get(`/payments/qr-by-appointment/${transactionId}`)
        .then(function (response) {
            console.log('QR Details Response:', response.data);

            if (!response.data || !response.data.data) {
                console.error('Invalid response format from QR details endpoint');
                return;
            }

            const data = response.data.data;

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

            // Update display elements
            $('#payment-service-name').text(appointment.procedures || 'N/A');
            $('#payment-date').text(formattedDate);
            $('#payment-amount').text(payment.total);

            // ✅ Set correct payment ID (not the appointment ID)
            $('#payment-transaction-id').text(payment.id);
            $('#payment-form-transaction-id').val(payment.id);

            // Remove any previously attached event to prevent stacking
            $('#submitPaymentBtn').off('click').on('click', function () {
                const paymentId = $('#payment-transaction-id').text() || $('#payment-form-transaction-id').val();

                if (!paymentId) {
                    console.error('Missing payment ID.');
                    alert('Error: Payment ID is required.');
                    return;
                }

                console.log('Submitting payment update for ID:', paymentId);
                updatePayment(paymentId);
            });

            // Populate dropdown or display QR details
            qrDetailsDropdown(qr);
        })
        .catch(function (error) {
            console.error('Error fetching QR details:', error);

            // Set fallback transaction ID if available
            $('#payment-transaction-id').text(transactionId);

            alert('Could not fetch payment details. Please try again later.');
        });
}


function showPaymentModal(transactionId) {
    console.log('Showing payment modal for transaction ID:', transactionId);

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

function fetchLatestAppointment() {
    var tableBody = $("#latest-appointment-table");

    // Initial loading message
    tableBody.html('<tr><td colspan="5" class="text-center">Loading latest appointment...</td></tr>');

    $.ajax({
        url: '/user/latest-appointment',
        method: 'GET',
        success: function(data) {
            console.log('Latest appointments data:', data);

            // Wrap appointment in an array for easier mapping
            let appointments = data.appointment ? [data.appointment] : [];
            if (!appointments.length) {
                tableBody.html('<tr><td colspan="5" class="text-center">No recent appointments found</td></tr>');
                return;
            }

            const rows = appointments.map(appointment => {
                const formattedDate = new Date(appointment.appointment_date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });

                const payment = appointment.payments?.[0] || {}; // Get first payment or default to empty
                const balance = '₱' + parseFloat(payment.total || 0).toFixed(2);
                const paymentStatus = capitalize(payment.status || 'Unpaid');
                const appointmentStatus = capitalize(appointment.status || 'N/A');
                const procedures = appointment.procedures || 'N/A';

                const payButton = payment.status?.toLowerCase() === 'pending' ? ` 
                    <button style="margin-top: 15px;" class="btn btn-success btn-pay btn-sm payButton" data-appointment-id="${appointment.id}">Pay</button>
                ` : '';

                return `
                    <tr>
                        <td>${procedures}</td>
                        <td>${balance}</td>
                        <td>${paymentStatus}</td>
                        <td>${formattedDate}</td>
                        <td>
                            <button class="btn view-details btn-primary btn-sm" data-appointment-id="${appointment.id}">Details</button>
                            ${payButton}
                        </td>
                    </tr>
                `;
            }).join('');

            // Add click event listeners
            $(document).on('click', '.btn-pay', function () {
                var appointmentId = $(this).data('appointment-id');
                $('#appointmentDetailsModal').css('display', 'none');
                console.log('Pay clicked for appointment ID:', appointmentId);
                showPaymentModal(appointmentId);
            });

            $(document).on('click', '#latest-appointment-table .view-details', function() {
                var appointmentId = $(this).data('appointment-id');
                console.log('View details clicked for appointment ID:', appointmentId);
                viewPaymentDetails(appointmentId);
            });

            // Insert the built rows into the table
            tableBody.html(rows);
        },
        error: function(error) {
            console.error('Error fetching latest appointment:', error);
            tableBody.html('<tr><td colspan="5" class="text-center text-danger">Error fetching data</td></tr>');
        }
    });
}

function fetchPaymentHistory() {
    var tableBody = $("#paymentHistory");

    // Show loading message while fetching data
    tableBody.html('<tr><td colspan="5" class="text-center">Loading payment history...</td></tr>');

    axios.get('/payments/except-latest')
        .then(function (response) {
            console.log(response.data); // Log the full response data for debugging

            const data = response.data; // Store response data

            // Check if there are no payments and handle the message
            if (data.message) {
                if (data.message === 'No other appointments found for the user') {
                    tableBody.html('<tr><td colspan="5" class="text-center text-warning">No payment records found</td></tr>');
                } else {
                    tableBody.html('<tr><td colspan="5" class="text-center text-danger">Error fetching data</td></tr>');
                }
                return;
            }

            // If there are payments, check if it's an array and handle empty arrays
            if (Array.isArray(data.payments) && data.payments.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center text-danger">No payment records found</td></tr>');
                return;
            }

            // If there are payment records, create table rows
            const rows = data.payments.map(payment => {
                const formattedDate = new Date(payment.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });

                // Construct each table row
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

            // Add the rows to the table body
            tableBody.html(rows);
        })
        .catch(function (error) {
            console.error('Error fetching payment history:', error);
            tableBody.html('<tr><td colspan="5" class="text-center text-danger">Error fetching data</td></tr>');
        });
}


function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}