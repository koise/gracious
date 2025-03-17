import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    fetchLatestAppointment();
    fetchPaymentHistory();
});

function fetchLatestAppointment() {
    var tableBody = $("#latest-appointment-table");

    tableBody.html('<tr><td colspan="7" class="text-center">Loading latest appointment...</td></tr>');

    fetch('/dashboard/payment')
        .then(response => response.json())
        .then(data => {
            console.log(response);
            let appointments = Array.isArray(data) ? data : [data];

            if (!appointments.length || appointments[0].transaction_id === undefined) {
                tableBody.html('<tr><td colspan="7" class="text-center">No recent appointments found</td></tr>');
                return;
            }

            var rows = appointments.map(appointment => {
                const formattedDate = new Date(appointment.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });

                return `
                    <tr>
                        <td>${appointment.transaction_id}</td>
                        <td>${appointment.service_name || appointment.procedure_name}</td>
                        <td>₱${parseFloat(appointment.balance).toFixed(2)}</td>
                        <td>${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</td>
                        <td>${formattedDate}</td>
                        <td>${appointment.payment_recipient || 'N/A'}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewDetails(${appointment.transaction_id})">Details</button>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.html(rows);
        })
        .catch(error => {
            console.error('Error fetching latest appointment:', error);
            tableBody.html('<tr><td colspan="7" class="text-center text-danger">Error fetching data</td></tr>');
        });
}


// Function to fetch appointment list and return JSON response
function fetchAppointmentList(page = 1, search = '') {
    return new Promise((resolve, reject) => {
        axios.post(`appointment/populate?page=${page}&search=${search}`)
            .then(response => {
                const { data: appointments, current_page, last_page } = response.data;
                const tableBody = $('#appointmentListTableBody');
                const paginationWrapper = $('#appointmentPagination');

                renderAppointmentRows(appointments, tableBody);
                renderPagination(current_page, last_page, paginationWrapper, (page) => {
                    fetchAppointmentList(page);
                });

                resolve({ message: "Success", data: appointments, current_page, last_page });
            })
            .catch(error => {
                console.error('Error fetching appointments:', error);
                reject({ message: "Error fetching appointments", error: error });
            });
    });
}
// Fetch payment history
function fetchPaymentHistory() {
    var tableBody = $("#paymentHistory");

    tableBody.html('<tr><td colspan="5" class="text-center">Loading payment history...</td></tr>');

    fetch('/payment/history')
        .then(response => response.json())
        .then(data => {
            console.log("Fetched Payment Data:", data); // Debugging output

            // Ensure data is treated as an array
            if (!Array.isArray(data) || data.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center text-danger">No payment records found</td></tr>');
                return;
            }

            var rows = data.map(payment => {
                const formattedDate = new Date(payment.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });

                return `
                    <tr>
                        <td>${payment.service_name || payment.procedure_name}</td>
                        <td>₱${parseFloat(payment.balance).toFixed(2)}</td>
                        <td>${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}</td>
                        <td>${formattedDate}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewDetails(${payment.transaction_id})">View</button>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.html(rows);
        })
        .catch(error => {
            console.error('Error fetching payment history:', error);
            tableBody.html('<tr><td colspan="5" class="text-center text-danger">Error fetching data</td></tr>');
        });
}
