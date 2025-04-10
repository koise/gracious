import $ from 'jquery';
import axios from 'axios';

// Set CSRF token for all axios requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// Function to trigger the population of payment data
function populatePayments(status = 'All', search = '') {
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

    // Make the GET request to the backend
    axios.get('/admin/populate-payments', { params })
        .then(response => {
            console.log('Response from backend:', response);

            if (response.data.data && response.data.data.length > 0) {
                $('#userTableBody').empty();

                // Loop through the data and populate the table
                response.data.data.forEach(payment => {
                    const user = payment.appointment.user; // Access the user object inside appointment

                    $('#userTableBody').append(`
                        <tr>
                            <td>${payment.id}</td>
                            <td>${payment.appointment.procedures}</td>
                            <td>${user.first_name} ${user.last_name}</td> <!-- Combine first and last name -->
                            <td>${payment.paid}</td>
                            <td>${payment.total}</td>
                            <td>${payment.status}</td>
                            <td>${payment.qr.name}</td>
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
            console.log(payment);

            // Populate modal fields
            $('#paymentModal .modal-title').text(`Payment Details - ID: ${paymentId}`);
            $('#paymentModal #patientName').text(payment.first_name + ' ' + payment.last_name);
            $('#paymentModal #appointmentDetails').text(payment.appointment_details);
            $('#paymentModal #paid').text(`The Customer Paid: ${payment.paid}`); 
            $('#paymentModal #referenceNumber').text(`Reference Number: ${payment.reference_number ? payment.reference_number : 'Not Paid'}`);
            $('#paymentModal #totalAmount').val(payment.total);
            $('#paymentModal #balance').text(`Balance: ${payment.total - payment.paid}`);
            $('#paymentModal #paymentGcashName').text(payment.gcash_name);
            $('#paymentModal #paymentDate').text(payment.created_at);
            $('#paymentModal #paymentStatus').val(payment.status);
            $('#paymentModal #status').text(`Appointment status: ${payment.status}`);
            $('#paymentModal #procedures').text(`Procedures: ${payment.procedures}`);
            $('#paymentModal #remarks').text(`Remarks: ${payment.remarks ? payment.remarks : 'N/A'}`);

            // ✅ Set patient image
            if (payment.file_path) {
                $('#paymentModal #patientImage').attr('src', '/storage/' + payment.file_path);
            } else {
                $('#paymentModal #patientImage').attr('src', '/default-image.jpg');
            }

            // Event listener for Save button
            $('#savePaymentStatus').click(function() {
                const status = $('#paymentModal #paymentStatus').val();
                let confirmationMessage = '';

                // Set confirmation message based on status
                if (status === 'pending') {
                    confirmationMessage = 'Are you sure you want to set the payment status to Pending?';
                } else if (status === 'paid') {
                    confirmationMessage = 'Are you sure you want to set the payment status to Paid?';
                } else if (status === 'cancelled') {
                    confirmationMessage = 'Are you sure you want to cancel the payment?';
                } else if (status === 'completed') {
                    confirmationMessage = 'Are you sure you want to mark the payment as Completed?';
                }

                // Set the message in the confirmation modal
                $('#confirmationMessage').text(confirmationMessage);

                // Show the confirmation modal
                $('#confirmationModal').css('display', 'flex');

                // Handle confirmation
                $('#confirmStatusChange').click(function() {
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
        populatePayments(status, '');
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
            alert('Payment status updated to "Paid"');
            populatePayments('paid', '');  // Update payments list
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            alert('Error updating payment status. Please try again.');
        });
}


function paymentCancelled (paymentId){
    alert('Cancel' + paymentId);
}

function paymentCompleted(paymentId) {
        axios.put(`/admin/complete-payment`, { payment_id: paymentId })
            .then(response => {
                // Handle success response
                alert('Payment status updated to "Completed"');
                populatePayments('completed', ''); // Refresh the payments list if needed
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

// Trigger the populatePayments function when the status changes
$('#status').change(function() {
    const status = $(this).val();  // Get selected status
    const search = $('#activeSearchInput').val();  // Get current search value
    populatePayments(status, search);
});

// Trigger the populatePayments function when the search input changes
$('#activeSearchInput').keyup(function() {
    const search = $(this).val();  // Get the search input value
    const status = $('#status').val();  // Get the selected status
    populatePayments(status, search);
});

// Close the modal when the close button is clicked
$('.payment-close-btn').click(function() {
    $('#paymentModal').css('display' ,'none');
});

// Initialize table with default "All" status and empty search field
$(document).ready(function() {
    populatePayments('All', '');
});
