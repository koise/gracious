import $ from 'jquery';
import axios from 'axios';

// Set CSRF token for all axios requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// Function to trigger the population of payment data
function populatePayments(status = 'All', search = '', filters = {}) {
    // Show a loading indicator
    $('#userTableBody').html('<tr><th colspan="7" style="font-size:17px;">Fetching QR Details...</th></tr>');

    // Prepare the parameters for the request
    let params = {};
    if (status && status !== 'All') {
        params.status = status; // Add status filter if provided and not "All"
    }
    if (search) {
        params.search = search; // Add search filter if provided
    }

    // Add date range filters if provided
    if (filters.dateFrom) {
        params.date_from = filters.dateFrom;
    }
    if (filters.dateTo) {
        params.date_to = filters.dateTo;
    }
    
    // Add amount range filters if provided
    if (filters.minAmount) {
        params.min_amount = filters.minAmount;
    }
    if (filters.maxAmount) {
        params.max_amount = filters.maxAmount;
    }

    // Make the GET request to the backend
    axios.get('/admin/populate-payments', { params })
        .then(response => {
            console.log('Response from backend:', response);

            if (response.data.data && response.data.data.length > 0) {
                $('#userTableBody').empty();

                // Loop through the data and populate the table
                response.data.data.forEach(payment => {
                    // Add null checks for nested properties
                    const user = payment.appointment && payment.appointment.user ? payment.appointment.user : null;
                    const userName = user ? `${user.first_name} ${user.last_name}` : 'N/A';
                    const procedures = payment.appointment ? payment.appointment.procedures : 'N/A';
                    const qrName = payment.qr ? payment.qr.name : 'N/A';
                    
                    // Values with fallbacks for null or undefined
                    const paidAmount = payment.paid || 0;
                    const totalAmount = payment.total || 0;
                    const paymentStatus = payment.status || 'Unknown';

                    // Define status class based on payment status
                    let statusClass = '';
                    switch(paymentStatus.toLowerCase()) {
                        case 'pending':
                            statusClass = 'status-pending';
                            break;
                        case 'paid':
                            statusClass = 'status-paid';
                            break;
                        case 'completed':
                            statusClass = 'status-completed';
                            break;
                        case 'cancelled':
                            statusClass = 'status-cancelled';
                            break;
                        default:
                            statusClass = '';
                    }

                    $('#userTableBody').append(`
                        <tr>
                            <td>${payment.id}</td>
                            <td>${procedures}</td>
                            <td>${userName}</td>
                            <td>${paidAmount}</td>
                            <td>${totalAmount}</td>
                            <td><span class="status-badge ${statusClass}">${paymentStatus}</span></td>
                            <td>${qrName}</td>
                            <td>
                                <button class="action-btn" data-payment-id="${payment.id}" data-action="view">View</button>
                            </td>
                        </tr>
                    `);
                });
            } else {
                // Show a message if no payments are found
                $('#userTableBody').html('<tr><th colspan="7" style="font-size:17px;">No payments found</th></tr>');
            }
        })
        .catch(error => {
            console.error('Error fetching payments:', error);
            // Show an error message in case of failure
            $('#userTableBody').html('<tr><th colspan="7" style="font-size:17px;">Unable to fetch payment details</th></tr>');
        });
}

function viewPaymentDetails(paymentId) {
    $('#paymentModal').css('display', 'flex'); // Show the payment modal

    axios.get(`/admin/payment-details/${paymentId}`)
        .then(response => {
            const payment = response.data;
            console.log('Payment details:', payment);

            // Set payment ID in the title
            $('#payment-id').text(paymentId);
            
            // Populate patient info input fields
            $('#patientName').val(payment.first_name + ' ' + payment.last_name);
            $('#procedures').val('Procedures: ' + (payment.procedures || 'N/A'));
            $('#remarks').val('Remarks: ' + (payment.remarks || 'N/A'));
            
            // Populate payment details
            $('#paid').val('The Customer Paid: ' + (payment.paid || '0'));
            $('#referenceNumber').val('Reference Number: ' + (payment.reference_number || 'Not Paid'));
            $('#balance').val('Balance: ' + ((payment.total || 0) - (payment.paid || 0)));
            
            // Set total amount field
            $('#totalAmount').val(payment.total || 0);
            
            // Set payment status dropdown and status badge
            $('#paymentStatus').val(payment.status || 'pending');
            
            // Update the status indicator with the appropriate class
            const statusElement = $('#status');
            statusElement.text(payment.status || 'Pending');
            
            // Remove all existing status classes
            statusElement.removeClass('status-pending status-paid status-completed status-cancelled');
            
            // Add the appropriate status class
            switch((payment.status || 'pending').toLowerCase()) {
                case 'pending':
                    statusElement.addClass('status-pending');
                    break;
                case 'paid':
                    statusElement.addClass('status-paid');
                    break;
                case 'completed':
                    statusElement.addClass('status-completed');
                    break;
                case 'cancelled':
                    statusElement.addClass('status-cancelled');
                    break;
            }

            // Set patient image
            if (payment.file_path) {
                $('#patientImage').attr('src', '/storage/' + payment.file_path);
            } else {
                $('#patientImage').attr('src', '/default-image.jpg');
            }

            // Set QR image if available
            if (payment.qr_image_url) {
                $('#qrImage').attr('src', payment.qr_image_url);
            } else if (payment.image_path) {
                $('#qrImage').attr('src', '/' + payment.image_path);
            } else {
                $('#qrImage').attr('src', '/default-image.jpg');
            }

            // Event listener for Save button
            $('#savePaymentStatus').off('click').on('click', function() {
                const status = $('#paymentStatus').val();
                let confirmationMessage = '';

                // Set confirmation message based on status
                if (status === 'pending') {
                    confirmationMessage = 'Are you sure you want to set the payment status to Pending?';
                } else if (status === 'paid') {
                    confirmationMessage = 'Are you sure you want to set the payment status to Paid? The appointment status will remain as Pending for review.';
                } else if (status === 'cancelled') {
                    confirmationMessage = 'Are you sure you want to cancel the payment?';
                } else if (status === 'completed') {
                    confirmationMessage = 'Are you sure you want to mark the payment as Completed? This will also update the appointment status to Accepted.';
                }

                // Set the message in the confirmation modal
                $('#confirmationMessage').text(confirmationMessage);

                // Show the confirmation modal
                $('#confirmationModal').css('display', 'flex');

                // Handle confirmation
                $('#confirmStatusChange').off('click').on('click', function() {
                    $('#confirmationModal').css('display', 'none');

                    // Call the appropriate function based on the selected status
                    if (status === 'pending') {
                        sendingBalance(paymentId, status);
                    } else if (status === 'paid') {
                        paymentPaid(paymentId);
                    } else if (status === 'cancelled') {
                        paymentCancelled(paymentId);
                    } else if (status === 'completed') {
                        paymentCompleted(paymentId);
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error fetching payment details:', error);
            alert('Error fetching payment details. Please try again.');
        });
}


function sendingBalance(paymentId, status) {
    const totalAmount = $('#totalAmount').val();  

    if (!totalAmount || isNaN(totalAmount) || totalAmount <= 0) {
        alert("Please enter a valid total amount.");
        return;
    }

    // Send the paymentId and total amount to the backend using axios
    axios.post('/admin/send-total-payment', {
        payment_id: paymentId,
        total: totalAmount
    })
    .then(response => {
        console.log(response.data);
        $('#paymentModal').css('display', 'none');
        alert('Sucessfully sent sms and updated');
        populatePayments(status, '', {});
    })
    .catch(error => {
        console.error('Error sending total payment:', error);
        alert('An error occurred while updating the payment.');
    });
}


function paymentPaid(paymentId) {
    const totalAmount = $('#totalAmount').val();  

    // Validate the total amount
    if (!totalAmount || isNaN(totalAmount) || totalAmount <= 0) {
        alert("Please enter a valid total amount.");
        return;
    }

    // Send request to update payment status
    axios.post(`/admin/receive-payment`, { 
            payment_id: paymentId,
            total: totalAmount 
        })
        .then(response => {
            // Handle success response
            alert('Payment status updated to "Paid" successfully');
            $('#paymentModal').css('display', 'none');
            populatePayments('paid', '', {});  // Update payments list
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            alert('Error updating payment status. Please try again.');
        });
}


function paymentCancelled(paymentId) {
    // Confirm cancellation is intentional
    if (!confirm('Are you sure you want to cancel this payment?')) {
        return;
    }

    axios.post(`/admin/cancel-payment`, { payment_id: paymentId })
        .then(response => {
            // Handle success response
            alert('Payment status updated to "Cancelled" successfully');
            $('#paymentModal').css('display', 'none');
            populatePayments('cancelled', '', {}); // Refresh the payments list
        })
        .catch(error => {
            console.error('Error cancelling payment:', error);
            alert('Error updating payment status. Please try again.');
        });
}

function paymentCompleted(paymentId) {
    axios.post(`/admin/mark-payment-completed`, { payment_id: paymentId })
        .then(response => {
            // Handle success response
            alert('Payment status updated to "Completed" successfully');
            $('#paymentModal').css('display', 'none');
            populatePayments('completed', '', {}); // Refresh the payments list
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            alert('Error updating payment status. Please try again.');
        });
}


// Event listener for the View button click
$(document).on('click', '[data-action="view"]', function() {
    const paymentId = $(this).data('payment-id');  // Get the payment ID from the button's data attribute
    viewPaymentDetails(paymentId);  // Trigger the viewPaymentDetails function
});

// Event listeners for filter buttons
$('#applyFilters').click(function() {
    const status = $('#status').val();
    const search = $('#activeSearchInput').val();
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    const minAmount = $('#minAmount').val();
    const maxAmount = $('#maxAmount').val();
    
    const filters = {
        dateFrom: dateFrom,
        dateTo: dateTo,
        minAmount: minAmount,
        maxAmount: maxAmount
    };
    
    populatePayments(status, search, filters);
});

$('#resetFilters').click(function() {
    // Reset all filter inputs
    $('#status').val('All');
    $('#activeSearchInput').val('');
    $('#dateFrom').val('');
    $('#dateTo').val('');
    $('#minAmount').val('');
    $('#maxAmount').val('');
    
    // Reset to default view
    populatePayments('All', '', {});
});

// Trigger the populatePayments function when the status changes
$('#status').change(function() {
    // We no longer immediately trigger search on status change
    // User needs to click Apply Filters button
});

// Maintain the search functionality
$('#activeSearchInput').keyup(function(e) {
    // If user presses Enter, apply search immediately
    if (e.keyCode === 13) {
        const status = $('#status').val();
        const search = $(this).val();
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        const minAmount = $('#minAmount').val();
        const maxAmount = $('#maxAmount').val();
        
        const filters = {
            dateFrom: dateFrom,
            dateTo: dateTo,
            minAmount: minAmount,
            maxAmount: maxAmount
        };
        
        populatePayments(status, search, filters);
    }
});

// Close the modal when the close button is clicked
$('.payment-close-btn').click(function() {
    $('#paymentModal').css('display' ,'none');
});

// Initialize table with default "All" status and empty search field
$(document).ready(function() {
    populatePayments('All', '', {});
});
