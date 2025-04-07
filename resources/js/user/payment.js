import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    fetchLatestAppointment();
    fetchPaymentHistory();
});


let latestTransaction = 0;

$(document).on('click', '#submitPaymentBtn', function () {
    const transactionId = $(this).data('transaction-id');
    updatePayment(latestTransaction);
});

$(document).on('click', '#closePaymentModal', function () {
    $('#paymentModal').hide();
});


$(document).on('click', '#closeDetailbtn, .close-modal-btn', function () {
    $('#appointmentDetailsModal').css('display', 'none');
});

$(document).on('click', '.btn-pay', function () {
    $('#appointmentDetailsModal').css('display', 'none');
    $('#paymentModal').css('display', 'flex');
});

$(document).on('click', '.btn-primary', function() {
    var transactionId = $(this).data('transaction-id');
    if ($(this).text() === "View") {
        viewDetails(transactionId);
    } else if ($(this).text() === "Details") {
        viewDetails(transactionId);
    }
});

// View Appointment Details (modal population)
function viewDetails(transactionId) {
    $('#appointmentDetailsModal').css('display', 'flex');                
    $.ajax({
        url: `/appointments/${transactionId}`,
        method: 'GET',
        success: function(data) {
            if (data) {
                // Populate the modal with the fetched data
                $('#modalTransactionId').text(data.transaction_id);
                $('#modalProcedureType').text(data.procedure_name || 'N/A');
                $('#modalServiceName').text(data.service_name || 'N/A');
                $('#modalBalance').text('₱' + parseFloat(data.balance).toFixed(2));
                $('#modalStatus').text(capitalize(data.status));
                $('#modalDate').text(new Date(data.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                $('#modalPaymentRecipient').text(data.payment_recipient || 'N/A');

                console.log('Showing appointment details modal for ID:', transactionId);
                console.log('Appointment data:', data);
            } else {
                console.error('Appointment data not found');
            }
        },
        error: function(error) {
            console.error('Error fetching appointment details:', error);
        }
    });
}


function updatePayment(transactionId) {
    console.log("Update Payment called"); // Add this line
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
        transaction_id: transactionId,  // Ensure this is the transaction_id
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
        $('#paymentModal').modal('hide');
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
    document.getElementById('qr-image').src = `/${qr.image_path}`;
    document.getElementById('qr-account-name').textContent = qr.gcash_name;
    document.getElementById('qr-account-number').textContent = qr.number;
}

function qrDetailsDropdown(qrData) {
    const qrSelectionDropdown = document.getElementById('qr-selection');
    const qrDetailsContainer = document.getElementById('qr-details');

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
}


 function paymentDetails(transactionId) {
    axios.get(`/payments/qr-details/${transactionId}`)
        .then(function (response) {
            console.log('QR Details Response:', response.data);
            const data = response.data.data;
            const appointment = data.appointment;
            const payment = data.payment;
            const qr = data.qr;  

            // Format the appointment date
            const formattedDate = new Date(appointment.appointment_date).toLocaleDateString('en-US', {
                month: 'long', day: 'numeric', year: 'numeric'
            });

            // Update the page with the formatted details
            document.getElementById('payment-transaction-id').textContent = payment.id;
            document.getElementById('payment-service-name').textContent = appointment.procedures;
            document.getElementById('payment-date').textContent = formattedDate;  // Display formatted date
            document.getElementById('payment-amount').textContent = payment.total;
            // Pass the QR data (which is an array) to the dropdown function
            qrDetailsDropdown(qr);
        })
        .catch(function (error) {
            console.log('Error fetching QR details:', error);
        });
}



function showPaymentModal(transactionId) {
    console.log('Showing payment modal for transaction ID:', transactionId);
    $('#paymentTransactionId').text(transactionId);  
    $('#paymentModal').css('display', 'flex');
    paymentDetails(transactionId);
    latestTransaction = transactionId;
}

// jQuery click event handler for Pay button
$(document).on('click', '.payButton', function() {
    var transactionId = $(this).data('transaction-id');
    console.log('Transaction ID:', transactionId);
    showPaymentModal(transactionId); 
});

function fetchLatestAppointment() {
    var tableBody = $("#latest-appointment-table");

    tableBody.html('<tr><td colspan="7" class="text-center">Loading latest appointment...</td></tr>');

    $.ajax({
        url: '/dashboard/payment',
        method: 'GET',
        success: function(data) {
            console.log(data); // Debug log

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

                const payButton = appointment.status === 'pending' ? `
                <button class="btn btn-success btn-sm payButton" data-transaction-id="${appointment.transaction_id}">Pay</button>
            ` : '';

                return `
                    <tr>
                        <td>${appointment.service_name || appointment.procedure_name}</td>
                        <td>₱${parseFloat(appointment.balance).toFixed(2)}</td>
                        <td>${capitalize(appointment.status)}</td>
                        <td>${appointment.payment_recipient || 'N/A'}</td>
                        <td style="display:flex; flex-direction:column; gap:10px;">
                            <button class="btn btn-primary btn-sm" id="detail-btn" data-transaction-id="${appointment.transaction_id}">Details</button>
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
            console.log("Fetched Payment Data:", data);

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
                        <td>
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
    return str.charAt(0).toUpperCase() + str.slice(1);
}
